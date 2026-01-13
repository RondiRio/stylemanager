<?php
// verify.php - Verificação de E-mail
require_once 'includes/db_connect.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Token inválido.");
}

$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE token_verificacao = ? AND ativo = 0");
$stmt->execute([$token]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Token inválido ou conta já ativada.");
}

$pdo->prepare("UPDATE usuarios SET ativo = 1, token_verificacao = NULL WHERE id = ?")->execute([$usuario['id']]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conta Ativada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container text-center mt-5">
    <div class="alert alert-success">
        <h4>Parabéns, <?php echo htmlspecialchars($usuario['nome']); ?>!</h4>
        <p>Sua conta foi ativada com sucesso.</p>
        <a href="login.php" class="btn btn-primary">Fazer Login</a>
    </div>
</div>
</body>
</html>