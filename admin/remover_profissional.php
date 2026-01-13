<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';

requer_login('admin');

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['sucesso' => false]);
    exit;
}

/*
 Não usar DELETE.
 Apenas desativa o profissional.
 Histórico permanece íntegro.
*/
$stmt = $pdo->prepare("
    UPDATE usuarios 
    SET ativo = 0 
    WHERE id = ? AND tipo = 'profissional'
");
$stmt->execute([$id]);

echo json_encode(['sucesso' => true]);
