<?php
// profissional/view_agenda_dia.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Agenda do Dia";
requer_login('profissional');
include '../includes/header.php';

$data = $_GET['data'] ?? date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT 
        a.id,
        a.data,
        a.hora_inicio,
        a.status,
        GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos
    FROM agendamentos a
    JOIN agendamento_itens ai ON ai.agendamento_id = a.id
    JOIN servicos s ON s.id = ai.servico_id
    WHERE a.profissional_id = ?
    GROUP BY a.id
    ORDER BY a.data, a.hora_inicio
");
$stmt->execute([$_SESSION['usuario_id']]);
$agenda = $stmt->fetchAll();
?>
<h2>Agenda - <?php echo formatar_data($data); ?></h2>
<div class="mb-3">
    <input type="date" id="selecionarData" class="form-control w-auto d-inline" value="<?php echo $data; ?>">
    <button class="btn btn-primary" onclick="mudarData()">Ir</button>
</div>

<div class="row row-cols-1 row-cols-md-2 g-4">
    <?php foreach ($agenda as $item): ?>
    <div class="col">
        <div class="card h-100 <?php echo $item['ag_status'] === 'finalizado' ? 'border-success' : ''; ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo formatar_hora($item['hora_inicio']); ?> - <?php echo htmlspecialchars($item['servico']); ?></h5>
                <p class="card-text">
                    <strong>Cliente:</strong> <?php echo htmlspecialchars($item['cliente']); ?><br>
                    <strong>Telefone:</strong> <?php echo $item['telefone'] ?? '—'; ?><br>
                    <strong>Valor:</strong> <?php echo formatar_moeda($item['preco']); ?>
                </p>
                <div class="btn-group" role="group">
                    <?php if ($item['ag_status'] === 'agendado'): ?>
                    <a href="handle_agenda_action.php?action=chegada&item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-outline-info">Chegada</a>
                    <?php elseif ($item['ag_status'] === 'confirmado'): ?>
                    <a href="handle_agenda_action.php?action=iniciar&item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary">Iniciar</a>
                    <?php elseif ($item['ag_status'] === 'em_atendimento'): ?>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#finalizarModal<?php echo $item['item_id']; ?>">
                        Finalizar
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#vendaModal<?php echo $item['item_id']; ?>">
                        Vender Produto
                    </button>
                </div>
            </div>
            <div class="card-footer text-muted small">
                Status: <?php echo ucfirst(str_replace('_', ' ', $item['ag_status'])); ?>
            </div>
        </div>

        <!-- Modal Finalizar -->
        <div class="modal fade" id="finalizarModal<?php echo $item['item_id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <form action="handle_add_atendimento.php" method="POST">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Finalizar Atendimento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                            <p>Confirma o término do serviço <strong><?php echo htmlspecialchars($item['servico']); ?></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Finalizar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Venda Produto -->
        <div class="modal fade" id="vendaModal<?php echo $item['item_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="handle_add_venda_produto.php" method="POST">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Vender Produto</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                            <div id="produtosContainer<?php echo $item['item_id']; ?>"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">Registrar Venda</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function mudarData() {
    const data = document.getElementById('selecionarData').value;
    location.href = '?data=' + data;
}

// Carregar produtos no modal
document.querySelectorAll('[data-bs-target^="vendaModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const itemId = btn.dataset.bsTarget.split('vendaModal')[1];
        fetch('../api/get_produtos.php')
            .then(r => r.json())
            .then(prods => {
                let html = '<table class="table"><thead><tr><th>Produto</th><th>Preço</th><th>Quantidade</th></tr></thead><tbody>';
                prods.forEach(p => {
                    html += `<tr>
                        <td>${p.nome}</td>
                        <td>${p.preco}</td>
                        <td><input type="number" name="produtos[${p.id}][qtd]" min="0" class="form-control form-control-sm" style="width:80px;"></td>
                        <input type="hidden" name="produtos[${p.id}][preco]" value="${p.preco.replace('R$ ', '').replace(',', '.')}">
                    </tr>`;
                });
                html += '</tbody></table>';
                document.getElementById('produtosContainer' + itemId).innerHTML = html;
            });
    });
});
</script>
<?php include '../includes/footer.php'; ?>