<?php
/**
 * Handler Universal para Criar Posts
 * Funciona para todos os tipos de usuários (admin, profissional, cliente)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/utils.php';

// Verificar se está logado (qualquer tipo de usuário)
if (!isset($_SESSION['usuario_id'])) {
    redirecionar_com_mensagem('../login.php', 'Você precisa estar logado.', 'danger');
}

// Verificar CSRF token
if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $redirect = determinarRedirect();
    redirecionar_com_mensagem($redirect, 'Token inválido.', 'danger');
}

$legenda = trim($_POST['legenda'] ?? '');
$imagens = $_FILES['imagens'] ?? [];
$video = $_FILES['video'] ?? [];

$pasta = __DIR__ . '/../assets/img/feed/';
if (!is_dir($pasta)) mkdir($pasta, 0755, true);

$midia_url = null;
$tipo = 'texto';

// Processar vídeo
if ($video['name'] ?? '') {
    $ext = strtolower(pathinfo($video['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp4', 'webm', 'ogg'])) {
        $redirect = determinarRedirect();
        redirecionar_com_mensagem($redirect, 'Vídeo inválido.', 'danger');
    }
    $midia_url = uniqid() . '.' . $ext;
    if (!move_uploaded_file($video['tmp_name'], $pasta . $midia_url)) {
        $redirect = determinarRedirect();
        redirecionar_com_mensagem($redirect, 'Erro ao salvar vídeo.', 'danger');
    }
    $tipo = 'video';
}
// Processar imagem
elseif (!empty($imagens['name'][0])) {
    $ext = strtolower(pathinfo($imagens['name'][0], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $redirect = determinarRedirect();
        redirecionar_com_mensagem($redirect, 'Imagem inválida.', 'danger');
    }
    $midia_url = uniqid() . '.' . $ext;
    if (!move_uploaded_file($imagens['tmp_name'][0], $pasta . $midia_url)) {
        $redirect = determinarRedirect();
        redirecionar_com_mensagem($redirect, 'Erro ao salvar imagem.', 'danger');
    }
    $tipo = 'foto';
}
// Sem mídia
else {
    $redirect = determinarRedirect();
    redirecionar_com_mensagem($redirect, 'Adicione uma foto ou vídeo.', 'warning');
}

// Inserir post no banco
$stmt = $pdo->prepare("INSERT INTO posts (usuario_id, tipo, midia_url, legenda) VALUES (?, ?, ?, ?)");
$stmt->execute([$_SESSION['usuario_id'], $tipo, $midia_url, $legenda]);

$redirect = determinarRedirect();
redirecionar_com_mensagem($redirect, 'Post publicado com sucesso!', 'success');

/**
 * Determina para qual página redirecionar baseado no tipo de usuário
 */
function determinarRedirect() {
    $tipo = $_SESSION['tipo'] ?? 'cliente';

    switch ($tipo) {
        case 'admin':
            return '../admin/feed.php';
        case 'profissional':
            return '../profissional/dashboard.php';
        case 'cliente':
        default:
            return '../cliente/dashboard.php';
    }
}
