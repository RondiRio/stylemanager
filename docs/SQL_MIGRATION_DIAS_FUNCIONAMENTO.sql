-- Migration: Adicionar configuração de dias de funcionamento
-- Data: 2026-01-14
-- Descrição: Permite ao admin configurar quais dias da semana a empresa funciona

-- Adicionar campos de dias de funcionamento na tabela configuracoes
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS funciona_domingo TINYINT(1) DEFAULT 0 COMMENT 'Se 1, empresa funciona aos domingos',
ADD COLUMN IF NOT EXISTS funciona_segunda TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às segundas',
ADD COLUMN IF NOT EXISTS funciona_terca TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às terças',
ADD COLUMN IF NOT EXISTS funciona_quarta TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às quartas',
ADD COLUMN IF NOT EXISTS funciona_quinta TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às quintas',
ADD COLUMN IF NOT EXISTS funciona_sexta TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona às sextas',
ADD COLUMN IF NOT EXISTS funciona_sabado TINYINT(1) DEFAULT 1 COMMENT 'Se 1, empresa funciona aos sábados';

-- Definir valores padrão (funciona de segunda a sábado)
UPDATE configuracoes
SET
    funciona_domingo = 0,
    funciona_segunda = 1,
    funciona_terca = 1,
    funciona_quarta = 1,
    funciona_quinta = 1,
    funciona_sexta = 1,
    funciona_sabado = 1
WHERE id = 1;
