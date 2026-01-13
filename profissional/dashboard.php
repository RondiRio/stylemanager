<?php
// profissional/dashboard.php - CORRIGIDO
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
$titulo = "Dashboard Profissional";
requer_login('profissional');

$profissional_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

// === REGISTRO DE ATENDIMENTO ===
if ($_POST && verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    $agendamento_id = (int)($_POST['agendamento_id'] ?? 0);
    $cliente_nome = trim($_POST['cliente_nome'] ?? '');
    $vale = (float)str_replace(['.', ','], ['', '.'], $_POST['vale'] ?? '0');
    $gorjeta = (float)str_replace(['.', ','], ['', '.'], $_POST['gorjeta'] ?? '0');
    $servicos_selecionados = is_array($_POST['servicos'] ?? []) ? $_POST['servicos'] : [];
    $produtos = $_POST['produtos'] ?? [];

    $pdo->beginTransaction();
    try {
        // VALIDAÇÕES
        if ($agendamento_id > 0) {
            // Buscar dados do agendamento
            $stmt = $pdo->prepare("
                SELECT a.*, c.nome as cliente_nome
                FROM agendamentos a
                JOIN usuarios c ON c.id = a.cliente_id
                WHERE a.id = ? AND a.profissional_id = ? AND a.status = 'agendado'
            ");
            $stmt->execute([$agendamento_id, $profissional_id]);
            $agendamento = $stmt->fetch();
            
            if (!$agendamento) {
                throw new Exception("Agendamento inválido ou já concluído.");
            }
            
            $cliente_nome = $agendamento['cliente_nome'];
            $cliente_id = $agendamento['cliente_id'];
        } else {
            if (empty($cliente_nome)) {
                throw new Exception("Informe o nome do cliente para atendimento avulso.");
            }
            $cliente_id = null;
        }

        if (empty($servicos_selecionados)) {
            throw new Exception("Selecione pelo menos um serviço.");
        }

        // CALCULAR VALORES DOS SERVIÇOS
        $valor_total_servicos = 0;
        $servicos_dados = [];
        
        foreach ($servicos_selecionados as $servico_id) {
            $servico_id = (int)$servico_id;
            $stmt = $pdo->prepare("SELECT id, nome, preco, duracao_min, categoria FROM servicos WHERE id = ? AND ativo = 1");
            $stmt->execute([$servico_id]);
            $s = $stmt->fetch();
            
            if ($s) {
                $valor_total_servicos += $s['preco'];
                $servicos_dados[] = $s;
            }
        }

        // CALCULAR VALORES DOS PRODUTOS
        $valor_total_produtos = 0;
        $produtos_dados = [];
        
        foreach ($produtos as $produto_id => $quantidade) {
            $quantidade = (int)$quantidade;
            if ($quantidade > 0) {
                $produto_id = (int)$produto_id;
                $stmt = $pdo->prepare("SELECT id, nome, preco_venda FROM produtos WHERE id = ? AND ativo = 1");
                $stmt->execute([$produto_id]);
                $p = $stmt->fetch();
                
                if ($p) {
                    $valor_produto = $p['preco_venda'] * $quantidade;
                    $valor_total_produtos += $valor_produto;
                    $produtos_dados[] = [
                        'id' => $p['id'],
                        'nome' => $p['nome'],
                        'quantidade' => $quantidade,
                        'valor_unitario' => $p['preco_venda'],
                        'valor_total' => $valor_produto
                    ];
                }
            }
        }

        // CALCULAR COMISSÃO
        $stmt = $pdo->prepare("SELECT COALESCE(comissao_padrao, 0) FROM usuarios WHERE id = ?");
        $stmt->execute([$profissional_id]);
        $comissao_percentual = (float)$stmt->fetchColumn();
        
        $comissao = ($valor_total_servicos + $valor_total_produtos) * ($comissao_percentual / 100);

        // 1. CRIAR ATENDIMENTO
        $stmt = $pdo->prepare("
            INSERT INTO atendimentos (
                profissional_id,
                cliente_nome,
                data_atendimento,
                agendamento_id,
                cliente_id,
                servico_id,
                hora_inicio,
                valor_servico,
                valor_produto,
                gorjeta,
                vale,
                comissao,
                status,
                criado_em
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'concluido', NOW()
            )
        ");
        
        $primeiro_servico_id = $servicos_dados[0]['id'] ?? null;
        $hora_atual = date('H:i:s');
        
        $stmt->execute([
            $profissional_id,
            $cliente_nome,
            $hoje,
            $agendamento_id > 0 ? $agendamento_id : null,
            $cliente_id,
            $primeiro_servico_id,
            $hora_atual,
            $valor_total_servicos,
            $valor_total_produtos,
            $gorjeta,
            $vale,
            $comissao
        ]);
        
        $atendimento_id = $pdo->lastInsertId();

        // 2. ATUALIZAR AGENDAMENTO
        if ($agendamento_id > 0) {
            $stmt = $pdo->prepare("
                UPDATE agendamentos 
                SET status = 'concluido', atendimento_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$atendimento_id, $agendamento_id]);
        }

        // 3. REGISTRAR SERVIÇOS REALIZADOS
        $stmt = $pdo->prepare("
            INSERT INTO servicos_realizados 
            (atendimento_id, servico_id, nome, preco, duracao_min, categoria, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($servicos_dados as $s) {
            $stmt->execute([
                $atendimento_id,
                $s['id'],
                $s['nome'],
                $s['preco'],
                $s['duracao_min'],
                $s['categoria']
            ]);
        }

        // 4. REGISTRAR PRODUTOS VENDIDOS
        if (!empty($produtos_dados)) {
            $stmt = $pdo->prepare("
                INSERT INTO vendas_produtos 
                (atendimento_id, produto_id, quantidade, valor_unitario, criado_em) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            foreach ($produtos_dados as $prod) {
                $stmt->execute([
                    $atendimento_id,
                    $prod['id'],
                    $prod['quantidade'],
                    $prod['valor_total']
                ]);
            }
        }

        // 5. REGISTRAR GORJETA
        if ($gorjeta > 0) {
            $pdo->prepare("
                INSERT INTO gorjetas (atendimento_id, valor, criado_em) 
                VALUES (?, ?, NOW())
            ")->execute([$atendimento_id, $gorjeta]);
        }

        // 6. REGISTRAR VALE
        if ($vale > 0) {
            $pdo->prepare("
                INSERT INTO vales (profissional_id, vale, criado_em) 
                VALUES (?, ?, NOW())
            ")->execute([$profissional_id, $vale]);
        }

        $pdo->commit();
        redirecionar_com_mensagem('dashboard.php', 'Atendimento registrado com sucesso!', 'success');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = $e->getMessage();
        error_log("Erro no registro de atendimento: " . $e->getMessage());
    }
}

// === BUSCAR DADOS DO DIA ===

// Total de Serviços
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor_servico), 0) AS total
    FROM atendimentos
    WHERE profissional_id = ? 
      AND DATE(data_atendimento) = ? 
      AND status = 'concluido'
");
$stmt->execute([$profissional_id, $hoje]);
$total_servicos = (float)$stmt->fetchColumn();

// Total de Produtos
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor_produto), 0) AS total
    FROM atendimentos
    WHERE profissional_id = ? 
      AND DATE(data_atendimento) = ? 
      AND status = 'concluido'
");
$stmt->execute([$profissional_id, $hoje]);
$total_produtos = (float)$stmt->fetchColumn();

// Total de Gorjetas
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(gorjeta), 0) AS total
    FROM atendimentos
    WHERE profissional_id = ? 
      AND DATE(data_atendimento) = ? 
      AND status = 'concluido'
");
$stmt->execute([$profissional_id, $hoje]);
$total_gorjetas = (float)$stmt->fetchColumn();

// Total de Vales
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(v.valor), 0) AS total
    FROM vales v
    WHERE v.profissional_id = ? 
      AND DATE(v.data_vale) = ?
