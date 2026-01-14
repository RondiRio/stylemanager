<?php
// profissional/handle_add_vale.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor'] ?? '0');
    $motivo = $_POST['motivo'] ?? '';
    $data = $_POST['data'] ?? date('Y-m-d');

    $pdo->prepare("INSERT INTO vales (profissional_id, valor, motivo, data_vale) VALUES (?, ?, ?, ?)")
        ->execute([$_SESSION['usuario_id'], $valor, $motivo, $data]);

    redirecionar_com_mensagem('dashboard.php', 'Vale registrado!', 'success');
}

// Se não for POST, redireciona para o dashboard
redirecionar_com_mensagem('dashboard.php', 'Método inválido.', 'danger');