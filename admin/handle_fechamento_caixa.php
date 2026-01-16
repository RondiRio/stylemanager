<?php
/**
 * Handler para Processar Fechamento de Caixa
 * Gera PDF e zera métricas do profissional
 */
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';
require_once '../includes/FechamentoPDF.php';

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
    $pdo->beginTransaction();

    // Verificar se já existe fechamento para este período
    $stmt = $pdo->prepare("
        SELECT id FROM fechamentos_caixa
        WHERE profissional_id = ?
          AND data_inicio = ?
          AND data_fim = ?
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
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

    // Marcar gorjetas como processadas (opcional - apenas para controle)
    $stmt = $pdo->prepare("
        UPDATE gorjetas
        SET data_aprovacao = COALESCE(data_aprovacao, NOW())
        WHERE profissional_id = ?
          AND data_gorjeta BETWEEN ? AND ?
          AND status = 'aprovado'
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);

    // Marcar vales como processados (opcional - apenas para controle)
    $stmt = $pdo->prepare("
        UPDATE vales
        SET data_aprovacao = COALESCE(data_aprovacao, NOW())
        WHERE profissional_id = ?
          AND data_vale BETWEEN ? AND ?
          AND status = 'aprovado'
    ");
    $stmt->execute([$profissional_id, $data_inicio, $data_fim]);

    $pdo->commit();

    // Gerar PDF
    try {
        $pdfGenerator = new FechamentoPDF($pdo);
        $pdf_filename = $pdfGenerator->gerarPDF($fechamento_id);

        $mensagem = 'Fechamento processado com sucesso! PDF gerado: ' . $pdf_filename;
    } catch (Exception $e) {
        $mensagem = 'Fechamento processado, mas erro ao gerar PDF: ' . $e->getMessage();
    }

    // Redirecionar para visualizar o PDF
    redirecionar_com_mensagem(
        'view_fechamento_pdf.php?id=' . $fechamento_id,
        $mensagem,
        'success'
    );

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirecionar_com_mensagem('fechamento_caixa.php', 'Erro ao processar: ' . $e->getMessage(), 'danger');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirecionar_com_mensagem('fechamento_caixa.php', 'Erro: ' . $e->getMessage(), 'danger');
}
