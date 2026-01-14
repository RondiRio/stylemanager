<?php
/**
 * Handler Universal para Curtir/Descurtir Posts
 * Funciona para todos os tipos de usuários (admin, profissional, cliente)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/utils.php';
header('Content-Type: application/json');

// Verificar se está logado (qualquer tipo de usuário)
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!verificar_csrf_token($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

$post_id = (int)($data['post_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];

// Verificar se o post existe
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Post não encontrado']);
    exit;
}

// Verificar se já curtiu
$stmt = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND usuario_id = ?");
$stmt->execute([$post_id, $usuario_id]);
$curtiu = $stmt->rowCount() > 0;

if ($curtiu) {
    // DESCURTIR
    $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND usuario_id = ?")->execute([$post_id, $usuario_id]);
} else {
    // CURTIR
    $pdo->prepare("INSERT INTO post_likes (post_id, usuario_id) VALUES (?, ?)")->execute([$post_id, $usuario_id]);
}

// Contar total de curtidas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$likes = $stmt->fetchColumn();

echo json_encode(['success' => true, 'curtiu' => !$curtiu, 'likes' => $likes]);
