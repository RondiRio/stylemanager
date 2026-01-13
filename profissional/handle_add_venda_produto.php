<?php
// profissional/handle_add_venda_produto.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('view_agenda_dia.php', 'Token inválido.', 'danger');
}

$produtos = $_POST['produtos'] ?? [];
if (empty($produtos)) redirecionar_com_mensagem('view_agenda_dia.php', 'Nenhum produto selecionado.');

foreach ($produtos as $prod_id => $dados) {
    $qtd = (int)($dados['qtd'] ?? 0);
    $preco_unit = str_replace(['R$ ', '.'], ['', ''], $dados['preco']);
    $preco_unit = str_replace(',', '.', $preco_unit);
    if ($qtd <= 0) continue;

    $valor_total = $preco_unit * $qtd;
    $stmt = $pdo->prepare("SELECT percentual FROM comissoes WHERE profissional_id = ? AND tipo = 'produto'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $comissao_perc = $stmt->fetchColumn() ?: 0;
    $comissao = $valor_total * $comissao_perc / 100;

    $pdo->prepare("
        INSERT INTO vendas_produto (profissional_id, produto_id, quantidade, valor_total, comissao_produto)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$_SESSION['usuario_id'], $prod_id, $qtd, $valor_total, $comissao]);
}

redirecionar_com_mensagem('view_agenda_dia.php', 'Venda(s) registrada(s) com sucesso!');
?>