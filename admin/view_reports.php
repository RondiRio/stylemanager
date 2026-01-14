<?php
// admin/view_reports.php - VERSÃO MELHORADA
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Relatórios";
requer_login('admin');
include '../includes/header.php';

// ==================================================
// FILTROS
// ==================================================
$mes = $_GET['mes'] ?? date('Y-m');
$inicio = $mes . '-01 00:00:00';
$fim = date('Y-m-t 23:59:59', strtotime($inicio));

// ==================================================
// 1. FATURAMENTO SERVIÇOS
// ==================================================
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(x.preco), 0) AS total
    FROM (
        SELECT sr.id, sr.preco
        FROM servicos_realizados sr
        JOIN atendimentos a ON a.id = sr.atendimento_id
        WHERE a.data_atendimento BETWEEN ? AND ?
          AND a.status = 'concluido'
        GROUP BY sr.id
    ) AS x
");

$stmt->execute([$inicio, $fim]);
$faturamento_servicos = (float)$stmt->fetchColumn();

// ==================================================
// 2. FATURAMENTO PRODUTOS
// ==================================================
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(vp.valor_unitario * vp.quantidade), 0) as total
    FROM atendimentos a
    JOIN vendas_produtos vp ON vp.atendimento_id = a.id
    WHERE a.data_atendimento BETWEEN ? AND ?
");
$stmt->execute([$inicio, $fim]);
$faturamento_produtos = (float)$stmt->fetchColumn();

$faturamento_total = $faturamento_servicos + $faturamento_produtos;



// ==================================================
// 2.1 FATURAMENTO GORJETAS (TOTAL GERAL)
// ==================================================
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(a.gorjeta), 0) as total
    FROM atendimentos a
    WHERE a.data_atendimento BETWEEN ? AND ?
      AND a.status = 'concluido'
      AND a.profissional_id IS NOT NULL
");
$stmt->execute([$inicio, $fim]);
$faturamento_gorjetas_total = (float)$stmt->fetchColumn();
// ==================================================
// 3. COMISSÕES POR PROFISSIONAL
// ==================================================
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.nome,
        u.avatar,
        COALESCE(atd.total_atendimentos, 0) AS total_atendimentos,
        COALESCE(srv.faturamento_servicos, 0) AS faturamento_servicos,
        COALESCE(prd.faturamento_produtos, 0) AS faturamento_produtos,
        COALESCE(grt.gorjetas, 0) AS gorjetas,
        COALESCE(c.servico, 0) AS comissao_servico,
        COALESCE(c.produto, 0) AS comissao_produto
    FROM usuarios u
    LEFT JOIN (
        SELECT profissional_id, COUNT(DISTINCT id) AS total_atendimentos
        FROM atendimentos
        WHERE data_atendimento BETWEEN ? AND ? AND status = 'concluido'
        GROUP BY profissional_id
    ) atd ON atd.profissional_id = u.id
    LEFT JOIN (
        SELECT a.profissional_id, SUM(sr.preco) AS faturamento_servicos
        FROM atendimentos a
        JOIN servicos_realizados sr ON sr.atendimento_id = a.id
        WHERE a.data_atendimento BETWEEN ? AND ? AND a.status = 'concluido'
        GROUP BY a.profissional_id
    ) srv ON srv.profissional_id = u.id
    LEFT JOIN (
        SELECT a.profissional_id, SUM(vp.valor_unitario * vp.quantidade) AS faturamento_produtos
        FROM atendimentos a
        JOIN vendas_produtos vp ON vp.atendimento_id = a.id
        WHERE a.data_atendimento BETWEEN ? AND ? AND a.status = 'concluido'
        GROUP BY a.profissional_id
    ) prd ON prd.profissional_id = u.id
    LEFT JOIN (
        SELECT profissional_id, SUM(gorjeta) AS gorjetas
        FROM atendimentos
        WHERE data_atendimento BETWEEN ? AND ? AND status = 'concluido'
        GROUP BY profissional_id
    ) grt ON grt.profissional_id = u.id
    LEFT JOIN comissoes c ON c.profissional_id = u.id
    WHERE u.tipo = 'profissional' AND u.ativo = 1
    HAVING (COALESCE(faturamento_servicos, 0) + COALESCE(faturamento_produtos, 0)) > 0
    ORDER BY (COALESCE(faturamento_servicos, 0) + COALESCE(faturamento_produtos, 0)) DESC
