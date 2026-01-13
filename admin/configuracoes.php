<?php
// admin/configuracoes.php (já enviado anteriormente, mas atualizado com logo)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
require_once '../includes/theme.php';
$titulo = "Configurações";
requer_login('admin');


if ($_POST) {
    $tipo = $_POST['tipo_empresa'];
    $primaria = $_POST['cor_primaria'];
    $secundaria = $_POST['cor_secundaria'];
    $fundo = $_POST['cor_fundo'];
    $abertura = $_POST['abertura'];
    $fechamento = $_POST['fechamento'];
    $intervalo = $_POST['intervalo'];
    $prazo = $_POST['prazo_cancelamento'];

    // Upload de logo
    if (!empty($_FILES['logo']['name'])) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nome = 'logo.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], '../assets/img/' . $nome);
        $pdo->prepare("UPDATE configuracoes SET logo = ? WHERE id = 1")->execute([$nome]);
    }

    $pdo->prepare("UPDATE configuracoes SET tipo_empresa=?, cor_primaria=?, cor_secundaria=?, cor_fundo=?, horario_abertura=?, horario_fechamento=?, intervalo_slot=?, prazo_cancelamento_horas=? WHERE id=1")
        ->execute([$tipo, $primaria, $secundaria, $fundo, $abertura, $fechamento, $intervalo, $prazo]);

    redirecionar_com_mensagem('configuracoes.php', 'Configurações salvas!');
}

$config = $pdo->query("SELECT * FROM configuracoes WHERE id=1")->fetch();

include '../includes/header.php';
?>
<h2>Configurações Gerais</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Tipo de Empresa</label>
            <select name="tipo_empresa" class="form-select" onchange="atualizarCoresPadrao(this.value)">
                <?php foreach(['barbearia','salao','manicure','estetica'] as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $config['tipo_empresa']==$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8 mb-3">
            <label>Logo</label><br>
            <?php if ($config['logo'] ?? ''): ?>
            <img src="../assets/img/<?php echo $config['logo']; ?>" alt="Logo" style="height:50px; margin-bottom:10px;">
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4"><input type="color" name="cor_primaria" value="<?php echo $config['cor_primaria']; ?>" class="form-control form-control-color"></div>
        <div class="col-md-4"><input type="color" name="cor_secundaria" value="<?php echo $config['cor_secundaria']; ?>" class="form-control form-control-color"></div>
        <div class="col-md-4"><input type="color" name="cor_fundo" value="<?php echo $config['cor_fundo']; ?>" class="form-control form-control-color"></div>
    </div>
    <div class="row mt-3">
        <div class="col-md-3">
        <label for="">Abertura</label>    
        <input type="time" name="abertura" value="<?php echo $config['horario_abertura']; ?>" class="form-control"></div>
        <div class="col-md-3">
            <label for="">fechamento</label>
            <input type="time" name="fechamento" value="<?php echo $config['horario_fechamento']; ?>" class="form-control"></div>
        <div class="col-md-3">
            <label for="">intervalo</label>
            <input type="number" name="intervalo" value="<?php echo $config['intervalo_slot']; ?>" class="form-control" placeholder="Slot (min)"></div>
        <div class="col-md-3">
            <label for="">prazo para cancelamento</label>
            <input type="number" name="prazo_cancelamento" value="<?php echo $config['prazo_cancelamento_horas']; ?>" class="form-control" placeholder="Cancel. (h)"></div>
    </div>
    <br>
<!-- Adicione este botão no topo da página admin/configuracoes.php -->
<div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
    <div>
        <i class="fas fa-info-circle me-2"></i>
        <strong>Segurança:</strong> Altere sua senha regularmente para manter sua conta protegida.
    </div>
    <a href="alterar_senha.php" class="btn btn-primary btn-sm">
        <i class="fas fa-key me-1"></i>Alterar Senha
    </a>
</div>

<div class="alert alert-dark d-flex justify-content-between align-items-center mb-4">
    <div>
        <i class="fas fa-info-circle me-2"></i>
        <strong>E-mail:</strong> Configure o envio de E-mail para seus clientes.
    </div>
    <a href="configuracoes_email.php" class="btn btn-primary btn-sm">
        <i class="fas fa-key me-1"></i>Configurar E-mail
    </a>
</div>
    <!-- // admin/configuracoes.php -->
    <label>
        <input type="checkbox" name="agendamento_ativo" <?php echo $config['agendamento_ativo'] ? 'checked' : ''; ?>>
        Permitir agendamento online
    </label>
    <button type="submit" class="btn btn-success mt-3">Salvar</button>
</form>

<script>
function atualizarCoresPadrao(tipo) {
    const padroes = {
        barbearia: {p: '#1a1a1a', s: '#d4af37', f: '#f5f5f5'},
        salao:     {p: '#6d4c41', s: '#f06292', f: '#fff8f6'},
        manicure:  {p: '#e91e63', s: '#4a148c', f: '#fce4ec'},
        estetica:  {p: '#1de9b6', s: '#004d40', f: '#e0f2f1'}
    };
    const c = padroes[tipo];
    document.querySelector('[name="cor_primaria"]').value = c.p;
    document.querySelector('[name="cor_secundaria"]').value = c.s;
    document.querySelector('[name="cor_fundo"]').value = c.f;
}
</script>
<?php include '../includes/footer.php'; ?>