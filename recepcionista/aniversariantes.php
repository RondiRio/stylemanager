<?php
/**
 * Aniversariantes do Mês - Recepcionista
 */
$titulo = 'Aniversariantes';
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';

// Apenas admin e recepcionista
requer_login(['admin', 'recepcionista']);

// Buscar filtros
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Buscar aniversariantes do mês
$aniversariantes = [];

// Usuários clientes
try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            nome,
            email,
            telefone,
            data_nascimento,
            YEAR(CURDATE()) - YEAR(data_nascimento) as idade,
            DAY(data_nascimento) as dia
        FROM usuarios
        WHERE tipo = 'cliente'
          AND MONTH(data_nascimento) = ?
          AND ativo = 1
        ORDER BY DAY(data_nascimento), nome
    ");
    $stmt->execute([$mes]);
    $usuarios = $stmt->fetchAll();

    foreach ($usuarios as $u) {
        $aniversariantes[] = [
            'tipo' => 'usuario',
            'id' => $u['id'],
            'nome' => $u['nome'],
            'email' => $u['email'],
            'telefone' => $u['telefone'],
            'data_nascimento' => $u['data_nascimento'],
            'idade' => $u['idade'],
            'dia' => $u['dia']
        ];
    }
} catch (PDOException $e) {
    // Continuar se houver erro
}

// Clientes rápidos
try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            nome,
            telefone,
            data_nascimento,
            YEAR(CURDATE()) - YEAR(data_nascimento) as idade,
            DAY(data_nascimento) as dia
        FROM clientes_rapidos
        WHERE MONTH(data_nascimento) = ?
        ORDER BY DAY(data_nascimento), nome
    ");
    $stmt->execute([$mes]);
    $rapidos = $stmt->fetchAll();

    foreach ($rapidos as $r) {
        $aniversariantes[] = [
            'tipo' => 'rapido',
            'id' => $r['id'],
            'nome' => $r['nome'],
            'email' => null,
            'telefone' => $r['telefone'],
            'data_nascimento' => $r['data_nascimento'],
            'idade' => $r['idade'],
            'dia' => $r['dia']
        ];
    }
} catch (PDOException $e) {
    // Continuar se houver erro
}

// Ordenar por dia
usort($aniversariantes, function($a, $b) {
    return $a['dia'] - $b['dia'];
});

$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-birthday-cake me-2"></i>
                    Aniversariantes - <?php echo $meses[$mes]; ?> <?php echo $ano; ?>
                </h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label for="mes" class="form-label">Mês</label>
                            <select name="mes" id="mes" class="form-select">
                                <?php foreach ($meses as $num => $nome): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $num == $mes ? 'selected' : ''; ?>>
                                        <?php echo $nome; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="ano" class="form-label">Ano</label>
                            <select name="ano" id="ano" class="form-select">
                                <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $ano ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Aniversariantes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        <?php echo count($aniversariantes); ?> Aniversariante(s) encontrado(s)
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($aniversariantes)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                            <p class="mb-0">Nenhum aniversariante neste mês</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dia</th>
                                        <th>Nome</th>
                                        <th>Idade</th>
                                        <th>Telefone</th>
                                        <th>E-mail</th>
                                        <th>Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $dia_atual = (int)date('d');
                                    foreach ($aniversariantes as $aniv):
                                        $eh_hoje = ($aniv['dia'] == $dia_atual && $mes == date('m'));
                                        $classe = $eh_hoje ? 'table-success fw-bold' : '';
                                    ?>
                                        <tr class="<?php echo $classe; ?>">
                                            <td>
                                                <?php if ($eh_hoje): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-birthday-cake me-1"></i>HOJE
                                                    </span>
                                                <?php else: ?>
                                                    <?php echo str_pad($aniv['dia'], 2, '0', STR_PAD_LEFT); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($aniv['nome']); ?>
                                            </td>
                                            <td><?php echo $aniv['idade']; ?> anos</td>
                                            <td>
                                                <?php if ($aniv['telefone']): ?>
                                                    <a href="https://wa.me/55<?php echo preg_replace('/\D/', '', $aniv['telefone']); ?>"
                                                       target="_blank"
                                                       class="text-success">
                                                        <i class="fab fa-whatsapp me-1"></i>
                                                        <?php echo htmlspecialchars($aniv['telefone']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($aniv['email']): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($aniv['email']); ?>">
                                                        <?php echo htmlspecialchars($aniv['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $aniv['tipo'] === 'usuario' ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $aniv['tipo'] === 'usuario' ? 'Cadastrado' : 'Rápido'; ?>
                                                </span>
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
</div>

<?php include '../includes/footer.php'; ?>
