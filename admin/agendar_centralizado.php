<?php
$titulo = 'Agendamento Centralizado';
require_once '../includes/header.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../includes/utils.php';

// Apenas admin e recepcionista têm acesso
requer_login(['admin', 'recepcionista']);

// Verificar se agenda centralizada está ativa
$config = $pdo->query("SELECT agenda_centralizada_ativa, agendamento_sem_profissional FROM configuracoes WHERE id = 1")->fetch();
if (!$config || !$config['agenda_centralizada_ativa']) {
    $_SESSION['flash'] = ['tipo' => 'warning', 'msg' => 'Agenda centralizada não está ativa. Ative nas configurações.'];
    header('Location: configuracoes.php');
    exit;
}

$permite_sem_profissional = $config['agendamento_sem_profissional'] ?? 0;

// Buscar profissionais ativos
$profissionais = $pdo->query("
    SELECT id, nome
    FROM usuarios
    WHERE tipo = 'profissional' AND ativo = 1
    ORDER BY nome
")->fetchAll();

// Buscar serviços ativos
$servicos = $pdo->query("
    SELECT id, nome, preco, duracao_min
    FROM servicos
    WHERE ativo = 1
    ORDER BY nome
")->fetchAll();
?>

<style>
    .search-results {
        position: absolute;
        z-index: 1000;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-height: 300px;
        overflow-y: auto;
        width: 100%;
        display: none;
    }

    .search-results.active {
        display: block;
    }

    .search-result-item {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }

    .search-result-item:hover {
        background: #f8f9fa;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-name {
        font-weight: 600;
        color: #333;
    }

    .search-result-info {
        font-size: 0.875rem;
        color: #666;
    }

    .badge-usuario {
        background: #667eea;
    }

    .badge-rapido {
        background: #f59e0b;
    }

    .servico-item {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }

    .servico-item.selected {
        border-color: #667eea;
        background: #f0f4ff;
    }

    .preco-customizado-input {
        display: none;
    }

    .preco-customizado-input.active {
        display: block;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-calendar-plus me-2"></i>Agendamento Centralizado
                </h4>
                <small>Agende atendimentos para clientes de forma rápida e fácil</small>
            </div>
            <div class="card-body">
                <form id="formAgendamento">

                    <!-- Busca de Cliente -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>1. Cliente</h5>

                            <div class="position-relative">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text"
                                           class="form-control form-control-lg"
                                           id="buscarCliente"
                                           placeholder="Digite nome ou telefone do cliente..."
                                           autocomplete="off">
                                </div>

                                <div id="resultadosBusca" class="search-results"></div>
                            </div>

                            <!-- Cliente Selecionado -->
                            <div id="clienteSelecionado" class="alert alert-info mt-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-check me-2"></i>
                                        <strong id="clienteNome"></strong>
                                        <span id="clienteTelefone" class="text-muted ms-2"></span>
                                        <span id="clienteTipo" class="badge ms-2"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="limparCliente()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Botão para novo cliente -->
                            <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
                                <i class="fas fa-user-plus me-2"></i>Novo Cliente Rápido
                            </button>

                            <input type="hidden" id="clienteId" name="cliente_id">
                            <input type="hidden" id="clienteTipoInput" name="cliente_tipo">
                        </div>
                    </div>

                    <hr>

                    <!-- Profissional -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                <i class="fas fa-user-tie me-2"></i>2. Profissional
                                <?php if ($permite_sem_profissional): ?>
                                    <small class="text-muted">(Opcional)</small>
                                <?php endif; ?>
                            </h5>
                            <select class="form-select form-select-lg" id="profissionalId" name="profissional_id" <?php echo !$permite_sem_profissional ? 'required' : ''; ?>>
                                <option value="">
                                    <?php echo $permite_sem_profissional ? 'Nenhum (primeiro disponível)' : 'Selecione um profissional'; ?>
                                </option>
                                <?php foreach ($profissionais as $prof): ?>
                                    <option value="<?php echo $prof['id']; ?>">
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($permite_sem_profissional): ?>
                                <small class="text-muted mt-1 d-block">
                                    <i class="fas fa-info-circle me-1"></i>Deixe vazio para atender com qualquer profissional disponível
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- Data e Hora -->
                        <div class="col-md-3">
                            <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>3. Data</h5>
                            <input type="date"
                                   class="form-control form-control-lg"
                                   id="dataAgendamento"
                                   name="data_agendamento"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   required>
                        </div>

                        <div class="col-md-3">
                            <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Horário</h5>
                            <input type="time"
                                   class="form-control form-control-lg"
                                   id="horaAgendamento"
                                   name="hora_agendamento"
                                   required>
                        </div>
                    </div>

                    <hr>

                    <!-- Serviços -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-scissors me-2"></i>4. Serviços</h5>

                            <div id="servicosLista">
                                <?php foreach ($servicos as $servico): ?>
                                    <div class="servico-item" data-servico-id="<?php echo $servico['id']; ?>">
                                        <div class="form-check">
                                            <input class="form-check-input servico-checkbox"
                                                   type="checkbox"
                                                   name="servicos[]"
                                                   value="<?php echo $servico['id']; ?>"
                                                   id="servico<?php echo $servico['id']; ?>"
                                                   data-preco="<?php echo $servico['preco']; ?>"
                                                   data-duracao="<?php echo $servico['duracao_min']; ?>">
                                            <label class="form-check-label w-100" for="servico<?php echo $servico['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($servico['nome']); ?></strong>
                                                        <small class="text-muted d-block">
                                                            Duração: <?php echo $servico['duracao_min']; ?> min
                                                        </small>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-success preco-padrao">
                                                            <?php echo formatar_moeda($servico['preco']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <!-- Preço Customizado -->
                                        <div class="preco-customizado-input mt-2" id="precoCustomizado<?php echo $servico['id']; ?>">
                                            <div class="input-group">
                                                <span class="input-group-text">R$</span>
                                                <input type="number"
                                                       class="form-control"
                                                       name="preco_customizado[<?php echo $servico['id']; ?>]"
                                                       placeholder="Preço customizado"
                                                       step="0.01"
                                                       min="0">
                                                <button class="btn btn-outline-secondary" type="button" onclick="removerPrecoCustomizado(<?php echo $servico['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Deixe vazio para usar preço padrão</small>
                                        </div>

                                        <!-- Botão para adicionar preço customizado -->
                                        <button type="button"
                                                class="btn btn-sm btn-link text-decoration-none p-0 mt-1 btn-preco-customizado"
                                                onclick="mostrarPrecoCustomizado(<?php echo $servico['id']; ?>)"
                                                style="display: none;">
                                            <i class="fas fa-dollar-sign me-1"></i>Definir preço customizado
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Total Estimado -->
                            <div class="alert alert-light border mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Total Estimado:</strong>
                                    <h4 class="mb-0 text-primary" id="totalEstimado">R$ 0,00</h4>
                                </div>
                                <small class="text-muted">Duração total: <span id="duracaoTotal">0</span> minutos</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Observações -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3"><i class="fas fa-comment me-2"></i>5. Observações (Opcional)</h5>
                            <textarea class="form-control"
                                      name="observacoes"
                                      rows="3"
                                      placeholder="Observações sobre o agendamento..."></textarea>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check me-2"></i>Confirmar Agendamento
                            </button>
                            <a href="view_agenda_geral.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Cliente -->
<div class="modal fade" id="modalNovoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Cadastro Rápido de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoCliente">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="novoClienteNome" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Telefone *</label>
                        <input type="tel" class="form-control" id="novoClienteTelefone" placeholder="(XX) XXXXX-XXXX" required>
                        <small class="text-muted">Digite apenas números</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Data de Nascimento</label>
                        <input type="date" class="form-control" id="novoClienteDataNascimento" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" id="novoClienteObs" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="cadastrarClienteRapido()">
                    <i class="fas fa-save me-2"></i>Cadastrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let clienteSelecionado = null;
let debounceTimer = null;

// Busca de clientes com debounce
document.getElementById('buscarCliente').addEventListener('input', function(e) {
    const termo = e.target.value.trim();

    clearTimeout(debounceTimer);

    if (termo.length < 2) {
        document.getElementById('resultadosBusca').classList.remove('active');
        return;
    }

    debounceTimer = setTimeout(() => {
        buscarClientes(termo);
    }, 300);
});

async function buscarClientes(termo) {
    try {
        const response = await fetch(`api_buscar_clientes.php?termo=${encodeURIComponent(termo)}`);
        const data = await response.json();

        if (data.success) {
            mostrarResultados(data.clientes);
        } else {
            console.error('Erro na busca:', data.error);
        }
    } catch (error) {
        console.error('Erro ao buscar clientes:', error);
    }
}

function mostrarResultados(clientes) {
    const container = document.getElementById('resultadosBusca');

    if (clientes.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted">Nenhum cliente encontrado</div>';
        container.classList.add('active');
        return;
    }

    container.innerHTML = clientes.map(cliente => `
        <div class="search-result-item" onclick='selecionarCliente(${JSON.stringify(cliente)})'>
            <div class="search-result-name">${cliente.nome}</div>
            <div class="search-result-info">
                ${cliente.telefone}
                ${cliente.email ? ' • ' + cliente.email : ''}
                <span class="badge badge-${cliente.tipo} ms-2">${cliente.tipo === 'usuario' ? 'Cadastrado' : 'Rápido'}</span>
            </div>
        </div>
    `).join('');

    container.classList.add('active');
}

function selecionarCliente(cliente) {
    clienteSelecionado = cliente;

    document.getElementById('clienteId').value = cliente.id;
    document.getElementById('clienteTipoInput').value = cliente.tipo;
    document.getElementById('clienteNome').textContent = cliente.nome;
    document.getElementById('clienteTelefone').textContent = cliente.telefone;
    document.getElementById('clienteTipo').textContent = cliente.tipo === 'usuario' ? 'Cadastrado' : 'Rápido';
    document.getElementById('clienteTipo').className = 'badge ms-2 badge-' + cliente.tipo;

    document.getElementById('clienteSelecionado').style.display = 'block';
    document.getElementById('resultadosBusca').classList.remove('active');
    document.getElementById('buscarCliente').value = '';
}

function limparCliente() {
    clienteSelecionado = null;
    document.getElementById('clienteSelecionado').style.display = 'none';
    document.getElementById('clienteId').value = '';
    document.getElementById('clienteTipoInput').value = '';
}

// Fechar resultados ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('#buscarCliente') && !e.target.closest('#resultadosBusca')) {
        document.getElementById('resultadosBusca').classList.remove('active');
    }
});

// Cadastrar cliente rápido
async function cadastrarClienteRapido() {
    const nome = document.getElementById('novoClienteNome').value.trim();
    const telefone = document.getElementById('novoClienteTelefone').value.trim();
    const dataNascimento = document.getElementById('novoClienteDataNascimento').value;
    const observacoes = document.getElementById('novoClienteObs').value.trim();

    if (!nome || !telefone) {
        alert('Nome e telefone são obrigatórios');
        return;
    }

    try {
        const response = await fetch('api_cadastrar_cliente_rapido.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({nome, telefone, data_nascimento: dataNascimento, observacoes})
        });

        const data = await response.json();

        if (data.success) {
            selecionarCliente(data.cliente);
            bootstrap.Modal.getInstance(document.getElementById('modalNovoCliente')).hide();
            document.getElementById('formNovoCliente').reset();
        } else {
            alert(data.error);
        }
    } catch (error) {
        alert('Erro ao cadastrar cliente: ' + error.message);
    }
}

