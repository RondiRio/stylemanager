<?php
// admin/view_agenda_geral.php - Agenda de todos os profissionais
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Agenda Geral";
requer_login('admin');

$data = $_GET['data'] ?? date('Y-m-d');
$profissional_id = $_GET['profissional_id'] ?? '';

// Buscar todos os profissionais
$profissionais = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'profissional' AND ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Buscar agendamentos
$query = "
    SELECT
        a.id AS agendamento_id,
        a.data,
        a.hora_inicio,
        a.hora_fim,
        a.status,
        GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') AS servicos,
        SUM(s.preco) AS valor_total,
        SUM(s.duracao_min) AS duracao_total,
        u.nome AS cliente_nome,
        u.telefone AS cliente_telefone,
        u.email AS cliente_email,
        u.avatar AS cliente_avatar,
        p.nome AS profissional_nome,
        p.avatar AS profissional_avatar
    FROM agendamentos a
    JOIN agendamento_itens ai ON ai.agendamento_id = a.id
    JOIN servicos s ON s.id = ai.servico_id
    LEFT JOIN usuarios u ON u.id = a.cliente_id
    LEFT JOIN usuarios p ON p.id = a.profissional_id
    WHERE DATE(a.data) = ?
";

$params = [$data];

if (!empty($profissional_id)) {
    $query .= " AND a.profissional_id = ?";
    $params[] = $profissional_id;
}

$query .= " GROUP BY a.id ORDER BY a.hora_inicio";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas do dia
$total_agendamentos = count($agendamentos);
$faturamento_previsto = array_sum(array_column($agendamentos, 'valor_total'));
$agendamentos_concluidos = count(array_filter($agendamentos, fn($a) => $a['status'] === 'finalizado'));
$agendamentos_pendentes = count(array_filter($agendamentos, fn($a) => $a['status'] === 'agendado'));

// Mapa de status
$status_map = [
    'agendado' => ['badge' => 'bg-info', 'texto' => 'Agendado', 'icone' => 'clock'],
    'confirmado' => ['badge' => 'bg-primary', 'texto' => 'Confirmado', 'icone' => 'check'],
    'em_atendimento' => ['badge' => 'bg-warning', 'texto' => 'Em Atendimento', 'icone' => 'scissors'],
    'finalizado' => ['badge' => 'bg-success', 'texto' => 'Finalizado', 'icone' => 'check-circle'],
    'cancelado' => ['badge' => 'bg-danger', 'texto' => 'Cancelado', 'icone' => 'times-circle']
];

include '../includes/header.php';
?>

