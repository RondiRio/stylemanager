<?php
/**
 * Handler para Aprovar/Negar Vales
 */
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
header('Content-Type: application/json');

requer_login('admin');

// Receber dados JSON
$data = json_decode(file_get_contents('php://input'), true);
$vale_id = (int)($data['vale_id'] ?? 0);
$acao = $data['acao'] ?? '';
$csrf_token = $data['csrf_token'] ?? '';

// Validar CSRF
if (!verificar_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// Validações
if (!$vale_id) {
    echo json_encode(['success' => false, 'error' => 'ID do vale inválido']);
    exit;
}

if (!in_array($acao, ['aprovar', 'negar'])) {
    echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    exit;
}

try {
    // Verificar se o vale existe e está pendente
    $stmt = $pdo->prepare("SELECT id, status, valor, profissional_id FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    $vale = $stmt->fetch();

    if (!$vale) {
        echo json_encode(['success' => false, 'error' => 'Vale não encontrado']);
        exit;
    }

    if ($vale['status'] !== 'pendente') {
        echo json_encode(['success' => false, 'error' => 'Este vale já foi processado']);
        exit;
    }

    // Atualizar status
    $admin_id = $_SESSION['usuario_id'];
    $novo_status = $acao === 'aprovar' ? 'aprovado' : 'negado';

    $stmt = $pdo->prepare("
        UPDATE vales
        SET status = ?,
            aprovado_por = ?,
            data_aprovacao = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$novo_status, $admin_id, $vale_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Vale ' . ($acao === 'aprovar' ? 'aprovado' : 'negado') . ' com sucesso'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()]);
}
