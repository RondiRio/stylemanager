<?php
// admin/fechamento_caixa.php - Fechamento de Caixa
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Fechamento de Caixa";
requer_login('admin');

// Buscar configuração de período
$config = $pdo->query("SELECT tipo_fechamento FROM configuracoes WHERE id = 1")->fetch();
$tipo_fechamento = $config['tipo_fechamento'] ?? 'mensal';

// Calcular datas do período atual
$hoje = date('Y-m-d');
switch ($tipo_fechamento) {
    case 'diario':
        $data_inicio = $hoje;
        $data_fim = $hoje;
        break;
    case 'semanal':
        $data_inicio = date('Y-m-d', strtotime('monday this week'));
        $data_fim = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'quinzenal':
        $dia = (int)date('d');
        if ($dia <= 15) {
            $data_inicio = date('Y-m-01');
            $data_fim = date('Y-m-15');
        } else {
            $data_inicio = date('Y-m-16');
            $data_fim = date('Y-m-t');
        }
        break;
    case 'mensal':
    default:
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-t');
        break;
}

// Override se vier do formulário
if (isset($_GET['data_inicio']) && isset($_GET['data_fim'])) {
    $data_inicio = $_GET['data_inicio'];
    $data_fim = $_GET['data_fim'];
}

$profissional_id = $_GET['profissional_id'] ?? null;

// Buscar profissionais com cálculo de valores
$stmt = $pdo->prepare("
    SELECT
        u.id, u.nome, u.avatar,
        (SELECT COUNT(*) FROM fechamentos_caixa WHERE profissional_id = u.id AND status = 'aberto') as tem_aberto,
        (SELECT MAX(data_fim) FROM fechamentos_caixa WHERE profissional_id = u.id AND status = 'pago') as ultimo_pagamento
    FROM usuarios u
    WHERE u.tipo = 'profissional' AND u.ativo = 1
    ORDER BY u.nome
");
$stmt->execute();
$profissionais = $stmt->fetchAll();

// Se tiver profissional selecionado, calcular valores
$dados_fechamento = null;
if ($profissional_id) {
    // Comissões
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(sr.preco * c.servico / 100), 0) as total_comissoes,
            COUNT(DISTINCT a.id) as qtd_atendimentos
        FROM atendimentos a
        JOIN servicos_realizados sr ON sr.atendimento_id = a.id
        LEFT JOIN comissoes c ON c.profissional_id = a.profissional_id
        WHERE a.profissional_id = ?
          AND a.data_atendimento BETWEEN ? AND ?
          AND a.status = 'concluido'
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);
    $comissoes = $stmt->fetch();

    // Gorjetas aprovadas
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(valor), 0) as total_gorjetas,
            COUNT(*) as qtd_gorjetas
        FROM gorjetas
        WHERE profissional_id = ?
          AND data_gorjeta BETWEEN ? AND ?
          AND status = 'aprovado'
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);
    $gorjetas = $stmt->fetch();

    // Vales aprovados
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(valor), 0) as total_vales,
            COUNT(*) as qtd_vales
        FROM vales
        WHERE profissional_id = ?
          AND data_vale BETWEEN ? AND ?
          AND status = 'aprovado'
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);
    $vales = $stmt->fetch();

    $dados_fechamento = [
        'comissoes' => $comissoes,
        'gorjetas' => $gorjetas,
        'vales' => $vales,
        'total_liquido' => $comissoes['total_comissoes'] + $gorjetas['total_gorjetas'] - $vales['total_vales']
    ];
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-cash-register me-2"></i>Fechamento de Caixa
        <small class="text-muted">(<?php echo ucfirst($tipo_fechamento); ?>)</small>
    </h2>

    <!-- Período -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>" required>
                </div>
                <?php if ($profissional_id): ?>
                <input type="hidden" name="profissional_id" value="<?php echo $profissional_id; ?>">
                <?php endif; ?>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Coluna: Lista de Profissionais -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Profissionais</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                    <?php foreach ($profissionais as $p): ?>
                    <a href="?profissional_id=<?php echo $p['id']; ?>&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>"
                       class="list-group-item list-group-item-action <?php echo $profissional_id == $p['id'] ? 'active' : ''; ?>">
                        <div class="d-flex align-items-center">
                            <img src="../assets/img/avatars/<?php echo $p['avatar'] ?? 'default.png'; ?>"
                                 class="rounded-circle me-2" width="40" height="40" alt="">
                            <div class="flex-grow-1">
                                <strong><?php echo htmlspecialchars($p['nome']); ?></strong>
                                <?php if ($p['tem_aberto']): ?>
                                    <br><small class="badge bg-warning text-dark">Fechamento Aberto</small>
                                <?php elseif ($p['ultimo_pagamento']): ?>
                                    <br><small class="text-muted">Último: <?php echo date('d/m/Y', strtotime($p['ultimo_pagamento'])); ?></small>
                                <?php endif; ?>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Coluna: Detalhes do Fechamento -->
        <div class="col-md-8">
            <?php if (!$profissional_id): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-hand-point-left fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Selecione um profissional para visualizar o fechamento</p>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Fechamento: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Resumo -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-percent fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-0 text-success"><?php echo formatar_moeda($dados_fechamento['comissoes']['total_comissoes']); ?></h4>
                                    <small class="text-muted">Comissões<br>(<?php echo $dados_fechamento['comissoes']['qtd_atendimentos']; ?> atend.)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-0 text-success"><?php echo formatar_moeda($dados_fechamento['gorjetas']['total_gorjetas']); ?></h4>
                                    <small class="text-muted">Gorjetas<br>(<?php echo $dados_fechamento['gorjetas']['qtd_gorjetas']; ?> aprovadas)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-hand-holding-usd fa-2x text-danger mb-2"></i>
                                    <h4 class="mb-0 text-danger">-<?php echo formatar_moeda($dados_fechamento['vales']['total_vales']); ?></h4>
                                    <small class="text-muted">Vales<br>(<?php echo $dados_fechamento['vales']['qtd_vales']; ?> aprovados)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                    <h4 class="mb-0"><?php echo formatar_moeda($dados_fechamento['total_liquido']); ?></h4>
                                    <small>TOTAL LÍQUIDO</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulário de Fechamento -->
                    <form method="POST" action="handle_fechamento_caixa.php">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <input type="hidden" name="profissional_id" value="<?php echo $profissional_id; ?>">
                        <input type="hidden" name="data_inicio" value="<?php echo $data_inicio; ?>">
                        <input type="hidden" name="data_fim" value="<?php echo $data_fim; ?>">
                        <input type="hidden" name="total_comissoes" value="<?php echo $dados_fechamento['comissoes']['total_comissoes']; ?>">
                        <input type="hidden" name="total_gorjetas" value="<?php echo $dados_fechamento['gorjetas']['total_gorjetas']; ?>">
                        <input type="hidden" name="total_vales" value="<?php echo $dados_fechamento['vales']['total_vales']; ?>">
                        <input type="hidden" name="total_liquido" value="<?php echo $dados_fechamento['total_liquido']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Observações (opcional)</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Ex: Pagamento via PIX, desconto especial, etc."></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="fechamento_caixa.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Processar Fechamento e Gerar PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
