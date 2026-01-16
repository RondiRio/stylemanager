<?php
/**
 * Handler para Alterar Status de Agendamento
 * Permite ao admin/recepcionista atualizar o status do agendamento
 * e atribuir profissional ao finalizar
 */
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';

// Apenas admin e recepcionista
requer_login(['admin', 'recepcionista']);

// Resposta padrão
header('Content-Type: application/json');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Verificar CSRF
if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// Receber dados
$agendamento_id = (int)($_POST['agendamento_id'] ?? 0);
$novo_status = trim($_POST['status'] ?? '');
$profissional_id = !empty($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : null;
$observacoes = trim($_POST['observacoes'] ?? '');

// Validar dados básicos
if (!$agendamento_id) {
    echo json_encode(['success' => false, 'error' => 'ID do agendamento inválido']);
    exit;
}

if (empty($novo_status)) {
    echo json_encode(['success' => false, 'error' => 'Status não informado']);
    exit;
}

// Status permitidos
$status_permitidos = [
    'agendado',
    'confirmado',
    'cliente_chegou',
    'em_atendimento',
    'finalizado',
    'nao_chegou',
    'cancelado'
];

if (!in_array($novo_status, $status_permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Status inválido']);
    exit;
}

// Se o status for 'finalizado', profissional é obrigatório
if ($novo_status === 'finalizado' && !$profissional_id) {
    echo json_encode(['success' => false, 'error' => 'Profissional é obrigatório ao finalizar']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar se o agendamento existe
    $stmt = $pdo->prepare("SELECT id, status, profissional_id FROM agendamentos WHERE id = ?");
    $stmt->execute([$agendamento_id]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Agendamento não encontrado']);
        exit;
    }

    // Não permitir alterar status de agendamentos já finalizados
    if ($agendamento['status'] === 'finalizado' && $novo_status !== 'finalizado') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Não é possível alterar status de agendamento já finalizado']);
        exit;
    }

    // Preparar dados para atualização
    $dados_atualizacao = ['status' => $novo_status];

    // Se há observações, adicionar
    if (!empty($observacoes)) {
        $dados_atualizacao['observacoes'] = $observacoes;
    }

    // Se está finalizando, atualizar profissional_id
    if ($novo_status === 'finalizado' && $profissional_id) {
        $dados_atualizacao['profissional_id'] = $profissional_id;
    }

    // Construir query de atualização
    $campos = [];
    $valores = [];
    foreach ($dados_atualizacao as $campo => $valor) {
        $campos[] = "$campo = ?";
        $valores[] = $valor;
    }
    $valores[] = $agendamento_id;

    $query = "UPDATE agendamentos SET " . implode(', ', $campos) . " WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute($valores);

    // Se está finalizando, criar registro de atendimento se ainda não existir
    if ($novo_status === 'finalizado') {
        // Verificar se já existe atendimento para este agendamento
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM atendimentos WHERE agendamento_item_id IN (SELECT id FROM agendamento_itens WHERE agendamento_id = ?)");
        $stmt->execute([$agendamento_id]);
        $tem_atendimento = $stmt->fetchColumn() > 0;

        // Se não tem atendimento registrado, criar um básico
        if (!$tem_atendimento) {
            // Buscar dados do agendamento
            $stmt = $pdo->prepare("
                SELECT
                    a.id,
                    a.cliente_id,
                    a.profissional_id,
                    a.data_agendamento as data,
                    ai.id as agendamento_item_id,
                    ai.servico_id,
                    s.preco as valor_servico
                FROM agendamentos a
                LEFT JOIN agendamento_itens ai ON ai.agendamento_id = a.id
                LEFT JOIN servicos s ON s.id = ai.servico_id
                WHERE a.id = ?
            ");
            $stmt->execute([$agendamento_id]);
            $dados = $stmt->fetch();

            if ($dados && $dados['servico_id']) {
                // Buscar comissão do profissional
                $stmt = $pdo->prepare("SELECT servico FROM comissoes WHERE profissional_id = ? LIMIT 1");
                $stmt->execute([$profissional_id]);
                $comissao_percentual = $stmt->fetchColumn() ?: 0;
                $comissao_valor = ($dados['valor_servico'] * $comissao_percentual) / 100;

                // Inserir atendimento
                $stmt = $pdo->prepare("
                    INSERT INTO atendimentos (
                        agendamento_item_id,
                        profissional_id,
                        servico_id,
                        cliente_id,
                        valor_servico,
                        comissao_servico,
                        status,
                        data_atendimento
                    ) VALUES (?, ?, ?, ?, ?, ?, 'concluido', NOW())
                ");
                $stmt->execute([
                    $dados['agendamento_item_id'],
                    $profissional_id,
                    $dados['servico_id'],
                    $dados['cliente_id'],
                    $dados['valor_servico'],
                    $comissao_valor
                ]);

                // Também inserir em servicos_realizados
                $atendimento_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("
                    SELECT nome, preco FROM servicos WHERE id = ?
                ");
                $stmt->execute([$dados['servico_id']]);
                $servico = $stmt->fetch();

                $stmt = $pdo->prepare("
                    INSERT INTO servicos_realizados (
                        atendimento_id,
                        profissional_id,
                        servico_id,
                        cliente_id,
                        nome_servico,
                        preco,
                        comissao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $atendimento_id,
                    $profissional_id,
                    $dados['servico_id'],
                    $dados['cliente_id'],
                    $servico['nome'],
                    $servico['preco'],
                    $comissao_valor
                ]);
            }
        }
    }

    $pdo->commit();

    // Log da ação
    $mensagem_status = [
        'confirmado' => 'Agendamento confirmado',
        'cliente_chegou' => 'Cliente marcado como chegou',
        'em_atendimento' => 'Atendimento iniciado',
        'finalizado' => 'Atendimento finalizado',
        'nao_chegou' => 'Cliente marcado como não chegou',
        'cancelado' => 'Agendamento cancelado'
    ];

    echo json_encode([
        'success' => true,
        'message' => $mensagem_status[$novo_status] ?? 'Status atualizado com sucesso'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao atualizar status do agendamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar status: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao processar requisição: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao processar requisição: ' . $e->getMessage()]);
}
