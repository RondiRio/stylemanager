<?php
/**
 * LOGIN - SISTEMA DE SALÃO
 * Corrigido para funcionar com cliente, profissional e admin
 */

require_once 'includes/db_connect.php';
require_once 'includes/auth.php';
require_once 'includes/utils.php';

// Se já está logado, redireciona
if (esta_logado()) {
    $destino = match($_SESSION['tipo']) {
        'admin' => 'admin/dashboard.php',
        'profissional' => 'profissional/dashboard.php',
        'cliente' => 'cliente/dashboard.php',
        default => 'index.php'
    };
    header("Location: $destino");
    exit;
}

$erro = '';
$sucesso = '';

// PROCESSAMENTO DO LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    // Validações básicas
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Buscar usuário
            $stmt = $pdo->prepare("
                SELECT 
                    id, nome, email, senha, tipo, telefone, avatar, ativo 
                FROM usuarios 
                WHERE email = ? 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                $erro = 'E-mail ou senha incorretos.';
            } elseif (!$usuario['ativo']) {
                $erro = 'Sua conta está inativa. Entre em contato com o suporte.';
            } elseif (!password_verify($senha, $usuario['senha'])) {
                $erro = 'E-mail ou senha incorretos.';
            } else {
                // Login bem-sucedido!
                session_regenerate_id(true);
                
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['tipo'] = $usuario['tipo'];
                $_SESSION['avatar'] = $usuario['avatar'] ?? 'default.png';
                $_SESSION['telefone'] = $usuario['telefone'];
                
                // Redirecionar conforme o tipo
                $destino = match($usuario['tipo']) {
                    'admin' => 'admin/dashboard.php',
                    'profissional' => 'profissional/dashboard.php',
                    'cliente' => 'cliente/dashboard.php',
                    default => 'index.php'
                };
                
                // Log de acesso (opcional)
                try {
                    $pdo->prepare("
                        INSERT INTO logs_sistema (usuario_id, acao, detalhes, ip_address, user_agent)
                        VALUES (?, 'login', 'Login realizado', ?, ?)
                    ")->execute([
                        $usuario['id'],
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                } catch (Exception $e) {
                    // Ignora se tabela de logs não existir
                }
                
                header("Location: $destino");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $erro = 'Erro ao processar login. Tente novamente.';
        }
    }
}

// Recuperação de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'recuperar') {
    $email_recuperar = trim($_POST['email_recuperar'] ?? '');
    
    if (empty($email_recuperar)) {
        $erro = 'Digite seu e-mail.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email_recuperar]);
            $user = $stmt->fetch();
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Salvar token
                $pdo->prepare("
                    INSERT INTO password_resets (usuario_id, token, expira_em) 
                    VALUES (?, ?, ?)
                ")->execute([$user['id'], $token, $expira]);
                
                // Enviar e-mail (se configurado)
                if (function_exists('enviar_email')) {
                    require_once 'includes/email_sender.php';
                    $link = "http://" . $_SERVER['HTTP_HOST'] . "/barbearia/reset_password.php?token=" . $token;
                    $corpo = "
                        <h3>Recuperação de Senha</h3>
                        <p>Olá, {$user['nome']}!</p>
                        <p>Clique no link abaixo para redefinir sua senha:</p>
                        <p><a href='$link' style='background:#f06292;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Redefinir Senha</a></p>
                        <p>Link válido por 1 hora.</p>
                        <p><small>Se você não solicitou, ignore este e-mail.</small></p>
                    ";
                    enviar_email($email_recuperar, 'Recuperação de Senha', $corpo);
                }
                
                $sucesso = 'Se o e-mail estiver cadastrado, você receberá instruções em breve.';
            } else {
                // Não revela se e-mail existe (segurança)
                $sucesso = 'Se o e-mail estiver cadastrado, você receberá instruções em breve.';
            }
        } catch (PDOException $e) {
            error_log("Erro na recuperação: " . $e->getMessage());
            $erro = 'Erro ao processar. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistema de Salão</title>
    
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Customizado -->
    <link href="assets/css/components.css" rel="stylesheet">
    <link href="assets/css/animations.css" rel="stylesheet">
    
    <?php require 'includes/theme.php'; aplicar_tema_css(); ?>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--cor-primaria) 0%, var(--cor-secundaria) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria));
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .login-header p {
            margin: 0.5rem 0 0;
            opacity: 0.95;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--cor-secundaria);
            box-shadow: 0 0 0 4px rgba(240, 98, 146, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        
        .divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e5e7eb;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .link-secundario {
            color: var(--cor-primaria);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .link-secundario:hover {
            color: var(--cor-secundaria);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            animation: slideInDown 0.4s ease;
        }
        
        .logo-login {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            z-index: 10;
        }
        
        .form-floating {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-login">
                    <i class="fas fa-cut"></i>
                </div>
                <h2>Bem-vindo!</h2>
                <p>Faça login para continuar</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                
                <!-- Mensagens -->
                <?php if ($erro): ?>
                <div class="alert alert-danger animate-shake">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($sucesso); ?>
                </div>
                <?php endif; ?>
                
                <!-- Formulário de Login -->
                <form method="POST" action="" id="formLogin">
                    <input type="hidden" name="acao" value="login">
                    
                    <div class="form-floating mb-3">
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="nome@exemplo.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>E-mail
                        </label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" 
                               class="form-control" 
                               id="senha" 
                               name="senha" 
                               placeholder="Senha"
                               required>
                        <label for="senha">
                            <i class="fas fa-lock me-2"></i>Senha
                        </label>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                            <label class="form-check-label" for="lembrar">
                                Lembrar-me
                            </label>
                        </div>
                        <a href="#" class="link-secundario" data-bs-toggle="modal" data-bs-target="#modalRecuperar">
                            Esqueceu a senha?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Entrar
                    </button>
                </form>
                
                <div class="divider">
                    <span>ou</span>
                </div>
                
                <div class="text-center">
                    <p class="mb-2">Ainda não tem conta?</p>
                    <a href="register.php" class="link-secundario">
                        <i class="fas fa-user-plus me-1"></i>
                        Criar conta grátis
                    </a>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="link-secundario">
                        <i class="fas fa-arrow-left me-1"></i>
                        Voltar ao site
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Versão do Sistema -->
        <div class="text-center mt-4">
            <small style="color: rgba(255,255,255,0.8);">
                Sistema de Salão v2.0 | © <?php echo date('Y'); ?>
            </small>
        </div>
    </div>

    <!-- Modal Recuperar Senha -->
    <div class="modal fade" id="modalRecuperar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none;">
                <form method="POST" action="">
                    <input type="hidden" name="acao" value="recuperar">
                    
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria)); color: white; border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-key me-2"></i>
                            Recuperar Senha
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body p-4">
                        <p class="text-muted">Digite seu e-mail cadastrado. Você receberá instruções para redefinir sua senha.</p>
                        
                        <div class="form-floating">
                            <input type="email" 
                                   class="form-control" 
                                   id="email_recuperar" 
                                   name="email_recuperar" 
                                   placeholder="nome@exemplo.com"
                                   required>
                            <label for="email_recuperar">
                                <i class="fas fa-envelope me-2"></i>E-mail
                            </label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-login" style="width: auto; padding: 0.75rem 2rem;">
                            <i class="fas fa-paper-plane me-2"></i>
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle visualização de senha
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Validação de formulário
        document.getElementById('formLogin').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const senha = document.getElementById('senha').value;
            
            if (!email || !senha) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos.');
                return false;
            }
        });
        
        // Auto-focus no email
        window.addEventListener('load', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>