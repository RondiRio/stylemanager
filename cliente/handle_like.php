<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');
requer_login('cliente');

$data = json_decode(file_get_contents('php://input'), true);
if (!verificar_csrf_token($data['csrf_token'])) die(json_encode(['success' => false]));

$post_id = $data['post_id'];
$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND usuario_id = ?");
$stmt->execute([$post_id, $usuario_id]);
$curtiu = $stmt->rowCount() > 0;

if ($curtiu) {
    $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND usuario_id = ?")->execute([$post_id, $usuario_id]);
} else {
    $pdo->prepare("INSERT INTO post_likes (post_id, usuario_id) VALUES (?, ?)")->execute([$post_id, $usuario_id]);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$likes = $stmt->fetchColumn();

echo json_encode(['success' => true, 'curtiu' => !$curtiu, 'likes' => $likes]);