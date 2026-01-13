<?php
// profissional/handle_agenda_action.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

$action = $_GET['action'] ?? '';
$item_id = $_GET['item_id'] ?? 0;

if (!$action || !$item_id) redirecionar_com_mensagem('view_agenda_dia.php', 'Parâmetros inválidos.', 'danger');

$stmt = $pdo->prepare("SELECT a.status FROM agendamento_itens ai JOIN agendamentos a ON a.id = ai.agendamento_id WHERE ai.id = ?");
$stmt->execute([$item_id]);
$status = $stmt->fetchColumn();

$novos_status = [
    'chegada' => 'confirmado',
    'iniciar' => 'em_atendimento'
];

if (!isset($novos_status[$action]) || $status !== ($action === 'chegada' ? 'agendado' : 'confirmado')) {
    redirecionar_com_mensagem('view_agenda_dia.php', 'Ação não permitida.', 'danger');
}

$pdo->prepare("UPDATE agendamentos a JOIN agendamento_itens ai ON ai.agendamento_id = a.id SET a.status = ? WHERE ai.id = ?")
    ->execute([$novos_status[$action], $item_id]);

redirecionar_com_mensagem('view_agenda_dia.php', ucfirst($action) . ' registrado!');
?>