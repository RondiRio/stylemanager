<?php
/**
 * Handler: Processar Agendamento Centralizado
 * Cria agendamento realizado por admin/recepcionista
 */

require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

// Apenas admin e recepcionista
requer_login(['admin', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Receber dados
$cliente_id = $_POST['cliente_id'] ?? null;
$cliente_tipo = $_POST['cliente_tipo'] ?? null;
$profissional_id = !empty($_POST['profissional_id']) ? (int)$_POST['profissional_id'] : null;
$data_agendamento = $_POST['data_agendamento'] ?? '';
$hora_agendamento = $_POST['hora_agendamento'] ?? '';
$servicos = $_POST['servicos'] ?? [];
$precos_customizados = $_POST['preco_customizado'] ?? [];
$observacoes = trim($_POST['observacoes'] ?? '');

// Buscar configuração para ver se permite agendamento sem profissional
$config = $pdo->query("SELECT agendamento_sem_profissional FROM configuracoes WHERE id = 1")->fetch();
$permite_sem_profissional = $config['agendamento_sem_profissional'] ?? 0;

// Validações
if (!$cliente_id || !$cliente_tipo) {
    echo json_encode(['success' => false, 'error' => 'Cliente não selecionado']);
    exit;
}

if (!$profissional_id && !$permite_sem_profissional) {
    echo json_encode(['success' => false, 'error' => 'Profissional não selecionado']);
    exit;
}

if (empty($data_agendamento) || empty($hora_agendamento)) {
    echo json_encode(['success' => false, 'error' => 'Data e hora são obrigatórias']);
    exit;
}

if (empty($servicos) || !is_array($servicos)) {
    echo json_encode(['success' => false, 'error' => 'Selecione pelo menos um serviço']);
    exit;
}

// Validar data não é passada
$data_hora_agendamento = $data_agendamento . ' ' . $hora_agendamento . ':00';
if (strtotime($data_hora_agendamento) < time()) {
    echo json_encode(['success' => false, 'error' => 'Não é possível agendar em data/hora passada']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Buscar dados do cliente
    $cliente_nome = null;
    $cliente_telefone = null;
    $cliente_usuario_id = null;
    $cliente_rapido_id = null;

    if ($cliente_tipo === 'usuario') {
        $stmt = $pdo->prepare("SELECT nome, COALESCE(telefone_principal, telefone) as telefone FROM usuarios WHERE id = ?");
        $stmt->execute([$cliente_id]);
        $cliente_dados = $stmt->fetch();

        if (!$cliente_dados) {
            throw new Exception('Cliente não encontrado');
        }

        $cliente_nome = $cliente_dados['nome'];
        $cliente_telefone = $cliente_dados['telefone'];
        $cliente_usuario_id = $cliente_id;

    } else if ($cliente_tipo === 'rapido') {
        $stmt = $pdo->prepare("SELECT nome, telefone FROM clientes_rapidos WHERE id = ?");
        $stmt->execute([$cliente_id]);
        $cliente_dados = $stmt->fetch();

        if (!$cliente_dados) {
            throw new Exception('Cliente não encontrado');
        }

        $cliente_nome = $cliente_dados['nome'];
        $cliente_telefone = $cliente_dados['telefone'];
        $cliente_rapido_id = $cliente_id;
    }

    // Verificar se profissional está disponível neste horário (apenas se profissional foi especificado)
    if ($profissional_id) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM agendamentos
            WHERE profissional_id = ?
              AND data_agendamento = ?
              AND hora_agendamento = ?
              AND status != 'cancelado'
        ");
        $stmt->execute([$profissional_id, $data_agendamento, $hora_agendamento]);
        $conflito = $stmt->fetch();

        if ($conflito['total'] > 0) {
            throw new Exception('Profissional já possui agendamento neste horário');
        }
    }

    // Criar agendamento
    $stmt = $pdo->prepare("
        INSERT INTO agendamentos (
            cliente_id,
            cliente_rapido_id,
            cliente_nome,
            cliente_telefone,
            profissional_id,
            data_agendamento,
            hora_agendamento,
            status,
            observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmado', ?)
    ");

    $stmt->execute([
        $cliente_usuario_id,
        $cliente_rapido_id,
        $cliente_nome,
        $cliente_telefone,
        $profissional_id,
        $data_agendamento,
        $hora_agendamento,
        $observacoes
    ]);

    $agendamento_id = $pdo->lastInsertId();

    // Criar itens de agendamento (serviços vinculados ao agendamento)
    $stmt_item = $pdo->prepare("
        INSERT INTO agendamento_itens (
            agendamento_id,
            profissional_id,
            servico_id
        ) VALUES (?, ?, ?)
    ");

    foreach ($servicos as $servico_id) {
        $servico_id = (int)$servico_id;
        $stmt_item->execute([
            $agendamento_id,
            $profissional_id,
            $servico_id
        ]);
    }

    // Armazenar preços customizados como observações se houver
    if (!empty($precos_customizados)) {
        $obs_precos = [];
        foreach ($precos_customizados as $sid => $preco) {
            if (!empty($preco)) {
                $obs_precos[] = "Serviço ID $sid: R$ $preco (customizado)";
            }
        }
        if (!empty($obs_precos)) {
            $obs_adicional = "\n\nPreços customizados:\n" . implode("\n", $obs_precos);
            $stmt = $pdo->prepare("UPDATE agendamentos SET observacoes = CONCAT(COALESCE(observacoes, ''), ?) WHERE id = ?");
            $stmt->execute([$obs_adicional, $agendamento_id]);
        }
    }

    // NOTA: Registros em 'atendimentos' e 'servicos_realizados' serão criados apenas
    // quando o agendamento for marcado como 'finalizado' no handle_status_agendamento.php
    // Isso garante que o profissional correto seja atribuído e receba as comissões

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Agendamento realizado com sucesso',
        'agendamento_id' => $agendamento_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar agendamento: ' . $e->getMessage()
    ]);
}