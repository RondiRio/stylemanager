<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');
requer_login('cliente');

$data = json_decode(file_get_contents('php://input'), true);
if (!verificar_csrf_token($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false]);
    exit;
}

$post_id = $data['post_id'] ?? 0;
$stmt = $pdo->prepare("SELECT tipo, midia_url, usuario_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post || $post['usuario_id'] != $_SESSION['usuario_id']) {
    echo json_encode(['success' => false]);
    exit;
}

if ($post['midia_url']) {
    @unlink(__DIR__ . '/../assets/img/feed/' . $post['midia_url']);
    @unlink(__DIR__ . '/../assets/img/posts/' . $post['midia_url']);
}

$pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
echo json_encode(['success' => true]);
?>