// Gestão de serviços e preços
document.querySelectorAll('.servico-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const servicoItem = this.closest('.servico-item');
        const btnPrecoCustomizado = servicoItem.querySelector('.btn-preco-customizado');

        if (this.checked) {
            servicoItem.classList.add('selected');
            btnPrecoCustomizado.style.display = 'block';
        } else {
            servicoItem.classList.remove('selected');
            btnPrecoCustomizado.style.display = 'none';
            removerPrecoCustomizado(this.value);
        }

        calcularTotal();
    });
});

function mostrarPrecoCustomizado(servicoId) {
    document.getElementById('precoCustomizado' + servicoId).classList.add('active');
    document.querySelector(`[data-servico-id="${servicoId}"] .btn-preco-customizado`).style.display = 'none';
}

function removerPrecoCustomizado(servicoId) {
    const container = document.getElementById('precoCustomizado' + servicoId);
    container.classList.remove('active');
    container.querySelector('input').value = '';
    document.querySelector(`[data-servico-id="${servicoId}"] .btn-preco-customizado`).style.display = 'block';
    calcularTotal();
}

function calcularTotal() {
    let total = 0;
    let duracao = 0;

    document.querySelectorAll('.servico-checkbox:checked').forEach(checkbox => {
        const servicoId = checkbox.value;
        const precoCustomizadoInput = document.querySelector(`input[name="preco_customizado[${servicoId}]"]`);
        const precoCustomizado = precoCustomizadoInput && precoCustomizadoInput.value ? parseFloat(precoCustomizadoInput.value) : null;

        const preco = precoCustomizado !== null ? precoCustomizado : parseFloat(checkbox.dataset.preco);
        total += preco;
        duracao += parseInt(checkbox.dataset.duracao);
    });

    document.getElementById('totalEstimado').textContent = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(total);

    document.getElementById('duracaoTotal').textContent = duracao;
}

// Submeter formulário
document.getElementById('formAgendamento').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!clienteSelecionado) {
        alert('Selecione um cliente');
        return;
    }

    const servicosSelecionados = Array.from(document.querySelectorAll('.servico-checkbox:checked'));
    if (servicosSelecionados.length === 0) {
        alert('Selecione pelo menos um serviço');
        return;
    }

    const formData = new FormData(this);

    try {
        const response = await fetch('handle_agendar_centralizado.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = 'view_agenda_geral.php?msg=Agendamento realizado com sucesso';
        } else {
            alert(data.error);
        }
    } catch (error) {
        alert('Erro ao processar agendamento: ' + error.message);
    }
});

// Máscara de telefone
document.getElementById('novoClienteTelefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 10) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
    } else if (value.length > 6) {
        value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
    } else if (value.length > 2) {
        value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
    }

    e.target.value = value;
});
</script>

<?php require_once '../includes/footer.php'; ?>
