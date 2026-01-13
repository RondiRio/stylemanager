<?php
// cliente/perfil.php (EDIÇÃO DE PERFIL DO CLIENTE)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Meu Perfil";
requer_login('cliente');
include '../includes/header.php';

// === DADOS DO CLIENTE ===
$stmt = $pdo->prepare("SELECT nome, email, telefone, avatar FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    redirecionar_com_mensagem('dashboard.php', 'Usuário não encontrado.', 'danger');
}

// === PROCESSAR EDIÇÃO ===
if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $remover_avatar = !empty($_POST['remover_avatar']);

    // Validações
    if (!$nome) {
        $erro = 'Nome é obrigatório.';
    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif ($telefone && strlen($telefone) < 10) {
        $erro = 'Telefone incompleto.';
    }

    if (!isset($erro)) {
        // === AVATAR ===
        $novo_avatar = $usuario['avatar'];
        if ($remover_avatar) {
            if ($novo_avatar && $novo_avatar !== 'default.png') {
                @unlink(__DIR__ . '/../assets/img/avatars/' . $novo_avatar);
            }
            $novo_avatar = 'default.png';
        } elseif (!empty($_FILES['avatar']['name'])) {
            $extensoes = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $extensoes)) {
                $erro = 'Formato de avatar inválido.';
            } else {
                $pasta = __DIR__ . '/../assets/img/avatars/';
                if (!is_dir($pasta)) mkdir($pasta, 0755, true);
                $novo_nome = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $pasta . $novo_nome)) {
                    if ($novo_avatar && $novo_avatar !== 'default.png') {
                        @unlink($pasta . $novo_avatar);
                    }
                    $novo_avatar = $novo_nome;
                } else {
                    $erro = 'Erro ao fazer upload do avatar.';
                }
            }
        }

        if (!isset($erro)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$nome, $email ?: null, $telefone ?: null, $novo_avatar, $_SESSION['usuario_id']]);
            $_SESSION['nome'] = $nome;
            $_SESSION['avatar'] = $novo_avatar;
            redirecionar_com_mensagem('perfil.php', 'Perfil atualizado com sucesso!', 'success');
        }
    }
}
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Editar Perfil</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($erro)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">

                        <!-- AVATAR -->
                        <div class="text-center mb-4">
                            <img src="../assets/img/avatars/<?php echo $usuario['avatar'] ?? 'default.png'; ?>" 
                                 class="rounded-circle img-thumbnail" width="150" alt="Avatar" id="previewAvatar">
                            <div class="mt-3">
                                <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted">JPG, PNG, GIF - Máx. 2MB</small>
                            </div>
                            <?php if ($usuario['avatar'] && $usuario['avatar'] !== 'default.png'): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remover_avatar" id="remover">
                                <label class="form-check-label text-danger" for="remover">Remover foto atual</label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <!-- DADOS -->
                        <div class="mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" placeholder="Opcional">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefone (WhatsApp)</label>
                            <input type="text" name="telefone" class="form-control" value="<?php echo formatar_telefone($usuario['telefone']); ?>" 
                                   placeholder="(99) 99999-9999" maxlength="15" onkeyup="mascaraTelefone(this)">
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview da imagem
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('previewAvatar').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

// Máscara de telefone
function mascaraTelefone(input) {
    let v = input.value.replace(/\D/g, '');
    v = v.replace(/^(\d{2})(\d)/g, '($1) $2');
    v = v.replace(/(\d)(\d{4})$/, '$1-$2');
    input.value = v.substring(0, 15);
}
</script>

<?php include '../includes/footer.php'; ?>