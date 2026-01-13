<?php
require_once 'db_connect.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (($_POST['acao'] ?? '') !== 'login') {
    header('Location: ..login.php?erro=acao');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    header('Location: ../login.php?erro=campos');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, nome, senha, tipo 
     FROM usuarios 
     WHERE email = ? AND ativo = 1"
);
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    header('Location: ../login.php?erro=login');
    exit;
}

// echo 'teste';
session_regenerate_id(true);
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nome']       = $usuario['nome'];
$_SESSION['tipo']       = $usuario['tipo'];

header("Location: ../{$usuario['tipo']}/dashboard.php");
exit;
