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
$descricao = trim($_POST['descricao'] ?? '');

if ($valor <= 0) redirecionar_com_mensagem('dashboard.php', 'Valor inválido.', 'danger');

$pdo->prepare("
    INSERT INTO vales (profissional_id, vale, descricao, data_vale)
    VALUES (?, ?, ?, NOW())
")->execute([$_SESSION['usuario_id'], $valor, $descricao]);

redirecionar_com_mensagem('dashboard.php', "Vale registrado com sucesso!", 'success');
?>