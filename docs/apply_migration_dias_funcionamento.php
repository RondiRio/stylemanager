<?php
/**
 * Script para aplicar migration de dias de funcionamento
 * Executar via: php docs/apply_migration_dias_funcionamento.php
 */

require_once __DIR__ . '/../includes/init.php';

try {
    echo "Aplicando migration de dias de funcionamento...\n";

    // Adicionar campos de dias de funcionamento
    $pdo->exec("
        ALTER TABLE configuracoes
        ADD COLUMN IF NOT EXISTS funciona_domingo TINYINT(1) DEFAULT 0 COMMENT 'Se 1, empresa funciona aos domingos',
        ADD COLUMN IF NOT EXISTS funciona_segunda TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às segundas',
        ADD COLUMN IF NOT EXISTS funciona_terca TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às terças',
        ADD COLUMN IF NOT EXISTS funciona_quarta TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às quartas',
        ADD COLUMN IF NOT EXISTS funciona_quinta TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às quintas',
        ADD COLUMN IF NOT EXISTS funciona_sexta TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às sextas',
        ADD COLUMN IF NOT EXISTS funciona_sabado TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona aos sábados'
    ");

    echo "Campos adicionados com sucesso!\n";

    // Definir valores padrão
    $pdo->exec("
        UPDATE configuracoes
        SET
            funciona_domingo = 0,
            funciona_segunda = 1,
            funciona_terca = 1,
            funciona_quarta = 1,
            funciona_quinta = 1,
            funciona_sexta = 1,
            funciona_sabado = 1
        WHERE id = 1
    ");

    echo "Valores padrão definidos com sucesso!\n";
    echo "Migration aplicada com sucesso!\n";

} catch (PDOException $e) {
    echo "Erro ao aplicar migration: " . $e->getMessage() . "\n";
    exit(1);
}
