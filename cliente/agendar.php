<?php
// cliente/agendar.php (SISTEMA COMPLETO E FUNCIONAL)
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Agendar Serviço";
requer_login('cliente');
include '../includes/header.php';

// Buscar profissionais ativos
$profissionais = $pdo->query("
    SELECT id, nome, avatar, bio 
    FROM usuarios 
    WHERE tipo = 'profissional' AND ativo = 1 
    ORDER BY nome
")->fetchAll();

if (empty($profissionais)) {
    echo '<div class="alert alert-warning text-center"><i class="fas fa-exclamation-triangle me-2"></i>Nenhum profissional cadastrado no momento.</div>';
    include '../includes/footer.php';
    exit;
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Agendar Serviço</h4>
                    <small>Siga os passos abaixo para concluir seu agendamento</small>
                </div>
                <div class="card-body p-4">
                    <form id="formAgendamento" method="POST" action="../handlers/handle_agendamento.php">
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        <input type="hidden" name="servicos_json" id="servicos_json">
                        <input type="hidden" name="profissional_id" id="profissional_id_hidden">

                        <!-- PASSO 1: PROFISSIONAL -->
                        <div class="step-section mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number">1</div>
                                <h5 class="mb-0 ms-2">Escolha o Profissional</h5>
                            </div>
                            <div class="row g-3">
                                <?php foreach ($profissionais as $p): ?>
                                <div class="col-md-6">
                                    <label class="profissional-card" for="prof_<?php echo $p['id']; ?>">
                                        <input type="radio" name="profissional_id" id="prof_<?php echo $p['id']; ?>" 
                                               value="<?php echo $p['id']; ?>" class="d-none" required>
                                        <div class="card h-100 border-2">
                                            <div class="card-body d-flex align-items-center p-3">
                                                <img src="../assets/img/avatars/<?php echo htmlspecialchars($p['avatar'] ?? 'default.png'); ?>" 
                                                     class="rounded-circle me-3" width="60" height="60" alt="">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($p['nome']); ?></h6>
                                                    <?php if ($p['bio']): ?>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($p['bio'], 0, 60)); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <i class="fas fa-check-circle check-icon"></i>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- PASSO 2: SERVIÇOS -->
                        <div id="blocoServicos" class="step-section mb-4 d-none">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number">2</div>
                                <h5 class="mb-0 ms-2">Selecione os Serviços</h5>
                            </div>
                            <button type="button" class="btn btn-outline-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalServicos">
                                <i class="fas fa-plus me-2"></i>Adicionar Serviços
                            </button>
                            <div id="carrinho"></div>
                        </div>

                        <!-- PASSO 3: DATA E HORÁRIO -->
                        <div id="blocoAgenda" class="step-section mb-4 d-none">
                            <div class="d-flex align-items-center mb-3">
                                <div class="step-number">3</div>
                                <h5 class="mb-0 ms-2">Data e Horário</h5>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Selecione a Data</label>
                                    <input type="date" name="data" id="data_input" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div id="horariosContainer"></div>
                        </div>

                        <!-- BOTÃO CONFIRMAR -->
                        <div id="blocoFinal" class="text-end d-none">
                            <hr class="my-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-check me-2"></i>Confirmar Agendamento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SERVIÇOS -->
<div class="modal fade" id="modalServicos" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-cut me-2"></i>Serviços Disponíveis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="buscaServico" class="form-control mb-3" 
                       placeholder="Buscar serviço...">
                <div id="listaServicos" class="row g-3"></div>
            </div>
        </div>
    </div>
</div>

<style>
.step-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.profissional-card {
    cursor: pointer;
    display: block;
    margin: 0;
}

.profissional-card .card {
    transition: all 0.2s;
    border-color: #dee2e6;
}

.profissional-card:hover .card {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.profissional-card input:checked + .card {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.profissional-card .check-icon {
    color: #dee2e6;
    font-size: 1.5rem;
}

.profissional-card input:checked + .card .check-icon {
    color: #0d6efd;
}

.servico-card {
    transition: transform 0.2s;
    cursor: pointer;
}

.servico-card:hover {
    transform: translateY(-4px);
}

.horario-btn {
    transition: all 0.2s;
}

.horario-btn:hover:not(:disabled) {
    transform: scale(1.05);
}

.horario-btn input:checked + label {
    background-color: #0d6efd;
    color: white;
}
</style>

<script>
let servicosDisponiveis = [];
let carrinho = [];
let profissionalIdSelecionado = null;

// === SELECIONAR PROFISSIONAL ===
document.querySelectorAll('input[name="profissional_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        profissionalIdSelecionado = this.value;
        document.getElementById('profissional_id_hidden').value = this.value;
        document.getElementById('blocoServicos').classList.remove('d-none');
        carrinho = [];
        atualizarCarrinho();
        
        // Scroll suave
        document.getElementById('blocoServicos').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

// === ABRIR MODAL E CARREGAR SERVIÇOS ===
document.getElementById('modalServicos').addEventListener('show.bs.modal', async function() {
    const lista = document.getElementById('listaServicos');
    lista.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary"></div></div>';

    try {
        const res = await fetch(`../api/get_servicos.php?profissional_id=${profissionalIdSelecionado}`);
        const data = await res.json();

        if (!data.sucesso) {
            throw new Error(data.erro || 'Erro ao carregar serviços');
        }

        servicosDisponiveis = data.servicos;
        renderizarServicos(servicosDisponiveis);

    } catch (err) {
        lista.innerHTML = `<div class="col-12 alert alert-danger">${err.message}</div>`;
    }
});

// === RENDERIZAR SERVIÇOS ===
function renderizarServicos(servicos) {
    const lista = document.getElementById('listaServicos');
    
    if (servicos.length === 0) {
        lista.innerHTML = '<div class="col-12 text-center text-muted py-4">Nenhum serviço encontrado.</div>';
        return;
    }

    lista.innerHTML = servicos.map(s => {
        const jaAdicionado = carrinho.some(item => item.id === s.id);
        return `
            <div class="col-md-6 servico-item" data-nome="${s.nome.toLowerCase()}">
                <div class="card h-100 servico-card">
                    <div class="card-body">
                        <h6 class="fw-bold">${s.nome}</h6>
                        <p class="text-muted small mb-2">${s.descricao || 'Serviço profissional'}</p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary">${s.preco_formatado}</span>
                            <span class="text-muted small"><i class="fas fa-clock"></i> ${s.duracao_formatada}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-success w-100" 
                                onclick="adicionarServico(${s.id})" ${jaAdicionado ? 'disabled' : ''}>
                            ${jaAdicionado ? '<i class="fas fa-check"></i> Adicionado' : '<i class="fas fa-plus"></i> Adicionar'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// === BUSCAR SERVIÇOS ===
document.getElementById('buscaServico').addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    const filtrados = servicosDisponiveis.filter(s => s.nome.toLowerCase().includes(termo));
    renderizarServicos(filtrados);
});

// === ADICIONAR SERVIÇO ===
function adicionarServico(servicoId) {
    const servico = servicosDisponiveis.find(s => s.id === servicoId);
    if (!servico || carrinho.some(item => item.id === servicoId)) return;

    carrinho.push(servico);
    atualizarCarrinho();
    renderizarServicos(servicosDisponiveis);
    
    // Fechar modal
    bootstrap.Modal.getInstance(document.getElementById('modalServicos')).hide();
}

// === REMOVER SERVIÇO ===
function removerServico(servicoId) {
    carrinho = carrinho.filter(item => item.id !== servicoId);
    atualizarCarrinho();
}

// === ATUALIZAR CARRINHO ===
function atualizarCarrinho() {
    const container = document.getElementById('carrinho');
    const blocoAgenda = document.getElementById('blocoAgenda');

    if (carrinho.length === 0) {
        container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhum serviço selecionado. Clique em "Adicionar Serviços".</div>';
        blocoAgenda.classList.add('d-none');
        document.getElementById('blocoFinal').classList.add('d-none');
        return;
    }

    let totalDuracao = 0;
    let totalPreco = 0;

    const itens = carrinho.map(s => {
        totalDuracao += s.duracao_min;
        totalPreco += s.preco;
        return `
            <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2 bg-light">
                <div>
                    <h6 class="mb-1">${s.nome}</h6>
                    <small class="text-muted">${s.duracao_formatada} • ${s.preco_formatado}</small>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerServico(${s.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }).join('');

    container.innerHTML = `
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <strong><i class="fas fa-shopping-cart me-2"></i>Serviços Selecionados (${carrinho.length})</strong>
            </div>
            <div class="card-body">
                ${itens}
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Duração Total:</strong>
                    <span>${Math.floor(totalDuracao / 60)}h ${totalDuracao % 60}min</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <strong>Valor Total:</strong>
                    <span class="text-success fw-bold fs-5">R$ ${totalPreco.toFixed(2).replace('.', ',')}</span>
                </div>
            </div>
        </div>
    `;

    // Atualizar JSON
    document.getElementById('servicos_json').value = JSON.stringify(carrinho.map(s => ({
        id: s.id,
        nome: s.nome,
        preco: s.preco,
        duracao: s.duracao_min
    })));

    blocoAgenda.classList.remove('d-none');
    
    // Scroll para agenda
    setTimeout(() => {
        blocoAgenda.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 300);
}

// === CARREGAR HORÁRIOS ===
document.getElementById('data_input').addEventListener('change', async function() {
    const data = this.value;
    if (!data || carrinho.length === 0) {
        console.warn('Data ou carrinho vazio');
        return;
    }

    const container = document.getElementById('horariosContainer');
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Carregando horários disponíveis...</p></div>';

    const duracaoTotal = carrinho.reduce((sum, s) => sum + s.duracao_min, 0);

    console.log('Buscando horários:', { data, duracaoTotal, profissional: profissionalIdSelecionado });

    try {
        const url = `../api/get_horarios_disponiveis.php?data=${data}&duracao=${duracaoTotal}&profissional_id=${profissionalIdSelecionado}`;
        console.log('URL da requisição:', url);
        
        const res = await fetch(url);
        const dataRes = await res.json();

        console.log('Resposta da API:', dataRes);

        if (!dataRes.sucesso) {
            container.innerHTML = `<div class="alert alert-danger">Erro: ${dataRes.erro || 'Erro desconhecido'}</div>`;
            if (dataRes.debug) {
                console.error('Debug da API:', dataRes.debug);
            }
            document.getElementById('blocoFinal').classList.add('d-none');
            return;
        }

        if (!dataRes.horarios || dataRes.horarios.length === 0) {
            container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-info-circle me-2"></i>Nenhum horário disponível para esta data. Tente outra data.</div>';
            document.getElementById('blocoFinal').classList.add('d-none');
            return;
        }

        container.innerHTML = `
            <label class="form-label fw-medium">Horários Disponíveis para ${new Date(data + 'T12:00:00').toLocaleDateString('pt-BR')}</label>
            <p class="text-muted small mb-3">
                <i class="fas fa-info-circle me-1"></i>
                Duração total: ${Math.floor(duracaoTotal / 60)}h ${duracaoTotal % 60}min • 
                ${dataRes.horarios.filter(h => h.disponivel).length} horários disponíveis
            </p>
            <div class="row g-2">
                ${dataRes.horarios.map(h => `
                    <div class="col-4 col-md-3 col-lg-2">
                        <label class="horario-btn w-100 ${h.disponivel ? '' : 'disabled'}">
                            <input type="radio" name="hora_inicio" value="${h.hora}" 
                                   ${h.disponivel ? '' : 'disabled'} 
                                   class="d-none"
                                   onchange="selecionarHorario(this)">
                            <div class="btn btn-outline-primary w-100 ${h.disponivel ? '' : 'disabled'}" 
                                 style="font-size: 0.9rem;">
                                ${h.label || h.hora.substring(0,5)}
                            </div>
                        </label>
                    </div>
                `).join('')}
            </div>
        `;

        console.log('Horários renderizados com sucesso');

    } catch (err) {
        console.error('Erro ao buscar horários:', err);
        container.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erro: ${err.message}</div>`;
        document.getElementById('blocoFinal').classList.add('d-none');
    }
});

// === SELECIONAR HORÁRIO ===
function selecionarHorario(radio) {
    // Remover seleção anterior
    document.querySelectorAll('.horario-btn .btn').forEach(btn => {
        btn.classList.remove('btn-primary', 'text-white');
        btn.classList.add('btn-outline-primary');
    });
    
    // Adicionar seleção atual
    const btn = radio.nextElementSibling;
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-primary', 'text-white');
    
    // Mostrar botão de confirmar
    document.getElementById('blocoFinal').classList.remove('d-none');
    
    // Scroll suave
    setTimeout(() => {
        document.getElementById('blocoFinal').scrollIntoView({ behavior: 'smooth' });
    }, 200);
    
    console.log('Horário selecionado:', radio.value);
}

// === VALIDAÇÃO FINAL ===
document.getElementById('formAgendamento').addEventListener('submit', function(e) {
    if (!profissionalIdSelecionado || carrinho.length === 0 || 
        !document.querySelector('input[name="hora_inicio"]:checked') ||
        !document.getElementById('data_input').value) {
        e.preventDefault();
        alert('Por favor, complete todos os passos do agendamento.');
    }
});
</script>

<?php include '../includes/footer.php'; ?>