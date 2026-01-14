<?php
// cliente/handle_add_recomendacao.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('cliente');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('view_agendamentos.php', 'Token inválido.', 'danger');
}

$agendamento_id = $_POST['agendamento_id'] ?? 0;
$nota = $_POST['nota'] ?? 0;
$comentario = trim($_POST['comentario'] ?? '');

if ($nota < 1 || $nota > 5 || !$agendamento_id) {
    redirecionar_com_mensagem('view_agendamentos.php', 'Dados inválidos.', 'danger');
}

$stmt = $pdo->prepare("
    SELECT a.profissional_id
    FROM agendamentos a
    WHERE a.id = ? AND a.cliente_id = ? AND a.status = 'finalizado'
    LIMIT 1
");
$stmt->execute([$agendamento_id, $_SESSION['usuario_id']]);
$item = $stmt->fetch();

if (!$item) {
    redirecionar_com_mensagem('view_agendamentos.php', 'Atendimento não finalizado.', 'danger');
}

$pdo->prepare("
INSERT INTO recomendacoes
(cliente_id, profissional_id, nota, comentario)
VALUES (?, ?, ?, ?)

")->execute([
    $_SESSION['usuario_id'],
    $item['profissional_id'],
    $nota,
    $comentario
]);

redirecionar_com_mensagem('view_agendamentos.php', 'Obrigado pela avaliação!');
?>

