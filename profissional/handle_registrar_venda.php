<?php
// profissional/handle_registrar_venda.php (CORRIGIDO)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('dashboard.php', 'Token inválido.', 'danger');
}

$produto_id = $_POST['produto_id'] ?? 0;
$qtd = max(1, (int)($_POST['quantidade'] ?? 1));
$metodo = $_POST['metodo_pagamento'] ?? 'dinheiro';

if (!$produto_id) {
    redirecionar_com_mensagem('dashboard.php', 'Selecione um produto.', 'danger');
}

// CORREÇÃO: Usar prepare + execute + fetch corretamente
$stmt = $pdo->prepare("SELECT nome, preco_venda FROM produtos WHERE id = ? AND ativo = 1");
$stmt->execute([$produto_id]);
$produto = $stmt->fetch();

if (!$produto) {
    redirecionar_com_mensagem('dashboard.php', 'Produto inválido ou inativo.', 'danger');
}

$valor_total = $produto['preco_venda'] * $qtd;

// Comissão de produto
$stmt_comissao = $pdo->prepare("SELECT percentual FROM comissoes WHERE profissional_id = ? AND tipo = 'produto'");
$stmt_comissao->execute([$_SESSION['usuario_id']]);
$comissao_perc = $stmt_comissao->fetchColumn() ?: 0;

$comissao = $comissao_perc > 0 ? round($valor_total * $comissao_perc / 100, 2) : 0;

// Registrar venda
$stmt_insert = $pdo->prepare("
    INSERT INTO vendas_produtos
    (profissional_id, produto_id, quantidade, valor_unitario, valor_total, comissao_produto)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt_insert->execute([
    $_SESSION['usuario_id'],
    $produto_id,
    $qtd,
    $produto['preco_venda'],
    $valor_total,
    $comissao
]);

redirecionar_com_mensagem('dashboard.php', "Venda de {$qtd}x {$produto['nome']} registrada!", 'success');
?>