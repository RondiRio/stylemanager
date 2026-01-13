<?php
// cliente/cancelar_agendamento.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
require_once '../includes/email_sender.php';
requer_login('cliente');

$id = $_GET['id'] ?? 0;
if (!$id) redirecionar_com_mensagem('view_agendamentos.php', 'Agendamento não encontrado.', 'danger');

$stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = ? AND cliente_id = ?");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$ag = $stmt->fetch();

if (!$ag || $ag['status'] !== 'agendado') {
    redirecionar_com_mensagem('view_agendamentos.php', 'Não é possível cancelar.', 'danger');
}

$pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ?")->execute([$id]);

// E-mail de cancelamento
$cliente = $pdo->query("SELECT email FROM usuarios WHERE id = {$_SESSION['usuario_id']}")->fetch();
$corpo = "<p>Seu agendamento para " . formatar_data($ag['data']) . " às {$ag['hora_inicio']} foi cancelado.</p>";
enviar_email($cliente['email'], 'Agendamento Cancelado', $corpo);

redirecionar_com_mensagem('view_agendamentos.php', 'Agendamento cancelado com sucesso!');
?>