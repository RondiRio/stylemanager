<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('cliente');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('dashboard.php', 'Token inválido.', 'danger');
}

$legenda = trim($_POST['legenda'] ?? '');
$publico = !empty($_POST['publico']) ? 1 : 0;
$imagens = $_FILES['imagens'] ?? [];
$video = $_FILES['video'] ?? [];

$pasta = __DIR__ . '/../assets/img/feed/';
if (!is_dir($pasta)) mkdir($pasta, 0755, true);

$arquivo_final = $video_nome = null;

if ($video['name'] ?? '') {
    $ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm', 'ogg'])) {
        redirecionar_com_mensagem('dashboard.php', 'Vídeo inválido.', 'danger');
    }
    $video_nome = uniqid() . '.' . $ext;
    if (!move_uploaded_file($video['tmp_name'], $pasta . $video_nome)) {
        redirecionar_com_mensagem('dashboard.php', 'Erro ao salvar vídeo.', 'danger');
    }
} elseif (!empty($imagens['name'][0])) {
    $ext = strtolower(pathinfo($imagens['name'][0], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        redirecionar_com_mensagem('dashboard.php', 'Imagem inválida.', 'danger');
    }
    $arquivo_final = uniqid() . '.' . $ext;
    if (!move_uploaded_file($imagens['tmp_name'][0], $pasta . $arquivo_final)) {
        redirecionar_com_mensagem('dashboard.php', 'Erro ao salvar imagem.', 'danger');
    }
} else {
    redirecionar_com_mensagem('dashboard.php', 'Adicione uma foto ou vídeo.', 'warning');
}

$stmt = $pdo->prepare("INSERT INTO posts (usuario_id, imagem, video, legenda, publico) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$_SESSION['usuario_id'], $arquivo_final, $video_nome, $legenda, $publico]);
redirecionar_com_mensagem('dashboard.php', 'Post publicado!', 'success');
?>