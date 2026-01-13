<?php
// admin/manage_recommendations.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Moderar Avaliações";
requer_login('admin');


if ($_GET['aprovar'] ?? '') {
    $pdo->prepare("UPDATE recomendacoes SET aprovado = 1 WHERE id = ?")->execute([$_GET['aprovar']]);
}
if ($_GET['rejeitar'] ?? '') {
    $pdo->prepare("DELETE FROM recomendacoes WHERE id = ?")->execute([$_GET['rejeitar']]);
}

// Consulta com LEFT JOIN para permitir valores nulos ou dados deletados
$recs = $pdo->query("
    SELECT r.*, 
           u.nome AS cliente, 
           p.nome AS profissional, 
           s.nome AS servico 
    FROM recomendacoes r 
    LEFT JOIN usuarios u ON u.id = r.cliente_id 
    LEFT JOIN usuarios p ON p.id = r.profissional_id 
    LEFT JOIN servicos s ON s.id = r.servico_id 
    ORDER BY r.data_avaliacao DESC
")->fetchAll();
include '../includes/header.php';
?>
<h2>Avaliações</h2>
<div class="row row-cols-1 row-cols-md-2 g-4">
    <?php foreach ($recs as $r): ?>
    <div class="col">
        <div class="card">
            <div class="card-body">
                <p><strong><?php echo htmlspecialchars($r['cliente']); ?></strong> sobre <strong><?php echo htmlspecialchars($r['profissional']); ?></strong> - <?php echo htmlspecialchars($r['servico']); ?></p>
                <p class="text-warning">Nota: <?php echo str_repeat('★', $r['nota']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($r['comentario'])); ?></p>
                <small><?php echo formatar_data($r['data_avaliacao']); ?></small>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <?php if (!$r['aprovado']): ?>
                <a href="?aprovar=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">Aprovar</a>
                <a href="?rejeitar=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Rejeitar?')">Rejeitar</a>
                <?php else: ?>
                <span class="badge bg-success">Aprovado</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php include '../includes/footer.php'; ?>