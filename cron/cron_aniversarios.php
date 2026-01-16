<?php
/**
 * Cron Job: Verificar e Enviar Emails de Aniversário
 *
 * Execução sugerida: Diariamente às 08:00
 * Crontab: 0 8 * * * php /caminho/para/cron_aniversarios.php
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
$log_file = __DIR__ . '/../logs/cron_aniversarios_' . date('Y-m') . '.log';
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

function log_aniversario($mensagem) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $mensagem\n", FILE_APPEND);
}

try {
    log_aniversario("=== Início da verificação de aniversários ===");

    // Verificar se o sistema de lembretes está ativo
    $config = $pdo->query("SELECT lembrar_aniversarios FROM configuracoes WHERE id = 1")->fetch();

    if (!$config || !($config['lembrar_aniversarios'] ?? 0)) {
        log_aniversario("Sistema de lembretes de aniversário está desativado");
        exit;
    }

    // Buscar aniversariantes do dia em ambas as tabelas (usuarios e clientes_rapidos)
    $hoje_dia = date('d');
    $hoje_mes = date('m');
    $ano_atual = date('Y');

    // Aniversariantes cadastrados como usuários
    $stmt = $pdo->prepare("
        SELECT
            u.id as usuario_id,
            NULL as cliente_rapido_id,
            u.nome,
            u.email,
            u.data_nascimento,
            YEAR(CURDATE()) - YEAR(u.data_nascimento) as idade
        FROM usuarios u
        WHERE DAY(u.data_nascimento) = ?
          AND MONTH(u.data_nascimento) = ?
          AND u.email IS NOT NULL
          AND u.email != ''
          AND u.ativo = 1
    ");
    $stmt->execute([$hoje_dia, $hoje_mes]);
    $aniversariantes_usuarios = $stmt->fetchAll();

    // Aniversariantes em clientes_rapidos (que tenham email)
    try {
        $stmt = $pdo->prepare("
            SELECT
                NULL as usuario_id,
                cr.id as cliente_rapido_id,
                cr.nome,
                NULL as email,
                cr.data_nascimento,
                YEAR(CURDATE()) - YEAR(cr.data_nascimento) as idade
            FROM clientes_rapidos cr
            WHERE DAY(cr.data_nascimento) = ?
              AND MONTH(cr.data_nascimento) = ?
        ");
        $stmt->execute([$hoje_dia, $hoje_mes]);
        $aniversariantes_rapidos = $stmt->fetchAll();
    } catch (PDOException $e) {
        log_aniversario("Tabela clientes_rapidos não existe ainda: " . $e->getMessage());
        $aniversariantes_rapidos = [];
    }

    // Combinar ambas as listas
    $aniversariantes = array_merge($aniversariantes_usuarios, $aniversariantes_rapidos);

    if (empty($aniversariantes)) {
        log_aniversario("Nenhum aniversariante encontrado para hoje (" . date('d/m') . ")");
        exit;
    }

    log_aniversario("Encontrados " . count($aniversariantes) . " aniversariantes para hoje");

    // Instanciar template de email
    $emailTemplates = new EmailTemplates($pdo);

    $enviados = 0;
    $erros = 0;

    foreach ($aniversariantes as $aniversariante) {
        // Pular clientes rápidos sem email
        if (empty($aniversariante['email'])) {
            log_aniversario("Cliente '{$aniversariante['nome']}' não possui email cadastrado - pulando");
            continue;
        }

        // Verificar se já enviamos email hoje para evitar duplicatas
        $data_hoje = date('Y-m-d');
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM logs_email
                WHERE destinatario = ?
                  AND assunto LIKE '%Aniversário%'
                  AND DATE(criado_em) = ?
                  AND status = 'enviado'
            ");
            $stmt->execute([$aniversariante['email'], $data_hoje]);
            $ja_enviado = $stmt->fetchColumn() > 0;

            if ($ja_enviado) {
                log_aniversario("Email já enviado hoje para {$aniversariante['nome']} ({$aniversariante['email']})");
                continue;
            }
        } catch (PDOException $e) {
            // Tabela logs_email pode não existir, continuar
        }

        // Gerar email de aniversário
        $corpo_email = $emailTemplates->emailAniversario(
            $aniversariante['nome'],
            $aniversariante['idade']
        );

        // Enviar email
        $resultado = enviar_email(
            $aniversariante['email'],
            '🎂 Feliz Aniversário, ' . $aniversariante['nome'] . '!',
            $corpo_email,
            $aniversariante['nome']
        );

        if ($resultado) {
            $enviados++;
            log_aniversario("✓ Email enviado para {$aniversariante['nome']} ({$aniversariante['email']}) - {$aniversariante['idade']} anos");

            // Atualizar registro de lembrete (se tabela existir)
            try {
                if ($aniversariante['usuario_id']) {
                    $stmt = $pdo->prepare("
                        INSERT INTO lembretes_aniversario (usuario_id, nome, data_nascimento, ultimo_lembrete)
                        VALUES (?, ?, ?, CURDATE())
                        ON DUPLICATE KEY UPDATE ultimo_lembrete = CURDATE()
                    ");
                    $stmt->execute([
                        $aniversariante['usuario_id'],
                        $aniversariante['nome'],
                        $aniversariante['data_nascimento']
                    ]);
                } elseif ($aniversariante['cliente_rapido_id']) {
                    $stmt = $pdo->prepare("
                        INSERT INTO lembretes_aniversario (cliente_rapido_id, nome, data_nascimento, ultimo_lembrete)
                        VALUES (?, ?, ?, CURDATE())
                        ON DUPLICATE KEY UPDATE ultimo_lembrete = CURDATE()
                    ");
                    $stmt->execute([
                        $aniversariante['cliente_rapido_id'],
                        $aniversariante['nome'],
                        $aniversariante['data_nascimento']
                    ]);
                }
            } catch (PDOException $e) {
                // Tabela pode não existir ainda
                log_aniversario("Não foi possível registrar lembrete: " . $e->getMessage());
            }
        } else {
            $erros++;
            log_aniversario("✗ Erro ao enviar email para {$aniversariante['nome']} ({$aniversariante['email']})");
        }
    }

    log_aniversario("=== Fim da verificação ===");
    log_aniversario("Total: " . count($aniversariantes) . " | Enviados: $enviados | Erros: $erros");

    // Se executado via web, mostrar resultado
    if (php_sapi_name() !== 'cli') {
        echo json_encode([
            'success' => true,
            'total' => count($aniversariantes),
            'enviados' => $enviados,
            'erros' => $erros
        ]);
    }

} catch (Exception $e) {
    $erro = "ERRO FATAL: " . $e->getMessage();
    log_aniversario($erro);

    if (php_sapi_name() !== 'cli') {
        echo json_encode(['success' => false, 'error' => $erro]);
    }
}
