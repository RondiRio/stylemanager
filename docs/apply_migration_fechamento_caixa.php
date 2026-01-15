<?php
/**
 * Script para aplicar migration de fechamento de caixa
 * Executar via: php docs/apply_migration_fechamento_caixa.php
 */

require_once __DIR__ . '/../includes/init.php';

try {
    echo "Aplicando migration de fechamento de caixa...\n\n";

    // 1. Configurações
    echo "1. Adicionando configurações de fechamento...\n";
    $pdo->exec("
        ALTER TABLE configuracoes
        ADD COLUMN IF NOT EXISTS tipo_fechamento ENUM('diario', 'semanal', 'quinzenal', 'mensal') DEFAULT 'mensal'
            COMMENT 'Período de fechamento de caixa',
        ADD COLUMN IF NOT EXISTS gorjetas_requerem_aprovacao TINYINT(1) DEFAULT 0
            COMMENT 'Se 1, gorjetas precisam ser aprovadas pelo admin'
    ");
    echo "   ✓ Configurações adicionadas\n";

    // 2. Gorjetas
    echo "2. Atualizando tabela de gorjetas...\n";
    $pdo->exec("
        ALTER TABLE gorjetas
        ADD COLUMN IF NOT EXISTS status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente'
            COMMENT 'Status da gorjeta',
        ADD COLUMN IF NOT EXISTS motivo_negacao TEXT NULL
            COMMENT 'Motivo da negação da gorjeta',
        ADD COLUMN IF NOT EXISTS aprovado_por INT NULL
            COMMENT 'ID do admin que aprovou/negou',
        ADD COLUMN IF NOT EXISTS data_aprovacao DATETIME NULL
            COMMENT 'Data da aprovação/negação'
    ");

    // Adicionar índices
    try {
        $pdo->exec("ALTER TABLE gorjetas ADD INDEX idx_status (status)");
    } catch (PDOException $e) {
        // Índice já existe
    }
    try {
        $pdo->exec("ALTER TABLE gorjetas ADD INDEX idx_profissional_status (profissional_id, status)");
    } catch (PDOException $e) {
        // Índice já existe
    }
    echo "   ✓ Tabela gorjetas atualizada\n";

    // 3. Vales
    echo "3. Atualizando tabela de vales...\n";
    $pdo->exec("
        ALTER TABLE vales
        ADD COLUMN IF NOT EXISTS status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente'
            COMMENT 'Status do vale',
        ADD COLUMN IF NOT EXISTS aprovado_por INT NULL
            COMMENT 'ID do admin que aprovou/negou',
        ADD COLUMN IF NOT EXISTS data_aprovacao DATETIME NULL
            COMMENT 'Data da aprovação/negação'
    ");

    // Adicionar índices
    try {
        $pdo->exec("ALTER TABLE vales ADD INDEX idx_status (status)");
    } catch (PDOException $e) {
        // Índice já existe
    }
    try {
        $pdo->exec("ALTER TABLE vales ADD INDEX idx_profissional_status (profissional_id, status)");
    } catch (PDOException $e) {
        // Índice já existe
    }
    echo "   ✓ Tabela vales atualizada\n";

    // 4. Criar tabela de fechamentos
    echo "4. Criando tabela de fechamentos de caixa...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS fechamentos_caixa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            profissional_id INT NOT NULL,
            data_inicio DATE NOT NULL,
            data_fim DATE NOT NULL,
            total_comissoes DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total de comissões no período',
            total_gorjetas DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total de gorjetas aprovadas',
            total_vales DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total de vales aprovados',
            total_liquido DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Valor líquido a pagar',
            status ENUM('aberto', 'pago') DEFAULT 'aberto',
            pdf_path VARCHAR(255) NULL COMMENT 'Caminho do PDF gerado',
            observacoes TEXT NULL,
            criado_por INT NOT NULL COMMENT 'ID do admin que criou',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            pago_em DATETIME NULL,

            INDEX idx_profissional (profissional_id),
            INDEX idx_periodo (data_inicio, data_fim),
            INDEX idx_status (status),
            FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Tabela fechamentos_caixa criada\n";

    // 5. Atualizar dados existentes
    echo "5. Atualizando dados existentes...\n";

    // Marcar gorjetas antigas como aprovadas
    $stmt = $pdo->exec("UPDATE gorjetas SET status = 'aprovado', data_aprovacao = NOW() WHERE status = 'pendente'");
    echo "   ✓ {$stmt} gorjetas antigas marcadas como aprovadas\n";

    // Marcar vales antigos como aprovados
    $stmt = $pdo->exec("UPDATE vales SET status = 'aprovado', data_aprovacao = NOW() WHERE status = 'pendente'");
    echo "   ✓ {$stmt} vales antigos marcados como aprovados\n";

    // Definir valores padrão
    $pdo->exec("UPDATE configuracoes SET tipo_fechamento = 'mensal', gorjetas_requerem_aprovacao = 0 WHERE id = 1");
    echo "   ✓ Configurações padrão definidas\n";

    echo "\n✅ Migration aplicada com sucesso!\n";

} catch (PDOException $e) {
    echo "\n❌ Erro ao aplicar migration: " . $e->getMessage() . "\n";
    exit(1);
}