<div class="animate-fade-in">
    <!-- HEADER COM NAVEGAÇÃO -->
    <div class="card-glass mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-0">
                        <i class="fas fa-calendar-week text-primary me-2"></i>
                        Agenda Geral
                    </h3>
                    <p class="text-muted mb-0 mt-1">
                        <?php
                        $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                        $dia_semana = $dias_semana[date('w', strtotime($data))];
                        echo $dia_semana . ', ' . date('d/m/Y', strtotime($data));
                        ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="row g-2 justify-content-md-end mt-3 mt-md-0">
                        <div class="col-auto">
                            <select name="profissional_id" class="form-select">
                                <option value="">Todos os Profissionais</option>
                                <?php foreach ($profissionais as $prof): ?>
                                <option value="<?php echo $prof['id']; ?>" <?php echo $profissional_id == $prof['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prof['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <input type="date" name="data" class="form-control" value="<?php echo $data; ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.href='?data=' + new Date().toISOString().split('T')[0]">
                                <i class="fas fa-calendar-day"></i> Hoje
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTATÍSTICAS DO DIA -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card-glass text-center" style="border-left: 4px solid #3b82f6;">
                <div class="card-body py-3">
                    <h2 class="mb-1 text-primary"><?php echo $total_agendamentos; ?></h2>
                    <small class="text-muted">Total de Agendamentos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card-glass text-center" style="border-left: 4px solid #10b981;">
                <div class="card-body py-3">
                    <h2 class="mb-1 text-success"><?php echo formatar_moeda($faturamento_previsto); ?></h2>
                    <small class="text-muted">Faturamento Previsto</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card-glass text-center" style="border-left: 4px solid #22c55e;">
                <div class="card-body py-3">
                    <h2 class="mb-1 text-success"><?php echo $agendamentos_concluidos; ?></h2>
                    <small class="text-muted">Finalizados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card-glass text-center" style="border-left: 4px solid #f59e0b;">
                <div class="card-body py-3">
                    <h2 class="mb-1 text-warning"><?php echo $agendamentos_pendentes; ?></h2>
                    <small class="text-muted">Pendentes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- LISTA DE AGENDAMENTOS -->
    <?php if (empty($agendamentos)): ?>
        <div class="card-glass text-center py-5">
            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhum agendamento para este dia</h5>
            <p class="text-muted mb-0">Selecione outra data ou profissional</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($agendamentos as $agendamento):
                $status_info = $status_map[$agendamento['status']] ?? $status_map['agendado'];
            ?>
            <div class="col-12">
                <div class="card-glass hover-lift">
                    <div class="card-body">
                        <div class="row">
                            <!-- Profissional -->
                            <div class="col-md-2 border-end">
                                <div class="text-center">
                                    <img src="../assets/img/avatars/<?php echo $agendamento['profissional_avatar'] ?? 'default.png'; ?>"
                                         alt="Profissional"
                                         class="avatar-salao mb-2"
                                         style="width: 60px; height: 60px;">
                                    <h6 class="mb-0 small fw-bold"><?php echo htmlspecialchars($agendamento['profissional_nome']); ?></h6>
                                    <small class="text-muted">Profissional</small>
                                </div>
                            </div>

                            <!-- Cliente -->
                            <div class="col-md-3 border-end">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <img src="../assets/img/avatars/<?php echo $agendamento['cliente_avatar'] ?? 'default.png'; ?>"
                                         alt="Cliente"
                                         class="avatar-salao"
                                         style="width: 50px; height: 50px;">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i>
                                            <?php echo $agendamento['cliente_telefone'] ?? 'Não informado'; ?>
                                        </small>
                                    </div>
                                </div>

                                <span class="badge <?php echo $status_info['badge']; ?> w-100 py-2">
                                    <i class="fas fa-<?php echo $status_info['icone']; ?> me-1"></i>
                                    <?php echo $status_info['texto']; ?>
                                </span>
                            </div>

                            <!-- Detalhes do Agendamento -->
                            <div class="col-md-5 border-end">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo substr($agendamento['hora_inicio'], 0, 5); ?>
                                    <?php if ($agendamento['hora_fim']): ?>
                                        - <?php echo substr($agendamento['hora_fim'], 0, 5); ?>
                                    <?php endif; ?>
                                </h6>

                                <div class="mb-3">
                                    <label class="text-muted small mb-1">
                                        <i class="fas fa-scissors me-1"></i>
                                        Serviços
                                    </label>
                                    <div class="fw-bold"><?php echo htmlspecialchars($agendamento['servicos']); ?></div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted">Duração</small>
                                        <div class="fw-bold">
                                            <i class="fas fa-hourglass-half text-info me-1"></i>
                                            <?php echo $agendamento['duracao_total']; ?> min
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Valor</small>
                                        <div class="fw-bold text-success">
                                            <i class="fas fa-dollar-sign me-1"></i>
                                            <?php echo formatar_moeda($agendamento['valor_total']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="col-md-2 d-flex flex-column gap-2 justify-content-center">
                                <?php if ($agendamento['status'] === 'agendado'): ?>
                                    <button class="btn btn-outline-danger btn-sm"
                                            onclick="if(confirm('Cancelar este agendamento?')) location.href='handle_cancelar_agendamento.php?id=<?php echo $agendamento['agendamento_id']; ?>'">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </button>
                                <?php elseif ($agendamento['status'] === 'finalizado'): ?>
                                    <button class="btn btn-outline-success btn-sm" disabled>
                                        <i class="fas fa-check-circle me-1"></i>Concluído
                                    </button>
                                <?php endif; ?>

                                <?php if ($agendamento['cliente_telefone']): ?>
                                <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', $agendamento['cliente_telefone']); ?>"
                                   target="_blank"
                                   class="btn btn-outline-success btn-sm">
                                    <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
