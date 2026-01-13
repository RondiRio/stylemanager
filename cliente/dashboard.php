<?php
// cliente/dashboard.php (FEED PROFISSIONAL + EDIÇÃO + VÍDEOS)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Feed";
requer_login('cliente');
include '../includes/header.php';

// === CONFIGURAÇÃO DO SALÃO ===
$stmt = $pdo->prepare("SELECT agendamento_ativo FROM configuracoes WHERE id = 1");
$stmt->execute();
$config = $stmt->fetch();
$agendamento_ativo = $config['agendamento_ativo'] ?? 0;

// === POSTS DO FEED (COM VÍDEOS) ===
$stmt = $pdo->prepare("
    SELECT 
        p.id, p.usuario_id, p.imagem, p.video, p.legenda, p.criado_em,
        u.nome AS autor_nome, u.avatar AS autor_avatar,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS likes,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND usuario_id = ?) AS curtiu,
        EXISTS(SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = p.usuario_id) AS segue
    FROM posts p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.publico = 1 
       OR p.usuario_id IN (SELECT seguido_id FROM seguidores WHERE seguidor_id = ?)
       OR p.usuario_id = ?
    ORDER BY p.criado_em DESC
    LIMIT 20
");
$stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$posts = $stmt->fetchAll();

// === PROFISSIONAIS ===
$stmt = $pdo->prepare("SELECT id, nome, avatar FROM usuarios WHERE tipo = 'profissional' AND ativo = 1 ORDER BY nome LIMIT 5");
$stmt->execute();
$profs = $stmt->fetchAll();

