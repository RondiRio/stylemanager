<?php
// admin/feed.php (FEED PARA ADMINISTRADOR)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Feed Social";
requer_login('admin');
include '../includes/header.php';

// === POSTS DO FEED (TODOS OS POSTS) ===
$stmt = $pdo->prepare("
    SELECT
        p.id, p.usuario_id, p.tipo, p.midia_url, p.legenda, p.created_at,
        u.nome AS autor_nome, u.avatar AS autor_avatar, u.tipo AS autor_tipo,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS likes,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND usuario_id = ?) AS curtiu,
        EXISTS(SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = p.usuario_id) AS segue
    FROM posts p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.usuario_id IN (SELECT seguido_id FROM seguidores WHERE seguidor_id = ?)
       OR p.usuario_id = ?
    ORDER BY p.created_at DESC
    LIMIT 30
");
$stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$posts = $stmt->fetchAll();

// === PROFISSIONAIS ===
$stmt = $pdo->prepare("SELECT id, nome, avatar FROM usuarios WHERE tipo = 'profissional' AND ativo = 1 ORDER BY nome LIMIT 5");
$stmt->execute();
$profs = $stmt->fetchAll();

// === ADMIN ATIVO ===
$stmt = $pdo->prepare("SELECT id, nome, avatar FROM usuarios WHERE id = ? AND tipo = 'admin' LIMIT 1");
$stmt->execute([$_SESSION['usuario_id']]);
$admin_user = $stmt->fetch();

// === BUSCAR CORES DA CONFIGURAÇÃO ===
$stmt = $pdo->query("SELECT cor_primaria, cor_secundaria, cor_fundo FROM configuracoes WHERE id = 1");
$config_cores = $stmt->fetch();
$cor_primaria = $config_cores['cor_primaria'] ?? '#667eea';
$cor_secundaria = $config_cores['cor_secundaria'] ?? '#764ba2';
$cor_fundo = $config_cores['cor_fundo'] ?? '#f5f7fa';

// Criar gradiente baseado nas cores configuradas
$feed_gradient_primary = "linear-gradient(135deg, {$cor_primaria} 0%, {$cor_secundaria} 100%)";
?>
<style>
/* Feed Moderno - Estilos Customizados com Cores Dinâmicas */
:root {
    --feed-gradient-primary: <?php echo $feed_gradient_primary; ?>;
    --feed-gradient-success: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --feed-card-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    --feed-card-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.15);
    --cor-primaria: <?php echo $cor_primaria; ?>;
    --cor-secundaria: <?php echo $cor_secundaria; ?>;
}

body {
    background: <?php echo $cor_fundo; ?>;
    min-height: 100vh;
}

.feed-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--feed-card-shadow);
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.feed-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--feed-card-shadow-hover);
}

.profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: var(--feed-card-shadow);
    overflow: hidden;
    border: none;
}

.profile-card-header {
    background: var(--feed-gradient-primary);
    padding: 2rem 1rem 3rem;
    position: relative;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-top: -30px;
}

.sidebar-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--feed-card-shadow);
    border: none;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.sidebar-card-header {
    background: var(--feed-gradient-primary);
    color: white;
    padding: 1rem;
    font-weight: 600;
}

.post-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--feed-card-shadow);
    border: none;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.post-card:hover {
    box-shadow: var(--feed-card-shadow-hover);
}

.post-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.post-author-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.post-media {
    max-height: 500px;
    border-radius: 12px;
    object-fit: cover;
    width: 100%;
    margin-bottom: 1rem;
}

.btn-action {
    border-radius: 20px;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: scale(1.05);
}

.btn-follow {
    background: var(--feed-gradient-primary);
    border: none;
    color: white;
    border-radius: 20px;
    padding: 0.4rem 1.2rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-follow:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-following {
    background: #e9ecef;
    color: #495057;
    border: none;
    border-radius: 20px;
    padding: 0.4rem 1.2rem;
    font-size: 0.875rem;
}

.create-post-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--feed-card-shadow);
    border: none;
    margin-bottom: 1.5rem;
}

.create-post-textarea {
    border: none;
    resize: none;
    font-size: 1rem;
}

