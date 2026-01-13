<?php
// === ARQUIVO 1: api/get_servicos.php ===
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

$profissional_id = $_GET['profissional_id'] ?? 0;

if (!$profissional_id) {
    echo json_encode(['sucesso' => false, 'erro' => 'Profissional não informado']);
    exit;
}

try {
    // Buscar serviços do profissional (ou todos se não houver tabela profissional_servico)
    $has_table = $pdo->query("SHOW TABLES LIKE 'profissional_servico'")->rowCount() > 0;

    if ($has_table) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.nome, s.preco, s.duracao_min, s.categoria, s.descricao
            FROM servicos s
            JOIN profissional_servico ps ON ps.servico_id = s.id
            WHERE ps.profissional_id = ? AND s.ativo = 1
            ORDER BY s.categoria, s.nome
        ");
        $stmt->execute([$profissional_id]);
    } else {
        // Fallback: mostrar todos os serviços
        $stmt = $pdo->prepare("
            SELECT id, nome, preco, duracao_min, categoria, descricao
            FROM servicos
            WHERE ativo = 1
            ORDER BY categoria, nome
        ");
        $stmt->execute();
    }

    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar dados para o frontend
    $servicos_formatados = array_map(function($s) {
        return [
            'id' => (int)$s['id'],
            'nome' => $s['nome'],
            'preco' => (float)$s['preco'],
            'preco_formatado' => 'R$ ' . number_format($s['preco'], 2, ',', '.'),
            'duracao_min' => (int)$s['duracao_min'],
            'duracao_formatada' => $s['duracao_min'] . ' min',
            'categoria' => $s['categoria'] ?? 'Geral',
            'descricao' => $s['descricao'] ?? ''
        ];
    }, $servicos);

    echo json_encode([
        'sucesso' => true,
        'servicos' => $servicos_formatados,
        'total' => count($servicos_formatados)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao buscar serviços: ' . $e->getMessage()
    ]);
}
?>