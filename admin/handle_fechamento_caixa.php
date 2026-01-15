<?php
/**
 * Handler para Processar Fechamento de Caixa
 * TODO: Implementar geração de PDF usando TCPDF ou similar
 */
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';

requer_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar_com_mensagem('fechamento_caixa.php', 'Método inválido', 'danger');
}

// Verificar CSRF
if (!verificar_csrf_token($_POST['csrf_token'] ?? '')) {
    redirecionar_com_mensagem('fechamento_caixa.php', 'Token CSRF inválido', 'danger');
}

// Receber dados
$profissional_id = (int)$_POST['profissional_id'];
$data_inicio = $_POST['data_inicio'];
$data_fim = $_POST['data_fim'];
$total_comissoes = (float)$_POST['total_comissoes'];
$total_gorjetas = (float)$_POST['total_gorjetas'];
$total_vales = (float)$_POST['total_vales'];
$total_liquido = (float)$_POST['total_liquido'];
$observacoes = trim($_POST['observacoes'] ?? '');
$admin_id = $_SESSION['usuario_id'];

try {
    // Verificar se já existe fechamento para este período
    $stmt = $pdo->prepare("
        SELECT id FROM fechamentos_caixa
        WHERE profissional_id = ?
          AND data_inicio = ?
          AND data_fim = ?
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);
    if ($stmt->fetch()) {
        redirecionar_com_mensagem('fechamento_caixa.php', 'Já existe um fechamento para este período', 'warning');
    }

    // Inserir fechamento
    $stmt = $pdo->prepare("
        INSERT INTO fechamentos_caixa (
            profissional_id, data_inicio, data_fim,
            total_comissoes, total_gorjetas, total_vales, total_liquido,
            status, observacoes, criado_por, pago_em
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pago', ?, ?, NOW())
    ");
    $stmt->execute([
        $profissional_id, $data_inicio, $data_fim,
        $total_comissoes, $total_gorjetas, $total_vales, $total_liquido,
        $observacoes, $admin_id
    ]);

    $fechamento_id = $pdo->lastInsertId();

    // TODO: Gerar PDF aqui
    // $pdf_path = gerarPDFFechamento($fechamento_id);
    // $pdo->prepare("UPDATE fechamentos_caixa SET pdf_path = ? WHERE id = ?")->execute([$pdf_path, $fechamento_id]);

    redirecionar_com_mensagem(
        'fechamento_caixa.php?profissional_id=' . $profissional_id,
        'Fechamento processado com sucesso!',
        'success'
    );

} catch (PDOException $e) {
    redirecionar_com_mensagem('fechamento_caixa.php', 'Erro ao processar: ' . $e->getMessage(), 'danger');
}
