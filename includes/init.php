<?php
/**
 * INIT.PHP - Arquivo de Inicialização Global
 * Incluir no início de TODOS os arquivos PHP do projeto
 * 
 * USO:
 * require_once __DIR__ . '/includes/init.php';
 */

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Mostrar erros apenas em desenvolvimento
$isProduction = (getenv('APP_ENV') === 'production' || !getenv('APP_ENV'));
if (!$isProduction) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Diretório base
define('BASE_PATH', dirname(__DIR__));

// Carregar dependências principais
require_once __DIR__ . '/db_connect.php';

// FASE 1 - Segurança Básica
require_once __DIR__ . '/csrf_protection.php';
require_once __DIR__ . '/output_sanitization.php';
require_once __DIR__ . '/secure_upload.php';

// FASE 2 - Segurança Avançada
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/session_security.php';
require_once __DIR__ . '/input_validator.php';
require_once __DIR__ . '/error_handler.php';

// FASE 3 - Performance
require_once __DIR__ . '/cache_manager.php';
require_once __DIR__ . '/pagination.php';
require_once __DIR__ . '/flash_messages.php';

// FASE 4 - Qualidade
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/logger.php';

// Utils
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/auth.php';

// Repositories (se existir)
if (file_exists(__DIR__ . '/repositories/RepositoryFactory.php')) {
    require_once __DIR__ . '/repositories/RepositoryFactory.php';
}

// Registrar Error Handler
ErrorHandler::register();

// Inicializar Session Security
SessionSecurity::init();

// Validar sessão em páginas protegidas (exceto login, register, index)
$currentFile = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'register.php', 'index.php', 'reset_password.php', 'verify_email.php', 'verify.php'];

if (!in_array($currentFile, $publicPages)) {
    if (!SessionSecurity::validate()) {
        // Redirecionar para login se sessão inválida
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}
