<?php
// profissional/feed_profissional.php (COMPLETO E CORRIGIDO)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Feed";
requer_login('profissional');

$profissional_id = $_SESSION['usuario_id'];

// === CARREGAR CONFIGURAÇÕES DE CORES DO ADMIN ===
$config = $pdo->query("
    SELECT 
        cor_primaria,
        cor_secundaria,
        cor_fundo
    FROM configuracoes
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$cores = [
    'primaria'   => $config['cor_primaria']   ?? '#0d6efd',
    'secundaria' => $config['cor_secundaria'] ?? '#6c757d',
    'sucesso'    => '#28a745',
    'info'       => '#17a2b8',
    'aviso'      => '#ffc107',
    'perigo'     => '#dc3545',
    'escura'     => '#212529',
    'clara'      => '#f8f9fa'
];

// === FUNÇÃO PARA GERAR CSS DINÂMICO ===
function gerar_css_cores($cores) {
    return "
    :root {
        --bs-primary: {$cores['primaria']};
        --bs-secondary: {$cores['secundaria']};
        --bs-success: {$cores['sucesso']};
        --bs-info: {$cores['info']};
        --bs-warning: {$cores['aviso']};
        --bs-danger: {$cores['perigo']};
        --bs-dark: {$cores['escura']};
        --bs-light: {$cores['clara']};
    }
    .card-header { background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 80%, black)); }
    .btn-primary { background-color: var(--bs-primary); border-color: var(--bs-primary); }
    .btn-success { background-color: var(--bs-success); border-color: var(--bs-success); }
    .text-primary { color: var(--bs-primary) !important; }
    .bg-gradient-primary { background: linear-gradient(135deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary) 70%, black)); }
    .follow-btn { font-size: 0.85rem; }
    ";
}

// === PROCESSAR NOVO POST ===
if ($_POST && !isset($_POST['acao_seguir']) && !isset($_POST['acao_curtir']) && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $texto = trim($_POST['texto'] ?? '');
    $midia_tipo = $_POST['midia_tipo'] ?? 'nenhum';
    $midia_arquivo = '';

    try {
        $pdo->beginTransaction();

        // Upload de mídia
        if ($midia_tipo !== 'nenhum' && !empty($_FILES['midia']['name']) && $_FILES['midia']['error'] === UPLOAD_ERR_OK) {
            $extensoes_img = ['jpg', 'jpeg', 'png', 'webp'];
            $extensoes_vid = ['mp4', 'webm', 'mov'];
            $ext = strtolower(pathinfo($_FILES['midia']['name'], PATHINFO_EXTENSION));

            if ($midia_tipo === 'imagem' && !in_array($ext, $extensoes_img)) {
                throw new Exception("Formato de imagem inválido.");
            }
            if ($midia_tipo === 'video' && !in_array($ext, $extensoes_vid)) {
                throw new Exception("Formato de vídeo inválido (MP4, WebM, MOV).");
            }
            if ($_FILES['midia']['size'] > 50 * 1024 * 1024) {
                throw new Exception("Arquivo muito grande (máx 50MB).");
            }

            $nome_arquivo = $profissional_id . '_' . time() . '.' . $ext;
            $caminho = '../assets/img/posts/' . $nome_arquivo;
            
            if (!is_dir('../assets/img/posts/')) {
                mkdir('../assets/img/posts/', 0755, true);
            }
            
            if (!move_uploaded_file($_FILES['midia']['tmp_name'], $caminho)) {
                throw new Exception("Erro ao salvar mídia.");
            }
            $midia_arquivo = $nome_arquivo;
        }

        // Inserir post
        $tipo_post = $midia_tipo === 'imagem' ? 'foto' : ($midia_tipo === 'video' ? 'video' : 'texto');
        $stmt = $pdo->prepare("
            INSERT INTO posts (usuario_id, tipo, midia_url, legenda)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $profissional_id,
            $tipo_post,
            $midia_arquivo,
            $texto
        ]);

        $pdo->commit();
        $sucesso = "Postagem publicada!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro: " . $e->getMessage();
    }
}

