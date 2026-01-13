<?php
// admin/manage_services_form.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Formulário de Serviço";
requer_login('admin');


$id = $_GET['id'] ?? null;
$servico = $id ? $pdo->query("SELECT * FROM servicos WHERE id = $id")->fetch() : [];

if ($_POST) {
    $nome = $_POST['nome'];
    $duracao = $_POST['duracao_min'];
    $preco = $_POST['preco'];
    $categoria = $_POST['categoria'];

    if ($id) {
        $pdo->prepare("UPDATE servicos SET nome=?, duracao_min=?, preco=?, categoria=? WHERE id=?")
            ->execute([$nome, $duracao, $preco, $categoria, $id]);
    } else {
        $pdo->prepare("INSERT INTO servicos (nome, duracao_min, preco, categoria) VALUES (?, ?, ?, ?)")
            ->execute([$nome, $duracao, $preco, $categoria]);
    }
    redirecionar_com_mensagem('manage_services.php', 'Serviço salvo!');
}
include '../includes/header.php'; 
?>
<h2><?php echo $id ? 'Editar' : 'Novo'; ?> Serviço</h2>
<form method="POST">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Nome</label>
            <input type="text" name="nome" class="form-control" value="<?php echo $servico['nome'] ?? ''; ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label>Duração (min)</label>
            <input type="number" name="duracao_min" class="form-control" value="<?php echo $servico['duracao_min'] ?? ''; ?>" required>
        </div>
        <div class="col-md-3 mb-3">
            <label>Preço</label>
            <input type="number" step="0.01" name="preco" class="form-control" value="<?php echo $servico['preco'] ?? ''; ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label>Categoria</label>
        <select name="categoria" class="form-select" required>
            <option value="barbearia" <?php if(($servico['categoria']??'')=='barbearia') echo 'selected'; ?>>Barbearia</option>
            <option value="cabelo" <?php if(($servico['categoria']??'')=='cabelo') echo 'selected'; ?>>Cabelo</option>
            <option value="unhas" <?php if(($servico['categoria']??'')=='unhas') echo 'selected'; ?>>Unhas</option>
            <option value="estetica" <?php if(($servico['categoria']??'')=='estetica') echo 'selected'; ?>>Estética</option>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Salvar</button>
    <a href="manage_services.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php include '../includes/footer.php'; ?>