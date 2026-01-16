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

    // Se está finalizando, criar registros de atendimento
    if ($novo_status === 'finalizado') {
        // Buscar dados do agendamento
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.cliente_id,
                a.cliente_rapido_id,
                a.cliente_nome,
                a.data_agendamento
            FROM agendamentos a
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamento_id]);
        $agendamento_dados = $stmt->fetch();

        if (!$agendamento_dados) {
            throw new Exception('Agendamento não encontrado');
        }

        // Buscar itens do agendamento (serviços)
        $stmt = $pdo->prepare("
            SELECT
                ai.id as agendamento_item_id,
                ai.servico_id,
                s.nome as servico_nome,
                s.preco as servico_preco
            FROM agendamento_itens ai
            JOIN servicos s ON s.id = ai.servico_id
            WHERE ai.agendamento_id = ?
        ");
        $stmt->execute([$agendamento_id]);
        $itens = $stmt->fetchAll();

        if (empty($itens)) {
            throw new Exception('Nenhum serviço encontrado para este agendamento');
        }

        // Buscar comissão do profissional
        $stmt = $pdo->prepare("SELECT servico FROM comissoes WHERE profissional_id = ? LIMIT 1");
        $stmt->execute([$profissional_id]);
        $comissao_percentual = $stmt->fetchColumn() ?: 0;

        // Criar um atendimento consolidado
        $valor_total = array_sum(array_column($itens, 'servico_preco'));
        $comissao_total = ($valor_total * $comissao_percentual) / 100;

        $stmt = $pdo->prepare("
            INSERT INTO atendimentos (
                profissional_id,
                cliente_id,
                cliente_nome,
                valor_servico,
                comissao_servico,
                status,
                data_atendimento,
                metodo_pagamento
            ) VALUES (?, ?, ?, ?, ?, 'concluido', ?, 'dinheiro')
        ");
        $stmt->execute([
            $profissional_id,
            $agendamento_dados['cliente_id'],
            $agendamento_dados['cliente_nome'],
            $valor_total,
            $comissao_total,
            $agendamento_dados['data_agendamento'] . ' ' . date('H:i:s')
        ]);

        $atendimento_id = $pdo->lastInsertId();

        // Inserir cada serviço em servicos_realizados
        $stmt_servico = $pdo->prepare("
            INSERT INTO servicos_realizados (
                atendimento_id,
                profissional_id,
                servico_id,
                cliente_id,
                nome,
                preco,
                comissao
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($itens as $item) {
            $comissao_item = ($item['servico_preco'] * $comissao_percentual) / 100;

            $stmt_servico->execute([
                $atendimento_id,
                $profissional_id,
                $item['servico_id'],
                $agendamento_dados['cliente_id'],
                $item['servico_nome'],
                $item['servico_preco'],
                $comissao_item
            ]);
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