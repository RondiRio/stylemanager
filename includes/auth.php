<?php
// includes/auth.php
session_start();
require_once 'db_connect.php';

function esta_logado() {
    return isset($_SESSION['usuario_id']);
}

function requer_login($tipo_necessario = null) {
    if (!esta_logado()) {
        header('Location: ../login.php');
        exit;
    }
    if ($tipo_necessario && $_SESSION['tipo'] !== $tipo_necessario) {
        header('Location: ../login.php');
        exit;
    }
}

function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Proteção CSRF
function gerar_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificar_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
?>