");
$stmt->execute([$inicio, $fim, $inicio, $fim, $inicio, $fim, $inicio, $fim]);
$profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular comissões
foreach ($profissionais as &$prof) {
    $prof['comissao_servicos'] = ($prof['faturamento_servicos'] * $prof['comissao_servico']) / 100;
    $prof['comissao_produtos'] = ($prof['faturamento_produtos'] * $prof['comissao_produto']) / 100;
    $prof['comissao_total'] = $prof['comissao_servicos'] + $prof['comissao_produtos'];
    $prof['faturamento_total'] = $prof['faturamento_servicos'] + $prof['faturamento_produtos'];
}
unset($prof);

// Total de comissões
$total_comissoes = array_sum(array_column($profissionais, 'comissao_total'));

// ==================================================
// 4. VALES DO PERÍODO
// ==================================================
$stmt = $pdo->prepare("
    SELECT 
        u.nome as profissional,
        COALESCE(SUM(v.valor), 0) as total_vales
    FROM vales v
    JOIN usuarios u ON u.id = v.profissional_id
    WHERE v.data_vale BETWEEN ? AND ?
    GROUP BY u.id, u.nome
    HAVING total_vales > 0
    ORDER BY total_vales DESC
");
$stmt->execute([$inicio, $fim]);
$vales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_vales = array_sum(array_column($vales, 'total_vales'));

// ==================================================
// 5. TOP 5 SERVIÇOS
// ==================================================
$stmt = $pdo->prepare("
    SELECT 
    s.nome,
    COUNT(*) AS qtd,
    SUM(x.preco) AS faturamento,
    ROUND(AVG(x.preco), 2) AS ticket_medio
FROM (
    SELECT DISTINCT sr.id, sr.servico_id, sr.preco
    FROM servicos_realizados sr
    JOIN atendimentos a ON a.id = sr.atendimento_id
    WHERE a.data_atendimento BETWEEN ? AND ?
      AND a.status = 'concluido'
) x
JOIN servicos s ON s.id = x.servico_id
GROUP BY x.servico_id, s.nome
ORDER BY qtd DESC
LIMIT 5
");
$stmt->execute([$inicio, $fim]);
$top_servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// 6. TOP 5 PRODUTOS
// ==================================================
$stmt = $pdo->prepare("
    SELECT
        p.nome,
        SUM(vp.quantidade) as qtd_vendida,
        COALESCE(SUM(vp.valor_unitario * vp.quantidade), 0) as faturamento
    FROM atendimentos a
    JOIN vendas_produtos vp ON vp.atendimento_id = a.id
    JOIN produtos p ON p.id = vp.produto_id
    WHERE a.data_atendimento BETWEEN ? AND ?
    GROUP BY p.id, p.nome
    ORDER BY qtd_vendida DESC
    LIMIT 5
");
$stmt->execute([$inicio, $fim]);
$top_produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// 7. ESTATÍSTICAS GERAIS
// ==================================================
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT a.id) as total_atendimentos,
        COUNT(DISTINCT a.profissional_id) as profissionais_ativos,
        COUNT(DISTINCT a.cliente_nome) as clientes_atendidos
    FROM atendimentos a
    WHERE a.data_atendimento BETWEEN ? AND ? AND a.status = 'concluido'
");
$stmt->execute([$inicio, $fim]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$ticket_medio = $stats['total_atendimentos'] > 0 ? $faturamento_total / $stats['total_atendimentos'] : 0;
$dias_mes = date('j', strtotime($fim));
$faturamento_medio_dia = $faturamento_total / $dias_mes;
?>

<style>
    .report-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        height: 100%;
        transition: all 0.3s ease;
    }
    .report-card:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .metric-large {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }
    .metric-label {
        color: #6b7280;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    .prof-row {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
        background: #f9fafb;
    }
    .prof-row:hover {
        background: #f3f4f6;
        transform: translateX(5px);
    }
    .chart-container {
        position: relative;
        height: 250px;
    }
    .progress-bar-custom {
        height: 8px;
        border-radius: 10px;
        background: #e5e7eb;
        overflow: hidden;
        margin: 0.5rem 0;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--cor-primaria), var(--cor-secundaria));
        transition: width 0.5s ease;
    }
</style>

