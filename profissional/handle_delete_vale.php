<?php
// profissional/handle_delete_vale.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

$vale_id = (int)($_GET['id'] ?? 0);
$profissional_id = $_SESSION['usuario_id'];

if (!$vale_id) {
    redirecionar_com_mensagem('dashboard.php', 'Vale não encontrado.', 'danger');
}

// Verificar se o vale pertence ao profissional logado
$stmt = $pdo->prepare("SELECT id FROM vales WHERE id = ? AND profissional_id = ?");
$stmt->execute([$vale_id, $profissional_id]);
$vale = $stmt->fetch();

if (!$vale) {
    redirecionar_com_mensagem('dashboard.php', 'Vale não encontrado ou você não tem permissão para excluí-lo.', 'danger');
}

// Deletar o vale
$stmt = $pdo->prepare("DELETE FROM vales WHERE id = ? AND profissional_id = ?");
$stmt->execute([$vale_id, $profissional_id]);

redirecionar_com_mensagem('dashboard.php', 'Vale excluído com sucesso!', 'success');