// === PROCESSAR SEGUIR/DEIXAR DE SEGUIR (AJAX) ===
if (isset($_POST['acao_seguir']) && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    $alvo_id = (int)($_POST['alvo_id'] ?? 0);
    if ($alvo_id <= 0 || $alvo_id == $profissional_id) {
        exit(json_encode(['erro' => 'Usuário inválido']));
    }

    $stmt = $pdo->prepare("SELECT id FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$profissional_id, $alvo_id]);
    $ja_segue = $stmt->fetch();

    $pdo->beginTransaction();
    try {
        if ($_POST['acao_seguir'] === 'seguir' && !$ja_segue) {
            $pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)")
                ->execute([$profissional_id, $alvo_id]);
        } elseif ($_POST['acao_seguir'] === 'deixar' && $ja_segue) {
            $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?")
                ->execute([$profissional_id, $alvo_id]);
        }
        $pdo->commit();
        exit(json_encode(['sucesso' => true]));
    } catch (Exception $e) {
        $pdo->rollBack();
        exit(json_encode(['erro' => 'Erro no banco']));
    }
}

// === PROCESSAR CURTIDA (AJAX) ===
if (isset($_POST['acao_curtir']) && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Content-Type: application/json');
    $post_id = (int)($_POST['post_id'] ?? 0);
    
    if ($post_id <= 0) {
        exit(json_encode(['erro' => 'Post inválido']));
    }

    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND usuario_id = ?");
    $stmt->execute([$post_id, $profissional_id]);
    $ja_curtiu = $stmt->fetch();

    try {
        if ($ja_curtiu) {
            $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND usuario_id = ?")
                ->execute([$post_id, $profissional_id]);
        } else {
            $pdo->prepare("INSERT INTO post_likes (post_id, usuario_id) VALUES (?, ?)")
                ->execute([$post_id, $profissional_id]);
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $likes = $stmt->fetchColumn();

        exit(json_encode(['sucesso' => true, 'curtiu' => !$ja_curtiu, 'likes' => $likes]));
    } catch (Exception $e) {
        exit(json_encode(['erro' => 'Erro ao curtir']));
    }
}

// === CARREGAR POSTAGENS (AJAX + INICIAL) ===
if (isset($_GET['atualizar'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.legenda,
            p.tipo,
            p.midia_url,
            p.created_at,
            u.id AS autor_id,
            u.nome AS autor_nome,
            u.avatar,
            u.bio,
            COALESCE(l.likes, 0) AS likes,
            CASE WHEN cl.usuario_id IS NOT NULL THEN 1 ELSE 0 END AS curtido,
            CASE WHEN s.seguidor_id IS NOT NULL THEN 1 ELSE 0 END AS segue
        FROM posts p
        JOIN usuarios u ON u.id = p.usuario_id
        LEFT JOIN (
            SELECT post_id, COUNT(*) AS likes
            FROM post_likes
            GROUP BY post_id
        ) l ON l.post_id = p.id
        LEFT JOIN post_likes cl
            ON cl.post_id = p.id
            AND cl.usuario_id = ?
        LEFT JOIN seguidores s
            ON s.seguidor_id = ?
            AND s.seguido_id = u.id
        WHERE
            (
                p.usuario_id IN (
                    SELECT seguido_id FROM seguidores WHERE seguidor_id = ?
                    UNION
                    SELECT seguidor_id FROM seguidores WHERE seguido_id = ?
                )
                OR p.usuario_id = ?
            )
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([
        $profissional_id,
        $profissional_id,
        $profissional_id,
        $profissional_id,
        $profissional_id
    ]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($posts);
    exit;
}

// Carregamento inicial
$stmt = $pdo->prepare("
    SELECT
        p.id, p.tipo, p.legenda, p.midia_url, p.created_at,
        u.id AS autor_id, u.nome AS autor_nome, u.avatar, u.bio,
        COALESCE(l.likes, 0) AS likes,
        CASE WHEN cl.usuario_id IS NOT NULL THEN 1 ELSE 0 END AS curtido,
        CASE WHEN s.seguidor_id IS NOT NULL THEN 1 ELSE 0 END AS segue
    FROM posts p
    JOIN usuarios u ON u.id = p.usuario_id
    LEFT JOIN (
        SELECT post_id, COUNT(*) AS likes FROM post_likes GROUP BY post_id
    ) l ON l.post_id = p.id
    LEFT JOIN post_likes cl ON cl.post_id = p.id AND cl.usuario_id = ?
    LEFT JOIN seguidores s ON s.seguidor_id = ? AND s.seguido_id = u.id
    WHERE p.usuario_id IN (
        SELECT seguido_id FROM seguidores WHERE seguidor_id = ?
        UNION SELECT seguidor_id FROM seguidores WHERE seguido_id = ?
    ) OR p.usuario_id = ?
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$profissional_id, $profissional_id, $profissional_id, $profissional_id, $profissional_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .card { border: none; border-radius: 18px; overflow: hidden; margin-bottom: 1.5rem; transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important; }
        .card-header { padding: 1rem; border-bottom: none; }
        .post-img, .post-video { width: 100%; max-height: 500px; object-fit: cover; border-radius: 12px; }
        .bio-text { font-size: 0.9rem; color: #6c757d; margin-top: 0.5rem; line-height: 1.4; }
        .like-btn { cursor: pointer; transition: all 0.2s; }
        .like-btn:hover { transform: scale(1.1); }
        .like-btn.liked { color: var(--bs-danger); }
        .navbar { position: sticky; top: 0; z-index: 1000; }
        .loading { text-align: center; padding: 2rem; color: #6c757d; }
        .feed-container { max-width: 700px; }
        .post-form { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .midia-preview { max-height: 200px; object-fit: cover; border-radius: 12px; }
        <?php echo gerar_css_cores($cores); ?>
    </style>
</head>
<body class="bg-light">
<?php
include('../includes/header.php');
?>
 <!--NAVBAR -->
<!--<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">-->
<!--    <div class="container-fluid">-->
<!--        <a class="navbar-brand fw-bold" href="dashboard.php">-->
<!--            <i class="fas fa-cut me-2"></i>Barbearia System-->
<!--        </a>-->
<!--        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">-->
<!--            <span class="navbar-toggler-icon"></span>-->
<!--        </button>-->
<!--        <div class="collapse navbar-collapse" id="navbarNav">-->
<!--            <ul class="navbar-nav ms-auto align-items-center">-->
<!--                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>-->
<!--                <li class="nav-item"><a class="nav-link" href="view_agenda_dia.php">Agenda</a></li>-->
<!--                <li class="nav-item"><a class="nav-link active" href="feed_profissional.php">Feed</a></li>-->
<!--                <li class="nav-item dropdown">-->
<!--                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">-->
<!--                        <img src="../assets/img/avatars/<?php echo $_SESSION['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="32" height="32" alt="">-->
<!--                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>-->
<!--                    </a>-->
<!--                    <ul class="dropdown-menu dropdown-menu-end shadow">-->
<!--                        <li><a class="dropdown-item" href="perfil.php">Perfil</a></li>-->
<!--                        <li><hr class="dropdown-divider"></li>-->
<!--                        <li><a class="dropdown-item text-danger" href="../logout.php">Sair</a></li>-->
<!--                    </ul>-->
<!--                </li>-->
<!--            </ul>-->
<!--        </div>-->
<!--    </div>-->
<!--</nav>-->

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 feed-container">

            <!-- FORMULÁRIO DE POST -->
            <div class="post-form mb-4">
                <?php if (isset($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($erro); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if (isset($sucesso)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($sucesso); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                    <div class="mb-3">
                        <textarea name="texto" class="form-control" rows="3" placeholder="No que você está pensando?" required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select name="midia_tipo" id="midia_tipo" class="form-select">
                                <option value="nenhum">Sem mídia</option>
                                <option value="imagem">Imagem</option>
                                <option value="video">Vídeo</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="file" name="midia" id="midia_input" class="form-control d-none" accept="image/*,video/*">
                            <button type="button" class="btn btn-outline-secondary w-100" id="midia_btn">Escolher arquivo</button>
                            <div id="midia_nome" class="form-text mt-1"></div>
                        </div>
                    </div>
                    <div id="midia_preview" class="text-center mb-3 d-none"></div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success px-4">Publicar</button>
                    </div>
                </form>
            </div>

            <!-- HEADER DO FEED -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white text-center">
                    <h4 class="mb-0">Feed de Inspiração</h4>
                    <small>Postagens recentes de quem você segue</small>
                </div>
            </div>

            <!-- POSTAGENS -->
            <div id="feed-posts">
                <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhuma postagem ainda</h5>
                    <p class="text-muted">Siga outros profissionais para ver o feed!</p>
                </div>
                <?php else: ?>
                    <?php foreach ($posts as $p): ?>
                    <div class="card shadow-sm post-card" data-id="<?php echo $p['id']; ?>">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="../assets/img/avatars/<?php echo htmlspecialchars($p['avatar'] ?? 'default.png'); ?>" 
                                     class="rounded-circle me-3" width="48" height="48" alt="">
                                <div>
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($p['autor_nome']); ?></h6>
                                    <small class="text-muted"><?php echo formatar_data($p['created_at']); ?></small>
                                </div>
                            </div>
                            <?php if ($p['autor_id'] != $profissional_id): ?>
                            <button class="btn follow-btn <?php echo $p['segue'] ? 'btn-outline-danger' : 'btn-outline-primary'; ?> btn-sm"
                                    data-id="<?php echo $p['autor_id']; ?>">
                                <?php echo $p['segue'] ? 'Seguindo' : 'Seguir'; ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($p['bio']): ?>
                            <p class="bio-text"><em>"<?php echo htmlspecialchars($p['bio']); ?>"</em></p>
                            <hr class="my-2">
                            <?php endif; ?>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($p['legenda'] ?? '')); ?></p>
                            <?php if (($p['tipo'] ?? '') == 'foto' && !empty($p['midia_url'])): ?>
                            <img src="../assets/img/posts/<?php echo htmlspecialchars($p['midia_url']); ?>" class="post-img img-fluid" alt="Post">
                            <?php elseif (($p['tipo'] ?? '') == 'video' && !empty($p['midia_url'])): ?>
                            <video src="../assets/img/posts/<?php echo htmlspecialchars($p['midia_url']); ?>" class="post-video" controls></video>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-heart like-btn <?php echo $p['curtido'] ? 'liked' : ''; ?>" 
                                       data-id="<?php echo $p['id']; ?>" 
                                       style="font-size: 1.4rem; color: <?php echo $p['curtido'] ? 'var(--bs-danger)' : '#6c757d'; ?>;"></i>
                                    <span class="ms-2 fw-medium like-count"><?php echo $p['likes']; ?></span>
                                </div>
                                <small class="text-muted">Toque no coração para curtir</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- LOADING -->
            <div id="loading" class="loading d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    'use strict';

    const feed = document.getElementById('feed-posts');
    const loading = document.getElementById('loading');
    const midiaTipo = document.getElementById('midia_tipo');
    const midiaInput = document.getElementById('midia_input');
    const midiaBtn = document.getElementById('midia_btn');
    const midiaNome = document.getElementById('midia_nome');
    const midiaPreview = document.getElementById('midia_preview');
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    let carregando = false;

    // === FORMULÁRIO DE POST ===
    midiaBtn.onclick = () => midiaInput.click();
    midiaInput.onchange = () => {
        const file = midiaInput.files[0];
        if (file) {
            midiaNome.textContent = file.name;
            const reader = new FileReader();
            reader.onload = e => {
                midiaPreview.innerHTML = '';
                const tag = midiaTipo.value === 'video' ? 'video' : 'img';
                const el = document.createElement(tag);
                el.src = e.target.result;
                el.className = 'midia-preview img-fluid';
                if (tag === 'video') el.controls = true;
                midiaPreview.appendChild(el);
                midiaPreview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    };
    midiaTipo.onchange = () => {
        midiaInput.value = '';
        midiaNome.textContent = '';
        midiaPreview.classList.add('d-none');
        midiaPreview.innerHTML = '';
        const accept = midiaTipo.value === 'video' ? 'video/*' : 'image/*';
        midiaInput.accept = accept;
    };

    // === SEGUIR/DEIXAR DE SEGUIR ===
    const seguir = async (btn) => {
        const id = btn.dataset.id;
        const seguir = !btn.classList.contains('btn-outline-danger');
        const form = new FormData();
        form.append('acao_seguir', seguir ? 'seguir' : 'deixar');
        form.append('alvo_id', id);
        form.append('csrf_token', csrfToken);

        try {
            const res = await fetch('', { method: 'POST', body: form });
            const data = await res.json();
            if (data.sucesso) {
                btn.classList.toggle('btn-outline-primary', seguir);
                btn.classList.toggle('btn-outline-danger', !seguir);
                btn.textContent = seguir ? 'Seguindo' : 'Seguir';
            }
        } catch (err) { console.error(err); }
    };

    // === CURTIR POST ===
    const curtirPost = async (btn) => {
        const postId = btn.dataset.id;
        const count = btn.nextElementSibling;
        const isLiked = btn.classList.contains('liked');

        const form = new FormData();
        form.append('acao_curtir', '1');
        form.append('post_id', postId);
        form.append('csrf_token', csrfToken);

        try {
            const res = await fetch('', { method: 'POST', body: form });
            const data = await res.json();
            if (data.sucesso) {
                btn.classList.toggle('liked', data.curtiu);
                btn.style.color = data.curtiu ? 'var(--bs-danger)' : '#6c757d';
                count.textContent = data.likes;
            }
        } catch (err) { console.error(err); }
    };

    // === ATUALIZAR FEED ===
    const atualizarFeed = async () => {
        if (carregando) return;
        carregando = true;
        loading.classList.remove('d-none');

        try {
            const res = await fetch('feed_profissional.php?atualizar=1');
            const posts = await res.json();

            feed.innerHTML = '';
            if (posts.length === 0) {
                feed.innerHTML = `<div class="text-center py-5"><i class="fas fa-images fa-3x text-muted mb-3"></i><h5 class="text-muted">Nenhuma postagem ainda</h5><p class="text-muted">Siga outros profissionais!</p></div>`;
            } else {
                posts.forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'card shadow-sm post-card';
                    card.dataset.id = p.id;

                    const bioHtml = p.bio ? `<p class="bio-text"><em>"${p.bio}"</em></p><hr class="my-2">` : '';
                    const midiaHtml = (p.tipo === 'foto' && p.midia_url) ? `<img src="../assets/img/posts/${p.midia_url}" class="post-img img-fluid" alt="Post">` :
                                     (p.tipo === 'video' && p.midia_url) ? `<video src="../assets/img/posts/${p.midia_url}" class="post-video" controls></video>` : '';
                    const curtidoClass = p.curtido ? 'liked' : '';
                    const corCurtido = p.curtido ? 'var(--bs-danger)' : '#6c757d';
                    const followBtn = p.autor_id != <?php echo $profissional_id; ?> ? `
                        <button class="btn follow-btn ${p.segue ? 'btn-outline-danger' : 'btn-outline-primary'} btn-sm" data-id="${p.autor_id}">
                            ${p.segue ? 'Seguindo' : 'Seguir'}
                        </button>` : '';

                    card.innerHTML = `
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="../assets/img/avatars/${p.avatar || 'default.png'}" class="rounded-circle me-3" width="48" height="48" alt="">
                                <div>
                                    <h6 class="mb-0 fw-bold">${p.autor_nome}</h6>
                                    <small class="text-muted">${formatarData(p.created_at)}</small>
                                </div>
                            </div>
                            ${followBtn}
                        </div>
                        <div class="card-body">
                            ${bioHtml}
                            <p class="card-text">${p.legenda.replace(/\n/g, '<br>')}</p>
                            ${midiaHtml}
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-heart like-btn ${curtidoClass}" data-id="${p.id}" style="font-size: 1.4rem;
                                     color: ${corCurtido};"></i>
                                                                        <span class="ms-2 fw-medium like-count">${p.likes}</span>
                                                                    </div>
                                                                    <small class="text-muted">Toque no coração para curtir</small>
                                                                </div>
                                                            </div>
                                                        `;
                                                        feed.appendChild(card);
                                                    });
                                                }

                                                // Reattach event listeners
                                                document.querySelectorAll('.like-btn').forEach(btn => {
                                                    btn.onclick = () => curtirPost(btn);
                                                });
                                                document.querySelectorAll('.follow-btn').forEach(btn => {
                                                    btn.onclick = () => seguir(btn);
                                                });
                                            } catch (err) {
                                                console.error('Erro ao atualizar feed:', err);
                                            } finally {
                                                carregando = false;
                                                loading.classList.add('d-none');
                                            }
                                        };

                                        // === FORMATAÇÃO DE DATA ===
                                        const formatarData = (data) => {
                                            const d = new Date(data);
                                            const agora = new Date();
                                            const diff = Math.floor((agora - d) / 1000);
                                            if (diff < 60) return 'Agora mesmo';
                                            if (diff < 3600) return Math.floor(diff / 60) + 'm atrás';
                                            if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
                                            if (diff < 604800) return Math.floor(diff / 86400) + 'd atrás';
                                            return d.toLocaleDateString('pt-BR');
                                        };

                                        // === EVENT LISTENERS ===
                                        document.querySelectorAll('.like-btn').forEach(btn => {
                                            btn.onclick = () => curtirPost(btn);
                                        });
                                        document.querySelectorAll('.follow-btn').forEach(btn => {
                                            btn.onclick = () => seguir(btn);
                                        });

                                        // Auto-atualizar feed a cada 30 segundos
                                        setInterval(atualizarFeed, 30000);
                                    })();
                                    </script>
                                    </body>
                                    </html>