");
$stmt->execute([$profissional_id, $hoje]);
$total_vales = (float)$stmt->fetchColumn();

// Clientes Atendidos
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT id) AS total
    FROM atendimentos
    WHERE profissional_id = ? 
      AND DATE(data_atendimento) = ? 
      AND status = 'concluido'
");
$stmt->execute([$profissional_id, $hoje]);
$clientes_atendidos = (int)$stmt->fetchColumn();

// Buscar as comissões específicas (serviço e produto) do profissional
$stmt = $pdo->prepare("SELECT servico, produto FROM comissoes WHERE profissional_id = ? LIMIT 1");
$stmt->execute([$profissional_id]);
$comissoes = $stmt->fetch(PDO::FETCH_ASSOC);

// Definir valores padrão caso não exista registro na tabela comissoes
$percentual_servico = $comissoes ? (float)$comissoes['servico'] : 0.0;
$percentual_produto = $comissoes ? (float)$comissoes['produto'] : 0.0;

// Calcular comissão
$total_comissao = round(($total_servicos + $total_produtos) * ($comissao_percentual / 100), 2);

// Montar array de dados
$dados = [
    'servicos' => $total_servicos,
    'produtos' => $total_produtos,
    'vales' => $total_vales,
    'gorjetas' => $total_gorjetas,
    'comissao' => $total_comissao,
    'percentual_servico'=> $percentual_servico,
    'percentual_produto'=> $percentual_produto,
    'comissao_percentual' => $comissao_percentual,
    'clientes_atendidos' => $clientes_atendidos,
    'total' => $total_servicos + $total_produtos + $total_gorjetas
];

