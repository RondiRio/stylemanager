<?php
// admin/aprovar_vales.php - Aprovação de Vales
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Aprovar Vales";
requer_login('admin');

// Buscar vales
$stmt = $pdo->prepare("
    SELECT
        v.id, v.valor, v.data_vale, v.motivo, v.status, v.data_aprovacao,
        u.nome AS profissional_nome,
        admin.nome AS aprovado_por_nome
    FROM vales v
    JOIN usuarios u ON u.id = v.profissional_id
    LEFT JOIN usuarios admin ON admin.id = v.aprovado_por
    ORDER BY
        CASE v.status
            WHEN 'pendente' THEN 1
            WHEN 'aprovado' THEN 2
            WHEN 'negado' THEN 3
        END,
        v.data_vale DESC
");
$stmt->execute();
$vales = $stmt->fetchAll();

// Contar status
$pendentes = count(array_filter($vales, fn($v) => $v['status'] == 'pendente'));
$aprovados = count(array_filter($vales, fn($v) => $v['status'] == 'aprovado'));
$negados = count(array_filter($vales, fn($v) => $v['status'] == 'negado'));

// Total de valores pendentes
$total_pendente = array_sum(array_map(fn($v) => $v['status'] == 'pendente' ? $v['valor'] : 0, $vales));

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-hand-holding-usd text-primary me-2"></i>Aprovar Vales
            </h2>
            <p class="text-muted mb-0">Gerencie os vales solicitados pelos profissionais</p>
        </div>
    </div>

    <!-- Cards de resumo -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h3 class="mb-0"><?php echo $pendentes; ?></h3>
                    <p class="text-muted mb-1">Pendentes</p>
                    <small class="text-warning fw-bold"><?php echo formatar_moeda($total_pendente); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="mb-0"><?php echo $aprovados; ?></h3>
                    <p class="text-muted mb-0">Aprovados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h3 class="mb-0"><?php echo $negados; ?></h3>
                    <p class="text-muted mb-0">Negados</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-2x text-info mb-2"></i>
                    <h3 class="mb-0"><?php echo count($vales); ?></h3>
                    <p class="text-muted mb-0">Total</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select id="filtroStatus" class="form-select">
                        <option value="todos">Todos</option>
                        <option value="pendente" selected>Pendentes</option>
                        <option value="aprovado">Aprovados</option>
                        <option value="negado">Negados</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Profissional</label>
                    <input type="text" id="filtroProfissional" class="form-control" placeholder="Nome do profissional...">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-secondary w-100" onclick="limparFiltros()">
                        <i class="fas fa-redo me-2"></i>Limpar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de vales -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($vales)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhum vale registrado ainda.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="tabelaVales">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Profissional</th>
                            <th>Valor</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vales as $v): ?>
                        <tr data-status="<?php echo $v['status']; ?>" data-profissional="<?php echo strtolower($v['profissional_nome']); ?>">
                            <td>
                                <i class="fas fa-calendar me-1 text-muted"></i>
                                <?php echo date('d/m/Y', strtotime($v['data_vale'])); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($v['profissional_nome']); ?></strong>
                            </td>
                            <td>
                                <strong class="text-danger"><?php echo formatar_moeda($v['valor']); ?></strong>
                            </td>
                            <td>
                                <?php if ($v['motivo']): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars($v['motivo']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($v['status'] == 'pendente'): ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pendente
                                    </span>
                                <?php elseif ($v['status'] == 'aprovado'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Aprovado
                                    </span>
                                    <?php if ($v['data_aprovacao']): ?>
                                        <br><small class="text-muted">em <?php echo date('d/m/Y', strtotime($v['data_aprovacao'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Negado
                                    </span>
                                    <?php if ($v['data_aprovacao']): ?>
                                        <br><small class="text-muted">em <?php echo date('d/m/Y', strtotime($v['data_aprovacao'])); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($v['status'] == 'pendente'): ?>
                                    <button type="button" class="btn btn-sm btn-success me-1" onclick="aprovarVale(<?php echo $v['id']; ?>)" title="Aprovar">
                                        <i class="fas fa-check"></i> Aprovar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="negarVale(<?php echo $v['id']; ?>)" title="Negar">
                                        <i class="fas fa-times"></i> Negar
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

<script>
// Aprovar vale
function aprovarVale(id) {
    if (!confirm('Deseja realmente aprovar este vale?')) return;

    fetch('handle_aprovar_vale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vale_id: id,
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

// Negar vale
function negarVale(id) {
    if (!confirm('Deseja realmente negar este vale?\n\nO profissional será notificado da negação.')) return;

    fetch('handle_aprovar_vale.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vale_id: id,
            acao: 'negar',
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

// Filtros
document.getElementById('filtroStatus').addEventListener('change', filtrar);
document.getElementById('filtroProfissional').addEventListener('input', filtrar);

function filtrar() {
    const status = document.getElementById('filtroStatus').value;
    const profissional = document.getElementById('filtroProfissional').value.toLowerCase();

    document.querySelectorAll('#tabelaVales tbody tr').forEach(row => {
        const rowStatus = row.dataset.status;
        const rowProf = row.dataset.profissional;

        let mostrar = true;

        if (status !== 'todos' && rowStatus !== status) mostrar = false;
        if (profissional && !rowProf.includes(profissional)) mostrar = false;

        row.style.display = mostrar ? '' : 'none';
    });
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = 'pendente';
    document.getElementById('filtroProfissional').value = '';
    filtrar();
}

// Aplicar filtro inicial (pendentes)
filtrar();
</script>

<?php include '../includes/footer.php'; ?>