<div class="animate-fade-in">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Relatórios Financeiros</h2>
            <p class="text-muted mb-0"><?php echo date('F/Y', strtotime($inicio)); ?></p>
        </div>
        
        <!-- FILTRO -->
        <form method="GET" class="d-flex gap-2">
            <input type="month" 
                   name="mes" 
                   class="form-control" 
                   value="<?php echo $mes; ?>"
                   max="<?php echo date('Y-m'); ?>">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-2"></i>Filtrar
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Imprimir
            </button>
        </form>
    </div>

    <!-- CARDS DE RESUMO -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="report-card" style="border-left: 4px solid #10b981;">
                <div class="metric-label">
                    <i class="fas fa-dollar-sign me-2"></i>Faturamento Total
                </div>
                <div class="metric-large text-success"><?php echo formatar_moeda($faturamento_total); ?></div>
                <small class="text-muted">
                    <?php echo formatar_moeda($faturamento_medio_dia); ?> por dia
                </small>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="report-card" style="border-left: 4px solid #3b82f6;">
                <div class="metric-label">
                    <i class="fas fa-cut me-2"></i>Atendimentos
                </div>
                <div class="metric-large text-primary"><?php echo $stats['total_atendimentos']; ?></div>
                <small class="text-muted">
                    Ticket médio: <?php echo formatar_moeda($ticket_medio); ?>
                </small>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="report-card" style="border-left: 4px solid #f59e0b;">
                <div class="metric-label">
                    <i class="fas fa-money-bill-wave me-2"></i>Comissões
                </div>
                <div class="metric-large text-warning"><?php echo formatar_moeda($total_comissoes); ?></div>
                <small class="text-muted">
                    <?php echo count($profissionais); ?> profissionais
                </small>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="report-card" style="border-left: 4px solid #ef4444;">
                <div class="metric-label">
                    <i class="fas fa-hand-holding-usd me-2"></i>Vales
                </div>
                <div class="metric-large text-danger"><?php echo formatar_moeda($total_vales); ?></div>
                <small class="text-muted">
                    Líquido: <?php echo formatar_moeda(($total_comissoes + $faturamento_gorjetas_total) - $total_vales); ?>
                </small>
            </div>
        </div>
    </div>

    <!-- DIVISÃO SERVIÇOS/PRODUTOS -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="report-card">
                <h5 class="mb-3">
                    <i class="fas fa-scissors text-primary me-2"></i>
                    Serviços
                </h5>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h3 text-success mb-0"><?php echo formatar_moeda($faturamento_servicos); ?></div>
                        <small class="text-muted">
                            <?php 
                            $perc_servicos = $faturamento_total > 0 ? ($faturamento_servicos / $faturamento_total) * 100 : 0;
                            echo number_format($perc_servicos, 1); 
                            ?>% do total
                        </small>
                    </div>
                    <div class="chart-container" style="width: 120px; height: 120px;">
                        <canvas id="chartServicos"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="report-card">
                <h5 class="mb-3">
                    <i class="fas fa-shopping-bag text-info me-2"></i>
                    Produtos
                </h5>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h3 text-info mb-0"><?php echo formatar_moeda($faturamento_produtos); ?></div>
                        <small class="text-muted">
                            <?php 
                            $perc_produtos = $faturamento_total > 0 ? ($faturamento_produtos / $faturamento_total) * 100 : 0;
                            echo number_format($perc_produtos, 1); 
                            ?>% do total
                        </small>
                    </div>
                    <div class="chart-container" style="width: 120px; height: 120px;">
                        <canvas id="chartProdutos"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- COMISSÕES POR PROFISSIONAL -->
        <div class="col-lg-7">
            <div class="card-glass">
                <div class="card-glass-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Comissões por Profissional
                    </h5>
                </div>
                
                <?php if (empty($profissionais)): ?>
                    <p class="text-muted text-center py-4">Nenhum atendimento no período.</p>
                <?php else: ?>
                    <?php foreach ($profissionais as $prof): ?>
                    <div class="prof-row">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="../assets/img/avatars/<?php echo $prof['avatar'] ?? 'default.png'; ?>" 
                                 class="avatar-salao" 
                                 alt="<?php echo htmlspecialchars($prof['nome']); ?>">
                            
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($prof['nome']); ?></h6>
                                <small class="text-muted">
                                    <?php echo $prof['total_atendimentos']; ?> atendimentos •  
                                    <?php echo number_format($prof['comissao_servico'], 0); ?>% Serviços
                                    <?php echo number_format($prof['comissao_produto'], 0); ?>% Produtos
                                    
                                </small>
                            </div>
                            
                            <div class="text-end">
                                <div class="h5 mb-0 text-success fw-bold">
                                    <?php echo formatar_moeda($prof['comissao_total']); ?>
                                </div>
                                <small class="text-muted">
                                    sobre <?php echo formatar_moeda($prof['faturamento_total']); ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Barra de Progresso -->
                        <div class="progress-bar-custom">
                            <div class="progress-fill" 
                                 style="width: <?php echo $faturamento_total > 0 ? ($prof['faturamento_total'] / $faturamento_total) * 100 : 0; ?>%">
                            </div>
                        </div>
                        
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <small class="text-muted">Serviços:</small>
                                <strong class="d-block text-success">
                                    <?php echo formatar_moeda($prof['comissao_servicos']); ?>
                                </strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Produtos:</small>
                                <strong class="d-block text-info">
                                    <?php echo formatar_moeda($prof['comissao_produtos']); ?>
                                </strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Gorjetas:</small>
                                <strong class="d-block text-danger">
                                    <?php echo formatar_moeda($prof['gorjetas']); ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- TOTAL -->
                    <div class="prof-row" style="background: linear-gradient(135deg, #6d4c41, #f06292); color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">TOTAL A PAGAR</h5>
                            <h3 class="mb-0"><?php echo formatar_moeda($total_comissoes + $faturamento_gorjetas_total); ?></h3>
                        </div>
                        <?php if ($total_vales > 0): ?>
                        <hr style="border-color: rgba(255,255,255,0.3);">
                        <div class="d-flex justify-content-between">
                            <span>Descontar vales:</span>
                            <strong>- <?php echo formatar_moeda($total_vales); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Líquido:</span>
                            <strong><?php echo formatar_moeda(($total_comissoes + $faturamento_gorjetas_total) - $total_vales); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TOP SERVIÇOS E PRODUTOS -->
        <div class="col-lg-5">
            <!-- TOP SERVIÇOS -->
            <div class="card-glass mb-4">
                <div class="card-glass-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Top 5 Serviços
                    </h5>
                </div>
                
                <?php if (empty($top_servicos)): ?>
                    <p class="text-muted text-center py-3">Nenhum serviço realizado.</p>
                <?php else: ?>
                    <?php foreach ($top_servicos as $index => $s): ?>
                    <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" 
                         style="background: <?php echo $index === 0 ? '#f0fdf4' : '#f9fafb'; ?>;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary"><?php echo $index + 1; ?>º</span>
                            <div>
                                <strong><?php echo htmlspecialchars($s['nome']); ?></strong>
                                <small class="d-block text-muted">
                                    <?php echo $s['qtd']; ?>x • Ticket: <?php echo formatar_moeda($s['ticket_medio']); ?>
                                </small>
                            </div>
                        </div>
                        <strong class="text-success"><?php echo formatar_moeda($s['faturamento']); ?></strong>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- TOP PRODUTOS -->
            <div class="card-glass">
                <div class="card-glass-header">
                    <h5 class="mb-0">
                        <i class="fas fa-award me-2"></i>
                        Top 5 Produtos
                    </h5>
                </div>
                
                <?php if (empty($top_produtos)): ?>
                    <p class="text-muted text-center py-3">Nenhum produto vendido.</p>
                <?php else: ?>
                    <?php foreach ($top_produtos as $index => $p): ?>
                    <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" 
                         style="background: <?php echo $index === 0 ? '#eff6ff' : '#f9fafb'; ?>;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-info"><?php echo $index + 1; ?>º</span>
                            <div>
                                <strong><?php echo htmlspecialchars($p['nome']); ?></strong>
                                <small class="d-block text-muted"><?php echo $p['qtd_vendida']; ?> unidades</small>
                            </div>
                        </div>
                        <strong class="text-info"><?php echo formatar_moeda($p['faturamento']); ?></strong>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico Serviços
new Chart(document.getElementById('chartServicos'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo $faturamento_servicos; ?>, <?php echo $faturamento_produtos; ?>],
            backgroundColor: ['#10b981', '#e5e7eb'],
            borderWidth: 0
        }]
    },
    options: {
        cutout: '75%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
});

// Gráfico Produtos
new Chart(document.getElementById('chartProdutos'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo $faturamento_produtos; ?>, <?php echo $faturamento_servicos; ?>],
            backgroundColor: ['#3b82f6', '#e5e7eb'],
            borderWidth: 0
        }]
    },
    options: {
        cutout: '75%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
});
</script>

<?php include '../includes/footer.php'; ?>