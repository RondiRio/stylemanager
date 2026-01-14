<?php
// cliente/view_agendamentos.php (VERSÃO CORRIGIDA)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Meus Agendamentos";
requer_login('cliente');
include '../includes/header.php';

$hoje = date('Y-m-d');

// Buscar agendamentos do cliente
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.data,
        a.hora_inicio,
        a.status,
        a.created_at,
        u.nome AS profissional_nome,
        u.avatar AS profissional_avatar,
        GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos,
        SUM(s.preco) AS valor_total
    FROM agendamentos a
    JOIN usuarios u ON u.id = a.profissional_id
    LEFT JOIN agendamento_itens ai ON ai.agendamento_id = a.id
    LEFT JOIN servicos s ON s.id = ai.servico_id
    WHERE a.cliente_id = ?
    GROUP BY a.id
    ORDER BY a.data DESC, a.hora_inicio DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Meus Agendamentos
                        </h4>
                        <a href="agendar.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>Novo Agendamento
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($agendamentos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Você ainda não tem agendamentos</h5>
                        <p class="text-muted mb-4">Agende seu primeiro serviço agora!</p>
                        <a href="agendar.php" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Fazer Agendamento
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Data/Hora</th>
                                    <th>Profissional</th>
                                    <th>Serviços</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $ag): 
                                    $dataHora = strtotime($ag['data'] . ' ' . $ag['hora_inicio']);
                                    $podeAvaliar = in_array($ag['status'], ['finalizado']);
                                    $podeCancelar = $ag['status'] === 'agendado' && $dataHora > (time() + 3600); // 1 hora de antecedência
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-calendar text-primary fa-2x"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo date('d/m/Y', strtotime($ag['data'])); ?></div>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo substr($ag['hora_inicio'], 0, 5); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/img/avatars/<?php echo htmlspecialchars($ag['profissional_avatar'] ?? 'default.png'); ?>" 
                                                 class="rounded-circle me-2" width="36" height="36" alt="">
                                            <span class="fw-medium"><?php echo htmlspecialchars($ag['profissional_nome']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php 
                                            $servicosArray = json_decode($ag['servicos'], true);
                                            $servicosNomes = is_array($servicosArray)
                                                ? implode(', ', array_column($servicosArray, 'nome'))
                                                : '—';

                                            echo htmlspecialchars($servicosNomes);
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            R$ <?php echo number_format($ag['valor_total'], 2, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'agendado' => 'bg-warning',
                                            'confirmado' => 'bg-info',
                                            'em_atendimento' => 'bg-primary',
                                            'finalizado' => 'bg-success',
                                            'cancelado' => 'bg-danger'
                                        ];
                                        $badgeClass = $badges[$ag['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ag['status'])); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($podeCancelar): ?>
                                            <a href="cancelar_agendamento.php?id=<?php echo $ag['id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('⚠️ Tem certeza que deseja cancelar este agendamento?')">
                                                <i class="fas fa-times"></i> Cancelar
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($podeAvaliar): ?>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#avaliarModal<?php echo $ag['id']; ?>">
                                                <i class="fas fa-star"></i> Avaliar
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!$podeCancelar && !$podeAvaliar): ?>
                                            <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal de Avaliação -->
                                <?php if ($podeAvaliar): ?>
                                <div class="modal fade" id="avaliarModal<?php echo $ag['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="handle_add_recomendacao.php" method="POST">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-star me-2"></i>Avaliar Atendimento
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $ag['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                                                    
                                                    <div class="text-center mb-4">
                                                        <img src="../assets/img/avatars/<?php echo htmlspecialchars($ag['profissional_avatar'] ?? 'default.png'); ?>" 
                                                             class="rounded-circle mb-3" width="80" height="80" alt="">
                                                        <h6><?php echo htmlspecialchars($ag['profissional_nome']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($ag['servicos']); ?></small>
                                                    </div>

                                                    <div class="mb-4">
                                                        <label class="form-label fw-bold text-center d-block mb-3">Como foi sua experiência?</label>
                                                        <div class="star-rating-large justify-content-center">
                                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" name="nota" value="<?php echo $i; ?>" 
                                                                   id="star<?php echo $i; ?>_<?php echo $ag['id']; ?>" required>
                                                            <label for="star<?php echo $i; ?>_<?php echo $ag['id']; ?>" 
                                                                   title="<?php echo $i; ?> estrela<?php echo $i > 1 ? 's' : ''; ?>">
                                                                <i class="fas fa-star"></i>
                                                            </label>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Deixe seu comentário (opcional)</label>
                                                        <textarea name="comentario" class="form-control" rows="4" 
                                                                  placeholder="Conte como foi sua experiência..."></textarea>
                                                        <small class="form-text text-muted">Seu feedback ajuda outros clientes e o profissional.</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-paper-plane me-2"></i>Enviar Avaliação
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($agendamentos)): ?>
                <div class="card-footer bg-light text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Total de <?php echo count($agendamentos); ?> agendamento<?php echo count($agendamentos) > 1 ? 's' : ''; ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para avaliação por estrelas */
.star-rating, .star-rating-large {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.star-rating input,
.star-rating-large input {
    display: none;
}

.star-rating label,
.star-rating-large label {
    cursor: pointer;
    width: 35px;
    height: 35px;
    margin: 0 2px;
    transition: all 0.2s;
}

.star-rating-large label {
    width: 45px;
    height: 45px;
}

.star-rating label i,
.star-rating-large label i {
    font-size: 1.8rem;
    color: #ddd;
    transition: all 0.2s;
}

.star-rating-large label i {
    font-size: 2.5rem;
}

.star-rating input:checked ~ label i,
.star-rating label:hover ~ label i,
.star-rating label:hover i,
.star-rating-large input:checked ~ label i,
.star-rating-large label:hover ~ label i,
.star-rating-large label:hover i {
    color: #ffc107;
    transform: scale(1.1);
}

/* Animação suave nas linhas da tabela */
.table tbody tr {
    transition: all 0.2s;
}

.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
    transform: translateX(5px);
}

/* Badge com sombra */
.badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Ícone de calendário com gradiente */
.fa-calendar.text-primary {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

<?php include '../includes/footer.php'; ?>