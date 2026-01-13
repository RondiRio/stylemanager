<?php
// cliente/handle_seguir.php (SEGUIR/DEIXAR DE SEGUIR)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
header('Content-Type: application/json');

requer_login('cliente');

$data = json_decode(file_get_contents('php://input'), true);
if (!verificar_csrf_token($data['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

$seguido_id = (int)($data['seguido_id'] ?? 0);
$seguidor_id = $_SESSION['usuario_id'];

if ($seguido_id === $seguidor_id) {
    echo json_encode(['success' => false, 'error' => 'Você não pode seguir a si mesmo']);
    exit;
}

// Verifica se o usuário existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND ativo = 1");
$stmt->execute([$seguido_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
    exit;
}

// Verifica se já segue
$stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
$stmt->execute([$seguidor_id, $seguido_id]);
$ja_segue = $stmt->rowCount() > 0;

if ($ja_segue) {
    // DEIXAR DE SEGUIR
    $stmt = $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $seguindo = false;
} else {
    // SEGUIR
    $stmt = $pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)");
    $stmt->execute([$seguidor_id, $seguido_id]);
    $seguindo = true;
}

echo json_encode(['success' => true, 'seguindo' => $seguindo]);
?>