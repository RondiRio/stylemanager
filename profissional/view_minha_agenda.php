<?php
/**
 * Agenda View-Only para Profissionais
 * Profissionais podem ver seus agendamentos mas não podem editar
 */
$titulo = 'Minha Agenda';
require_once '../includes/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/utils.php';

// Apenas profissionais
requer_login('profissional');

// Verificar se profissional pode ver agenda
$config = $pdo->query("SELECT profissional_ve_propria_agenda FROM configuracoes WHERE id = 1")->fetch();
if (!$config || !($config['profissional_ve_propria_agenda'] ?? 0)) {
    $_SESSION['flash'] = ['tipo' => 'warning', 'msg' => 'Acesso à agenda não está disponível'];
    header('Location: dashboard.php');
    exit;
}

$data = $_GET['data'] ?? date('Y-m-d');
$profissional_id = $_SESSION['usuario_id'];

// Buscar agendamentos do profissional
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.data_agendamento,
        a.hora_agendamento,
        a.status,
        a.observacoes,
        COALESCE(u.nome, a.cliente_nome) as cliente_nome,
        COALESCE(u.telefone, a.cliente_telefone) as cliente_telefone
    FROM agendamentos a
    LEFT JOIN usuarios u ON u.id = a.cliente_id
    WHERE a.profissional_id = ?
      AND a.data_agendamento = ?
      AND a.status != 'cancelado'
    ORDER BY a.hora_agendamento
");
$stmt->execute([$profissional_id, $data]);
$agendamentos = $stmt->fetchAll();

// Estatísticas do dia
$total = count($agendamentos);
$confirmados = count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado'));
$concluidos = count(array_filter($agendamentos, fn($a) => $a['status'] === 'concluido'));

$status_map = [
    'agendado' => ['badge' => 'bg-info', 'texto' => 'Agendado', 'icone' => 'clock'],
    'confirmado' => ['badge' => 'bg-primary', 'texto' => 'Confirmado', 'icone' => 'check'],
    'em_atendimento' => ['badge' => 'bg-warning', 'texto' => 'Em Atendimento', 'icone' => 'scissors'],
    'concluido' => ['badge' => 'bg-success', 'texto' => 'Concluído', 'icone' => 'check-circle'],
    'cancelado' => ['badge' => 'bg-danger', 'texto' => 'Cancelado', 'icone' => 'times-circle']
];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Minha Agenda
                </h4>
                <small>Visualização dos seus agendamentos</small>
            </div>
        </div>
    </div>
</div>

<!-- Navegação -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <?php
                            $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                            $dia_semana = $dias_semana[date('w', strtotime($data))];
                            echo $dia_semana . ', ' . date('d/m/Y', strtotime($data));
                            ?>
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="row g-2 justify-content-md-end">
                            <div class="col-auto">
                                <a href="?data=<?php echo date('Y-m-d', strtotime($data . ' -1 day')); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="data" class="form-control" value="<?php echo $data; ?>" onchange="this.form.submit()">
                            </div>
                            <div class="col-auto">
                                <a href="?data=<?php echo date('Y-m-d', strtotime($data . ' +1 day')); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                            <div class="col-auto">
                                <a href="?data=<?php echo date('Y-m-d'); ?>" class="btn btn-primary">
                                    <i class="fas fa-calendar-day"></i> Hoje
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h2 class="text-primary mb-1"><?php echo $total; ?></h2>
                <small class="text-muted">Total de Agendamentos</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-info">
            <div class="card-body">
                <h2 class="text-info mb-1"><?php echo $confirmados; ?></h2>
                <small class="text-muted">Confirmados</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-success">
            <div class="card-body">
                <h2 class="text-success mb-1"><?php echo $concluidos; ?></h2>
                <small class="text-muted">Concluídos</small>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Agendamentos -->
<?php if (empty($agendamentos)): ?>
    <div class="card shadow-sm text-center py-5">
        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhum agendamento para este dia</h5>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($agendamentos as $agendamento):
            $status_info = $status_map[$agendamento['status']] ?? $status_map['agendado'];
        ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <!-- Horário -->
                        <div class="col-md-2 text-center border-end">
                            <h3 class="text-primary mb-0">
                                <?php echo date('H:i', strtotime($agendamento['hora_agendamento'])); ?>
                            </h3>
                            <small class="text-muted">Horário</small>
                        </div>

                        <!-- Cliente -->
                        <div class="col-md-4">
                            <h6 class="mb-1">
                                <i class="fas fa-user me-2 text-primary"></i>
                                <?php echo htmlspecialchars($agendamento['cliente_nome']); ?>
                            </h6>
                            <?php if ($agendamento['cliente_telefone']): ?>
                                <p class="mb-0 small text-muted">
                                    <i class="fas fa-phone me-1"></i>
                                    <?php echo htmlspecialchars($agendamento['cliente_telefone']); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Observações -->
                        <div class="col-md-4">
                            <?php if ($agendamento['observacoes']): ?>
                                <p class="mb-0 small">
                                    <i class="fas fa-comment me-1 text-muted"></i>
                                    <?php echo htmlspecialchars($agendamento['observacoes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-md-2 text-center">
                            <span class="badge <?php echo $status_info['badge']; ?>">
                                <i class="fas fa-<?php echo $status_info['icone']; ?> me-1"></i>
                                <?php echo $status_info['texto']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Informação:</strong> Esta é uma visualização somente leitura. Você não pode editar ou registrar atendimentos.
    Entre em contato com o administrador para alterações.
</div>

<?php require_once '../includes/footer.php'; ?>
