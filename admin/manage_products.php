<?php
// admin/manage_products.php
require_once '../includes/auth.php';
require_once '../includes/utils.php';
require_once '../includes/db_connect.php';
$titulo = "Gerenciar Produtos";
requer_login('admin');


if ($_GET['delete'] ?? '') {
    $pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = ?")->execute([$_GET['delete']]);
}

$prods = $pdo->query("SELECT * FROM produtos WHERE ativo = 1")->fetchAll();
include '../includes/header.php';
?>
<h2>Produtos</h2>
<a href="manage_products_form.php" class="btn btn-primary mb-3">Novo Produto</a>
<table class="table">
    <thead><tr><th>Nome</th><th>Preço</th><th>Ações</th></tr></thead>
    <tbody>
        <?php foreach ($prods as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['nome']); ?></td>
            <td><?php echo formatar_moeda($p['preco_venda']); ?></td>
            <td>
                <a href="manage_products_form.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Desativar?')">Desativar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>