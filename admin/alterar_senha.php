<?php
// ========================================
// ARQUIVO 1: admin/alterar_senha.php
// ========================================
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Alterar Senha";
requer_login('admin');
include '../includes/header.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // Validações
    if (empty($senha_atual) || empty($senha_nova) || empty($senha_confirmar)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (strlen($senha_nova) < 6) {
        $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha_nova !== $senha_confirmar) {
        $erro = 'A nova senha e a confirmação não coincidem.';
    } elseif ($senha_atual === $senha_nova) {
        $erro = 'A nova senha deve ser diferente da senha atual.';
    } else {
        // Buscar senha atual do banco
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ? AND tipo = 'admin'");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $erro = 'Usuário não encontrado.';
        } elseif (!password_verify($senha_atual, $usuario['senha'])) {
            $erro = 'Senha atual incorreta.';
        } else {
            // Atualizar senha
            $nova_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            
            if ($stmt->execute([$nova_hash, $_SESSION['usuario_id']])) {
                $sucesso = 'Senha alterada com sucesso!';
                
                // Opcional: Registrar log de segurança
                try {
                    $pdo->prepare("
                        INSERT INTO logs_seguranca (usuario_id, acao, ip_address, user_agent, criado_em)
                        VALUES (?, 'alteracao_senha', ?, ?, NOW())
                    ")->execute([
                        $_SESSION['usuario_id'],
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                } catch (PDOException $e) {
                    // Tabela de logs não existe, continuar normalmente
                }

                // Limpar campos após sucesso
                $_POST = [];
            } else {
                $erro = 'Erro ao atualizar a senha. Tente novamente.';
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>Alterar Senha
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($sucesso); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="formAlterarSenha" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">

                        <!-- Senha Atual -->
                        <div class="mb-4">
                            <label for="senha_atual" class="form-label fw-bold">
                                <i class="fas fa-lock me-1"></i>Senha Atual *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha_atual" 
                                       name="senha_atual" 
                                       required
                                       placeholder="Digite sua senha atual">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('senha_atual')">
                                    <i class="fas fa-eye" id="icon_senha_atual"></i>
                                </button>
                            </div>
                            <div class="form-text">Digite sua senha atual para confirmar a alteração.</div>
                        </div>

                        <!-- Nova Senha -->
                        <div class="mb-4">
                            <label for="senha_nova" class="form-label fw-bold">
                                <i class="fas fa-key me-1"></i>Nova Senha *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock-open"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha_nova" 
                                       name="senha_nova" 
                                       required
                                       minlength="6"
                                       placeholder="Mínimo 6 caracteres"
                                       oninput="validarSenha()">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('senha_nova')">
                                    <i class="fas fa-eye" id="icon_senha_nova"></i>
                                </button>
                            </div>
                            <div id="senha_strength" class="mt-2"></div>
                        </div>

                        <!-- Confirmar Nova Senha -->
                        <div class="mb-4">
                            <label for="senha_confirmar" class="form-label fw-bold">
                                <i class="fas fa-check-double me-1"></i>Confirmar Nova Senha *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-check"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha_confirmar" 
                                       name="senha_confirmar" 
                                       required
                                       minlength="6"
                                       placeholder="Confirme a nova senha"
                                       oninput="validarConfirmacao()">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePassword('senha_confirmar')">
                                    <i class="fas fa-eye" id="icon_senha_confirmar"></i>
                                </button>
                            </div>
                            <div id="senha_match" class="mt-2"></div>
                        </div>

                        <!-- Requisitos de Senha -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-info-circle me-1"></i>Requisitos de Senha:
                            </h6>
                            <ul class="mb-0 small">
                                <li id="req_length">Mínimo de 6 caracteres</li>
                                <li id="req_match">As senhas devem coincidir</li>
                                <li id="req_different">Diferente da senha atual</li>
                            </ul>
                        </div>

                        <!-- Botões -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success" id="btnSubmit">
                                <i class="fas fa-save me-2"></i>Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card de Segurança -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>Dicas de Segurança
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 small">
                        <li>Use uma senha forte e única</li>
                        <li>Evite palavras comuns ou sequências</li>
                        <li>Combine letras, números e símbolos</li>
                        <li>Não compartilhe sua senha com ninguém</li>
                        <li>Altere sua senha regularmente</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle visualização de senha
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('icon_' + fieldId);
    
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

// Validar força da senha
function validarSenha() {
    const senha = document.getElementById('senha_nova').value;
    const container = document.getElementById('senha_strength');
    const reqLength = document.getElementById('req_length');
    
    if (senha.length === 0) {
        container.innerHTML = '';
        reqLength.style.color = '';
        return;
    }
    
    let forca = 0;
    let feedback = '';
    let cor = '';
    
    // Comprimento
    if (senha.length >= 6) {
        forca += 1;
        reqLength.style.color = 'green';
    } else {
        reqLength.style.color = 'red';
    }
    if (senha.length >= 8) forca += 1;
    if (senha.length >= 12) forca += 1;
    
    // Complexidade
    if (/[a-z]/.test(senha)) forca += 1;
    if (/[A-Z]/.test(senha)) forca += 1;
    if (/[0-9]/.test(senha)) forca += 1;
    if (/[^a-zA-Z0-9]/.test(senha)) forca += 1;
    
    // Determinar nível
    if (forca < 3) {
        feedback = 'Fraca';
        cor = 'danger';
    } else if (forca < 5) {
        feedback = 'Média';
        cor = 'warning';
    } else if (forca < 6) {
        feedback = 'Boa';
        cor = 'info';
    } else {
        feedback = 'Forte';
        cor = 'success';
    }
    
    const largura = Math.min((forca / 7) * 100, 100);
    
    container.innerHTML = `
        <div class="d-flex justify-content-between mb-1">
            <small>Força da senha:</small>
            <small class="text-${cor} fw-bold">${feedback}</small>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-${cor}" style="width: ${largura}%"></div>
        </div>
    `;
    
    validarConfirmacao();
}

// Validar confirmação
function validarConfirmacao() {
    const senha = document.getElementById('senha_nova').value;
    const confirmar = document.getElementById('senha_confirmar').value;
    const container = document.getElementById('senha_match');
    const reqMatch = document.getElementById('req_match');
    
    if (confirmar.length === 0) {
        container.innerHTML = '';
        reqMatch.style.color = '';
        return;
    }
    
    if (senha === confirmar) {
        container.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>As senhas coincidem</small>';
        reqMatch.style.color = 'green';
    } else {
        container.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>As senhas não coincidem</small>';
        reqMatch.style.color = 'red';
    }
}

// Validação do formulário
document.getElementById('formAlterarSenha').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha_nova').value;
    const confirmar = document.getElementById('senha_confirmar').value;
    
    if (senha !== confirmar) {
        e.preventDefault();
        alert('As senhas não coincidem!');
        return false;
    }
    
    if (senha.length < 6) {
        e.preventDefault();
        alert('A senha deve ter no mínimo 6 caracteres!');
        return false;
    }
});

// Validação HTML5
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<style>
.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

.input-group .form-control {
    border-left: none;
}

.input-group .form-control:focus {
    border-left: none;
    box-shadow: none;
}

.input-group .form-control:focus + .btn {
    border-color: #86b7fe;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #b6d4fe;
}

.progress {
    border-radius: 10px;
}

.progress-bar {
    transition: width 0.3s ease;
}
</style>

<?php include '../includes/footer.php'; ?>
