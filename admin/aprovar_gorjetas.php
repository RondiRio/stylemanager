<?php
// admin/aprovar_gorjetas.php - Aprovação de Gorjetas
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Aprovar Gorjetas";
requer_login('admin');

// Buscar gorjetas pendentes
$stmt = $pdo->prepare("
    SELECT
        g.id, g.valor, g.data_gorjeta, g.observacoes, g.status, g.motivo_negacao, g.data_aprovacao,
        u.nome AS profissional_nome,
        a.cliente_nome,
        admin.nome AS aprovado_por_nome
    FROM gorjetas g
    JOIN usuarios u ON u.id = g.profissional_id
    LEFT JOIN atendimentos a ON a.id = g.atendimento_id
    LEFT JOIN usuarios admin ON admin.id = g.aprovado_por
    ORDER BY
        CASE g.status
            WHEN 'pendente' THEN 1
            WHEN 'aprovado' THEN 2
            WHEN 'negado' THEN 3
        END,
        g.data_gorjeta DESC
");
$stmt->execute();
$gorjetas = $stmt->fetchAll();

// Contar status
$pendentes = count(array_filter($gorjetas, fn($g) => $g['status'] == 'pendente'));
$aprovadas = count(array_filter($gorjetas, fn($g) => $g['status'] == 'aprovado'));
$negadas = count(array_filter($gorjetas, fn($g) => $g['status'] == 'negado'));

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-coins text-warning me-2"></i>Aprovar Gorjetas
            </h2>
            <p class="text-muted mb-0">Gerencie as gorjetas registradas pelos profissionais</p>
        </div>
        <a href="configuracoes.php" class="btn btn-outline-secondary">
            <i class="fas fa-cog me-2"></i>Configurações
        </a>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h3 class="mb-0"><?php echo $pendentes; ?></h3>
                    <p class="text-muted mb-0">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="mb-0"><?php echo $aprovadas; ?></h3>
                    <p class="text-muted mb-0">Aprovadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h3 class="mb-0"><?php echo $negadas; ?></h3>
                    <p class="text-muted mb-0">Negadas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filtroStatus" class="form-select">
                        <option value="todos">Todos</option>
                        <option value="pendente" selected>Pendentes</option>
                        <option value="aprovado">Aprovadas</option>
                        <option value="negado">Negadas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Profissional</label>
                    <input type="text" id="filtroProfissional" class="form-control" placeholder="Nome do profissional...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <input type="date" id="filtroData" class="form-control">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-secondary w-100" onclick="limparFiltros()">
                        <i class="fas fa-redo me-2"></i>Limpar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de gorjetas -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($gorjetas)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhuma gorjeta registrada ainda.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="tabelaGorjetas">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Profissional</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Observações</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gorjetas as $g): ?>
                        <tr data-status="<?php echo $g['status']; ?>" data-profissional="<?php echo strtolower($g['profissional_nome']); ?>" data-data="<?php echo $g['data_gorjeta']; ?>">
                            <td>
                                <i class="fas fa-calendar me-1 text-muted"></i>
                                <?php echo date('d/m/Y', strtotime($g['data_gorjeta'])); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($g['profissional_nome']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($g['cliente_nome'] ?? '-'); ?></td>
                            <td>
                                <strong class="text-success"><?php echo formatar_moeda($g['valor']); ?></strong>
                            </td>
                            <td>
                                <?php if ($g['observacoes']): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars(substr($g['observacoes'], 0, 50)); ?><?php echo strlen($g['observacoes']) > 50 ? '...' : ''; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($g['status'] == 'pendente'): ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pendente
                                    </span>
                                <?php elseif ($g['status'] == 'aprovado'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Aprovada
                                    </span>
                                    <?php if ($g['data_aprovacao']): ?>
                                        <br><small class="text-muted">em <?php echo date('d/m/Y', strtotime($g['data_aprovacao'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Negada
                                    </span>
                                    <?php if ($g['motivo_negacao']): ?>
                                        <br><small class="text-danger" title="<?php echo htmlspecialchars($g['motivo_negacao']); ?>">
                                            <i class="fas fa-info-circle"></i> Ver motivo
                                        </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($g['status'] == 'pendente'): ?>
                                    <button type="button" class="btn btn-sm btn-success me-1" onclick="aprovarGorjeta(<?php echo $g['id']; ?>)" title="Aprovar gorjeta">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="negarGorjeta(<?php echo $g['id']; ?>)" title="Negar gorjeta">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Negar Gorjeta -->
<div class="modal fade" id="modalNegarGorjeta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formNegarGorjeta">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Negar Gorjeta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="gorjeta_id" id="negarGorjetaId">
                    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Você está prestes a <strong>negar</strong> esta gorjeta. O profissional poderá ver o motivo da negação.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Motivo da Negação <span class="text-danger">*</span></label>
                        <textarea name="motivo_negacao" class="form-control" rows="4" required placeholder="Explique o motivo da negação..."></textarea>
                        <small class="text-muted">Este motivo será exibido para o profissional</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Confirmar Negação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Aprovar gorjeta
function aprovarGorjeta(id) {
    if (!confirm('Deseja realmente aprovar esta gorjeta?')) return;

    fetch('handle_aprovar_gorjeta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            gorjeta_id: id,
            acao: 'aprovar',
            csrf_token: '<?php echo gerar_csrf_token(); ?>'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    });
}

// Negar gorjeta
function negarGorjeta(id) {
    document.getElementById('negarGorjetaId').value = id;
    new bootstrap.Modal(document.getElementById('modalNegarGorjeta')).show();
}

// Submeter negação
document.getElementById('formNegarGorjeta').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('handle_aprovar_gorjeta.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    });
});

// Filtros
document.getElementById('filtroStatus').addEventListener('change', filtrar);
document.getElementById('filtroProfissional').addEventListener('input', filtrar);
document.getElementById('filtroData').addEventListener('change', filtrar);

function filtrar() {
    const status = document.getElementById('filtroStatus').value;
    const profissional = document.getElementById('filtroProfissional').value.toLowerCase();
    const data = document.getElementById('filtroData').value;

    document.querySelectorAll('#tabelaGorjetas tbody tr').forEach(row => {
        const rowStatus = row.dataset.status;
        const rowProf = row.dataset.profissional;
        const rowData = row.dataset.data;

        let mostrar = true;

        if (status !== 'todos' && rowStatus !== status) mostrar = false;
        if (profissional && !rowProf.includes(profissional)) mostrar = false;
        if (data && rowData !== data) mostrar = false;

        row.style.display = mostrar ? '' : 'none';
    });
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = 'pendente';
    document.getElementById('filtroProfissional').value = '';
    document.getElementById('filtroData').value = '';
    filtrar();
}

// Aplicar filtro inicial (pendentes)
filtrar();
</script>

<?php include '../includes/footer.php'; ?>