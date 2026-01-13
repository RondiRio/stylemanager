<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');
requer_login('cliente');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false]);
    exit;
}

$post_id = $_POST['post_id'] ?? 0;
$legenda = trim($_POST['legenda'] ?? '');

$stmt = $pdo->prepare("SELECT usuario_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post || $post['usuario_id'] != $_SESSION['usuario_id']) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("UPDATE posts SET legenda = ? WHERE id = ?");
$stmt->execute([$legenda, $post_id]);

echo json_encode(['success' => true, 'post_id' => $post_id, 'legenda' => $legenda]);
?>