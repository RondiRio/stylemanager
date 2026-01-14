<?php
// alterar_senha.php - Alteração de Senha (para todos os tipos de usuário)
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/utils.php';

if (!esta_logado()) {
    redirecionar('index.php');
}

$titulo = "Alterar Senha";
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo'];

// Definir pasta base dependendo do tipo de usuário
$pasta_base = $tipo_usuario === 'cliente' ? 'cliente' :
             ($tipo_usuario === 'profissional' ? 'profissional' : 'admin');

// Processar formulário
if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_confirma = $_POST['senha_confirma'] ?? '';

    try {
        // Validações
        if (empty($senha_atual) || empty($senha_nova) || empty($senha_confirma)) {
            throw new Exception("Todos os campos são obrigatórios");
        }

        if (strlen($senha_nova) < 6) {
            throw new Exception("A nova senha deve ter no mínimo 6 caracteres");
        }

        if ($senha_nova !== $senha_confirma) {
            throw new Exception("A nova senha e a confirmação não coincidem");
        }

        // Verificar senha atual
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify($senha_atual, $usuario['senha'])) {
            throw new Exception("Senha atual incorreta");
        }

        // Atualizar senha
        $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$senha_hash, $usuario_id]);

        redirecionar_com_mensagem($pasta_base . '/dashboard.php', 'Senha alterada com sucesso!', 'success');

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container py-5 animate-fade-in">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card-glass">
                <div class="card-glass-header" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        Alterar Senha
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

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Dicas para uma senha segura:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Use no mínimo 6 caracteres (recomendado 8+)</li>
                            <li>Misture letras maiúsculas e minúsculas</li>
                            <li>Inclua números e caracteres especiais</li>
                            <li>Não use informações pessoais óbvias</li>
                        </ul>
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">

                        <!-- Senha Atual -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-lock text-danger me-1"></i>
                                Senha Atual <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       name="senha_atual"
                                       id="senha_atual"
                                       class="input-salao"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha_atual')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Nova Senha -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-key text-success me-1"></i>
                                Nova Senha <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       name="senha_nova"
                                       id="senha_nova"
                                       class="input-salao"
                                       minlength="6"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha_nova')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="strength-bar"></div>
                                </div>
                                <small id="strength-text" class="text-muted"></small>
                            </div>
                        </div>

                        <!-- Confirmar Nova Senha -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-check-double text-success me-1"></i>
                                Confirmar Nova Senha <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       name="senha_confirma"
                                       id="senha_confirma"
                                       class="input-salao"
                                       minlength="6"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha_confirma')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Botões -->
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?php echo $pasta_base; ?>/dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-save me-1"></i>Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/Ocultar senha
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Indicador de força da senha
document.getElementById('senha_nova').addEventListener('input', function(e) {
    const password = e.target.value;
    let strength = 0;
    let strengthText = '';
    let strengthClass = '';

    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');

    switch(strength) {
        case 0:
        case 1:
            strengthText = 'Muito fraca';
            strengthClass = 'bg-danger';
            bar.style.width = '20%';
            break;
        case 2:
            strengthText = 'Fraca';
            strengthClass = 'bg-warning';
            bar.style.width = '40%';
            break;
        case 3:
            strengthText = 'Média';
            strengthClass = 'bg-info';
            bar.style.width = '60%';
            break;
        case 4:
            strengthText = 'Forte';
            strengthClass = 'bg-success';
            bar.style.width = '80%';
            break;
        case 5:
            strengthText = 'Muito forte';
            strengthClass = 'bg-success';
            bar.style.width = '100%';
            break;
    }

    bar.className = 'progress-bar ' + strengthClass;
    text.textContent = strengthText;
    text.className = 'text-' + strengthClass.replace('bg-', '');
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

<?php include 'includes/footer.php'; ?>
