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
    $agendamento_ativo = isset($_POST['agendamento_ativo']) ? 1 : 0;
    $profissional_ve_agenda = isset($_POST['profissional_ve_agenda']) ? 1 : 0;

    // Dias de funcionamento
    $funciona_domingo = isset($_POST['funciona_domingo']) ? 1 : 0;
    $funciona_segunda = isset($_POST['funciona_segunda']) ? 1 : 0;
    $funciona_terca = isset($_POST['funciona_terca']) ? 1 : 0;
    $funciona_quarta = isset($_POST['funciona_quarta']) ? 1 : 0;
    $funciona_quinta = isset($_POST['funciona_quinta']) ? 1 : 0;
    $funciona_sexta = isset($_POST['funciona_sexta']) ? 1 : 0;
    $funciona_sabado = isset($_POST['funciona_sabado']) ? 1 : 0;

    // Fechamento de caixa
    $tipo_fechamento = $_POST['tipo_fechamento'] ?? 'mensal';
    $gorjetas_requerem_aprovacao = isset($_POST['gorjetas_requerem_aprovacao']) ? 1 : 0;

    // Rede social e agenda centralizada
    $permitir_cadastro_cliente = isset($_POST['permitir_cadastro_cliente']) ? 1 : 0;
    $mostrar_landing_page = isset($_POST['mostrar_landing_page']) ? 1 : 0;
    $agenda_centralizada_ativa = isset($_POST['agenda_centralizada_ativa']) ? 1 : 0;
    $lembrar_aniversarios = isset($_POST['lembrar_aniversarios']) ? 1 : 0;
    $agendamento_sem_profissional = isset($_POST['agendamento_sem_profissional']) ? 1 : 0;
    $profissional_ve_propria_agenda = isset($_POST['profissional_ve_propria_agenda']) ? 1 : 0;

    // Upload de logo
    if (!empty($_FILES['logo']['name'])) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nome = 'logo.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], '../assets/img/' . $nome);
        $pdo->prepare("UPDATE configuracoes SET logo = ? WHERE id = 1")->execute([$nome]);
    }

    $pdo->prepare("UPDATE configuracoes SET tipo_empresa=?, cor_primaria=?, cor_secundaria=?, cor_fundo=?, horario_abertura=?, horario_fechamento=?, intervalo_slot=?, prazo_cancelamento_horas=?, agendamento_ativo=?, profissional_ve_agenda=?, funciona_domingo=?, funciona_segunda=?, funciona_terca=?, funciona_quarta=?, funciona_quinta=?, funciona_sexta=?, funciona_sabado=?, tipo_fechamento=?, gorjetas_requerem_aprovacao=?, permitir_cadastro_cliente=?, mostrar_landing_page=?, agenda_centralizada_ativa=?, lembrar_aniversarios=?, agendamento_sem_profissional=?, profissional_ve_propria_agenda=? WHERE id=1")
        ->execute([$tipo, $primaria, $secundaria, $fundo, $abertura, $fechamento, $intervalo, $prazo, $agendamento_ativo, $profissional_ve_agenda, $funciona_domingo, $funciona_segunda, $funciona_terca, $funciona_quarta, $funciona_quinta, $funciona_sexta, $funciona_sabado, $tipo_fechamento, $gorjetas_requerem_aprovacao, $permitir_cadastro_cliente, $mostrar_landing_page, $agenda_centralizada_ativa, $lembrar_aniversarios, $agendamento_sem_profissional, $profissional_ve_propria_agenda]);

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

<div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
    <div>
        <i class="fas fa-database me-2"></i>
        <strong>Novo:</strong> Sistema de Agendamento Centralizado disponível! Aplique a migração do banco de dados.
    </div>
    <a href="apply_migration_centralized_scheduling.php" class="btn btn-warning btn-sm">
        <i class="fas fa-database me-1"></i>Aplicar Migração
    </a>
