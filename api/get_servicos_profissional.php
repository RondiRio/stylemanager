<?php
// api/get_servicos_profissional.php
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$profissional_id = $data['profissional_id'] ?? 0;

if (!$profissional_id) {
    echo json_encode([]);
    exit;
}

// === SE TIVER TABELA profissional_servicos: FILTRA ===
$has_table = $pdo->query("SHOW TABLES LIKE 'profissional_servicos'")->rowCount() > 0;

if ($has_table) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.nome, s.duracao_min, s.preco 
        FROM servicos s 
        JOIN profissional_servicos ps ON ps.servico_id = s.id 
        WHERE ps.profissional_id = ? AND s.ativo = 1 
        ORDER BY s.nome
    ");
    $stmt->execute([$profissional_id]);
} else {
    // === SEM TABELA: MOSTRA TODOS OS SERVIÇOS (para funcionar enquanto não cadastra) ===
    $stmt = $pdo->prepare("
        SELECT id, nome, duracao_min, preco 
        FROM servicos 
        WHERE ativo = 1 
        ORDER BY nome
    ");
    $stmt->execute();
}

echo json_encode($stmt->fetchAll());
?>