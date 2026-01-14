-- ============================================
-- MIGRATION: Adicionar campo profissional_ve_agenda
-- Data: 2026-01-14
-- Descrição: Permite admin controlar se profissionais podem ver a agenda
-- ============================================

-- Adicionar coluna profissional_ve_agenda na tabela configuracoes
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS profissional_ve_agenda TINYINT(1) DEFAULT 1
COMMENT 'Se 1, profissionais podem ver tela de agenda';

-- Atualizar valor padrão para 1 (habilitado)
UPDATE configuracoes SET profissional_ve_agenda = 1 WHERE id = 1;

-- Verificar se foi adicionado corretamente
SELECT
    agendamento_ativo,
    profissional_ve_agenda,
    CASE
        WHEN agendamento_ativo = 1 AND profissional_ve_agenda = 1 THEN 'Profissionais PODEM ver agenda'
        WHEN agendamento_ativo = 1 AND profissional_ve_agenda = 0 THEN 'Profissionais NÃO podem ver agenda (apenas admin)'
        WHEN agendamento_ativo = 0 THEN 'Agendamento desativado (ninguém vê agenda)'
    END as status_agenda
FROM configuracoes
WHERE id = 1;

-- ============================================
-- NOTAS:
-- ============================================
-- 1. Este campo funciona em conjunto com 'agendamento_ativo'
-- 2. Para profissionais verem agenda, AMBOS devem estar = 1:
--    - agendamento_ativo = 1 (permite agendamentos)
--    - profissional_ve_agenda = 1 (profissionais veem agenda)
-- 3. Se profissional_ve_agenda = 0, apenas admin vê agenda
-- 4. Se agendamento_ativo = 0, ninguém vê agenda (desativado)
-- ============================================
