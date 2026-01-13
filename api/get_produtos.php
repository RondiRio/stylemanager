<?php
// api/get_produtos.php
require '../includes/db_connect.php';
header('Content-Type: application/json');
$prods = $pdo->query("SELECT id, nome, CONCAT('R$ ', REPLACE(FORMAT(preco_venda, 2), '.', ',')) AS preco FROM produtos WHERE ativo = 1")->fetchAll();
echo json_encode($prods);
?>