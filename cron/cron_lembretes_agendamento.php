<?php
/**
 * Cron Job: Enviar Lembretes de Agendamento
 *
 * Envia lembretes para agendamentos do dia seguinte (24h antes)
 *
 * Execução sugerida: Diariamente às 18:00
 * Crontab: 0 18 * * * php /caminho/para/cron_lembretes_agendamento.php
 */

// Permitir execução via CLI
if (php_sapi_name() !== 'cli' && php_sapi_name() !== 'cgi-fcgi') {
    // Se não for CLI, verificar se é admin logado
    require_once __DIR__ . '/../includes/auth.php';
    requer_login('admin');
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/email_sender.php';
require_once __DIR__ . '/../includes/EmailTemplates.php';

// Configuração de log
$log_file = __DIR__ . '/../logs/cron_lembretes_' . date('Y-m') . '.log';
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function log_lembrete($mensagem) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensagem\n", FILE_APPEND);
}

try {
    log_lembrete("=== Início do envio de lembretes de agendamento ===");

    // Buscar agendamentos de amanhã (24h antes)
    $data_amanha = date('Y-m-d', strtotime('+1 day'));

    $stmt = $pdo->prepare("
        SELECT
            a.id as agendamento_id,
            a.data_agendamento,
            a.hora_agendamento,
            a.cliente_id,
            a.cliente_rapido_id,
            a.cliente_nome,
            a.profissional_id,
            a.status,
            u.nome as cliente_nome_usuario,
            u.email as cliente_email,
            p.nome as profissional_nome
        FROM agendamentos a
        LEFT JOIN usuarios u ON u.id = a.cliente_id
        LEFT JOIN usuarios p ON p.id = a.profissional_id
        WHERE a.data_agendamento = ?
          AND a.status IN ('agendado', 'confirmado')
        ORDER BY a.hora_agendamento
    ");
    $stmt->execute([$data_amanha]);
    $agendamentos = $stmt->fetchAll();

    if (empty($agendamentos)) {
        log_lembrete("Nenhum agendamento encontrado para amanhã ($data_amanha)");
        exit;
    }

    log_lembrete("Encontrados " . count($agendamentos) . " agendamentos para amanhã");

    // Instanciar template de email
    $emailTemplates = new EmailTemplates($pdo);

    $enviados = 0;
    $sem_email = 0;
    $erros = 0;

    foreach ($agendamentos as $agendamento) {
        // Determinar nome e email do cliente
        $cliente_nome = $agendamento['cliente_nome_usuario'] ?: $agendamento['cliente_nome'];
        $cliente_email = $agendamento['cliente_email'];

        // Se for cliente rápido, tentar buscar email (se tiver)
        if ($agendamento['cliente_rapido_id'] && !$cliente_email) {
            try {
                $stmt = $pdo->prepare("SELECT nome FROM clientes_rapidos WHERE id = ?");
                $stmt->execute([$agendamento['cliente_rapido_id']]);
                $cliente_rapido = $stmt->fetch();
                if ($cliente_rapido) {
                    $cliente_nome = $cliente_rapido['nome'];
                }
                // Clientes rápidos normalmente não têm email, então vamos pular
            } catch (PDOException $e) {
                // Tabela não existe
            }
        }

        // Pular se não tiver email
        if (empty($cliente_email)) {
            $sem_email++;
            log_lembrete("Cliente '{$cliente_nome}' (ID agendamento: {$agendamento['agendamento_id']}) não possui email - pulando");
            continue;
        }

        // Verificar se já enviamos lembrete para este agendamento
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM logs_email
                WHERE destinatario = ?
                  AND assunto LIKE '%Lembrete%'
                  AND assunto LIKE '%$data_amanha%'
                  AND status = 'enviado'
            ");
            $stmt->execute([$cliente_email]);
            $ja_enviado = $stmt->fetchColumn() > 0;

            if ($ja_enviado) {
                log_lembrete("Lembrete já enviado para {$cliente_nome} ({$cliente_email}) - agendamento {$agendamento['agendamento_id']}");
                continue;
            }
        } catch (PDOException $e) {
            // Tabela logs_email pode não existir
        }

        // Buscar serviços do agendamento
        $servicos = [];
        try {
            $stmt = $pdo->prepare("
                SELECT s.nome
                FROM agendamento_itens ai
                JOIN servicos s ON s.id = ai.servico_id
                WHERE ai.agendamento_id = ?
            ");
            $stmt->execute([$agendamento['agendamento_id']]);
            $servicos_result = $stmt->fetchAll();

            foreach ($servicos_result as $servico) {
                $servicos[] = $servico['nome'];
            }
        } catch (PDOException $e) {
            log_lembrete("Erro ao buscar serviços: " . $e->getMessage());
        }

        // Se não encontrou serviços, usar placeholder
        if (empty($servicos)) {
            $servicos = ['Atendimento agendado'];
        }

        // Formatar data e hora
        $data_formatada = date('d/m/Y', strtotime($agendamento['data_agendamento']));
        $hora_formatada = date('H:i', strtotime($agendamento['hora_agendamento']));
        $profissional_nome = $agendamento['profissional_nome'] ?: 'A definir';

        // Gerar email de lembrete
        $corpo_email = $emailTemplates->emailLembreteAgendamento(
            $cliente_nome,
            $data_formatada,
            $hora_formatada,
            $profissional_nome,
            $servicos
        );

        // Enviar email
        $resultado = enviar_email(
            $cliente_email,
            "📅 Lembrete: Agendamento para $data_formatada às $hora_formatada",
            $corpo_email,
            $cliente_nome
        );

        if ($resultado) {
            $enviados++;
            log_lembrete("✓ Lembrete enviado para {$cliente_nome} ({$cliente_email}) - Agendamento ID: {$agendamento['agendamento_id']}");
        } else {
            $erros++;
            log_lembrete("✗ Erro ao enviar lembrete para {$cliente_nome} ({$cliente_email}) - Agendamento ID: {$agendamento['agendamento_id']}");
        }
    }

    log_lembrete("=== Fim do envio de lembretes ===");
    log_lembrete("Total: " . count($agendamentos) . " | Enviados: $enviados | Sem email: $sem_email | Erros: $erros");

    // Se executado via web, mostrar resultado
    if (php_sapi_name() !== 'cli') {
        echo json_encode([
            'success' => true,
            'total' => count($agendamentos),
            'enviados' => $enviados,
            'sem_email' => $sem_email,
            'erros' => $erros
        ]);
    }

} catch (Exception $e) {
    $erro = "ERRO FATAL: " . $e->getMessage();
    log_lembrete($erro);

    if (php_sapi_name() !== 'cli') {
        echo json_encode(['success' => false, 'error' => $erro]);
    }
}
