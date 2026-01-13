<?php
// profissional/handle_registrar_atendimento.php (CORRIGIDO E PADRONIZADO)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('dashboard.php', 'Token inválido.', 'danger');
}

$servico_id = $_POST['servico_id'] ?? 0;
$cliente_nome = trim($_POST['cliente_nome'] ?? '');
$valor_cobrado = $_POST['valor_cobrado'] !== '' ? (float)$_POST['valor_cobrado'] : null;
$metodo = $_POST['metodo_pagamento'] ?? 'dinheiro';

if (!$servico_id) {
    redirecionar_com_mensagem('dashboard.php', 'Selecione um serviço.', 'danger');
}

// === BUSCAR SERVIÇO ===
$stmt = $pdo->prepare("SELECT nome, preco FROM servicos WHERE id = ? AND ativo = 1");
$stmt->execute([$servico_id]);
$servico = $stmt->fetch();

if (!$servico) {
    redirecionar_com_mensagem('dashboard.php', 'Serviço inválido ou inativo.', 'danger');
}

// === VALOR FINAL ===
$valor_final = $valor_cobrado !== null && $valor_cobrado >= 0 ? $valor_cobrado : $servico['preco'];

// === COMISSÃO ===
$stmt_comissao = $pdo->prepare("SELECT percentual FROM comissoes WHERE profissional_id = ? AND tipo = 'servico'");
$stmt_comissao->execute([$_SESSION['usuario_id']]);
$comissao_perc = $stmt_comissao->fetchColumn() ?: 0;

$comissao = $comissao_perc > 0 ? round($valor_final * $comissao_perc / 100, 2) : 0;

// === CRIAR CLIENTE TEMPORÁRIO (SE NOME FOR INFORMADO) ===
$cliente_id = null;
if ($cliente_nome !== '') {
    $stmt_cliente = $pdo->prepare("
        INSERT INTO usuarios (nome, tipo, ativo) 
        VALUES (?, 'cliente', 1) 
        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
    ");
    $stmt_cliente->execute([$cliente_nome]);
    $cliente_id = $pdo->lastInsertId();
}

// === REGISTRAR ATENDIMENTO ===
$stmt_insert = $pdo->prepare("
    INSERT INTO atendimentos 
    (profissional_id, servico_id, cliente_id, valor_servico, comissao_servico, metodo_pagamento, data_atendimento)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");

$stmt_insert->execute([
    $_SESSION['usuario_id'],
    $servico_id,
    $cliente_id,
    $valor_final,
    $comissao,
    $metodo
]);

redirecionar_com_mensagem(
    'dashboard.php', 
    "Atendimento registrado: {$servico['nome']} (R$ " . formatar_moeda($valor_final) . ")", 
    'success'
);
?>