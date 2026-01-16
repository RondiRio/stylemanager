# Guia de Troubleshooting - Erro 500 no Servidor

**Data:** 2026-01-16

## 🔍 Diagnóstico do Erro 500

### Passo 1: Verificar Logs de Erro

Os logs são **ESSENCIAIS** para saber o que está causando o erro.

#### Onde encontrar os logs:

**cPanel:**
1. Acesse cPanel
2. Vá em "Errors" ou "Erros"
3. Clique em "Error Log" ou "Log de Erros"
4. Veja as últimas linhas (mais recentes no topo)

**Via FTP/SSH:**
```bash
# Log de erro do PHP
tail -50 /home/usuario/public_html/error_log

# Ou
tail -50 ~/logs/error_log

# Ou (Apache)
tail -50 /var/log/apache2/error.log
```

**Ative o display de erros temporariamente:**

Adicione no início do arquivo `index.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
```

---

## ⚡ Soluções Rápidas Mais Comuns

### 1. Verificar Permissões de Arquivos

**Via FTP (FileZilla):**
- Pastas: 755 (drwxr-xr-x)
- Arquivos PHP: 644 (-rw-r--r--)
- Arquivos .htaccess: 644

**Via SSH:**
```bash
cd /caminho/para/stylemanager

# Corrigir permissões de pastas
find . -type d -exec chmod 755 {} \;

# Corrigir permissões de arquivos
find . -type f -exec chmod 644 {} \;

# Permissão especial para logs (escrita)
chmod 755 logs/
chmod 666 logs/*.log 2>/dev/null || true
```

### 2. Verificar .htaccess

O arquivo `.htaccess` pode estar causando o erro.

**Renomeie temporariamente:**
```bash
mv .htaccess .htaccess.bak
```

Se o site funcionar depois disso, o problema é no `.htaccess`.

**Crie um .htaccess básico:**
```apache
# .htaccess básico para teste
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Bloquear acesso a arquivos sensíveis
    <FilesMatch "\.(env|log|sql|md)$">
        Require all denied
    </FilesMatch>

    # PHP settings
    php_value upload_max_filesize 32M
    php_value post_max_size 32M
    php_value max_execution_time 300
</IfModule>
```

### 3. Verificar Versão do PHP

O código usa PHP 7.4+ features (como `match()`).

**Verificar versão:**
```php
<?php phpinfo(); ?>
```

**Ou via cPanel:**
1. cPanel → "Select PHP Version" ou "MultiPHP Manager"
2. Selecione PHP 7.4, 8.0, 8.1 ou 8.2
3. Ative extensões: mysqli, pdo, pdo_mysql, mbstring, openssl

### 4. Verificar Conexão com Banco de Dados

**Edite `includes/db_connect.php`:**

```php
<?php
// Conexão com banco de dados
$host = 'localhost'; // ou o host fornecido pela hospedagem
$dbname = 'stylemanager'; // nome do banco
$user = 'seu_usuario'; // usuário do banco
$pass = 'sua_senha'; // senha do banco

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // NÃO mostrar senha em produção!
    error_log("Erro de conexão: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados. Verifique os logs.");
}
```

**Teste a conexão:**
```php
<?php
// teste_conexao.php
require_once 'includes/db_connect.php';
echo "Conexão bem-sucedida!";
var_dump($pdo);
```

### 5. Verificar Caminhos de Includes

Alguns servidores têm estrutura diferente.

**Problema comum:**
```php
// Se está em: /public_html/stylemanager/
// E o código tem: require_once '../includes/auth.php'
// Pode não funcionar
```

**Solução - Use caminhos absolutos:**
```php
// No início dos arquivos principais
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/includes/auth.php';
```

---

## 🔧 Checklist de Verificação

Execute este checklist na ordem:

### [ ] 1. Versão do PHP
```bash
php -v
# Ou crie arquivo: <?php phpinfo(); ?>
```
✅ Deve ser PHP 7.4 ou superior

### [ ] 2. Extensões PHP Necessárias
```bash
php -m | grep -E '(pdo|mysqli|mbstring|openssl)'
```
✅ Todas devem estar instaladas

### [ ] 3. Permissões de Arquivos
```bash
ls -la
```
✅ Pastas: 755, Arquivos: 644

### [ ] 4. Banco de Dados
- [ ] Banco criado?
- [ ] Usuário com permissões?
- [ ] Tabelas importadas?
- [ ] Credenciais corretas no db_connect.php?

### [ ] 5. Arquivo .htaccess
- [ ] Existe?
- [ ] Não tem erros de sintaxe?
- [ ] mod_rewrite ativo?

### [ ] 6. Logs de Erro
- [ ] Leu os logs?
- [ ] Identificou o erro específico?

---

## 🐛 Erros Comuns e Soluções

