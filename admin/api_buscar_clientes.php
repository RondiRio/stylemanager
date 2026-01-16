<?php
/**
 * API: Buscar Clientes
 * Busca clientes cadastrados e clientes rápidos por nome ou telefone
 * Usado na interface de agendamento centralizado
 */

require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

// Apenas admin e recepcionista podem buscar clientes
requer_login(['admin', 'recepcionista']);

$termo = $_GET['termo'] ?? '';
$termo = trim($termo);

if (strlen($termo) < 2) {
    echo json_encode([
        'success' => false,
        'error' => 'Digite pelo menos 2 caracteres para buscar'
    ]);
    exit;
}

try {
    // Buscar em usuários e clientes rápidos usando a view
    $stmt = $pdo->prepare("
        SELECT
            usuario_id,
            cliente_rapido_id,
            nome,
            telefone,
            email,
            data_nascimento,
            tipo_cliente
        FROM vw_clientes_unificado
        WHERE nome LIKE ? OR telefone LIKE ?
        ORDER BY nome ASC
        LIMIT 50
    ");

    $termo_busca = "%{$termo}%";
    $stmt->execute([$termo_busca, $termo_busca]);
    $clientes = $stmt->fetchAll();

    // Formatar resultados
    $resultados = [];
    foreach ($clientes as $cliente) {
        $resultados[] = [
            'id' => $cliente['usuario_id'] ?? $cliente['cliente_rapido_id'],
            'tipo' => $cliente['tipo_cliente'],
            'nome' => $cliente['nome'],
            'telefone' => $cliente['telefone'],
            'email' => $cliente['email'],
            'data_nascimento' => $cliente['data_nascimento'],
            'display' => $cliente['nome'] . ' - ' . $cliente['telefone'] . ($cliente['email'] ? ' (' . $cliente['email'] . ')' : '')
        ];
    }

    echo json_encode([
        'success' => true,
        'clientes' => $resultados,
        'total' => count($resultados)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar clientes: ' . $e->getMessage()
    ]);
}
