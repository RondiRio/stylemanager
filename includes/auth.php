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
    if ($tipo_necessario) {
        // Aceita string única ou array de tipos
        $tipos_permitidos = is_array($tipo_necessario) ? $tipo_necessario : [$tipo_necessario];
        if (!in_array($_SESSION['tipo'], $tipos_permitidos)) {
            header('Location: ../login.php');
            exit;
        }
    }
}

// Verifica se usuário tem permissão administrativa (admin ou recepcionista)
function tem_permissao_administrativa() {
    return esta_logado() && in_array($_SESSION['tipo'], ['admin', 'recepcionista']);
}

// Verifica se usuário é admin (permissões completas)
function e_admin() {
    return esta_logado() && $_SESSION['tipo'] === 'admin';
}

// Verifica se usuário é recepcionista
function e_recepcionista() {
    return esta_logado() && $_SESSION['tipo'] === 'recepcionista';
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