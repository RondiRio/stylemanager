<?php
// admin/view_agenda_geral.php - Agenda de todos os profissionais
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Agenda Geral";
requer_login('admin');

$data = $_GET['data'] ?? date('Y-m-d');
$profissional_id = $_GET['profissional_id'] ?? '';
$busca_cliente = $_GET['busca_cliente'] ?? '';

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

if (!empty($busca_cliente)) {
    $query .= " AND (u.nome LIKE ? OR u.telefone LIKE ? OR a.cliente_nome LIKE ? OR a.cliente_telefone LIKE ?)";
    $termo_busca = "%{$busca_cliente}%";
    $params[] = $termo_busca;
    $params[] = $termo_busca;
    $params[] = $termo_busca;
    $params[] = $termo_busca;
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
    'cliente_chegou' => ['badge' => 'bg-info', 'texto' => 'Cliente Chegou', 'icone' => 'door-open'],
    'em_atendimento' => ['badge' => 'bg-warning', 'texto' => 'Em Atendimento', 'icone' => 'scissors'],
    'finalizado' => ['badge' => 'bg-success', 'texto' => 'Finalizado', 'icone' => 'check-circle'],
    'nao_chegou' => ['badge' => 'bg-secondary', 'texto' => 'Não Chegou', 'icone' => 'user-times'],
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
                        <div class="col-12 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="busca_cliente" class="form-control"
                                       placeholder="Buscar cliente por nome ou telefone..."
                                       value="<?php echo htmlspecialchars($busca_cliente); ?>">
                            </div>
                        </div>
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
                            <a href="?data=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-calendar-day"></i> Hoje
                            </a>
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

                            <!-- Ações e Status -->
                            <div class="col-md-2 d-flex flex-column gap-2 justify-content-center">
                                <?php if ($agendamento['status'] !== 'cancelado' && $agendamento['status'] !== 'finalizado'): ?>
                                    <!-- Dropdown de Status -->
                                    <div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle w-100" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-edit me-1"></i>Alterar Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                   onclick="alterarStatus(<?php echo $agendamento['agendamento_id']; ?>, 'confirmado'); return false;">
                                                    <i class="fas fa-check text-primary me-2"></i>Confirmar
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                   onclick="alterarStatus(<?php echo $agendamento['agendamento_id']; ?>, 'cliente_chegou'); return false;">
                                                    <i class="fas fa-door-open text-info me-2"></i>Cliente Chegou
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                   onclick="alterarStatus(<?php echo $agendamento['agendamento_id']; ?>, 'em_atendimento'); return false;">
                                                    <i class="fas fa-scissors text-warning me-2"></i>Em Atendimento
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                   onclick="abrirModalFinalizar(<?php echo $agendamento['agendamento_id']; ?>, <?php echo $agendamento['profissional_id'] ?? 'null'; ?>); return false;">
                                                    <i class="fas fa-check-circle text-success me-2"></i>Finalizar
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#"
                                                   onclick="alterarStatus(<?php echo $agendamento['agendamento_id']; ?>, 'nao_chegou'); return false;">
                                                    <i class="fas fa-user-times text-danger me-2"></i>Não Chegou
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#"
                                                   onclick="if(confirm('Cancelar este agendamento?')) alterarStatus(<?php echo $agendamento['agendamento_id']; ?>, 'cancelado'); return false;">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                <?php elseif ($agendamento['status'] === 'finalizado'): ?>
                                    <button class="btn btn-success btn-sm" disabled>
                                        <i class="fas fa-check-circle me-1"></i>Concluído
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" disabled>
                                        <i class="fas fa-times-circle me-1"></i>Cancelado
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

<!-- Modal para Finalizar Atendimento -->
<div class="modal fade" id="modalFinalizarAtendimento" tabindex="-1" aria-labelledby="modalFinalizarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalFinalizarLabel">
                    <i class="fas fa-check-circle me-2"></i>Finalizar Atendimento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="finalizarAgendamentoId">

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Selecione o profissional que realizou o atendimento para que entre nas métricas corretas.
                </div>

                <div class="mb-3">
                    <label for="finalizarProfissionalId" class="form-label">
                        <i class="fas fa-user me-1"></i>Profissional que Atendeu *
                    </label>
                    <select class="form-select" id="finalizarProfissionalId" required>
                        <option value="">Selecione o profissional...</option>
                        <?php foreach ($profissionais as $prof): ?>
                        <option value="<?php echo $prof['id']; ?>">
                            <?php echo htmlspecialchars($prof['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">O profissional selecionado receberá as comissões deste atendimento.</small>
                </div>

                <div class="mb-3">
                    <label for="finalizarObservacoes" class="form-label">
                        <i class="fas fa-comment me-1"></i>Observações (Opcional)
                    </label>
                    <textarea class="form-control" id="finalizarObservacoes" rows="3"
                              placeholder="Adicione observações sobre o atendimento..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" onclick="confirmarFinalizacao()">
                    <i class="fas fa-check me-1"></i>Finalizar Atendimento
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para alterar status simples (sem necessidade de profissional)
function alterarStatus(agendamentoId, novoStatus) {
    if (!agendamentoId || !novoStatus) {
        alert('Dados inválidos');
        return;
    }

    // Confirmar ação para alguns status
    const statusTexto = {
        'confirmado': 'confirmar',
        'cliente_chegou': 'marcar como "Cliente Chegou"',
        'em_atendimento': 'marcar como "Em Atendimento"',
        'nao_chegou': 'marcar como "Não Chegou"',
        'cancelado': 'cancelar'
    };

    const acao = statusTexto[novoStatus] || 'alterar';

    // Confirmação adicional para status negativos
    if ((novoStatus === 'nao_chegou' || novoStatus === 'cancelado') &&
        !confirm(`Tem certeza que deseja ${acao} este agendamento?`)) {
        return;
    }

    // Enviar requisição
    fetch('handle_status_agendamento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `agendamento_id=${agendamentoId}&status=${novoStatus}&csrf_token=<?php echo gerar_csrf_token(); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status atualizado com sucesso!');
            location.reload();
        } else {
            alert('Erro ao atualizar status: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar requisição');
    });
}

// Função para abrir modal de finalização
function abrirModalFinalizar(agendamentoId, profissionalId) {
    document.getElementById('finalizarAgendamentoId').value = agendamentoId;

    // Pré-selecionar profissional se houver um associado
    if (profissionalId && profissionalId !== 'null') {
        document.getElementById('finalizarProfissionalId').value = profissionalId;
    } else {
        document.getElementById('finalizarProfissionalId').value = '';
    }

    // Limpar observações
    document.getElementById('finalizarObservacoes').value = '';

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalFinalizarAtendimento'));
    modal.show();
}

// Função para confirmar finalização com profissional
function confirmarFinalizacao() {
    const agendamentoId = document.getElementById('finalizarAgendamentoId').value;
    const profissionalId = document.getElementById('finalizarProfissionalId').value;
    const observacoes = document.getElementById('finalizarObservacoes').value;

    if (!profissionalId) {
        alert('Por favor, selecione o profissional que realizou o atendimento');
        return;
    }

    // Enviar requisição
    fetch('handle_status_agendamento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `agendamento_id=${agendamentoId}&status=finalizado&profissional_id=${profissionalId}&observacoes=${encodeURIComponent(observacoes)}&csrf_token=<?php echo gerar_csrf_token(); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Atendimento finalizado com sucesso!');
            location.reload();
        } else {
            alert('Erro ao finalizar atendimento: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar requisição');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