.create-post-textarea:focus {
    outline: none;
    box-shadow: none;
}

.stat-item {
    text-align: center;
    padding: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
    display: block;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.user-list-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    transition: background 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.user-list-item:hover {
    background: #f8f9fa;
}

.user-list-item:last-child {
    border-bottom: none;
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsividade Mobile */
@media (max-width: 768px) {
    .profile-avatar {
        width: 80px;
        height: 80px;
    }

    .post-card {
        border-radius: 12px;
    }

    .sidebar-card {
        border-radius: 12px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- SIDEBAR ESQUERDA -->
        <div class="col-md-3">
            <div class="profile-card mb-3 fade-in">
                <div class="profile-card-header text-center">
                    <h6 class="text-white mb-0">Meu Perfil</h6>
                </div>
                <div class="card-body text-center pt-0">
                    <img src="../assets/img/avatars/<?php echo $admin_user['avatar'] ?? 'default.png'; ?>" class="rounded-circle profile-avatar" alt="Avatar">
                    <h5 class="mt-2 mb-1"><?php echo htmlspecialchars($_SESSION['nome']); ?></h5>
                    <p class="text-muted small">@<?php echo strtolower(str_replace(' ', '', $_SESSION['nome'])); ?></p>
                    <a href="perfil.php" class="btn btn-sm btn-action btn-outline-primary w-100 mt-2">
                        <i class="fas fa-user me-1"></i>Ver Perfil
                    </a>
                </div>
            </div>

            <div class="sidebar-card fade-in">
                <div class="sidebar-card-header">
                    <i class="fas fa-tachometer-alt me-2"></i>Painel Admin
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Acesse o dashboard administrativo</p>
                    <a href="dashboard.php" class="btn btn-primary btn-action w-100">
                        <i class="fas fa-chart-line me-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <div class="sidebar-card fade-in">
                <div class="sidebar-card-header">
                    <i class="fas fa-calendar-alt me-2"></i>Agenda Geral
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Visualize todos os agendamentos</p>
                    <a href="view_agenda_geral.php" class="btn btn-action btn-outline-secondary w-100">
                        <i class="fas fa-calendar me-2"></i>Ver Agenda
                    </a>
                </div>
            </div>
        </div>

        <!-- FEED CENTRAL -->
        <div class="col-md-6">
            <!-- POSTAR FOTO/VÍDEO -->
            <div class="create-post-card fade-in">
                <div class="card-body p-4">
                    <form id="formPostar" action="../handlers/handle_postar.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <div class="d-flex align-items-start gap-3">
                            <img src="../assets/img/avatars/<?php echo $admin_user['avatar'] ?? 'default.png'; ?>" class="rounded-circle post-author-avatar" alt="Avatar">
                            <div class="flex-grow-1">
                                <textarea name="legenda" class="form-control create-post-textarea mb-3" rows="3" placeholder="Compartilhe seu novo visual ou experiência..."></textarea>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex gap-2">
                                        <label class="btn btn-outline-secondary btn-sm btn-action">
                                            <i class="fas fa-image me-1"></i> Foto
                                            <input type="file" name="imagens[]" class="d-none" accept="image/*" multiple onchange="previewMidia(this, 'foto')">
                                        </label>
                                        <label class="btn btn-outline-danger btn-sm btn-action">
                                            <i class="fas fa-video me-1"></i> Vídeo
                                            <input type="file" name="video" class="d-none" accept="video/*" onchange="previewMidia(this, 'video')">
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-action">
                                        <i class="fas fa-paper-plane me-2"></i>Publicar
                                    </button>
                                </div>
                                <div id="previewContainer" class="mt-3"></div>
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="publico" id="publico" checked>
                            <label class="form-check-label text-muted small" for="publico">
                                <i class="fas fa-globe me-1"></i>Publicar para todos
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            <!-- POSTS -->

            <?php
            // echo "<pre>";
            // print_r($posts);
            // echo '</pre>';
            if (empty($posts)): ?>
            <div class="post-card text-center p-5 fade-in">
                <div class="py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum post no feed ainda</h5>
                    <p class="text-muted">Comece seguindo profissionais ou compartilhando seu primeiro post!</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <div class="post-card fade-in post-item" data-post-id="<?php echo $post['id']; ?>">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div class="post-author">
                            <img src="../assets/img/avatars/<?php echo $post['autor_avatar'] ?? 'default.png'; ?>" class="post-author-avatar" alt="Avatar">
                            <div>
                                <strong class="d-block"><?php echo htmlspecialchars($post['autor_nome']); ?></strong>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo formatar_data($post['created_at']); ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($post['usuario_id'] == $_SESSION['usuario_id']): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item text-primary" href="#" onclick="editarPost(<?php echo $post['id']; ?>)"><i class="fas fa-edit"></i> Editar</a></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="apagarPost(<?php echo $post['id']; ?>)"><i class="fas fa-trash"></i> Apagar</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($post['tipo'] == 'video' && $post['midia_url']): ?>
                    <video controls class="post-media" style="max-height: 500px;">
                        <source src="../assets/img/feed/<?php echo $post['midia_url']; ?>" type="video/mp4">
                        Seu navegador não suporta vídeo.
                    </video>
                    <?php elseif ($post['tipo'] == 'foto' && $post['midia_url']): ?>
                    <img src="../assets/img/feed/<?php echo $post['midia_url']; ?>" class="post-media" alt="Post">
                    <?php endif; ?>

                    <?php if ($post['legenda']): ?>
                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($post['legenda'])); ?></p>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <button class="btn btn-sm <?php echo $post['curtiu'] ? 'btn-danger' : 'btn-outline-danger'; ?> btn-action btn-like" data-post="<?php echo $post['id']; ?>">
                            <i class="fas fa-heart me-1"></i>
                            <span><?php echo $post['likes']; ?> Curtida<?php echo $post['likes'] != 1 ? 's' : ''; ?></span>
                        </button>
                        <?php if ($post['usuario_id'] != $_SESSION['usuario_id'] && !$post['segue']): ?>
                        <button class="btn btn-sm btn-follow btn-seguir" data-usuario="<?php echo $post['usuario_id']; ?>">
                            <i class="fas fa-user-plus me-1"></i>Seguir
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR DIREITA (REMODELADA) -->
        <div class="col-md-3">
            <!-- Profissionais -->
            <div class="sidebar-card fade-in">
                <div class="sidebar-card-header">
                    <i class="fas fa-cut me-2"></i>Profissionais
                </div>
                <div class="card-body p-0">
                    <?php if (empty($profs)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                        <small>Nenhum profissional disponível</small>
                    </div>
                    <?php else: ?>
                    <div>
                        <?php foreach ($profs as $p):
                            $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
                            $stmt->execute([$_SESSION['usuario_id'], $p['id']]);
                            $ja_segue = $stmt->rowCount() > 0;
                        ?>
                        <div class="user-list-item">
                            <img src="../assets/img/avatars/<?php echo $p['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="44" height="44" alt="">
                            <div class="flex-grow-1">
                                <strong class="d-block"><?php echo htmlspecialchars($p['nome']); ?></strong>
                                <small class="text-muted">Profissional</small>
                            </div>
                            <button class="btn btn-sm <?php echo $ja_segue ? 'btn-following' : 'btn-follow'; ?> btn-seguir" data-usuario="<?php echo $p['id']; ?>">
                                <?php echo $ja_segue ? '<i class="fas fa-check me-1"></i>Seguindo' : '<i class="fas fa-user-plus me-1"></i>Seguir'; ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sugestões -->
            <div class="sidebar-card fade-in">
                <div class="sidebar-card-header">
                    <i class="fas fa-users me-2"></i>Sugestões para Você
                </div>
                <div class="card-body p-0">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT u.id, u.nome, u.avatar
                        FROM usuarios u
                        WHERE u.tipo = 'cliente' AND u.id != ?
                          AND u.id NOT IN (SELECT seguido_id FROM seguidores WHERE seguidor_id = ?)
                          AND u.ativo = 1
                        ORDER BY RAND() LIMIT 5
                    ");
                    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
                    $sugestoes = $stmt->fetchAll();
                    ?>
                    <?php if (empty($sugestoes)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                        <small>Você já segue todos!</small>
                    </div>
                    <?php else: ?>
                    <div>
                        <?php foreach ($sugestoes as $s): ?>
                        <div class="user-list-item">
                            <img src="../assets/img/avatars/<?php echo $s['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="44" height="44" alt="">
                            <div class="flex-grow-1">
                                <strong class="d-block"><?php echo htmlspecialchars($s['nome']); ?></strong>
                                <small class="text-muted">Cliente</small>
                            </div>
                            <button class="btn btn-sm btn-follow btn-seguir" data-usuario="<?php echo $s['id']; ?>">
                                <i class="fas fa-user-plus me-1"></i>Seguir
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="sidebar-card fade-in">
                <div class="sidebar-card-header">
                    <i class="fas fa-chart-line me-2"></i>Sua Atividade
                </div>
                <div class="card-body p-4">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE usuario_id = ?"); $stmt->execute([$_SESSION['usuario_id']]); $total_posts = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?"); $stmt->execute([$_SESSION['usuario_id']]); $seguindo = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?"); $stmt->execute([$_SESSION['usuario_id']]); $seguidores = $stmt->fetchColumn();
                    ?>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $total_posts; ?></span>
                                <span class="stat-label">Posts</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $seguindo; ?></span>
                                <span class="stat-label">Seguindo</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $seguidores; ?></span>
                                <span class="stat-label">Seguidores</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Post -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditar">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="post_id" id="editPostId">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                    <textarea name="legenda" id="editLegenda" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview de mídia
function previewMidia(input, tipo) {
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';
    if (tipo === 'foto' && input.files) {
        Array.from(input.files).slice(0, 4).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'd-inline-block position-relative me-2 mb-2';
                div.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">`;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    } else if (tipo === 'video' && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'position-relative';
            div.innerHTML = `<video controls class="img-thumbnail" style="width: 200px; height: 150px;"><source src="${e.target.result}" type="video/mp4"></video>`;
            container.appendChild(div);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Curtir
document.querySelectorAll('.btn-like').forEach(btn => {
    btn.addEventListener('click', () => {
        const postId = btn.dataset.post;
        fetch('../handlers/handle_like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, csrf_token: '<?php echo gerar_csrf_token(); ?>' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.classList.toggle('btn-danger');
                btn.classList.toggle('btn-outline-danger');
                const span = btn.querySelector('span');
                span.textContent = data.likes + (data.likes != 1 ? ' Curtidas' : ' Curtida');
            }
        });
    });
});

// Seguir
document.querySelectorAll('.btn-seguir').forEach(btn => {
    btn.addEventListener('click', () => {
        const usuarioId = btn.dataset.usuario;
        fetch('../handlers/handle_seguir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ seguido_id: usuarioId, csrf_token: '<?php echo gerar_csrf_token(); ?>' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Atualizar texto e ícone do botão
                if (data.seguindo) {
                    btn.innerHTML = '<i class="fas fa-check me-1"></i>Seguindo';
                    btn.classList.remove('btn-follow');
                    btn.classList.add('btn-following');
                } else {
                    btn.innerHTML = '<i class="fas fa-user-plus me-1"></i>Seguir';
                    btn.classList.remove('btn-following');
                    btn.classList.add('btn-follow');
                }
            }
        });
    });
});

// Editar Post
function editarPost(postId) {
    const post = document.querySelector(`.post-item[data-post-id="${postId}"]`);
    const legenda = post.querySelector('p').textContent.trim();
    document.getElementById('editPostId').value = postId;
    document.getElementById('editLegenda').value = legenda;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

document.getElementById('formEditar').addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('handle_editar_post.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const post = document.querySelector(`.post-item[data-post-id="${data.post_id}"] p`);
            post.textContent = data.legenda;
            bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
        }
    });
});

// Apagar Post
function apagarPost(postId) {
    if (!confirm('Tem certeza que deseja apagar este post?')) return;
    fetch('handle_apagar_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, csrf_token: '<?php echo gerar_csrf_token(); ?>' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`.post-item[data-post-id="${postId}"]`).remove();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>