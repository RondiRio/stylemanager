<?php
// admin/manage_services.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';

$titulo = "Gerenciar Serviços";
requer_login('admin');


if ($_GET['delete'] ?? '') {
    $pdo->prepare("UPDATE servicos SET ativo = 0 WHERE id = ?")->execute([$_GET['delete']]);
    redirecionar_com_mensagem('manage_services.php', 'Serviço desativado.');
}

$servicos = $pdo->query("SELECT * FROM servicos ORDER BY categoria, nome")->fetchAll();
include '../includes/header.php';
?>
<h2>Serviços</h2>
<a href="manage_services_form.php" class="btn btn-primary mb-3">Novo Serviço</a>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Duração</th>
            <th>Preço</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($servicos as $s): if (!$s['ativo']) continue; ?>
        <tr>
            <td><?php echo htmlspecialchars($s['nome']); ?></td>
            <td><?php echo ucfirst($s['categoria']); ?></td>
            <td><?php echo $s['duracao_min']; ?> min</td>
            <td><?php echo formatar_moeda($s['preco']); ?></td>
            <td>
                <a href="manage_services_form.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Desativar?')">Desativar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '../includes/footer.php'; ?>