### Erro: "Class 'PDO' not found"
**Causa:** Extensão PDO não instalada
**Solução:**
```bash
# Via cPanel: ativar extensão pdo e pdo_mysql
# Via SSH (se tiver acesso root):
sudo apt-get install php-mysql
sudo systemctl restart apache2
```

### Erro: "Call to undefined function password_hash()"
**Causa:** PHP muito antigo
**Solução:** Atualizar para PHP 7.0+

### Erro: "Parse error: syntax error, unexpected 'match'"
**Causa:** PHP < 8.0 não suporta match()
**Solução:**
1. Atualizar para PHP 8.0+, OU
2. Substituir match() por switch:

```php
// Antes (PHP 8+)
$destino = match($_SESSION['tipo']) {
    'admin' => 'admin/dashboard.php',
    default => 'index.php'
};

// Depois (PHP 7.4)
switch($_SESSION['tipo']) {
    case 'admin':
        $destino = 'admin/dashboard.php';
        break;
    case 'profissional':
        $destino = 'profissional/dashboard.php';
        break;
    case 'recepcionista':
        $destino = 'recepcionista/dashboard.php';
        break;
    case 'cliente':
        $destino = 'cliente/dashboard.php';
        break;
    default:
        $destino = 'index.php';
}
```

### Erro: "SQLSTATE[HY000] [2002] No such file or directory"
**Causa:** Host do MySQL incorreto
**Solução:** Troque 'localhost' por '127.0.0.1' ou vice-versa

```php
// Tente isto:
$host = '127.0.0.1';
// Ou isto:
$host = 'localhost';
// Ou o host fornecido pela hospedagem:
$host = 'mysql.seudominio.com';
```

### Erro: "Headers already sent"
**Causa:** Espaços ou caracteres antes de <?php
**Solução:** Remova BOM e espaços no início dos arquivos

### Erro: "Cannot modify header information"
**Causa:** Output antes de header()
**Solução:** Use ob_start() no início dos arquivos

---

## 🚀 Script de Diagnóstico Automático

Crie este arquivo na raiz: `diagnostico.php`

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico do Sistema</h1>";
echo "<pre>";

// 1. Versão PHP
echo "1. PHP Version: " . phpversion() . "\n";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "   ❌ ERRO: PHP deve ser 7.4 ou superior!\n";
} else {
    echo "   ✅ OK\n";
}

// 2. Extensões
echo "\n2. Extensões PHP:\n";
$extensoes = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'openssl', 'json'];
foreach ($extensoes as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "   $status $ext\n";
}

// 3. Permissões
echo "\n3. Permissões:\n";
$arquivos = [
    'includes/db_connect.php',
    'includes/auth.php',
    'login.php',
    'index.php',
    'logs'
];
foreach ($arquivos as $arq) {
    if (file_exists($arq)) {
        $perms = substr(sprintf('%o', fileperms($arq)), -4);
        echo "   ✅ $arq ($perms)\n";
    } else {
        echo "   ❌ $arq (NÃO EXISTE)\n";
    }
}

// 4. Banco de dados
echo "\n4. Banco de Dados:\n";
try {
    require_once 'includes/db_connect.php';
    echo "   ✅ Conexão OK\n";

    // Testar query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total = $stmt->fetchColumn();
    echo "   ✅ Query OK ($total usuários)\n";
} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
}

// 5. Caminhos
echo "\n5. Caminhos:\n";
echo "   Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "   Script Path: " . __DIR__ . "\n";
echo "   Includes: " . (file_exists('includes') ? '✅ OK' : '❌ NÃO EXISTE') . "\n";

// 6. .htaccess
echo "\n6. .htaccess:\n";
echo "   " . (file_exists('.htaccess') ? '✅ Existe' : '⚠️ Não existe') . "\n";

// 7. mod_rewrite
echo "\n7. Apache mod_rewrite:\n";
if (function_exists('apache_get_modules')) {
    echo "   " . (in_array('mod_rewrite', apache_get_modules()) ? '✅ Ativo' : '❌ Inativo') . "\n";
} else {
    echo "   ⚠️ Não foi possível verificar\n";
}

echo "</pre>";
?>
```

Acesse: `http://seusite.com/diagnostico.php`

---

## 📞 Próximos Passos

1. **Execute o script de diagnóstico** acima
2. **Leia os logs de erro** do servidor
3. **Me envie**:
   - Resultado do diagnóstico.php
   - Últimas 20 linhas do error_log
   - Versão do PHP
   - Tipo de hospedagem (cPanel, Plesk, VPS)

Com essas informações, posso te ajudar a resolver o problema específico!

---

## 🔒 Lembre-se de Deletar

Após resolver o problema, **DELETE** estes arquivos:

```bash
rm diagnostico.php
rm teste_conexao.php
rm phpinfo.php
```

Eles expõem informações sensíveis do sistema!

---

**Última atualização:** 2026-01-16
