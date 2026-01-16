<?php
/**
 * Agendamento Centralizado - Recepcionista
 * Redirecionamento para a página admin (recepcionistas têm mesma permissão)
 */
require_once '../includes/auth.php';

// Verificar se é recepcionista ou admin
requer_login(['admin', 'recepcionista']);

// Incluir página do admin
include __DIR__ . '/../admin/agendar_centralizado.php';
