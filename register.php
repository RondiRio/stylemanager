<?php
// ========================================
// ARQUIVO: register.php (ATUALIZADO)
// ========================================
require_once 'includes/db_connect.php';
require_once 'includes/utils.php';
require_once 'includes/email_sender.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    // Validações
    if (strlen($nome) < 3) {
        $erro = 'Nome deve ter pelo menos 3 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'Senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $senha_confirmar) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Verificar se e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            // Cadastrar usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $token_verificacao = bin2hex(random_bytes(32));
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, telefone, senha, tipo, token_verificacao, ativo, email_verificado)
                    VALUES (?, ?, ?, ?, 'cliente', ?, 1, 0)
                ");
                $stmt->execute([$nome, $email, $telefone, $senha_hash, $token_verificacao]);
                
                // Enviar e-mail de verificação
                $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $link_verificacao = $base_url . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?token=$token_verificacao";
                
                $corpo_email = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                            .button { display: inline-block; background: #28a745; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Bem-vindo(a)!</h1>
                            </div>
                            <div class='content'>
                                <h2>Olá, $nome!</h2>
                                <p>Obrigado por se cadastrar em nosso sistema.</p>
                                <p>Para ativar sua conta e começar a agendar serviços, clique no botão abaixo:</p>
                                <p style='text-align: center;'>
                                    <a href='$link_verificacao' class='button'>Verificar E-mail</a>
                                </p>
                                <p><small>Ou copie e cole este link no navegador:<br>
                                <a href='$link_verificacao'>$link_verificacao</a></small></p>
                                <p><strong>Este link expira em 24 horas.</strong></p>
                            </div>
                            <div class='footer'>
                                <p>Se você não se cadastrou, ignore este e-mail.</p>
                                <p>&copy; " . date('Y') . " Sistema Barbearia</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                
                $email_enviado = enviar_email($email, 'Confirme seu cadastro', $corpo_email, $nome);
                
                if ($email_enviado) {
                    $sucesso = "Cadastro realizado! Enviamos um e-mail de verificação para <strong>$email</strong>. Verifique sua caixa de entrada (e spam).";
                } else {
                    $sucesso = "Cadastro realizado! Você já pode fazer login, mas recomendamos verificar seu e-mail.";
                }
                
            } catch (PDOException $e) {
                $erro = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar - Sistema Barbearia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h3>Criar Conta</h3>
                        <p class="text-muted">Cadastre-se para agendar serviços</p>
                    </div>

                    <?php if ($erro): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erro; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $sucesso; ?>
                        <hr>
                        <a href="login.php" class="btn btn-success w-100 mt-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                        </a>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefone (opcional)</label>
                            <input type="tel" name="telefone" class="form-control"
                                   placeholder="(00) 00000-0000"
                                   value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha *</label>
                                <input type="password" name="senha" class="form-control" required minlength="6">
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha *</label>
                                <input type="password" name="senha_confirmar" class="form-control" required minlength="6">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Cadastrar
                        </button>
                    </form>

                    <div class="text-center">
                        <small>Já tem conta? <a href="login.php">Fazer login</a></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


<?php
// ========================================
// ARQUIVO: verify_email.php (NOVO)
// ========================================
require_once 'includes/db_connect.php';

$token = $_GET['token'] ?? '';
$mensagem = '';
$tipo = 'danger';

if (!$token) {
    $mensagem = 'Token de verificação não informado.';
} else {
    $stmt = $pdo->prepare("
        SELECT id, nome, email 
        FROM usuarios 
        WHERE token_verificacao = ? 
          AND email_verificado = 0
    ");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $mensagem = 'Token inválido ou e-mail já verificado.';
    } else {
        // Verificar e-mail
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET email_verificado = 1, 
                email_verificado_em = NOW(),
                token_verificacao = NULL
            WHERE id = ?
        ");
        $stmt->execute([$usuario['id']]);
        
        $mensagem = 'E-mail verificado com sucesso! Você já pode fazer login.';
        $tipo = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificação de E-mail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-5">
                    <i class="fas fa-<?php echo $tipo === 'success' ? 'check-circle text-success' : 'times-circle text-danger'; ?> fa-5x mb-4"></i>
                    <h3><?php echo $tipo === 'success' ? 'E-mail Verificado!' : 'Erro na Verificação'; ?></h3>
                    <p class="lead"><?php echo $mensagem; ?></p>
                    <?php if ($tipo === 'success'): ?>
                    <a href="login.php" class="btn btn-success btn-lg mt-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                    </a>
                    <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-user-plus me-2"></i>Tentar Novamente
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>