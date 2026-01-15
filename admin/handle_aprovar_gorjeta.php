<?php
/**
 * Handler para Aprovar/Negar Gorjetas
 */
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
header('Content-Type: application/json');

requer_login('admin');

// Receber dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['gorjeta_id'])) {
        // Form data (negação)
        $gorjeta_id = (int)$_POST['gorjeta_id'];
        $motivo_negacao = trim($_POST['motivo_negacao'] ?? '');
        $csrf_token = $_POST['csrf_token'] ?? '';
        $acao = 'negar';
    } else {
        // JSON data (aprovação)
        $data = json_decode(file_get_contents('php://input'), true);
        $gorjeta_id = (int)($data['gorjeta_id'] ?? 0);
        $acao = $data['acao'] ?? '';
        $motivo_negacao = '';
        $csrf_token = $data['csrf_token'] ?? '';
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Validar CSRF
if (!verificar_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

// Validações
if (!$gorjeta_id) {
    echo json_encode(['success' => false, 'error' => 'ID da gorjeta inválido']);
    exit;
}

if (!in_array($acao, ['aprovar', 'negar'])) {
    echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    exit;
}

if ($acao === 'negar' && empty($motivo_negacao)) {
    echo json_encode(['success' => false, 'error' => 'Motivo da negação é obrigatório']);
    exit;
}

try {
    // Verificar se a gorjeta existe e está pendente
    $stmt = $pdo->prepare("SELECT id, status FROM gorjetas WHERE id = ?");
    $stmt->execute([$gorjeta_id]);
    $gorjeta = $stmt->fetch();

    if (!$gorjeta) {
        echo json_encode(['success' => false, 'error' => 'Gorjeta não encontrada']);
        exit;
    }

    if ($gorjeta['status'] !== 'pendente') {
        echo json_encode(['success' => false, 'error' => 'Esta gorjeta já foi processada']);
        exit;
    }

    // Atualizar status
    $admin_id = $_SESSION['usuario_id'];

    if ($acao === 'aprovar') {
        $stmt = $pdo->prepare("
            UPDATE gorjetas
            SET status = 'aprovado',
                aprovado_por = ?,
                data_aprovacao = NOW(),
                motivo_negacao = NULL
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $gorjeta_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Gorjeta aprovada com sucesso'
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE gorjetas
            SET status = 'negado',
                aprovado_por = ?,
                data_aprovacao = NOW(),
                motivo_negacao = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $motivo_negacao, $gorjeta_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Gorjeta negada com sucesso'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()]);
}
