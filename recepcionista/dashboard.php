<?php
$titulo = 'Dashboard - Recepcionista';
require_once '../includes/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/utils.php';

// Apenas recepcionistas
requer_login('recepcionista');

$hoje = date('Y-m-d');

// Agendamentos de hoje
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM agendamentos
    WHERE data_agendamento = ?
      AND status != 'cancelado'
");
$stmt->execute([$hoje]);
$agendamentos_hoje = $stmt->fetch()['total'];

// Próximos agendamentos (próximas 2 horas)
$agora = date('Y-m-d H:i:s');
$daqui_2h = date('Y-m-d H:i:s', strtotime('+2 hours'));
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.hora_agendamento,
        COALESCE(u_cliente.nome, a.cliente_nome) as cliente_nome,
        u_prof.nome as profissional_nome
    FROM agendamentos a
    LEFT JOIN usuarios u_cliente ON u_cliente.id = a.cliente_id
    JOIN usuarios u_prof ON u_prof.id = a.profissional_id
    WHERE CONCAT(a.data_agendamento, ' ', a.hora_agendamento) BETWEEN ? AND ?
      AND a.status != 'cancelado'
    ORDER BY a.hora_agendamento ASC
    LIMIT 5
");
$stmt->execute([$agora, $daqui_2h]);
$proximos_agendamentos = $stmt->fetchAll();

// Aniversariantes do dia
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM (
        SELECT id FROM usuarios
        WHERE DATE_FORMAT(data_nascimento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
          AND tipo = 'cliente'
        UNION ALL
        SELECT id FROM clientes_rapidos
        WHERE DATE_FORMAT(data_nascimento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
    ) as aniversariantes
");
$stmt->execute();
$aniversariantes_hoje = $stmt->fetch()['total'];

// Total de clientes cadastrados
$stmt = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente') +
        (SELECT COUNT(*) FROM clientes_rapidos)
    as total_clientes
");
$total_clientes = $stmt->fetch()['total_clientes'];
?>

<div class="row">
    <!-- Card: Bem-vindo -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm bg-gradient-primary text-white">
            <div class="card-body">
                <h3 class="mb-1">
                    <i class="fas fa-user-tie me-2"></i>Olá, <?php echo htmlspecialchars(explode(' ', $_SESSION['nome'])[0]); ?>!
                </h3>
                <p class="mb-0">Bem-vindo(a) ao painel de recepção</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Card: Agendamentos Hoje -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Agendamentos Hoje</h6>
                        <h2 class="mb-0 text-primary"><?php echo $agendamentos_hoje; ?></h2>
                    </div>
                    <div class="text-primary" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
                <a href="view_agenda_geral.php" class="btn btn-sm btn-outline-primary mt-2 w-100">
                    Ver Agenda
                </a>
            </div>
        </div>
    </div>

    <!-- Card: Próximos (2h) -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card shadow-sm border-start border-4 border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Próximas 2 horas</h6>
                        <h2 class="mb-0 text-warning"><?php echo count($proximos_agendamentos); ?></h2>
                    </div>
                    <div class="text-warning" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <small class="text-muted">Agendamentos próximos</small>
            </div>
        </div>
    </div>

    <!-- Card: Aniversariantes -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card shadow-sm border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Aniversariantes Hoje</h6>
                        <h2 class="mb-0 text-success"><?php echo $aniversariantes_hoje; ?></h2>
                    </div>
                    <div class="text-success" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="fas fa-birthday-cake"></i>
                    </div>
                </div>
                <?php if ($aniversariantes_hoje > 0): ?>
                    <a href="aniversariantes.php" class="btn btn-sm btn-outline-success mt-2 w-100">
                        Ver Lista
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Card: Total Clientes -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total de Clientes</h6>
                        <h2 class="mb-0 text-info"><?php echo $total_clientes; ?></h2>
                    </div>
                    <div class="text-info" style="font-size: 2.5rem; opacity: 0.3;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <small class="text-muted">Cadastros no sistema</small>
            </div>
        </div>
    </div>
</div>

<!-- Próximos Agendamentos -->
<?php if (!empty($proximos_agendamentos)): ?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="mb-0">
                    <i class="fas fa-bell me-2"></i>Próximos Agendamentos (2 horas)
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($proximos_agendamentos as $ag): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($ag['cliente_nome']); ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($ag['profissional_nome']); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning text-dark" style="font-size: 1rem;">
                                    <i class="fas fa-clock me-1"></i><?php echo date('H:i', strtotime($ag['hora_agendamento'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Ações Rápidas -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Ações Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="agendar_centralizado.php" class="btn btn-lg btn-primary w-100">
                            <i class="fas fa-calendar-plus d-block mb-2" style="font-size: 2rem;"></i>
                            Novo Agendamento
                        </a>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <a href="view_agenda_geral.php" class="btn btn-lg btn-outline-primary w-100">
                            <i class="fas fa-calendar-alt d-block mb-2" style="font-size: 2rem;"></i>
                            Ver Agenda
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="aniversariantes.php" class="btn btn-lg btn-outline-success w-100">
                            <i class="fas fa-birthday-cake d-block mb-2" style="font-size: 2rem;"></i>
                            Aniversariantes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
