<?php
// admin/manage_profissionais.php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
$titulo = "Gerenciar Profissionais";
requer_login('admin');

$profs = $pdo->query("SELECT * FROM usuarios WHERE tipo = 'profissional' AND ativo = 1")->fetchAll();
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-gray-800">Profissionais</h2>
        <a href="manage_profissionais_form.php" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Novo Profissional
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profs as $p): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2 bg-primary text-white">
                                        <?php echo strtoupper(substr($p['nome'], 0, 1)); ?>
                                    </div>
                                    <span class="fw-bold"><?php echo htmlspecialchars($p['nome']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($p['email']); ?></td>
                            <td><?php echo $p['telefone'] ?? '<span class="text-muted small">—</span>'; ?></td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm shadow-sm border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item py-2" href="manage_profissionais_form.php?id=<?php echo $p['id']; ?>">
                                            <i class="fas fa-edit fa-sm fa-fw me-2 text-warning"></i> Editar Dados</a>
                                        </li>
                                        <li><a class="dropdown-item py-2" href="manage_profissional_servicos.php?id=<?php echo $p['id']; ?>">
                                            <i class="fas fa-scissors fa-sm fa-fw me-2 text-info"></i> Serviços</a>
                                        </li>
                                        <li><a class="dropdown-item py-2" href="manage_profissional_comissoes.php?id=<?php echo $p['id']; ?>">
                                            <i class="fas fa-percentage fa-sm fa-fw me-2 text-secondary"></i> Comissões</a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" 
                                               data-id="<?php echo $p['id']; ?>" 
                                               data-nome="<?php echo htmlspecialchars($p['nome']); ?>" 
                                               onclick="abrirModalRemocao(this)">
                                            <i class="fas fa-trash-alt fa-sm fa-fw me-2"></i> Remover</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS para o círculo com a inicial do nome */
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
}
/* Melhora o toque no mobile */
.dropdown-item {
    transition: all 0.2s;
}
.dropdown-item:active {
    background-color: #f8f9fa;
    color: inherit;
}
</style>

<?php include '../includes/footer.php'; ?>

<div class="modal fade" id="modalRemoverProfissional" tabindex="-1" aria-hidden="true">
    ... (resto do seu modal)
</div>
<script>
    ... (resto do seu script)
</script>