// === LISTA DE CLIENTES ATENDIDOS HOJE ===
$stmt = $pdo->prepare("
    SELECT 
        id,
        cliente_nome,
        data_atendimento,
        valor_servico,
        valor_produto,
        gorjeta
    FROM atendimentos
    WHERE profissional_id = ?
      AND DATE(data_atendimento) = ?
      AND status = 'concluido'
    ORDER BY data_atendimento DESC
");
$stmt->execute([$profissional_id, $hoje]);
$clientes_atendidos_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === AGENDA DO DIA ===
$stmt = $pdo->prepare("
    SELECT 
        a.id, 
        a.hora_inicio, 
        c.nome AS cliente_nome, 
        GROUP_CONCAT(s.nome SEPARATOR ', ') AS servicos
    FROM agendamentos a
    JOIN usuarios c ON c.id = a.cliente_id
    JOIN agendamento_itens ai ON ai.agendamento_id = a.id
    JOIN servicos s ON s.id = ai.servico_id
    WHERE a.profissional_id = ? 
      AND DATE(a.data) = ? 
      AND a.status = 'agendado'
    GROUP BY a.id
    ORDER BY a.hora_inicio
");
$stmt->execute([$profissional_id, $hoje]);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === PRODUTOS E SERVIÇOS ===
$produtos = $pdo->query("SELECT id, nome, preco_venda FROM produtos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$servicos = $pdo->query("SELECT id, nome, preco FROM servicos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// === ESTATÍSTICAS ===
$total_posts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE usuario_id = $profissional_id")->fetchColumn();
$seguidores = (int)$pdo->query("SELECT COUNT(*) FROM seguidores WHERE seguido_id = $profissional_id")->fetchColumn();

include '../includes/header.php';
?>

<div class="container-fluid py-4 animate-fade-in">
    <div class="row">
        <!-- COLUNA PRINCIPAL -->
        <div class="col-lg-8">

            <!-- RESUMO DO DIA -->
            <div class="card-glass mb-4">
                <div class="card-glass-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Resumo do Dia - <?php echo date('d/m/Y'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6 col-md-3">
                            <h5 class="mb-0 text-success">R$ <?php echo number_format($dados['servicos'], 2, ',', '.'); ?></h5>
                            <small class="text-muted">Serviços</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <h5 class="mb-0 text-info">R$ <?php echo number_format($dados['produtos'], 2, ',', '.'); ?></h5>
                            <small class="text-muted">Produtos</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <h5 class="mb-0 text-warning">R$ <?php echo number_format($dados['vales'], 2, ',', '.'); ?></h5>
                            <small class="text-muted">Vales</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <h5 class="mb-0 text-success">R$ <?php echo number_format($dados['gorjetas'], 2, ',', '.'); ?></h5>
                            <small class="text-muted">Gorjetas</small>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="mb-0 text-danger">
    R$ <?php 
    // print_r($dados);
        // Soma as partes que pertencem ao profissional e subtrai os vales
        $comissao_real = $dados['servicos'] * ($dados['percentual_servico'] / 100) + $dados['gorjetas'] - $dados['vales']; 
        echo number_format($comissao_real, 2, ',', '.'); 
    ?>
</h5>
                            <small class="text-muted">Comissão Líquida (<?php echo number_format($dados['comissao_percentual'], 1); ?>%)</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0 text-primary fw-bold">R$ <?php echo number_format($dados['total'], 2, ',', '.'); ?></h4>
                            <small class="text-muted">Total Faturado</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CLIENTES ATENDIDOS -->
            <?php if (!empty($clientes_atendidos_lista)): ?>
            <div class="card-glass mb-4">
                <div class="card-glass-header" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Clientes Atendidos Hoje (<?php echo count($clientes_atendidos_lista); ?>)</h5>
                </div>
                <div class="p-0">
                    <?php foreach ($clientes_atendidos_lista as $cliente): 
                        $total_cliente = $cliente['valor_servico'] + $cliente['valor_produto'] + $cliente['gorjeta'];
                    ?>
                    <div class="p-3 border-bottom hover-lift" style="border-left: 4px solid #3b82f6;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($cliente['cliente_nome']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i><?php echo substr($cliente['hora_inicio'], 0, 5); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">R$ <?php echo number_format($total_cliente, 2, ',', '.'); ?></div>
                                <?php if ($cliente['gorjeta'] > 0): ?>
                                <small class="badge bg-success">+ R$ <?php echo number_format($cliente['gorjeta'], 2, ',', '.'); ?> gorjeta</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- REGISTRAR ATENDIMENTO -->
            <div class="card-glass mb-4">
                <div class="card-glass-header" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Registrar Atendimento</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($erro)): ?>
                    <div class="alert-salao error animate-shake">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo htmlspecialchars($erro); ?></div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation animate-stagger" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo gerar_csrf_token(); ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Agendamento</label>
                                <select name="agendamento_id" id="agendamento_select" class="input-salao">
                                    <option value="">-- Atendimento Avulso --</option>
                                    <?php foreach ($agendamentos as $a): ?>
                                    <option value="<?php echo $a['id']; ?>">
                                        <?php echo substr($a['hora_inicio'], 0, 5); ?> - <?php echo htmlspecialchars($a['cliente_nome']); ?>
                                        <?php if (!empty($a['servicos'])): ?>(<?php echo htmlspecialchars($a['servicos']); ?>)<?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6" id="cliente_nome_wrap">
                                <label class="form-label fw-bold">Nome do Cliente <span class="text-danger">*</span></label>
                                <input type="text" name="cliente_nome" id="cliente_nome_input" class="input-salao" placeholder="Ex: João Silva" required>
                                <small class="text-muted">Obrigatório para atendimentos avulsos</small>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vale</label>
                                <input type="text" name="vale" class="input-salao money" value="0,00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Gorjeta</label>
                                <input type="text" name="gorjeta" class="input-salao money" value="0,00">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-scissors text-primary me-2"></i>
                                Serviços Realizados <span class="text-danger">*</span>
                            </label>
                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                <?php foreach ($servicos as $s): ?>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input servico-checkbox" type="checkbox" name="servicos[]" value="<?php echo $s['id']; ?>" id="servico_<?php echo $s['id']; ?>">
                                        <label class="form-check-label" for="servico_<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['nome']); ?> - R$ <?php echo number_format($s['preco'], 2, ',', '.'); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted d-block mt-2">Selecione pelo menos um serviço</small>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-shopping-bag text-info me-2"></i>
                                Produtos Vendidos
                            </label>
                            <div class="row row-cols-1 row-cols-md-2 g-3">
                                <?php foreach ($produtos as $p): ?>
                                <div class="col">
                                    <div class="input-group">
                                        <label class="input-group-text fw-bold" style="min-width: 150px;">
                                            <?php echo htmlspecialchars($p['nome']); ?>
                                        </label>
                                        <input type="number" name="produtos[<?php echo $p['id']; ?>]" class="form-control text-center" min="0" value="0" placeholder="Qtd" aria-label="Quantidade de <?php echo htmlspecialchars($p['nome']); ?>">
                                        <span class="input-group-text text-success fw-bold">R$ <?php echo number_format($p['preco_venda'], 2, ',', '.'); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn-salao btn-lg">
                                <i class="fas fa-check me-2"></i>Finalizar Atendimento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <div class="card-glass sticky-top" style="top: 1rem;">
                <div class="card-glass-header" style="background: linear-gradient(135deg, #1f2937, #111827);">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Minha Atividade
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="mb-0 text-primary"><?php echo $dados['clientes_atendidos']; ?></h4>
                            <small class="text-muted">Hoje</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-success"><?php echo $total_posts; ?></h4>
                            <small class="text-muted">Posts</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-info"><?php echo $seguidores; ?></h4>
                            <small class="text-muted">Seguidores</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-sync-alt me-1"></i>Atualizado em <?php echo date('H:i'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    'use strict';

    // Máscara monetária
    document.querySelectorAll('.money').forEach(input => {
        input.addEventListener('input', e => {
            let v = e.target.value.replace(/\D/g, '');
            if (v === '') v = '0';
            v = (parseInt(v) / 100).toFixed(2).replace('.', ',');
            v = v.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = v;
        });
    });

    // Toggle cliente avulso
    const sel = document.getElementById('agendamento_select');
    const wrap = document.getElementById('cliente_nome_wrap');
    const inp = document.getElementById('cliente_nome_input');
    
    const toggle = () => {
        if (sel?.value) {
            wrap.style.display = 'none';
            inp.required = false;
            inp.value = '';
        } else {
            wrap.style.display = '';
            inp.required = true;
        }
    };
    
    sel?.addEventListener('change', toggle);
    toggle();

    // Validação Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
})();
</script>

<?php include '../includes/footer.php'; ?>