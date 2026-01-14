<?php
// admin/export_report.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('admin');

$mes = $_GET['mes'] ?? date('Y-m');
$inicio = $mes . '-01 00:00:00';
$fim = date('Y-m-t 23:59:59', strtotime($inicio));

// Cabeçalhos para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="relatorio_' . $mes . '.xls"');
header('Cache-Control: max-age=0');

echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
echo "<h2>Relatório Mensal - " . date('F/Y', strtotime($inicio)) . "</h2>";

// Faturamento
$faturamentoServicos = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM atendimentos WHERE data_atendimento BETWEEN ? AND ? AND status = 'concluido'")->execute([$inicio, $fim]) ? $pdo->fetchColumn() : 0;
$faturamentoProdutos = $pdo->prepare("SELECT COALESCE(SUM(valor_total), 0) FROM vendas_produtos WHERE data_venda BETWEEN ? AND ?")->execute([$inicio, $fim]) ? $pdo->fetchColumn() : 0;
$total = $faturamentoServicos + $faturamentoProdutos;

echo "<table border='1'><tr><th>Tipo</th><th>Valor</th></tr>";
echo "<tr><td>Serviços</td><td>R$ " . number_format($faturamentoServicos, 2, ',', '.') . "</td></tr>";
echo "<tr><td>Produtos</td><td>R$ " . number_format($faturamentoProdutos, 2, ',', '.') . "</td></tr>";
echo "<tr><td><strong>Total</strong></td><td><strong>R$ " . number_format($total, 2, ',', '.') . "</strong></td></tr>";
echo "</table><br>";

// Comissões
$comissoes = $pdo->prepare("
    SELECT u.nome, 
           COALESCE(SUM(a.comissao), 0) AS servicos,
           COALESCE(SUM(v.comissao_produto), 0) AS produtos,
           (COALESCE(SUM(a.comissao), 0) + COALESCE(SUM(v.comissao_produto), 0)) AS total
    FROM usuarios u
    LEFT JOIN atendimentos a ON a.profissional_id = u.id AND a.data_atendimento BETWEEN ? AND ? AND a.status = 'concluido'
    LEFT JOIN vendas_produtos v ON v.profissional_id = u.id AND v.data_venda BETWEEN ? AND ?
    WHERE u.tipo = 'profissional' AND u.ativo = 1
    GROUP BY u.id HAVING total > 0
    ORDER BY total DESC
")->execute([$inicio, $fim, $inicio, $fim]) ? $pdo->fetchAll() : [];

echo "<h3>Comissões a Pagar</h3>";
echo "<table border='1'><tr><th>Profissional</th><th>Serviços</th><th>Produtos</th><th>Total</th></tr>";
foreach ($comissoes as $c) {
    echo "<tr><td>{$c['nome']}</td><td>R$ " . number_format($c['servicos'], 2, ',', '.') . "</td><td>R$ " . number_format($c['produtos'], 2, ',', '.') . "</td><td>R$ " . number_format($c['total'], 2, ',', '.') . "</td></tr>";
}
echo "</table>";