<?php
// profissional/handle_add_vale.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
requer_login('profissional');

if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
    $motivo = $_POST['motivo'];
    $data = $_POST['data'];

    $pdo->prepare("INSERT INTO vales (profissional_id, valor, motivo, data_vale) VALUES (?, ?, ?, ?)")
        ->execute([$_SESSION['usuario_id'], $valor, $motivo, $data]);

    redirecionar_com_mensagem('dashboard.php', 'Vale registrado!');
}
?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
    <input type="text" name="valor" placeholder="Valor" required>
    <input type="text" name="motivo" placeholder="Motivo">
    <input type="date" name="data" required>
    <button type="submit">Registrar Vale</button>
</form>