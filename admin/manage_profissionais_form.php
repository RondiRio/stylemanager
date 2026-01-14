<?php
// admin/manage_profissionais_form.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Profissional";
requer_login('admin');


$id = $_GET['id'] ?? null;
$prof = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $prof = $stmt->fetch() ?: [];
}

if ($_POST) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha_nova = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : null;

    if ($id && !empty($prof)) {
        // Atualizar profissional existente
        $sql = "UPDATE usuarios SET nome=?, email=?, telefone=?";
        $params = [$nome, $email, $telefone];

        // Se digitou nova senha, atualizar
        if ($senha_nova) {
            $sql .= ", senha=?";
            $params[] = $senha_nova;
        }

        $sql .= " WHERE id=?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);
    } else {
        // Criar novo profissional - senha é obrigatória
        if (!$senha_nova) {
            redirecionar_com_mensagem('manage_profissionais_form.php', 'Senha é obrigatória para novos profissionais.', 'danger');
            exit;
        }

        $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, tipo) VALUES (?, ?, ?, ?, 'profissional')")
            ->execute([$nome, $email, $senha_nova, $telefone]);
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