<?php
// profissional/handle_registrar_vale.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('dashboard.php', 'Token inválido.', 'danger');
}

$valor = (float)($_POST['valor'] ?? 0);
$motivo = trim($_POST['descricao'] ?? '');

if ($valor <= 0) redirecionar_com_mensagem('dashboard.php', 'Valor inválido.', 'danger');

$pdo->prepare("
    INSERT INTO vales (profissional_id, valor, motivo, data_vale)
    VALUES (?, ?, ?, CURDATE())
")->execute([$_SESSION['usuario_id'], $valor, $motivo]);

redirecionar_com_mensagem('dashboard.php', "Vale registrado com sucesso!", 'success');
?>