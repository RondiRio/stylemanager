-- Migration: Sistema de Fechamento de Caixa
-- Data: 2026-01-15
-- Descrição: Adiciona controle de gorjetas, vales e fechamento de caixa

-- ==================================================
-- 1. CONFIGURAÇÕES
-- ==================================================
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS tipo_fechamento ENUM('diario', 'semanal', 'quinzenal', 'mensal') DEFAULT 'mensal'
    COMMENT 'Período de fechamento de caixa',
ADD COLUMN IF NOT EXISTS gorjetas_requerem_aprovacao TINYINT(1) DEFAULT 0
    COMMENT 'Se 1, gorjetas precisam ser aprovadas pelo admin';

-- ==================================================
-- 2. GORJETAS - Adicionar campos de aprovação
-- ==================================================
ALTER TABLE gorjetas
ADD COLUMN IF NOT EXISTS status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente'
    COMMENT 'Status da gorjeta',
ADD COLUMN IF NOT EXISTS motivo_negacao TEXT NULL
    COMMENT 'Motivo da negação da gorjeta',
ADD COLUMN IF NOT EXISTS aprovado_por INT NULL
    COMMENT 'ID do admin que aprovou/negou',
ADD COLUMN IF NOT EXISTS data_aprovacao DATETIME NULL
    COMMENT 'Data da aprovação/negação',
ADD INDEX idx_status (status),
ADD INDEX idx_profissional_status (profissional_id, status);

-- ==================================================
-- 3. VALES - Adicionar campos de aprovação
-- ==================================================
ALTER TABLE vales
ADD COLUMN IF NOT EXISTS status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente'
    COMMENT 'Status do vale',
ADD COLUMN IF NOT EXISTS aprovado_por INT NULL
    COMMENT 'ID do admin que aprovou/negou',
ADD COLUMN IF NOT EXISTS data_aprovacao DATETIME NULL
    COMMENT 'Data da aprovação/negação',
ADD INDEX idx_status (status),
ADD INDEX idx_profissional_status (profissional_id, status);

-- ==================================================
-- 4. TABELA DE FECHAMENTOS DE CAIXA
-- ==================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================
-- 5. ATUALIZAR DADOS EXISTENTES
-- ==================================================

-- Marcar gorjetas antigas como aprovadas automaticamente
UPDATE gorjetas
SET status = 'aprovado', data_aprovacao = NOW()
WHERE status = 'pendente' AND id IS NOT NULL;

-- Marcar vales antigos como aprovados automaticamente
UPDATE vales
SET status = 'aprovado', data_aprovacao = NOW()
WHERE status = 'pendente' AND id IS NOT NULL;

-- Definir valores padrão nas configurações
UPDATE configuracoes
SET tipo_fechamento = 'mensal',
    gorjetas_requerem_aprovacao = 0
WHERE id = 1;
