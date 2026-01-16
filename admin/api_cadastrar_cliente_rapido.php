<?php
/**
 * API: Cadastrar Cliente Rápido
 * Cria um cadastro simplificado de cliente com apenas nome, telefone e data de nascimento
 * Usado quando o cliente não tem cadastro no sistema
 */

require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

// Apenas admin e recepcionista podem cadastrar clientes rápidos
requer_login(['admin', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Receber dados JSON
$data = json_decode(file_get_contents('php://input'), true);

$nome = trim($data['nome'] ?? '');
$telefone = trim($data['telefone'] ?? '');
$data_nascimento = trim($data['data_nascimento'] ?? '');
$observacoes = trim($data['observacoes'] ?? '');

// Validações
if (empty($nome)) {
    echo json_encode(['success' => false, 'error' => 'Nome é obrigatório']);
    exit;
}

if (empty($telefone)) {
    echo json_encode(['success' => false, 'error' => 'Telefone é obrigatório']);
    exit;
}

// Validar formato de telefone (apenas números, pode ter DDD)
$telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
if (strlen($telefone_limpo) < 10 || strlen($telefone_limpo) > 11) {
    echo json_encode(['success' => false, 'error' => 'Telefone inválido. Use formato (XX) XXXXX-XXXX']);
    exit;
}

// Validar data de nascimento se fornecida
if (!empty($data_nascimento)) {
    $data_obj = DateTime::createFromFormat('Y-m-d', $data_nascimento);
    if (!$data_obj || $data_obj->format('Y-m-d') !== $data_nascimento) {
        echo json_encode(['success' => false, 'error' => 'Data de nascimento inválida']);
        exit;
    }

    // Verificar se não é data futura
    if ($data_obj > new DateTime()) {
        echo json_encode(['success' => false, 'error' => 'Data de nascimento não pode ser futura']);
        exit;
    }
}

try {
    // Verificar se já existe cliente com este telefone
    $stmt = $pdo->prepare("
        SELECT 'usuario' as tipo, id, nome FROM usuarios WHERE telefone = ? OR telefone_principal = ?
        UNION
        SELECT 'rapido' as tipo, id, nome FROM clientes_rapidos WHERE telefone = ?
        LIMIT 1
    ");
    $stmt->execute([$telefone_limpo, $telefone_limpo, $telefone_limpo]);
    $existente = $stmt->fetch();

    if ($existente) {
        echo json_encode([
            'success' => false,
            'error' => 'Já existe um cliente cadastrado com este telefone: ' . $existente['nome'],
            'cliente_existente' => [
                'id' => $existente['id'],
                'nome' => $existente['nome'],
                'tipo' => $existente['tipo']
            ]
        ]);
        exit;
    }

    // Inserir cliente rápido
    $stmt = $pdo->prepare("
        INSERT INTO clientes_rapidos (nome, telefone, data_nascimento, observacoes, criado_por)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $nome,
        $telefone_limpo,
        !empty($data_nascimento) ? $data_nascimento : null,
        !empty($observacoes) ? $observacoes : null,
        $_SESSION['usuario_id']
    ]);

    $cliente_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Cliente cadastrado com sucesso',
        'cliente' => [
            'id' => $cliente_id,
            'tipo' => 'rapido',
            'nome' => $nome,
            'telefone' => $telefone_limpo,
            'data_nascimento' => $data_nascimento,
            'display' => $nome . ' - ' . $telefone_limpo
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao cadastrar cliente: ' . $e->getMessage()
    ]);
}