// === CLIENTE ATIVO ===
$stmt = $pdo->prepare("SELECT id, nome, avatar FROM usuarios WHERE id = ? AND tipo = 'cliente' AND ativo = 1 LIMIT 1");
$stmt->execute([$_SESSION['usuario_id']]);
$clients = $stmt->fetch();
?>
<div class="container-fluid py-4">
    <div class="row">
        <!-- SIDEBAR ESQUERDA -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-body text-center">
                <?php 
                // echo '<pre>';
                // print_r($clients);
                // echo '</pre>';
                ?>
                <img src="../assets/img/avatars/<?php echo $clients['avatar'] ?? 'default.png'; ?>" class="rounded-circle mb-2" width="80" alt="Avatar">
                    <h5><?php echo htmlspecialchars($_SESSION['nome']); ?></h5>
                    <a href="perfil.php" class="btn btn-sm btn-outline-primary w-100">Meu Perfil</a>
                </div>
            </div>

            <?php if ($agendamento_ativo): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Agendar Serviço</h6>
                    <a href="agendar.php" class="btn btn-success w-100">Ir para Agendamento</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <h6>Meus Agendamentos</h6>
                    <a href="meus_agendamentos.php" class="btn btn-outline-secondary w-100">Ver</a>
                </div>
            </div>
        </div>

        <!-- FEED CENTRAL -->
        <div class="col-md-6">
            <!-- POSTAR FOTO/VÍDEO -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form id="formPostar" action="handle_postar.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <div class="d-flex align-items-start">
                            <img src="../assets/img/avatars/<?php echo $clients['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-3" width="45" alt="Avatar">
                            <div class="flex-grow-1">
                                <textarea name="legenda" class="form-control mb-3" rows="2" placeholder="Compartilhe seu novo visual..."></textarea>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <label class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-image"></i> Foto
                                            <input type="file" name="imagens[]" class="d-none" accept="image/*" multiple onchange="previewMidia(this, 'foto')">
                                        </label>
                                        <label class="btn btn-outline-danger btn-sm ms-2">
                                            <i class="fas fa-video"></i> Vídeo
                                            <input type="file" name="video" class="d-none" accept="video/*" onchange="previewMidia(this, 'video')">
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Postar</button>
                                </div>
                                <div id="previewContainer" class="mt-3"></div>
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="publico" id="publico" checked>
                            <label class="form-check-label" for="publico">Post público</label>
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
            <div class="card text-center p-5">
                <p class="text-muted">Nenhum post no feed ainda. Comece compartilhando!</p>
            </div>
            <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <div class="card mb-3 shadow-sm post-item" data-post-id="<?php echo $post['id']; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <img src="../assets/img/avatars/<?php echo $post['autor_avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="40" alt="Avatar">
                            <div>
                                <strong><?php echo htmlspecialchars($post['autor_nome']); ?></strong>
                                <small class="text-muted"><?php echo formatar_data($post['criado_em']); ?></small>
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

                    <?php if ($post['video']): ?>
                    <video controls class="img-fluid rounded mb-3" style="max-height: 500px;">
                        <source src="../assets/img/feed/<?php echo $post['video']; ?>" type="video/mp4">
                        Seu navegador não suporta vídeo.
                    </video>
                    <?php elseif ($post['imagem']): ?>
                    <img src="../assets/img/feed/<?php echo $post['imagem']; ?>" class="img-fluid rounded mb-3" alt="Post" style="max-height: 500px; object-fit: cover;">
                    <?php endif; ?>

                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($post['legenda'])); ?></p>

                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-sm <?php echo $post['curtiu'] ? 'btn-danger' : 'btn-outline-danger'; ?> btn-like" data-post="<?php echo $post['id']; ?>">
                            <i class="fas fa-heart"></i> <span><?php echo $post['likes']; ?></span>
                        </button>
                        <?php if ($post['usuario_id'] != $_SESSION['usuario_id'] && !$post['segue']): ?>
                        <button class="btn btn-sm btn-outline-primary btn-seguir" data-usuario="<?php echo $post['usuario_id']; ?>">
                            Seguir
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
            <div class="card mb-3">
                <div class="card-header bg-gradient-primary text-white">
                    <h6 class="mb-0">Profissionais do Salão</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($profs)): ?>
                    <div class="p-3 text-center text-muted"><small>Nenhum profissional.</small></div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($profs as $p): 
                            $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
                            $stmt->execute([$_SESSION['usuario_id'], $p['id']]);
                            $ja_segue = $stmt->rowCount() > 0;
                        ?>
                        <li class="list-group-item px-3 py-2 d-flex align-items-center">
                            <img src="../assets/img/avatars/<?php echo $p['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="36" height="36" alt="">
                            <div class="flex-grow-1">
                                <strong class="d-block text-truncate" style="max-width: 120px;"><?php echo htmlspecialchars($p['nome']); ?></strong>
                                <small class="text-muted">@<?php echo strtolower(str_replace(' ', '', $p['nome'])); ?></small>
                            </div>
                            <button class="btn btn-sm <?php echo $ja_segue ? 'btn-primary' : 'btn-outline-primary'; ?> btn-seguir" data-usuario="<?php echo $p['id']; ?>">
                                <?php echo $ja_segue ? 'Seguindo' : 'Seguir'; ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sugestões -->
            <div class="card mb-3">
                <div class="card-header bg-gradient-success text-white">
                    <h6 class="mb-0">Pessoas que você pode conhecer</h6>
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
                    <div class="p-3 text-center text-muted"><small>Todos conectados!</small></div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($sugestoes as $s): ?>
                        <li class="list-group-item px-3 py-2 d-flex align-items-center">
                            <img src="../assets/img/avatars/<?php echo $s['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="36" height="36" alt="">
                            <div class="flex-grow-1">
                                <strong class="d-block text-truncate" style="max-width: 120px;"><?php echo htmlspecialchars($s['nome']); ?></strong>
                                <small class="text-muted">Cliente</small>
                            </div>
                            <button class="btn btn-sm btn-outline-success btn-seguir" data-usuario="<?php echo $s['id']; ?>">Seguir</button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">Sua Atividade</h6>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE usuario_id = ?"); $stmt->execute([$_SESSION['usuario_id']]); $total_posts = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguidor_id = ?"); $stmt->execute([$_SESSION['usuario_id']]); $seguindo = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seguidores WHERE seguido_id = ?"); $stmt->execute([$_SESSION['usuario_id']]); $seguidores = $stmt->fetchColumn();
                    ?>
                    <div class="row text-center">
                        <div class="col-4"><h5 class="mb-0"><?php echo $total_posts; ?></h5><small class="text-muted">Posts</small></div>
                        <div class="col-4"><h5 class="mb-0"><?php echo $seguindo; ?></h5><small class="text-muted">Seguindo</small></div>
                        <div class="col-4"><h5 class="mb-0"><?php echo $seguidores; ?></h5><small class="text-muted">Seguidores</small></div>
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
        fetch('handle_like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, csrf_token: '<?php echo gerar_csrf_token(); ?>' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.classList.toggle('btn-danger');
                btn.classList.toggle('btn-outline-danger');
                btn.querySelector('span').textContent = data.likes;
            }
        });
    });
});

// Seguir
document.querySelectorAll('.btn-seguir').forEach(btn => {
    btn.addEventListener('click', () => {
        const usuarioId = btn.dataset.usuario;
        fetch('handle_seguir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ seguido_id: usuarioId, csrf_token: '<?php echo gerar_csrf_token(); ?>' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.textContent = data.seguindo ? 'Seguindo' : 'Seguir';
                btn.classList.toggle('btn-primary', data.seguindo);
                btn.classList.toggle('btn-outline-primary', !data.seguindo);
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