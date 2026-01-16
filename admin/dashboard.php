<?php

// ini_set('display_errors', 0);  // Desliga exibição na tela (segurança em prod)
ini_set('log_errors', 1);  // Ativa logging
ini_set('error_log', __DIR__ . '/error_log.txt');  // Define um arquivo custom no mesmo dir (ou use o default do servidor)
error_reporting(E_ALL);  // Captura todos os erros
error_log("TESTE DE LOG: O sistema de logs está funcionando!");
// admin/dashboard.php - VERSÃO MELHORADA
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Dashboard Admin";
requer_login('admin');


$hoje = date('Y-m-d');
$mes_atual = date('Y-m');

// ==================================================
// FATURAMENTO DO DIA
// ==================================================
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(sr.preco), 0) as total_servicos,
        COALESCE(COUNT(DISTINCT a.id), 0) as total_atendimentos
    FROM atendimentos a
    LEFT JOIN servicos_realizados sr ON sr.atendimento_id = a.id
    WHERE DATE(a.data_atendimento) = ? AND a.status = 'concluido'
");
$stmt->execute([$hoje]);
$faturamento_dia = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor_unitario), 0) as total_produtos
    FROM vendas_produtos vp
    JOIN atendimentos a ON a.id = vp.atendimento_id
    WHERE DATE(a.data_atendimento) = ?
");
$stmt->execute([$hoje]);
$produtos_dia = $stmt->fetchColumn();

$faturamento_total_dia = $faturamento_dia['total_servicos'] + $produtos_dia;

// ==================================================
// FATURAMENTO DO MÊS - VERSÃO PRODUÇÃO
// ==================================================

// Definimos o primeiro e o último dia do mês para um filtro de alta performance
$data_inicio = $mes_atual . '-01';
$data_fim = date("Y-m-t", strtotime($data_inicio));

// 1. Soma dos Serviços
// Usamos BETWEEN em vez de DATE_FORMAT para que o banco use os índices e não trave
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(sr.preco), 0)
    FROM servicos_realizados sr
    JOIN atendimentos a ON sr.atendimento_id = a.id
    WHERE a.data_atendimento BETWEEN ? AND ? 
    AND a.status = 'concluido'
");
$stmt->execute([$data_inicio, $data_fim]);
$faturamento_mes_servicos = $stmt->fetchColumn();

// 2. Soma dos Produtos
// O JOIN garante que só somamos produtos de atendimentos válidos
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(vp.valor_unitario), 0)
    FROM vendas_produtos vp
    JOIN atendimentos a ON vp.atendimento_id = a.id
    WHERE a.data_atendimento BETWEEN ? AND ?
    AND a.status = 'concluido'
");
$stmt->execute([$data_inicio, $data_fim]);
$faturamento_mes_produtos = $stmt->fetchColumn();

$faturamento_total_mes = $faturamento_mes_servicos + $faturamento_mes_produtos;

// ==================================================
// ESTATÍSTICAS GERAIS
// ==================================================
$total_clientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente' AND ativo = 1")->fetchColumn();
$total_profissionais = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'profissional' AND ativo = 1")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM agendamentos 
    WHERE data_agendamento = ? AND status IN ('agendado', 'confirmado')
");
$stmt->execute([$hoje]);
$agendamentos_hoje = $stmt->fetchColumn();

