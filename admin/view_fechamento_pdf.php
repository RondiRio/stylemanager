<?php
/**
 * Visualizar PDF de Fechamento de Caixa
 */
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';

requer_login('admin');

$fechamento_id = (int)($_GET['id'] ?? 0);

if (!$fechamento_id) {
    $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'ID de fechamento inválido'];
    header('Location: fechamento_caixa.php');
    exit;
}

// Buscar dados do fechamento
$stmt = $pdo->prepare("SELECT * FROM fechamentos_caixa WHERE id = ?");
$stmt->execute([$fechamento_id]);
$fechamento = $stmt->fetch();

if (!$fechamento) {
    $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'Fechamento não encontrado'];
    header('Location: fechamento_caixa.php');
    exit;
}

$pdf_path = '../assets/pdf/fechamentos/' . $fechamento['pdf_path'];

if (!file_exists($pdf_path)) {
    $_SESSION['flash'] = ['tipo' => 'warning', 'msg' => 'PDF não encontrado. Tente gerar novamente.'];
    header('Location: fechamento_caixa.php');
    exit;
}

// Ler e exibir o PDF (que na verdade é um HTML estilizado)
$html = file_get_contents($pdf_path);
echo $html;
