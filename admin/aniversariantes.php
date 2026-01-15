<?php
$titulo = 'Aniversariantes';
require_once '../includes/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

// Apenas admin e recepcionista
requer_login(['admin', 'recepcionista']);

// Verificar se sistema de aniversários está ativo
$config = $pdo->query("SELECT lembrar_aniversarios FROM configuracoes WHERE id = 1")->fetch();
if (!$config || !($config['lembrar_aniversarios'] ?? 1)) {
    $_SESSION['flash'] = ['tipo' => 'warning', 'msg' => 'Sistema de aniversários não está ativo'];
    header('Location: dashboard.php');
    exit;
}

$hoje = date('m-d');
$mes_atual = date('m');

// Aniversariantes do dia (usuários + clientes rápidos)
$stmt = $pdo->prepare("
    SELECT
        id, nome, telefone, data_nascimento, 'usuario' as tipo,
        YEAR(CURDATE()) - YEAR(data_nascimento) as idade
    FROM usuarios
    WHERE DATE_FORMAT(data_nascimento, '%m-%d') = ?
      AND tipo = 'cliente'
    UNION ALL
    SELECT
        id, nome, telefone, data_nascimento, 'rapido' as tipo,
        YEAR(CURDATE()) - YEAR(data_nascimento) as idade
    FROM clientes_rapidos
    WHERE DATE_FORMAT(data_nascimento, '%m-%d') = ?
    ORDER BY nome
");
$stmt->execute([$hoje, $hoje]);
$aniversariantes_hoje = $stmt->fetchAll();

// Aniversariantes do mês
$stmt = $pdo->prepare("
    SELECT
        id, nome, telefone, data_nascimento, 'usuario' as tipo,
        YEAR(CURDATE()) - YEAR(data_nascimento) as idade,
        DAY(data_nascimento) as dia
    FROM usuarios
    WHERE MONTH(data_nascimento) = ?
      AND tipo = 'cliente'
    UNION ALL
    SELECT
        id, nome, telefone, data_nascimento, 'rapido' as tipo,
        YEAR(CURDATE()) - YEAR(data_nascimento) as idade,
        DAY(data_nascimento) as dia
    FROM clientes_rapidos
    WHERE MONTH(data_nascimento) = ?
    ORDER BY dia, nome
");
$stmt->execute([$mes_atual, $mes_atual]);
$aniversariantes_mes = $stmt->fetchAll();
?>

<style>
    .birthday-card {
        transition: transform 0.2s;
        border-left: 4px solid var(--cor-primaria);
    }

    .birthday-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .birthday-icon {
        font-size: 2rem;
        color: var(--cor-secundaria);
    }

    .age-badge {
        font-size: 1.1rem;
        font-weight: bold;
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-birthday-cake me-2"></i>Aniversariantes
                </h4>
                <small>Comemore com seus clientes!</small>
            </div>
        </div>
    </div>
</div>

<!-- Aniversariantes de Hoje -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="mb-0">
                    <i class="fas fa-gift me-2"></i>Aniversariantes de Hoje (<?php echo date('d/m'); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($aniversariantes_hoje)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum aniversariante hoje
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($aniversariantes_hoje as $cliente): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card birthday-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="birthday-icon me-3">
                                                🎂
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($cliente['nome']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($cliente['telefone']); ?>
                                                </small>
                                                <br>
                                                <span class="badge bg-primary age-badge mt-2">
                                                    <?php echo $cliente['idade']; ?> anos
                                                </span>
                                                <?php if ($cliente['tipo'] === 'rapido'): ?>
                                                    <span class="badge bg-warning text-dark ms-1">Cliente Rápido</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>?text=<?php echo urlencode('Parabéns pelo seu aniversário! 🎉🎂 Desejamos muitas felicidades! 🎈'); ?>"
                                               class="btn btn-success btn-sm w-100"
                                               target="_blank">
                                                <i class="fab fa-whatsapp me-2"></i>Enviar mensagem
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Aniversariantes do Mês -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>Todos os Aniversariantes de <?php echo strftime('%B', mktime(0, 0, 0, $mes_atual, 1)); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($aniversariantes_mes)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum aniversariante neste mês
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="100">Dia</th>
                                    <th>Nome</th>
                                    <th>Telefone</th>
                                    <th>Idade</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aniversariantes_mes as $cliente): ?>
                                    <tr class="<?php echo DATE_FORMAT(new DateTime($cliente['data_nascimento']), 'm-d') === $hoje ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong><?php echo sprintf('%02d/%s', $cliente['dia'], $mes_atual); ?></strong>
                                            <?php if (DATE_FORMAT(new DateTime($cliente['data_nascimento']), 'm-d') === $hoje): ?>
                                                <span class="badge bg-warning text-dark ms-1">HOJE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                        <td><?php echo $cliente['idade']; ?> anos</td>
                                        <td>
                                            <?php if ($cliente['tipo'] === 'rapido'): ?>
                                                <span class="badge bg-warning text-dark">Rápido</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Cadastrado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>?text=<?php echo urlencode('Parabéns pelo seu aniversário! 🎉🎂'); ?>"
                                               class="btn btn-success btn-sm"
                                               target="_blank"
                                               title="Enviar mensagem no WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
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
</div>

<?php require_once '../includes/footer.php'; ?>
