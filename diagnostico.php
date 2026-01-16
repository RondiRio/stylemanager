<?php
/**
 * DIAGNÓSTICO DO SISTEMA
 * Execute este arquivo para identificar problemas
 * IMPORTANTE: Delete após usar!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Função auxiliar para verificar status
function status($bool) {
    return $bool ? '✅ OK' : '❌ ERRO';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico do Sistema</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #2d2d2d;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
        }
        h1 {
            color: #00ff00;
            text-shadow: 0 0 10px #00ff00;
            margin-bottom: 30px;
            text-align: center;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #1a1a1a;
            border-left: 4px solid #00ff00;
            border-radius: 5px;
        }
        .section h2 {
            color: #00d4ff;
            margin-bottom: 15px;
        }
        .item {
            padding: 8px 0;
            border-bottom: 1px solid #333;
        }
        .item:last-child {
            border-bottom: none;
        }
        .ok { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .info { color: #00d4ff; }
        .delete-warning {
            background: #ff0000;
            color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            margin-top: 30px;
            border-radius: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        pre {
            background: #000;
            color: #0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DIAGNÓSTICO DO SISTEMA STYLEMANAGER</h1>

        <!-- 1. PHP Version -->
        <div class="section">
            <h2>1️⃣ Versão do PHP</h2>
            <?php
            $php_version = phpversion();
            $php_ok = version_compare($php_version, '7.4.0', '>=');
            ?>
            <div class="item <?php echo $php_ok ? 'ok' : 'error'; ?>">
                <?php echo status($php_ok); ?> Versão: <?php echo $php_version; ?>
                <?php if (!$php_ok): ?>
                    <br><span class="error">⚠️ CRÍTICO: É necessário PHP 7.4 ou superior!</span>
                    <br><span class="info">→ Solução: Atualize o PHP no cPanel (MultiPHP Manager)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Extensões PHP -->
        <div class="section">
            <h2>2️⃣ Extensões PHP Necessárias</h2>
            <?php
            $extensoes_necessarias = [
                'pdo' => 'Necessário para banco de dados',
                'pdo_mysql' => 'Driver MySQL para PDO',
                'mysqli' => 'Alternativa MySQL',
                'mbstring' => 'Manipulação de strings UTF-8',
                'openssl' => 'Criptografia e segurança',
                'json' => 'Manipulação de JSON',
                'curl' => 'Requisições HTTP',
                'fileinfo' => 'Informações de arquivos',
                'gd' => 'Manipulação de imagens'
            ];

            $extensoes_faltando = [];
            foreach ($extensoes_necessarias as $ext => $desc):
                $loaded = extension_loaded($ext);
                if (!$loaded) $extensoes_faltando[] = $ext;
            ?>
                <div class="item <?php echo $loaded ? 'ok' : 'error'; ?>">
                    <?php echo status($loaded); ?> <?php echo $ext; ?> - <span class="info"><?php echo $desc; ?></span>
                </div>
            <?php endforeach; ?>

            <?php if (count($extensoes_faltando) > 0): ?>
                <div class="item error">
                    <br>⚠️ <strong>Extensões faltando:</strong> <?php echo implode(', ', $extensoes_faltando); ?>
                    <br><span class="info">→ Solução: Ative no cPanel em "Select PHP Version" ou "PHP Extensions"</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Permissões de Arquivos -->
        <div class="section">
            <h2>3️⃣ Permissões de Arquivos</h2>
            <?php
            $arquivos_verificar = [
                'includes/db_connect.php',
                'includes/auth.php',
                'includes/utils.php',
                'login.php',
                'index.php',
                'logs',
                '.htaccess'
            ];

            foreach ($arquivos_verificar as $arquivo):
                if (file_exists($arquivo)):
                    $perms = substr(sprintf('%o', fileperms($arquivo)), -3);
                    $is_ok = is_dir($arquivo) ? $perms === '755' : $perms === '644';
            ?>
                    <div class="item <?php echo $is_ok ? 'ok' : 'warning'; ?>">
                        <?php echo status($is_ok); ?> <?php echo $arquivo; ?> (<?php echo $perms; ?>)
                        <?php if (!$is_ok): ?>
                            <br><span class="warning">→ Permissão recomendada: <?php echo is_dir($arquivo) ? '755' : '644'; ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="item error">
                        ❌ <?php echo $arquivo; ?> - <span class="error">ARQUIVO NÃO EXISTE</span>
                    </div>
                <?php endif;
            endforeach;
            ?>
        </div>

        <!-- 4. Banco de Dados -->
        <div class="section">
            <h2>4️⃣ Conexão com Banco de Dados</h2>
            <?php
            $db_ok = false;
            $db_error = '';
            $db_info = [];

            try {
                if (file_exists('includes/db_connect.php')) {
                    require_once 'includes/db_connect.php';
                    $db_ok = true;

                    // Testar query simples
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                    $total_usuarios = $stmt->fetchColumn();

                    $db_info[] = "Total de usuários: $total_usuarios";

                    // Verificar tabelas importantes
                    $tabelas = ['usuarios', 'agendamentos', 'servicos', 'configuracoes'];
                    foreach ($tabelas as $tabela) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabela");
                            $count = $stmt->fetchColumn();
                            $db_info[] = "Tabela $tabela: $count registro(s)";
                        } catch (Exception $e) {
                            $db_info[] = "⚠️ Tabela $tabela: NÃO EXISTE";
                        }
                    }
                } else {
                    $db_error = 'Arquivo includes/db_connect.php não existe';
                }
            } catch (PDOException $e) {
                $db_error = $e->getMessage();
            } catch (Exception $e) {
                $db_error = $e->getMessage();
            }
            ?>

            <div class="item <?php echo $db_ok ? 'ok' : 'error'; ?>">
                <?php echo status($db_ok); ?> Conexão com banco de dados
                <?php if ($db_ok): ?>
                    <br>
                    <?php foreach ($db_info as $info): ?>
                        <span class="info">→ <?php echo $info; ?></span><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <br><span class="error">Erro: <?php echo htmlspecialchars($db_error); ?></span>
                    <br><span class="info">→ Verifique as credenciais em includes/db_connect.php</span>
                    <br><span class="info">→ Verifique se o banco de dados existe</span>
                    <br><span class="info">→ Verifique se as tabelas foram importadas</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. Caminhos e Estrutura -->
        <div class="section">
            <h2>5️⃣ Estrutura de Diretórios</h2>
            <div class="item info">
                Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
            </div>
            <div class="item info">
                Script Path: <?php echo __DIR__; ?>
            </div>
            <div class="item info">
                Current Working Dir: <?php echo getcwd(); ?>
            </div>

            <?php
            $pastas_importantes = ['admin', 'cliente', 'profissional', 'recepcionista', 'includes', 'assets', 'logs'];
            foreach ($pastas_importantes as $pasta):
                $existe = is_dir($pasta);
            ?>
                <div class="item <?php echo $existe ? 'ok' : 'error'; ?>">
                    <?php echo status($existe); ?> Pasta: <?php echo $pasta; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 6. Configurações do Servidor -->
        <div class="section">
            <h2>6️⃣ Configurações do Servidor</h2>
            <div class="item info">
                max_execution_time: <?php echo ini_get('max_execution_time'); ?>s
            </div>
            <div class="item info">
                memory_limit: <?php echo ini_get('memory_limit'); ?>
            </div>
            <div class="item info">
                upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?>
            </div>
            <div class="item info">
                post_max_size: <?php echo ini_get('post_max_size'); ?>
            </div>
            <div class="item info">
                display_errors: <?php echo ini_get('display_errors') ? 'ON' : 'OFF'; ?>
                <?php if (ini_get('display_errors')): ?>
                    <span class="warning">⚠️ Desative em produção!</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 7. Apache Modules -->
        <div class="section">
            <h2>7️⃣ Apache Modules</h2>
            <?php if (function_exists('apache_get_modules')): ?>
                <?php
                $modules = ['mod_rewrite', 'mod_headers'];
                foreach ($modules as $mod):
                    $loaded = in_array($mod, apache_get_modules());
                ?>
                    <div class="item <?php echo $loaded ? 'ok' : 'warning'; ?>">
                        <?php echo status($loaded); ?> <?php echo $mod; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="item warning">
                    ⚠️ Não foi possível verificar módulos Apache
                    <br><span class="info">→ Isto é normal em alguns servidores</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- 8. Testes de Funcionalidade -->
        <div class="section">
            <h2>8️⃣ Testes de Funcionalidade</h2>
            <?php
            // Teste de sessão
            session_start();
            $_SESSION['teste'] = 'ok';
            $session_ok = isset($_SESSION['teste']);
            ?>
            <div class="item <?php echo $session_ok ? 'ok' : 'error'; ?>">
                <?php echo status($session_ok); ?> Sessões PHP
            </div>

            <?php
            // Teste de escrita
            $write_ok = is_writable('logs') || is_writable('.');
            ?>
            <div class="item <?php echo $write_ok ? 'ok' : 'warning'; ?>">
                <?php echo status($write_ok); ?> Permissão de escrita
                <?php if (!$write_ok): ?>
                    <br><span class="warning">→ Pode ter problemas para criar logs</span>
                <?php endif; ?>
            </div>

            <?php
            // Teste de password_hash
            $hash_ok = function_exists('password_hash');
            ?>
            <div class="item <?php echo $hash_ok ? 'ok' : 'error'; ?>">
                <?php echo status($hash_ok); ?> Função password_hash()
            </div>
        </div>

        <!-- Conclusão -->
        <div class="section">
            <h2>✅ Resumo</h2>
            <?php
            $problemas_criticos = [];

            if (!$php_ok) $problemas_criticos[] = "PHP muito antigo (versão $php_version)";
            if (count($extensoes_faltando) > 0) $problemas_criticos[] = "Extensões PHP faltando: " . implode(', ', $extensoes_faltando);
            if (!$db_ok) $problemas_criticos[] = "Erro de conexão com banco de dados";
            if (!$hash_ok) $problemas_criticos[] = "Função password_hash não disponível";

            if (count($problemas_criticos) === 0):
            ?>
                <div class="item ok">
                    🎉 <strong>TUDO OK!</strong> O sistema está pronto para funcionar.
                    <br><br>
                    Se ainda assim estiver com erro 500, verifique:
                    <br>→ Os logs de erro do servidor
                    <br>→ O arquivo .htaccess
                    <br>→ As credenciais do banco em includes/db_connect.php
                </div>
            <?php else: ?>
                <div class="item error">
                    ⚠️ <strong>PROBLEMAS ENCONTRADOS:</strong>
                    <br><br>
                    <?php foreach ($problemas_criticos as $problema): ?>
                        ❌ <?php echo $problema; ?><br>
                    <?php endforeach; ?>
                    <br>
                    <strong>Corrija estes problemas antes de usar o sistema!</strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Aviso de Segurança -->
        <div class="delete-warning">
            ⚠️ IMPORTANTE: DELETE ESTE ARQUIVO APÓS USAR! ⚠️
            <br>
            Ele expõe informações sensíveis do sistema!
            <br><br>
            Execute: <code>rm diagnostico.php</code>
        </div>
    </div>
</body>
</html>
