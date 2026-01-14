<?php
// admin/manage_profissional_servicos.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Serviços do Profissional";
requer_login(tipo_necessario: 'admin');


$prof_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$prof_id]);
$prof = $stmt->fetch() ?: [];

$servicos = $pdo->query("SELECT id, nome FROM servicos WHERE ativo = 1")->fetchAll();

$stmt = $pdo->prepare("SELECT servico_id FROM profissional_servicos WHERE profissional_id = ?");
$stmt->execute([$prof_id]);
$habilitados = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $pdo->prepare("DELETE FROM profissional_servicos WHERE profissional_id = ?")->execute([$prof_id]);
    if (!empty($_POST['servicos'])) {
        $stmt = $pdo->prepare("INSERT INTO profissional_servicos (profissional_id, servico_id) VALUES (?, ?)");
        foreach ($_POST['servicos'] as $s_id) {
            $stmt->execute([$prof_id, $s_id]);
        }
    }
    redirecionar_com_mensagem($_SERVER['PHP_SELF'] . '?id=' . $prof_id, 'Serviços atualizados!');
}
include '../includes/header.php';
?>
<h2>Serviços Habilitados - <?php echo htmlspecialchars($prof['nome'] ?? 'Profissional'); ?></h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
    <div class="row row-cols-1 row-cols-md-3 g-3">
        <?php foreach ($servicos as $s): ?>
        <div class="col">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="servicos[]" value="<?php echo $s['id']; ?>" id="s<?php echo $s['id']; ?>"
                       <?php echo in_array($s['id'], $habilitados) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="s<?php echo $s['id']; ?>">
                    <?php echo htmlspecialchars($s['nome']); ?>
                </label>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Salvar</button>
</form>
<?php include '../includes/footer.php'; ?>