<?php
// admin/manage_profissional_comissoes.php
require_once '../includes/auth.php';
require_once '../includes/utils.php';
require_once '../includes/db_connect.php';
$titulo = "Comissões";
requer_login('admin');


$prof_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT tipo, percentual FROM comissoes WHERE profissional_id = ?");
$stmt->execute([$prof_id]);
$comissoes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {

    $servico = isset($_POST['servico'])
        ? str_replace(',', '.', trim($_POST['servico']))
        : null;

    $produto = isset($_POST['produto'])
        ? str_replace(',', '.', trim($_POST['produto']))
        : null;

    // Verifica se já existe registro
    $stmt = $pdo->prepare("
        SELECT id FROM comissoes WHERE profissional_id = ?
    ");
    $stmt->execute([$prof_id]);
    $existe = $stmt->fetchColumn();

    if ($existe) {

        $campos = [];
        $valores = [];

        if ($servico !== '' && $servico !== null) {
            $campos[] = 'servico = ?';
            $valores[] = floatval($servico);
        }

        if ($produto !== '' && $produto !== null) {
            $campos[] = 'produto = ?';
            $valores[] = floatval($produto);
        }

        if ($campos) {
            $valores[] = $prof_id;
            $sql = "UPDATE comissoes SET " . implode(', ', $campos) . " WHERE profissional_id = ?";
            $pdo->prepare($sql)->execute($valores);
        }

    } else {

        $pdo->prepare("
            INSERT INTO comissoes (profissional_id, servico, produto)
            VALUES (?, ?, ?)
        ")->execute([
            $prof_id,
            ($servico !== '' ? floatval($servico) : null),
            ($produto !== '' ? floatval($produto) : null)
        ]);
    }

    redirecionar_com_mensagem(
        $_SERVER['PHP_SELF'] . '?id=' . $prof_id,
        'Comissões atualizadas!'
    );
}


include '../includes/header.php';


?>
<h2>Comissões do Profissional</h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label>Serviço (%)</label>
            <input type="number" name="servico" class="form-control" value="<?php echo $comissoes['servico'] ?? ''; ?>" placeholder="<?php //echo $comissoes['servico'];?>">
        </div>
        <div class="col-md-6">
            <label>Produto (%)</label>
            <input type="number" name="produto" class="form-control" value="<?php echo $comissoes['produto'] ?? ''; ?>" placeholder="<?php    //echo $comissoes['produto'] ?? '';?>">
        </div>
    </div>
    <button type="submit" class="btn btn-success mt-3">Salvar</button>
</form>
<?php include '../includes/footer.php'; ?>