<?php
// cliente/configuracoes.php - Configurações do Perfil do Cliente
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Meu Perfil";
requer_login('cliente');

$cliente_id = $_SESSION['usuario_id'];

// Processar formulário
if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

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

            $nome_arquivo = 'user_' . $cliente_id . '_' . time() . '.' . $ext;
            $caminho_destino = '../assets/img/avatars/' . $nome_arquivo;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $caminho_destino)) {
                // Deletar avatar antigo se existir
                $avatar_antigo = $pdo->prepare("SELECT avatar FROM usuarios WHERE id = ?");
                $avatar_antigo->execute([$cliente_id]);
                $old_avatar = $avatar_antigo->fetchColumn();

                if ($old_avatar && $old_avatar !== 'default.png' && file_exists('../assets/img/avatars/' . $old_avatar)) {
                    @unlink('../assets/img/avatars/' . $old_avatar);
                }

                // Atualizar no banco
                $stmt = $pdo->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
                $stmt->execute([$nome_arquivo, $cliente_id]);

                $_SESSION['avatar'] = $nome_arquivo;
            }
        }

        // Atualizar dados do cliente
        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET nome = ?, email = ?, telefone = ?
            WHERE id = ?
        ");
        $stmt->execute([$nome, $email, $telefone, $cliente_id]);

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
$stmt->execute([$cliente_id]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar estatísticas do cliente
$stmt = $pdo->prepare("SELECT COUNT(*) FROM agendamentos WHERE cliente_id = ? AND status != 'cancelado'");
$stmt->execute([$cliente_id]);
$total_agendamentos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM atendimentos WHERE cliente_id = ?");
$stmt->execute([$cliente_id]);
$total_atendimentos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT created_at FROM usuarios WHERE id = ?");
$stmt->execute([$cliente_id]);
$data_cadastro = $stmt->fetchColumn();

include '../includes/header.php';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria));
    border-radius: 20px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.profile-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 5px solid white;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    object-fit: cover;
    position: relative;
    z-index: 1;
}

.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: 100%;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.form-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
}

.form-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--cor-primaria);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--cor-primaria);
}

.avatar-upload-area {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto;
}

.avatar-preview {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--cor-primaria);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.avatar-upload-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: var(--cor-primaria);
    color: white;
    border: 3px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.avatar-upload-btn:hover {
    background: var(--cor-secundaria);
    transform: scale(1.1);
}

.info-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-radius: 10px;
    font-size: 0.9rem;
    margin: 0.25rem;
}

.security-section {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border-left: 4px solid #ef4444;
}
</style>

<div class="animate-fade-in">
    <div class="container-fluid">
        <!-- HEADER DO PERFIL -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="../assets/img/avatars/<?php echo $dados['avatar'] ?? 'default.png'; ?>"
                         alt="Avatar"
                         class="profile-avatar-large">
                </div>
                <div class="col-md-9">
                    <h2 class="mb-2"><?php echo htmlspecialchars($dados['nome']); ?></h2>
                    <p class="mb-3 opacity-90">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($dados['email'] ?? 'E-mail não cadastrado'); ?>
                        <span class="mx-3">|</span>
                        <i class="fas fa-phone me-2"></i><?php echo $dados['telefone'] ?? 'Telefone não cadastrado'; ?>
                    </p>
                    <div>
                        <span class="info-badge">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Membro desde <?php echo date('d/m/Y', strtotime($data_cadastro)); ?>
                        </span>
                        <span class="info-badge">
                            <i class="fas fa-user-check me-2"></i>
                            Cliente
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ESTATÍSTICAS -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                    <div class="stats-number"><?php echo $total_agendamentos; ?></div>
                    <div class="text-muted">Agendamentos Realizados</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-scissors fa-3x text-success mb-3"></i>
                    <div class="stats-number"><?php echo $total_atendimentos; ?></div>
                    <div class="text-muted">Atendimentos Concluídos</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                    <div class="stats-number">
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recomendacoes WHERE cliente_id = ?");
                        $stmt->execute([$cliente_id]);
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                    <div class="text-muted">Avaliações Feitas</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- MENSAGEM DE ERRO/SUCESSO -->
                <?php if (isset($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($erro); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- FORMULÁRIO DE PERFIL -->
                <div class="form-section">
                    <h5 class="form-section-title">
                        <i class="fas fa-user-edit me-2"></i>
                        Informações Pessoais
                    </h5>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">

                        <!-- Avatar -->
                        <div class="text-center mb-4">
                            <div class="avatar-upload-area">
                                <img src="../assets/img/avatars/<?php echo $dados['avatar'] ?? 'default.png'; ?>"
                                     alt="Avatar"
                                     class="avatar-preview"
                                     id="avatar-preview">
                                <label for="avatar" class="avatar-upload-btn">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="avatar" id="avatar" class="d-none" accept="image/*">
                            </div>
                            <p class="text-muted small mt-3 mb-0">Clique na câmera para alterar sua foto</p>
                            <small class="text-muted">JPG, PNG ou GIF • Máximo 2MB</small>
                        </div>

                        <div class="row g-3">
                            <!-- Nome -->
                            <div class="col-md-12">
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

                <!-- SEGURANÇA -->
                <div class="form-section security-section">
                    <h5 class="form-section-title" style="color: #ef4444; border-color: #ef4444;">
                        <i class="fas fa-shield-alt me-2"></i>
                        Segurança da Conta
                    </h5>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1 fw-bold">
                                <i class="fas fa-key text-danger me-2"></i>
                                Senha
                            </h6>
                            <small class="text-muted">
                                Mantenha sua conta protegida com uma senha forte
                            </small>
                        </div>
                        <a href="../alterar_senha.php" class="btn btn-danger">
                            <i class="fas fa-key me-1"></i>Alterar Senha
                        </a>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Dica de segurança:</strong> Altere sua senha regularmente e nunca compartilhe com outras pessoas.
                    </div>
                </div>

                <!-- PRIVACIDADE -->
                <div class="form-section" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-left: 4px solid #22c55e;">
                    <h5 class="form-section-title" style="color: #22c55e; border-color: #22c55e;">
                        <i class="fas fa-user-shield me-2"></i>
                        Privacidade & Termos
                    </h5>

                    <p class="text-muted mb-3">
                        Seus dados são protegidos de acordo com a Lei Geral de Proteção de Dados (LGPD).
                    </p>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <a href="../privacidade.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-shield-alt me-2"></i>
                                Política de Privacidade
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="../termos.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-file-contract me-2"></i>
                                Termos de Uso
                            </a>
                        </div>
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
        if (file.size > 2 * 1024 * 1024) {
            alert('Arquivo muito grande! Máximo 2MB.');
            this.value = '';
            return;
        }

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
