<?php
/**
 * Agenda Geral - Recepcionista
 */
$titulo = 'Agenda Geral';
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/utils.php';

// Apenas admin e recepcionista
requer_login(['admin', 'recepcionista']);

// Redirecionar para a view do admin (mesma funcionalidade)
header('Location: ../admin/view_agenda_geral.php');
exit;
