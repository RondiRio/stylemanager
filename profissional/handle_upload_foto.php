<?php
// profissional/handle_upload_foto.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '') || empty($_FILES['foto']['name'])) {
    redirecionar_com_mensagem('dashboard.php', 'Erro no upload.', 'danger');
}

$ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
$nome = bin2hex(random_bytes(16)) . '.' . $ext;
$caminho = "../assets/img/mural/$nome";

if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
    $pdo->prepare("INSERT INTO fotos_profissional (profissional_id, url_foto) VALUES (?, ?)")
        ->execute([$_SESSION['usuario_id'], $nome]);
    redirecionar_com_mensagem('dashboard.php', 'Foto adicionada ao mural!', 'success');
} else {
    redirecionar_com_mensagem('dashboard.php', 'Falha ao salvar foto.', 'danger');
}
?>