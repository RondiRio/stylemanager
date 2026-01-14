<?php
// profissional/configuracoes.php - Configurações do Perfil do Profissional
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Minhas Configurações";
requer_login('profissional');

$profissional_id = $_SESSION['usuario_id'];

// Processar formulário
if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $pdo->beginTransaction();
    try {
        // Validações
        if (empty($nome)) {
            throw new Exception("Nome é obrigatório");
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("E-mail inválido");
        }

        // Upload de avatar
        if (!empty($_FILES['avatar']['name'])) {
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $extensoes_permitidas)) {
                throw new Exception("Formato de imagem não permitido. Use JPG, PNG ou GIF.");
            }

            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Imagem muito grande. Máximo 2MB.");
            }

            $nome_arquivo = 'user_' . $profissional_id . '_' . time() . '.' . $ext;
            $caminho_destino = '../assets/img/avatars/' . $nome_arquivo;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $caminho_destino)) {
                // Deletar avatar antigo se existir
                $avatar_antigo = $pdo->prepare("SELECT avatar FROM usuarios WHERE id = ?");
                $avatar_antigo->execute([$profissional_id]);
                $old_avatar = $avatar_antigo->fetchColumn();

                if ($old_avatar && $old_avatar !== 'default.png' && file_exists('../assets/img/avatars/' . $old_avatar)) {
                    @unlink('../assets/img/avatars/' . $old_avatar);
                }

                // Atualizar no banco
                $stmt = $pdo->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
                $stmt->execute([$nome_arquivo, $profissional_id]);

                $_SESSION['avatar'] = $nome_arquivo;
            }
        }

        // Atualizar dados do profissional
        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET nome = ?, email = ?, telefone = ?, bio = ?
            WHERE id = ?
        ");
        $stmt->execute([$nome, $email, $telefone, $bio, $profissional_id]);

        $_SESSION['nome'] = $nome;

        $pdo->commit();
        redirecionar_com_mensagem('configuracoes.php', 'Perfil atualizado com sucesso!', 'success');

    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$profissional_id]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="animate-fade-in">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card-glass">
                <div class="card-glass-header" style="background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria));">
                    <h4 class="mb-0">
                        <i class="fas fa-user-cog me-2"></i>
                        Minhas Configurações
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">

                        <!-- Avatar -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="../assets/img/avatars/<?php echo $dados['avatar'] ?? 'default.png'; ?>"
                                     alt="Avatar"
                                     class="avatar-salao mb-3"
                                     id="avatar-preview"
                                     style="width: 150px; height: 150px; border: 4px solid var(--cor-primaria);">
                                <label for="avatar" class="position-absolute bottom-0 end-0 btn btn-primary btn-sm rounded-circle" style="width: 40px; height: 40px; padding: 0;">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="avatar" id="avatar" class="d-none" accept="image/*">
                            </div>
                            <p class="text-muted small mb-0">Clique no ícone para alterar sua foto</p>
                            <small class="text-muted">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</small>
                        </div>

                        <div class="row g-3">
                            <!-- Nome -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user text-primary me-1"></i>
                                    Nome Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="nome"
                                       class="input-salao"
                                       value="<?php echo htmlspecialchars($dados['nome']); ?>"
                                       required>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-envelope text-primary me-1"></i>
                                    E-mail
                                </label>
                                <input type="email"
                                       name="email"
                                       class="input-salao"
                                       value="<?php echo htmlspecialchars($dados['email'] ?? ''); ?>">
                            </div>

                            <!-- Telefone -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-phone text-primary me-1"></i>
                                    Telefone
                                </label>
                                <input type="text"
                                       name="telefone"
                                       class="input-salao telefone-mask"
                                       value="<?php echo htmlspecialchars($dados['telefone'] ?? ''); ?>"
                                       placeholder="(11) 99999-9999">
                            </div>

                            <!-- Bio -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-align-left text-primary me-1"></i>
                                    Biografia / Sobre Mim
                                </label>
                                <textarea name="bio"
                                          class="input-salao"
                                          rows="4"
                                          placeholder="Conte um pouco sobre você, sua experiência, especialidades..."><?php echo htmlspecialchars($dados['bio'] ?? ''); ?></textarea>
                                <small class="text-muted">Máximo 500 caracteres</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Botões -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn-salao">
                                <i class="fas fa-save me-1"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card de Segurança -->
            <div class="card-glass mt-4">
                <div class="card-glass-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Segurança
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Alterar Senha</h6>
                            <small class="text-muted">Mantenha sua conta segura alterando sua senha regularmente</small>
                        </div>
                        <a href="alterar_senha.php" class="btn btn-outline-danger">
                            <i class="fas fa-key me-1"></i>Alterar Senha
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de avatar
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Máscara de telefone
document.querySelectorAll('.telefone-mask').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);

        if (value.length > 10) {
            value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
        } else if (value.length > 6) {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        } else if (value.length > 2) {
            value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
        } else if (value.length > 0) {
            value = value.replace(/^(\d*)/, '($1');
        }

        e.target.value = value;
    });
});

// Validação Bootstrap
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
