<?php
// api/get_profissionais.php
require '../includes/db_connect.php';
require '../includes/auth.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? '';
$servico_ids = $input['servico_ids'] ?? [];

if (empty($servico_ids)) {
    echo json_encode([]);
    exit;
}

$placeholders = str_repeat('?,', count($servico_ids) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nome
    FROM usuarios u
    JOIN profissional_servico ps ON ps.profissional_id = u.id
    WHERE ps.servico_id IN ($placeholders)
    AND u.tipo = 'profissional' AND u.ativo = 1
");
$stmt->execute($servico_ids);
$profs = $stmt->fetchAll();
echo json_encode($profs);
?>