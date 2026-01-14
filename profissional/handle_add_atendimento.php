<?php
// profissional/handle_add_atendimento.php - VERSÃO CORRIGIDA
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('view_agenda_dia.php', 'Token inválido.', 'danger');
}

$agendamento_id = (int)($_POST['item_id'] ?? 0);
if (!$agendamento_id) {
    redirecionar_com_mensagem('view_agenda_dia.php', 'Agendamento não encontrado.', 'danger');
}

$profissional_id = $_SESSION['usuario_id'];

$pdo->beginTransaction();
try {
    // 1. BUSCAR DADOS DO AGENDAMENTO
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.cliente_id,
            a.profissional_id,
            a.data,
            a.hora_inicio,
            a.hora_fim,
            a.status,
            u.nome AS cliente_nome
        FROM agendamentos a
        LEFT JOIN usuarios u ON u.id = a.cliente_id
        WHERE a.id = ? AND a.profissional_id = ?
    ");
    $stmt->execute([$agendamento_id, $profissional_id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        throw new Exception("Agendamento não encontrado ou você não tem permissão.");
    }

    if ($agendamento['status'] === 'finalizado') {
        throw new Exception("Este agendamento já foi finalizado.");
    }

    $cliente_id = $agendamento['cliente_id'];
    $cliente_nome = $agendamento['cliente_nome'] ?? 'Cliente não identificado';

    // 2. BUSCAR SERVIÇOS DO AGENDAMENTO
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.nome,
            s.preco,
            s.duracao_min
        FROM agendamento_itens ai
        JOIN servicos s ON s.id = ai.servico_id
        WHERE ai.agendamento_id = ?
    ");
    $stmt->execute([$agendamento_id]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($servicos)) {
        throw new Exception("Nenhum serviço encontrado para este agendamento.");
    }

    // 3. CALCULAR VALORES
    $valor_total_servicos = array_sum(array_column($servicos, 'preco'));

    // Buscar comissões do profissional
    $stmt = $pdo->prepare("SELECT servico, produto FROM comissoes WHERE profissional_id = ? LIMIT 1");
    $stmt->execute([$profissional_id]);
    $comissoes = $stmt->fetch(PDO::FETCH_ASSOC);
    $percentual_servico = $comissoes ? (float)$comissoes['servico'] : 0.0;

    $comissao_total = ($valor_total_servicos * $percentual_servico) / 100;

    // 4. CRIAR ATENDIMENTO
    $primeiro_servico_id = $servicos[0]['id'];

    $stmt = $pdo->prepare("
        INSERT INTO atendimentos (
            profissional_id,
            cliente_nome,
            cliente_id,
            servico_id,
            valor_servico,
            valor_produto,
            gorjeta,
            comissao_servico,
            metodo_pagamento,
            status,
            data_atendimento
        ) VALUES (
            ?, ?, ?, ?, ?, 0, 0, ?, 'dinheiro', 'concluido', NOW()
        )
    ");

    $stmt->execute([
        $profissional_id,
        $cliente_nome,
        $cliente_id,
        $primeiro_servico_id,
        $valor_total_servicos,
        $comissao_total
    ]);

    $atendimento_id = $pdo->lastInsertId();

    // 5. REGISTRAR SERVIÇOS REALIZADOS
    $stmt = $pdo->prepare("
        INSERT INTO servicos_realizados
        (atendimento_id, profissional_id, servico_id, cliente_id, nome, preco, comissao, data_realizacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $comissao_por_servico = count($servicos) > 0 ? $comissao_total / count($servicos) : 0;

    foreach ($servicos as $s) {
        $stmt->execute([
            $atendimento_id,
            $profissional_id,
            $s['id'],
            $cliente_id,
            $s['nome'],
            $s['preco'],
            $comissao_por_servico
        ]);
    }

    // 6. ATUALIZAR STATUS DO AGENDAMENTO
    $stmt = $pdo->prepare("
        UPDATE agendamentos
        SET status = 'finalizado'
        WHERE id = ?
    ");
    $stmt->execute([$agendamento_id]);

    $pdo->commit();
    redirecionar_com_mensagem('view_agenda_dia.php', 'Atendimento finalizado e registrado com sucesso!', 'success');

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao finalizar atendimento: " . $e->getMessage());
    redirecionar_com_mensagem('view_agenda_dia.php', 'Erro: ' . $e->getMessage(), 'danger');
}
?>