</div>
    <!-- Configurações de Agendamento -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Configurações de Agendamento</h6>
        </div>
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="agendamento_ativo" id="agendamento_ativo" <?php echo $config['agendamento_ativo'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="agendamento_ativo">
                    <strong>Permitir agendamento online</strong>
                    <br>
                    <small class="text-muted">Clientes poderão fazer agendamentos através do site</small>
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="profissional_ve_agenda" id="profissional_ve_agenda" <?php echo ($config['profissional_ve_agenda'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="profissional_ve_agenda">
                    <strong>Profissionais podem ver agenda</strong>
                    <br>
                    <small class="text-muted">Profissionais terão acesso à tela de agenda com seus agendamentos</small>
                </label>
            </div>
        </div>
    </div>

    <!-- Configurações de Dias de Funcionamento -->
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Dias de Funcionamento</h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle me-1"></i>
                Selecione os dias da semana em que a empresa funciona. Nos dias não selecionados, o sistema não permitirá agendamentos.
            </p>
            <div class="row">
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_domingo" id="funciona_domingo" <?php echo ($config['funciona_domingo'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_domingo">
                            <i class="fas fa-calendar-day me-1"></i><strong>Domingo</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_segunda" id="funciona_segunda" <?php echo ($config['funciona_segunda'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_segunda">
                            <i class="fas fa-calendar-day me-1"></i><strong>Segunda</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_terca" id="funciona_terca" <?php echo ($config['funciona_terca'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_terca">
                            <i class="fas fa-calendar-day me-1"></i><strong>Terça</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_quarta" id="funciona_quarta" <?php echo ($config['funciona_quarta'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_quarta">
                            <i class="fas fa-calendar-day me-1"></i><strong>Quarta</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_quinta" id="funciona_quinta" <?php echo ($config['funciona_quinta'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_quinta">
                            <i class="fas fa-calendar-day me-1"></i><strong>Quinta</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_sexta" id="funciona_sexta" <?php echo ($config['funciona_sexta'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_sexta">
                            <i class="fas fa-calendar-day me-1"></i><strong>Sexta</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="funciona_sabado" id="funciona_sabado" <?php echo ($config['funciona_sabado'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="funciona_sabado">
                            <i class="fas fa-calendar-day me-1"></i><strong>Sábado</strong>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configurações de Rede Social e Agenda -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Rede Social e Agenda Centralizada</h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle me-1"></i>
                Configure o acesso de clientes ao sistema e funcionalidades de agendamento centralizado.
            </p>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-plus me-1"></i><strong>Cadastro de Clientes</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permitir_cadastro_cliente" id="permitir_cadastro_cliente" <?php echo ($config['permitir_cadastro_cliente'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="permitir_cadastro_cliente">
                            <strong>Permitir cadastro de clientes</strong>
                            <br>
                            <small class="text-muted">Quando ativo, clientes podem se cadastrar no sistema. Quando desativo, apenas admin/recepcionista podem cadastrar clientes.</small>
                        </label>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-home me-1"></i><strong>Landing Page</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="mostrar_landing_page" id="mostrar_landing_page" <?php echo ($config['mostrar_landing_page'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostrar_landing_page">
                            <strong>Mostrar landing page</strong>
                            <br>
                            <small class="text-muted">Exibe página inicial para visitantes não logados com informações do salão.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar-check me-1"></i><strong>Agenda Centralizada</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="agenda_centralizada_ativa" id="agenda_centralizada_ativa" <?php echo ($config['agenda_centralizada_ativa'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="agenda_centralizada_ativa">
                            <strong>Ativar agenda centralizada</strong>
                            <br>
                            <small class="text-muted">Permite admin e recepcionista agendarem clientes de forma centralizada. Aparecerá na agenda geral.</small>
                        </label>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-birthday-cake me-1"></i><strong>Aniversários</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="lembrar_aniversarios" id="lembrar_aniversarios" <?php echo ($config['lembrar_aniversarios'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="lembrar_aniversarios">
                            <strong>Lembretes de aniversário</strong>
                            <br>
                            <small class="text-muted">Sistema notificará sobre aniversariantes do dia/mês.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-tag me-1"></i><strong>Agendamento Sem Profissional</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="agendamento_sem_profissional" id="agendamento_sem_profissional" <?php echo ($config['agendamento_sem_profissional'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="agendamento_sem_profissional">
                            <strong>Permitir agendamento genérico</strong>
                            <br>
                            <small class="text-muted">Quando ativo, não é obrigatório selecionar profissional específico. Cliente será atendido por quem estiver disponível.</small>
                        </label>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-eye me-1"></i><strong>Agenda do Profissional</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="profissional_ve_propria_agenda" id="profissional_ve_propria_agenda" <?php echo ($config['profissional_ve_propria_agenda'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="profissional_ve_propria_agenda">
                            <strong>Profissional pode visualizar sua agenda</strong>
                            <br>
                            <small class="text-muted">Quando ativo, profissionais podem ver seus agendamentos, mas SEM poder registrar atendimentos.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mb-0">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Dica:</strong> Mesmo com cadastro de clientes desativado, você pode usar "Clientes Rápidos" na agenda centralizada para registrar atendimentos sem precisar criar conta completa.
            </div>
        </div>
    </div>

    <!-- Configurações de Fechamento de Caixa -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="fas fa-cash-register me-2"></i>Fechamento de Caixa</h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle me-1"></i>
                Configure o período de fechamento de caixa e controle de gorjetas/vales dos profissionais.
            </p>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tipo_fechamento" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i><strong>Período de Fechamento</strong>
                    </label>
                    <select name="tipo_fechamento" id="tipo_fechamento" class="form-select">
                        <option value="diario" <?php echo ($config['tipo_fechamento'] ?? 'mensal') == 'diario' ? 'selected' : ''; ?>>Diário</option>
                        <option value="semanal" <?php echo ($config['tipo_fechamento'] ?? 'mensal') == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                        <option value="quinzenal" <?php echo ($config['tipo_fechamento'] ?? 'mensal') == 'quinzenal' ? 'selected' : ''; ?>>Quinzenal</option>
                        <option value="mensal" <?php echo ($config['tipo_fechamento'] ?? 'mensal') == 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                    </select>
                    <small class="text-muted">Define o período para fechamento e pagamento dos profissionais</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-coins me-1"></i><strong>Controle de Gorjetas</strong>
                    </label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="gorjetas_requerem_aprovacao" id="gorjetas_requerem_aprovacao" <?php echo ($config['gorjetas_requerem_aprovacao'] ?? 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="gorjetas_requerem_aprovacao">
                            <strong>Gorjetas requerem aprovação</strong>
                            <br>
                            <small class="text-muted">Quando ativo, você deve aprovar ou negar cada gorjeta registrada</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mb-0">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Informação:</strong> Os vales sempre requerem aprovação do administrador. Use a tela de "Aprovar Vales" para gerenciá-los.
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success mt-3">
        <i class="fas fa-save me-2"></i>Salvar Configurações
    </button>
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