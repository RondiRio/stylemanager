<?php
// === ARQUIVO 3: handlers/handle_agendamento.php ===
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
require_once '../includes/email_sender.php';
requer_login('cliente');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('../cliente/agendar.php', 'Token inválido.', 'danger');
}
echo '<pre>';
print_r($_POST);
echo '</pre>';
$profissional_id = (int)($_POST['profissional_id'] ?? 0);
$data = $_POST['data'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$servicos_json = $_POST['servicos_json'] ?? '';


$servicos = json_decode($servicos_json, true);
$nomes_servicos = array_column($servicos, 'nome');

$nome_servico = $nomes_servicos[0];
// Validações
if (!$profissional_id || !$data || !$hora_inicio || !$servicos_json) {
    redirecionar_com_mensagem('../cliente/agendar.php', 'Dados incompletos.', 'danger');
}
echo $servicos_json;
$servicos = json_decode($servicos_json, true);
if (!is_array($servicos) || empty($servicos)) {
    redirecionar_com_mensagem('../cliente/agendar.php', 'Nenhum serviço selecionado.', 'danger');
}

// Verificar se profissional existe e está ativo
$stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ? AND tipo = 'profissional' AND ativo = 1");
$stmt->execute([$profissional_id]);
$profissional = $stmt->fetch();

if (!$profissional) {
    redirecionar_com_mensagem('../cliente/agendar.php', 'Profissional não encontrado.', 'danger');
}

// Calcular duração total
$duracao_total = array_reduce($servicos, function($sum, $s) {
    return $sum + (int)($s['duracao'] ?? 0);
}, 0);

// Calcular valor total (assumindo que cada serviço tem 'preco')
$valor_total = array_reduce($servicos, function($sum, $s) {
    return $sum + (float)($s['preco'] ?? 0);
}, 0);

$pdo->beginTransaction();
try {
    // Criar agendamento
    $stmt = $pdo->prepare("
        INSERT INTO agendamentos
        (cliente_id, profissional_id, data, hora_inicio, status)
        VALUES (?, ?, ?, ?, 'agendado')
    ");
    $stmt->execute([$_SESSION['usuario_id'], $profissional_id, $data, $hora_inicio]);
    $agendamento_id = $pdo->lastInsertId();

    // Inserir itens do agendamento na tabela agendamento_itens
    $stmt_item = $pdo->prepare("
        INSERT INTO agendamento_itens (agendamento_id, profissional_id, servico_id)
        VALUES (?, ?, ?)
    ");
    foreach ($servicos as $servico) {
        $stmt_item->execute([$agendamento_id, $profissional_id, $servico['id'] ?? 0]);
    }

    $pdo->commit();

    // Buscar dados do cliente com prepared statement
    $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        throw new Exception('Cliente não encontrado.');
    }

    // Formatar data e hora
    $data_formatada = date('d/m/Y', strtotime($data));
    $hora_formatada = substr($hora_inicio, 0, 5);
    
    $servicos_lista = implode(', ', array_column($servicos, 'nome'));
    
    $corpo = "
        <h3>Agendamento Confirmado!</h3>
        <p><strong>Cliente:</strong> {$cliente['nome']}</p>
        <p><strong>Profissional:</strong> {$profissional['nome']}</p>
        <p><strong>Data:</strong> {$data_formatada} às {$hora_formatada}</p>
        <p><strong>Serviços:</strong> {$servicos_lista}</p>
        <p><strong>Duração estimada:</strong> {$duracao_total} minutos</p>
        <p><strong>Valor total:</strong> R$ " . number_format($valor_total, 2, ',', '.') . "</p>
    ";

    if ($cliente['email']) {
        enviar_email($cliente['email'], 'Agendamento Confirmado', $corpo);
    }

    redirecionar_com_mensagem('../cliente/view_agendamentos.php', 'Agendamento realizado com sucesso!', 'success');

} catch (Exception $e) {
    $pdo->rollBack();
    redirecionar_com_mensagem('../cliente/agendar.php', 'Erro ao agendar: ' . $e->getMessage(), 'danger');
}