<?php
// profissional/handle_add_atendimento.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('view_agenda_dia.php', 'Token inválido.', 'danger');
}

$item_id = $_POST['item_id'] ?? 0;
if (!$item_id) redirecionar_com_mensagem('view_agenda_dia.php', 'Item não encontrado.', 'danger');

$stmt = $pdo->prepare("
    SELECT s.preco, c.percentual AS comissao_perc, ai.profissional_id
    FROM agendamento_itens ai
    JOIN servicos s ON s.id = ai.servico_id
    LEFT JOIN comissoes c ON c.profissional_id = ai.profissional_id AND c.tipo = 'servico'
    WHERE ai.id = ?
");
$stmt->execute([$item_id]);
$dados = $stmt->fetch();

$comissao = $dados['comissao_perc'] ? ($dados['preco'] * $dados['comissao_perc'] / 100) : 0;

$pdo->prepare("
    INSERT INTO atendimentos (agendamento_item_id, valor_servico, comissao_servico)
    VALUES (?, ?, ?)
")->execute([$item_id, $dados['preco'], $comissao]);

$pdo->prepare("UPDATE agendamentos a JOIN agendamento_itens ai ON ai.agendamento_id = a.id SET a.status = 'finalizado' WHERE ai.id = ?")
    ->execute([$item_id]);

redirecionar_com_mensagem('view_agenda_dia.php', 'Atendimento finalizado com sucesso!');
?>