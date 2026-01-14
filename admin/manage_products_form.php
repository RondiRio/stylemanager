<?php
// admin/manage_products_form.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Produto";
requer_login('admin');


$id = $_GET['id'] ?? null;
$prod = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch() ?: [];
}

if ($_POST) {
    $nome = $_POST['nome'] ?? '';
    $preco = str_replace(',', '.', $_POST['preco'] ?? '0');

    if ($id) {
        $pdo->prepare("UPDATE produtos SET nome=?, preco_venda=? WHERE id=?")->execute([$nome, $preco, $id]);
    } else {
        $pdo->prepare("INSERT INTO produtos (nome, preco_venda) VALUES (?, ?)")->execute([$nome, $preco]);
    }
    redirecionar_com_mensagem('manage_products.php', 'Produto salvo!');
}
include '../includes/header.php';
?>
<h2><?php echo $id ? 'Editar' : 'Novo'; ?> Produto</h2>
<form method="POST">
    <input type="text" name="nome" class="form-control mb-3" placeholder="Nome" value="<?php echo $prod['nome'] ?? ''; ?>" required>
    <input type="text" name="preco" class="form-control" placeholder="Preço (ex: 29,90)" value="<?php echo $prod['preco_venda'] ?? ''; ?>" required>
    <button type="submit" class="btn btn-success mt-3">Salvar</button>
</form>
<?php include '../includes/footer.php'; ?>