// ==================================================
// PERFORMANCE POR PROFISSIONAL (DIA)
// ==================================================
$stmt = $pdo->prepare("
    SELECT 
    u.id,
    u.nome,
    u.avatar,
    COUNT(DISTINCT a.id) as total_atendimentos,
    COALESCE(SUM(sr.preco), 0) as total_servicos,
    COALESCE(SUM(vp.valor_unitario), 0) as total_produtos,
    COALESCE(c.servico, 0) as comissao_servico,
    COALESCE(c.produto, 0) as comissao_produto
FROM usuarios u
LEFT JOIN atendimentos a 
    ON a.profissional_id = u.id 
    AND DATE(a.data_atendimento) = ? 
    AND a.status = 'concluido'
LEFT JOIN servicos_realizados sr ON sr.atendimento_id = a.id
LEFT JOIN vendas_produtos vp ON vp.atendimento_id = a.id
LEFT JOIN comissoes c ON c.profissional_id = u.id
WHERE u.tipo = 'profissional' AND u.ativo = 1
GROUP BY u.id
ORDER BY 
    (COALESCE(SUM(sr.preco), 0) + COALESCE(SUM(vp.valor_unitario), 0)) DESC
");
$stmt->execute([$hoje]);
$profissionais_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// PERFORMANCE POR PROFISSIONAL (MÊS) - VERSÃO SEGURA
// ==================================================

$data_inicio = $mes_atual . '-01';
$data_fim = date("Y-m-t", strtotime($data_inicio));

$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.nome,
        u.avatar,
        -- Subquery para Atendimentos e Serviços
        (SELECT COUNT(DISTINCT a.id) 
         FROM atendimentos a 
         WHERE a.profissional_id = u.id 
         AND a.data_atendimento BETWEEN ? AND ? 
         AND a.status = 'concluido') as total_atendimentos,

        (SELECT COALESCE(SUM(sr.preco), 0) 
         FROM servicos_realizados sr
         JOIN atendimentos a ON sr.atendimento_id = a.id
         WHERE a.profissional_id = u.id 
         AND a.data_atendimento BETWEEN ? AND ? 
         AND a.status = 'concluido') as total_servicos,

        -- Subquery para Produtos
        (SELECT COALESCE(SUM(vp.valor_unitario), 0) 
         FROM vendas_produtos vp
         JOIN atendimentos a ON vp.atendimento_id = a.id
         WHERE a.profissional_id = u.id 
         AND a.data_atendimento BETWEEN ? AND ? 
         AND a.status = 'concluido') as total_produtos,

        -- Comissões (Fixas do cadastro do profissional)
        COALESCE(c.servico, 0) as comissao_servico,
        COALESCE(c.produto, 0) as comissao_produto
        
    FROM usuarios u
    LEFT JOIN comissoes c ON c.profissional_id = u.id
    WHERE u.tipo = 'profissional' AND u.ativo = 1
    GROUP BY u.id
    ORDER BY (total_servicos + total_produtos) DESC
");

// Passamos os parâmetros para cada par de datas nas subqueries
$stmt->execute([
    $data_inicio, $data_fim, // Atendimentos
    $data_inicio, $data_fim, // Serviços
    $data_inicio, $data_fim  // Produtos
]);
$profissionais_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ==================================================
// TOP 5 SERVIÇOS MAIS REALIZADOS HOJE
// ==================================================
$stmt = $pdo->prepare("
    SELECT 
        sr.nome_servico,
        COUNT(*) as qtd,
        SUM(sr.preco) as valor_total
    FROM servicos_realizados sr
    JOIN atendimentos a ON a.id = sr.atendimento_id
    WHERE DATE(a.data_atendimento) = ? AND a.status = 'concluido'
    GROUP BY sr.servico_id, sr.nome_servico
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute([$hoje]);
$top_servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>

<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 4px solid;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .stat-card.primary { border-color: #6d4c41; }
    .stat-card.success { border-color: #10b981; }
    .stat-card.warning { border-color: #f59e0b; }
    .stat-card.info { border-color: #3b82f6; }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 0.5rem 0;
    }
    .stat-label {
        color: #6b7280;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    .prof-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .prof-card:hover {
        border-left-color: #f06292;
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .prof-rank {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        color: white;
    }
    .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #000; }
    .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e8e8e8); color: #000; }
    .rank-3 { background: linear-gradient(135deg, #cd7f32, #e8a87c); color: #fff; }
    .rank-default { background: linear-gradient(135deg, #6b7280, #9ca3af); }
</style>

<div class="animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Dashboard Admin</h2>
            <p class="text-muted mb-0"><?php echo formatar_data($hoje); ?></p>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-primary active" onclick="toggleView('dia')">Hoje</button>
            <button class="btn btn-outline-primary" onclick="toggleView('mes')">Este Mês</button>
        </div>
    </div>

    <!-- CARDS DE ESTATÍSTICAS -->
    <div class="row g-4 mb-4" id="statsDia">
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-label">Faturamento Hoje</div>
                <div class="stat-value text-success"><?php echo formatar_moeda($faturamento_total_dia); ?></div>
                <small class="text-muted">
                    Serviços: <?php echo formatar_moeda($faturamento_dia['total_servicos']); ?> | 
                    Produtos: <?php echo formatar_moeda($produtos_dia); ?>
                </small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-icon" style="background: rgba(109, 76, 65, 0.1); color: #6d4c41;">
                    <i class="fas fa-cut"></i>
                </div>
                <div class="stat-label">Atendimentos</div>
                <div class="stat-value"><?php echo $faturamento_dia['total_atendimentos']; ?></div>
                <small class="text-muted">Concluídos hoje</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-label">Agendamentos</div>
                <div class="stat-value text-warning"><?php echo $agendamentos_hoje; ?></div>
                <small class="text-muted">Para hoje</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-label">Profissionais</div>
                <div class="stat-value text-info"><?php echo $total_profissionais; ?></div>
                <small class="text-muted"><?php echo $total_clientes; ?> clientes ativos</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 d-none" id="statsMes">
        <div class="col-md-4">
            <div class="stat-card success">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-label">Faturamento do Mês</div>
                <div class="stat-value text-success"><?php echo formatar_moeda($faturamento_total_mes); ?></div>
                <small class="text-muted">
                    Serviços: <?php echo formatar_moeda($faturamento_mes_servicos); ?> | 
                    Produtos: <?php echo formatar_moeda($faturamento_mes_produtos); ?>
                </small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card primary">
                <div class="stat-icon" style="background: rgba(109, 76, 65, 0.1); color: #6d4c41;">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-label">Período</div>
                <div class="stat-value" style="font-size: 1.5rem;"><?php echo date('F/Y'); ?></div>
                <small class="text-muted">Mês atual</small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card info">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-label">Média Diária</div>
                <div class="stat-value text-info"><?php echo formatar_moeda($faturamento_total_mes / date('d')); ?></div>
                <small class="text-muted">Baseado em <?php echo date('d'); ?> dias</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- RANKING DE PROFISSIONAIS -->
        <div class="col-lg-8">
            <div class="card-glass">
                <div class="card-glass-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        <span id="rankingTitle">Performance dos Profissionais - Hoje</span>
                    </h5>
                </div>
                
                <div id="rankingDia">
                    <?php if (empty($profissionais_dia)): ?>
                        <p class="text-muted text-center py-4">Nenhum atendimento realizado hoje.</p>
                    <?php else: ?>
                        <?php foreach ($profissionais_dia as $index => $prof): 
                            $total = $prof['total_servicos'] + $prof['total_produtos'];
                            $comissao_servico = $prof['total_servicos'] * ($prof['comissao_servico'] / 100);
                            $comissao_produto = $prof['total_produtos'] * ($prof['comissao_produto'] / 100);
                            $comissao = $comissao_servico + $comissao_produto;

                            $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-default'));
                        ?>
                        <div class="prof-card animate-fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="prof-rank <?php echo $rank_class; ?>">
                                    <?php echo $index + 1; ?>º
                                </div>
                                
                                <img src="../assets/img/avatars/<?php echo $prof['avatar'] ?? 'default.png'; ?>" 
                                     class="avatar-salao avatar-salao-lg" 
                                     alt="<?php echo htmlspecialchars($prof['nome']); ?>">
                                
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($prof['nome']); ?></h5>
                                    <div class="d-flex gap-3 text-muted small">
                                        <span>
                                            <i class="fas fa-cut me-1"></i>
                                            <?php echo $prof['total_atendimentos']; ?> atendimentos
                                        </span>
                                        <span>
                                            <i class="fas fa-percent me-1"></i>
                                            <?php echo number_format($prof['comissao_produto'], 0); ?>% comissão
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <div class="h4 mb-0 text-success fw-bold">
                                        <?php echo formatar_moeda($total); ?>
                                    </div>
                                    <small class="text-muted">
                                        Comissão: <?php echo formatar_moeda($comissao); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: #f9fafb;">
                                        <small class="text-muted">Serviços</small>
                                        <strong><?php echo formatar_moeda($prof['total_servicos']); ?></strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: #f9fafb;">
                                        <small class="text-muted">Produtos</small>
                                        <strong><?php echo formatar_moeda($prof['total_produtos']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="rankingMes" class="d-none">
                    <?php if (empty($profissionais_mes)): ?>
                        <p class="text-muted text-center py-4">Nenhum atendimento este mês.</p>
                    <?php else: ?>
                        <?php foreach ($profissionais_mes as $index => $prof): 
                            $comissao_servico = $prof['total_servicos'] * ($prof['comissao_servico'] / 100);
                            $comissao_produto = $prof['total_produtos'] * ($prof['comissao_produto'] / 100);
                            $comissao = $comissao_servico + $comissao_produto;
                            $rank_class = $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-default'));
                        ?>
                        <div class="prof-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="prof-rank <?php echo $rank_class; ?>">
                                    <?php echo $index + 1; ?>º
                                </div>
                                
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($prof['nome']); ?></h5>
                                    <div class="d-flex gap-3 text-muted small">
                                        <span>
                                            <i class="fas fa-cut me-1"></i>
                                            <?php echo $prof['total_atendimentos']; ?> atendimentos
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <div class="h4 mb-0 text-success fw-bold">
                                        <?php echo formatar_moeda($total = $prof['total_servicos'] + $prof['total_produtos']); ?>
                                    </div>
                                    <small class="text-muted">
                                        Comissão: <?php echo formatar_moeda($comissao); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TOP SERVIÇOS
        <div class="col-lg-4">
            <div class="card-glass">
                <div class="card-glass-header">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Top Serviços Hoje
                    </h5>
                </div>
                
                <?php if (empty($top_servicos)): ?>
                    <p class="text-muted text-center py-4">Nenhum serviço realizado.</p>
                <?php else: ?>
                    <canvas id="chartServicos" height="250"></canvas>
                    
                    <div class="mt-3">
                        <?php foreach ($top_servicos as $servico): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: #f9fafb;">
                            <div>
                                <strong><?php echo htmlspecialchars($servico['nome']); ?></strong>
                                <small class="d-block text-muted"><?php echo $servico['qtd']; ?>x realizados</small>
                            </div>
                            <strong class="text-success"><?php echo formatar_moeda($servico['valor_total']); ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div> -->
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Toggle Dia/Mês
function toggleView(tipo) {
    if (tipo === 'dia') {
        document.getElementById('statsDia').classList.remove('d-none');
        document.getElementById('statsMes').classList.add('d-none');
        document.getElementById('rankingDia').classList.remove('d-none');
        document.getElementById('rankingMes').classList.add('d-none');
        document.getElementById('rankingTitle').textContent = 'Performance dos Profissionais - Hoje';
        document.querySelectorAll('.btn-group .btn').forEach((btn, i) => {
            btn.classList.toggle('active', i === 0);
        });
    } else {
        document.getElementById('statsDia').classList.add('d-none');
        document.getElementById('statsMes').classList.remove('d-none');
        document.getElementById('rankingDia').classList.add('d-none');
        document.getElementById('rankingMes').classList.remove('d-none');
        document.getElementById('rankingTitle').textContent = 'Performance dos Profissionais - Este Mês';
        document.querySelectorAll('.btn-group .btn').forEach((btn, i) => {
            btn.classList.toggle('active', i === 1);
        });
    }
}

// Gráfico de serviços
<?php if (!empty($top_servicos)): ?>
new Chart(document.getElementById('chartServicos'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($top_servicos, 'nome')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($top_servicos, 'qtd')); ?>,
            backgroundColor: ['#f06292', '#6d4c41', '#10b981', '#3b82f6', '#f59e0b'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>