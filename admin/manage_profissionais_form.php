<?php
// admin/manage_profissionais_form.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Profissional";
requer_login('admin');


$id = $_GET['id'] ?? null;
$prof = $id ? $pdo->query("SELECT * FROM usuarios WHERE id = $id")->fetch() : [];

if ($_POST) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : ($prof['senha'] ?? '');

    if ($id) {
        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?";
        $params = [$nome, $email, $telefone];
        if ($senha !== $prof['senha']) { $sql .= ", senha=?"; $params[] = $senha; }
        $sql .= " WHERE id=?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);
    } else {
        $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, tipo) VALUES (?, ?, ?, ?, 'profissional')")
            ->execute([$nome, $email, $senha, $telefone]);
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($ids) > 1) {
                // Remove the newly inserted record (assume it's the one with the greatest id)
                $novoId = max($ids);
                $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$novoId]);

                // Mensagem amigável e retorno ao formulário
                redirecionar_com_mensagem('manage_profissionais_form.php', 'O e-mail informado já está cadastrado.');
                exit;
            }
    }
    redirecionar_com_mensagem('manage_profissionais.php', 'Profissional salvo!');
}
include '../includes/header.php';
?>
<h2><?php echo $id ? 'Editar' : 'Novo'; ?> Profissional</h2>
<form method="POST">
    <div class="row g-3">
        <div class="col-md-6"><input type="text" name="nome" class="form-control" placeholder="Nome" value="<?php echo $prof['nome'] ?? ''; ?>" required></div>
        <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="E-mail" value="<?php echo $prof['email'] ?? ''; ?>" required></div>
        <div class="col-md-6"><input type="text" name="telefone" class="form-control" placeholder="Telefone" value="<?php echo $prof['telefone'] ?? ''; ?>"></div>
        <div class="col-md-6"><input type="password" name="senha" class="form-control" placeholder="Nova senha (deixe em branco para manter)"></div>
    </div>
    <button type="submit" class="btn btn-success mt-3">Salvar</button>
</form>
<?php include '../includes/footer.php'; ?> 