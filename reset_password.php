require_once 'includes/db_connect.php';
require_once 'includes/utils.php';
require_once 'includes/email_sender.php';

$mensagem = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'E-mail inválido.';
        $tipo = 'danger';
    } else {
        $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Gerar token
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (usuario_id, token, expira_em)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$usuario['id'], $token, $expira]);
            
            // Enviar e-mail
            $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $link_reset = $base_url . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
            
            $corpo = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #dc3545; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                        .button { display: inline-block; background: #0d6efd; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Recuperação de Senha</h1>
                        </div>
                        <div class='content'>
                            <h2>Olá, {$usuario['nome']}!</h2>
                            <p>Recebemos uma solicitação para redefinir sua senha.</p>
                            <p>Clique no botão abaixo para criar uma nova senha:</p>
                            <p style='text-align: center;'>
                                <a href='$link_reset' class='button'>Redefinir Senha</a>
                            </p>
                            <p><small>Ou copie e cole este link:<br><a href='$link_reset'>$link_reset</a></small></p>
                            <p><strong>Este link expira em 1 hora.</strong></p>
                            <p>Se você não solicitou a redefinição, ignore este e-mail.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            enviar_email($email, 'Recuperação de Senha', $corpo, $usuario['nome']);
        }
        
        // Sempre mostrar sucesso (segurança)
        $mensagem = 'Se o e-mail estiver cadastrado, você receberá as instruções de recuperação.';
        $tipo = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Senha</title>
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
                        <i class="fas fa-key fa-3x text-warning mb-3"></i>
                        <h3>Recuperar Senha</h3>
                        <p class="text-muted">Digite seu e-mail cadastrado</p>
                    </div>

                    <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo; ?>">
                        <?php echo $mensagem; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control form-control-lg" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Instruções
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Voltar ao Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>