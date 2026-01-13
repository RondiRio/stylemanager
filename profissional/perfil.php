<?php
// profissional/perfil.php (CONFIGURAÇÃO COMPLETA DO PERFIL DO PROFISSIONAL)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Meu Perfil";
requer_login('profissional');

$profissional_id = $_SESSION['usuario_id'];

// === PROCESSAR ATUALIZAÇÃO DO PERFIL ===
if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $comissao_padrao = (float)str_replace(['.', ','], ['', '.'], $_POST['comissao_padrao'] ?? '0');
    $avatar_atual = $_POST['avatar_atual'] ?? 'default.png';

    // Validações
    if (empty($nome)) {
        $erro = "Nome é obrigatório.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif ($comissao_padrao < 0 || $comissao_padrao > 100) {
        $erro = "Comissão deve ser entre 0% e 100%.";
    } else {
        try {
            $pdo->beginTransaction();

            // === 1. ATUALIZAR DADOS BÁSICOS ===
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nome = ?, email = ?, telefone = ?, bio = ?, comissao_padrao = ?
                WHERE id = ? AND tipo = 'profissional'
            ");
            $stmt->execute([$nome, $email, $telefone, $bio, $comissao_padrao, $profissional_id]);

            // === 2. UPLOAD DE AVATAR ===
            $avatar_novo = $avatar_atual;
            if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $extensoes = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $extensoes)) {
                    throw new Exception("Formato de imagem inválido. Use JPG, PNG ou WebP.");
                }
                if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                    throw new Exception("Imagem muito grande. Máximo 2MB.");
                }

                $nome_arquivo = $profissional_id . '_' . time() . '.' . $ext;
                $caminho = '../assets/img/avatars/' . $nome_arquivo;

                if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $caminho)) {
                    throw new Exception("Erro ao salvar imagem.");
                }

                // Remover avatar antigo (se não for default)
                if ($avatar_atual !== 'default.png' && file_exists('../assets/img/avatars/' . $avatar_atual)) {
                    unlink('../assets/img/avatars/' . $avatar_atual);
                }

                $avatar_novo = $nome_arquivo;
            }

            // === 3. ATUALIZAR AVATAR NO BANCO ===
            if ($avatar_novo !== $avatar_atual) {
                $pdo->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?")
                    ->execute([$avatar_novo, $profissional_id]);
                $_SESSION['avatar'] = $avatar_novo;
            }

            $_SESSION['nome'] = $nome;
            $pdo->commit();
            $sucesso = "Perfil atualizado com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

// === CARREGAR DADOS ATUAIS ===
$stmt = $pdo->prepare("
    SELECT nome, email, telefone, bio, comissao_padrao, avatar 
    FROM usuarios 
    WHERE id = ? AND tipo = 'profissional'
");
$stmt->execute([$profissional_id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    redirecionar_com_mensagem('../logout.php', 'Perfil não encontrado.', 'danger');
}

$avatar_url = $perfil['avatar'] ?? 'default.png';
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
        .card { border: none; border-radius: 16px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .btn { border-radius: 10px; font-weight: 500; }
        .shadow-sm { box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1) !important; }
        .avatar-preview {
            width: 120px; height: 120px; object-fit: cover; border-radius: 50%;
            border: 4px solid #007bff; box-shadow: 0 0 0 4px white;
        }
        .form-label { font-weight: 600; }
        .money { text-align: right; }
        .navbar { position: sticky; top: 0; z-index: 1000; }
    </style>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-cut me-2"></i>Barbearia System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="agenda.php"><i class="fas fa-calendar-alt"></i> Agenda</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                        <img src="../assets/img/avatars/<?php echo $_SESSION['avatar'] ?? 'default.png'; ?>" class="rounded-circle me-2" width="32" height="32" alt="">
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item active" href="perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white text-center">
                    <h4 class="mb-0"><i class="fas fa-user-cog me-2"></i>Configurações do Perfil</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($sucesso)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($sucesso); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <input type="hidden" name="avatar_atual" value="<?php echo htmlspecialchars($perfil['avatar'] ?? 'default.png'); ?>">

                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="../assets/img/avatars/<?php echo htmlspecialchars($avatar_url); ?>" 
                                     class="avatar-preview" id="avatar-preview" alt="Avatar">
                                <label for="avatar" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle p-2" 
                                       style="transform: translate(30%, 30%);">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" name="avatar" id="avatar" class="d-none" accept="image/*">
                            </div>
                            <p class="text-muted mt-2">Clique na câmera para alterar</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($perfil['nome'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control money" value="<?php echo htmlspecialchars($perfil['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" 
                                       value="<?php echo formatar_telefone($perfil['telefone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comissão Padrão (%) <span class="text-danger">*</span></label>
                                <input type="text" name="comissao_padrao" class="form-control money" 
                                       value="<?php echo number_format($perfil['comissao_padrao'] ?? 0, 2, ',', '.'); ?>" readonly>
                                <!-- <div class="form-text">Ex: 30,00 para 30% — campo não editável.</div> -->
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Bio / Apresentação</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Fale um pouco sobre você..."><?php echo htmlspecialchars($perfil['bio'] ?? ''); ?></textarea>
                            <div class="form-text">Máximo 200 caracteres. Aparece no feed público.</div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- CARD DE INFORMAÇÕES ADICIONAIS -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações do Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">ID do Profissional</h6>
                            <h5 class="text-primary">#<?php echo $profissional_id; ?></h5>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Tipo de Conta</h6>
                            <h5 class="text-success">Profissional</h5>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Última Atualização</h6>
                            <h5 class="text-info"><?php echo date('d/m/Y H:i'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    'use strict';

    // Preview do avatar
    document.getElementById('avatar')?.addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                document.getElementById('avatar-preview').src = ev.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Máscara monetária para comissão
    document.querySelectorAll('.money').forEach(input => {
        input.addEventListener('input', e => {
            let v = e.target.value.replace(/\D/g, '');
            if (v === '') v = '0';
            v = (parseInt(v) / 100).toFixed(2).replace('.', ',');
            v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = v;
        });
    });

    // Validação Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Máscara de telefone
    const tel = document.querySelector('input[name="telefone"]');
    tel?.addEventListener('input', e => {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length <= 11) {
            v = v.replace(/(\d{2})(\d)/, '($1) $2');
            v = v.replace(/(\d{5})(\d)/, '$1-$2');
        }
        e.target.value = v.substring(0, 15);
    });
})();
</script>
</body>
</html>