<?php
/**
 * Script de Instalação - Criar Estrutura de Pastas
 * Execute APENAS UMA VEZ após baixar o projeto
 * 
 * Acesse: http://localhost/barbearia/install/criar_estrutura.php
 */

// Prevenir execução múltipla
$lock_file = __DIR__ . '/.instalacao_completa';
if (file_exists($lock_file)) {
    die("
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Instalação já Concluída</title>
        <style>
            body { font-family: Arial; padding: 40px; background: #f5f5f5; }
            .alert { background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 8px; }
        </style>
    </head>
    <body>
        <div class='alert'>
            <h2>⚠️ Instalação já foi concluída anteriormente!</h2>
            <p>Para reinstalar, delete o arquivo: <code>install/.instalacao_completa</code></p>
            <a href='../index.php'>Ir para o Sistema</a>
        </div>
    </body>
    </html>
    ");
}

$erros = [];
$sucessos = [];

// Estrutura de diretórios necessária
$diretorios = [
    'assets/img/avatars' => 'Avatares de usuários',
    'assets/img/feed' => 'Posts do feed social',
    'assets/img/profissionais' => 'Fotos dos profissionais',
    'assets/img/servicos' => 'Fotos dos serviços',
    'assets/img/banners' => 'Banners promocionais',
    'assets/uploads/temp' => 'Uploads temporários',
    'logs' => 'Logs do sistema',
    'backups' => 'Backups automáticos'
];

echo "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação - Sistema de Salão</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #6d4c41, #f06292);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .step {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }
        .step.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .step.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .step.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .step-title {
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .icon { font-size: 24px; }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .code { 
            background: #f4f4f4; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>💇‍♀️ Instalação do Sistema</h1>
            <p>Configurando estrutura de pastas e permissões</p>
        </div>
        <div class='content'>
";

$total_passos = count($diretorios) + 3; // pastas + verificações
$passo_atual = 0;

// Criar diretórios
foreach ($diretorios as $dir => $descricao) {
    $passo_atual++;
    $progresso = ($passo_atual / $total_passos) * 100;
    
    $caminho_completo = __DIR__ . '/../' . $dir;
    
    if (!file_exists($caminho_completo)) {
        if (mkdir($caminho_completo, 0755, true)) {
            $sucessos[] = "Pasta criada: $dir";
            echo "
            <div class='step success'>
                <div class='step-title'>
                    <span class='icon'>✅</span>
                    <span>$descricao</span>
                </div>
                <small class='code'>$dir</small>
            </div>
            ";
        } else {
            $erros[] = "Falha ao criar: $dir";
            echo "
            <div class='step error'>
                <div class='step-title'>
                    <span class='icon'>❌</span>
                    <span>Erro: $descricao</span>
                </div>
                <small class='code'>$dir</small>
            </div>
            ";
        }
    } else {
        echo "
        <div class='step info'>
            <div class='step-title'>
                <span class='icon'>ℹ️</span>
                <span>$descricao (já existe)</span>
            </div>
            <small class='code'>$dir</small>
        </div>
        ";
    }
    
    echo "<div class='progress-bar'><div class='progress-fill' style='width: {$progresso}%'></div></div>";
    flush();
}

// Copiar avatar padrão
$passo_atual++;
$avatar_origem = __DIR__ . '/default-avatar.png';
$avatar_destino = __DIR__ . '/../assets/img/avatars/default.png';

if (!file_exists($avatar_destino)) {
    // Criar imagem padrão se não existir
    $img = imagecreatetruecolor(200, 200);
    $cor_fundo = imagecolorallocate($img, 108, 76, 65);
    $cor_texto = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 0, 0, 200, 200, $cor_fundo);
    imagettftext($img, 40, 0, 75, 115, $cor_texto, __DIR__ . '/../assets/fonts/arial.ttf', '👤');
    imagepng($img, $avatar_destino);
    imagedestroy($img);
    
    echo "
    <div class='step success'>
        <div class='step-title'>
            <span class='icon'>✅</span>
            <span>Avatar padrão criado</span>
        </div>
    </div>
    ";
} else {
    echo "
    <div class='step info'>
        <div class='step-title'>
            <span class='icon'>ℹ️</span>
            <span>Avatar padrão já existe</span>
        </div>
    </div>
    ";
}

// Verificar .htaccess
$passo_atual++;
$htaccess = __DIR__ . '/../.htaccess';
if (!file_exists($htaccess)) {
    $conteudo_htaccess = "
# Proteção básica
<FilesMatch \"\\.(env|log|sql|bak)$\">
    Order allow,deny
    Deny from all
</FilesMatch>

# Redirecionar para HTTPS (descomentar se tiver SSL)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevenir listagem de diretórios
Options -Indexes

# Proteção contra injeção de código
php_flag display_errors Off
php_value upload_max_filesize 10M
php_value post_max_size 10M
    ";
    
    if (file_put_contents($htaccess, trim($conteudo_htaccess))) {
        echo "
        <div class='step success'>
            <div class='step-title'>
                <span class='icon'>✅</span>
                <span>.htaccess criado</span>
            </div>
        </div>
        ";
    }
}

// Criar arquivo de bloqueio
$passo_atual++;
file_put_contents($lock_file, date('Y-m-d H:i:s'));

echo "
            <div class='progress-bar'><div class='progress-fill' style='width: 100%'></div></div>
            
            <div class='step success' style='margin-top: 30px; border: 2px solid #28a745;'>
                <div class='step-title' style='font-size: 18px;'>
                    <span class='icon'>🎉</span>
                    <span>Instalação Concluída!</span>
                </div>
                <p style='margin-top: 10px;'>Estrutura de pastas criada com sucesso.</p>
            </div>
        </div>
        <div class='footer'>
            <div>
                <strong>Próximos passos:</strong>
                <ol style='margin: 10px 0 0 20px; font-size: 14px;'>
                    <li>Execute o arquivo SQL no phpMyAdmin</li>
                    <li>Configure o arquivo .env</li>
                    <li>Acesse o sistema</li>
                </ol>
            </div>
            <a href='../index.php' class='btn btn-primary'>Ir para o Sistema →</a>
        </div>
    </div>
</body>
</html>
";

// Resumo no log
$log_conteudo = "=== INSTALAÇÃO REALIZADA ===\n";
$log_conteudo .= "Data: " . date('Y-m-d H:i:s') . "\n\n";
$log_conteudo .= "Pastas criadas: " . count($sucessos) . "\n";
$log_conteudo .= "Erros: " . count($erros) . "\n\n";

if (!empty($erros)) {
    $log_conteudo .= "ERROS:\n" . implode("\n", $erros) . "\n";
}

file_put_contents(__DIR__ . '/../logs/instalacao.log', $log_conteudo);
?>