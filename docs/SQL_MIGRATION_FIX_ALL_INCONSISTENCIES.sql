-- =====================================================
-- MIGRAÇÃO COMPLETA: Correção de Todas as Inconsistências
-- Data: 2026-01-16
-- Descrição: Consolida todas as migrations e corrige inconsistências
-- =====================================================

-- IMPORTANTE: Execute este script apenas UMA VEZ em um banco de dados limpo
-- ou após revisar cuidadosamente cada statement para evitar duplicação

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. CORRIGIR TABELA USUARIOS
-- =====================================================

-- Adicionar tipo 'recepcionista' se não existir
ALTER TABLE usuarios
MODIFY COLUMN tipo ENUM('admin', 'profissional', 'cliente', 'recepcionista') NOT NULL DEFAULT 'cliente';

-- Adicionar campos para data de nascimento e telefone principal (se não existirem)
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS data_nascimento DATE NULL AFTER email,
ADD COLUMN IF NOT EXISTS telefone_principal VARCHAR(20) NULL AFTER telefone;

-- Adicionar índices para performance
ALTER TABLE usuarios
ADD INDEX IF NOT EXISTS idx_nome (nome),
ADD INDEX IF NOT EXISTS idx_telefone (telefone),
ADD INDEX IF NOT EXISTS idx_telefone_principal (telefone_principal),
ADD INDEX IF NOT EXISTS idx_data_nascimento (data_nascimento);

-- =====================================================
-- 2. CORRIGIR TABELA AGENDAMENTOS - CRÍTICO!
-- =====================================================

-- Renomear colunas existentes para padronizar
ALTER TABLE agendamentos
CHANGE COLUMN `data` `data_agendamento` DATE NOT NULL,
CHANGE COLUMN `hora_inicio` `hora_agendamento` TIME NOT NULL;

-- Adicionar hora_fim se não existir
ALTER TABLE agendamentos
ADD COLUMN IF NOT EXISTS hora_fim TIME NULL AFTER hora_agendamento;

-- Atualizar ENUM de status para incluir novos valores
ALTER TABLE agendamentos
MODIFY COLUMN status ENUM('agendado', 'confirmado', 'cliente_chegou', 'em_atendimento', 'finalizado', 'nao_chegou', 'cancelado') DEFAULT 'agendado' COMMENT 'Status do agendamento';

-- Adicionar campos para clientes rápidos (se não existirem)
ALTER TABLE agendamentos
ADD COLUMN IF NOT EXISTS cliente_rapido_id INT NULL AFTER cliente_id,
ADD COLUMN IF NOT EXISTS cliente_nome VARCHAR(150) NULL COMMENT 'Nome do cliente (se for cliente rápido)' AFTER cliente_rapido_id,
ADD COLUMN IF NOT EXISTS cliente_telefone VARCHAR(20) NULL COMMENT 'Telefone do cliente (se for cliente rápido)' AFTER cliente_nome,
ADD COLUMN IF NOT EXISTS observacoes TEXT NULL AFTER cliente_telefone;

-- =====================================================
-- 3. CORRIGIR TABELA GORJETAS
-- =====================================================

-- Alterar ENUM de status para usar valores masculinos
ALTER TABLE gorjetas
MODIFY COLUMN status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente' COMMENT 'Status da gorjeta';

-- Adicionar campos de aprovação (se não existirem)
ALTER TABLE gorjetas
ADD COLUMN IF NOT EXISTS motivo_negacao TEXT NULL COMMENT 'Motivo da negação da gorjeta' AFTER status,
ADD COLUMN IF NOT EXISTS aprovado_por INT NULL COMMENT 'ID do admin que aprovou/negou' AFTER motivo_negacao,
ADD COLUMN IF NOT EXISTS data_aprovacao DATETIME NULL COMMENT 'Data da aprovação/negação' AFTER aprovado_por,
ADD COLUMN IF NOT EXISTS lido_profissional TINYINT(1) DEFAULT 0 AFTER data_aprovacao;

-- Adicionar índices
ALTER TABLE gorjetas
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_profissional_status (profissional_id, status);

-- Adicionar foreign key para aprovado_por se não existir
ALTER TABLE gorjetas
ADD CONSTRAINT IF NOT EXISTS fk_gorjetas_aprovado_por
FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- =====================================================
-- 4. CORRIGIR TABELA VALES
-- =====================================================

-- Adicionar campos de aprovação (se não existirem)
ALTER TABLE vales
ADD COLUMN IF NOT EXISTS status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente' COMMENT 'Status do vale' AFTER data_vale,
ADD COLUMN IF NOT EXISTS aprovado_por INT NULL COMMENT 'ID do admin que aprovou/negou' AFTER status,
ADD COLUMN IF NOT EXISTS data_aprovacao DATETIME NULL COMMENT 'Data da aprovação/negação' AFTER aprovado_por;

-- Adicionar índices
ALTER TABLE vales
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_profissional_status (profissional_id, status);

-- Adicionar foreign key para aprovado_por
ALTER TABLE vales
ADD CONSTRAINT IF NOT EXISTS fk_vales_aprovado_por
FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- =====================================================
-- 5. ATUALIZAR TABELA CONFIGURACOES
-- =====================================================

-- Adicionar campos do sistema de agendamento centralizado
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS permitir_cadastro_cliente TINYINT(1) DEFAULT 1 COMMENT 'Permite clientes se cadastrarem no sistema',
ADD COLUMN IF NOT EXISTS mostrar_landing_page TINYINT(1) DEFAULT 1 COMMENT 'Mostra landing page para novos visitantes',
ADD COLUMN IF NOT EXISTS agenda_centralizada_ativa TINYINT(1) DEFAULT 1 COMMENT 'Ativa agenda centralizada para admin/recepcionista',
ADD COLUMN IF NOT EXISTS lembrar_aniversarios TINYINT(1) DEFAULT 1 COMMENT 'Sistema de lembretes de aniversário ativo',
ADD COLUMN IF NOT EXISTS agendamento_sem_profissional TINYINT(1) DEFAULT 0 COMMENT 'Permite agendamento sem especificar profissional',
ADD COLUMN IF NOT EXISTS profissional_ve_propria_agenda TINYINT(1) DEFAULT 0 COMMENT 'Profissional pode visualizar sua própria agenda';

-- Adicionar campos do sistema de fechamento de caixa
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS tipo_fechamento ENUM('diario', 'semanal', 'quinzenal', 'mensal') DEFAULT 'mensal' COMMENT 'Período de fechamento de caixa',
ADD COLUMN IF NOT EXISTS gorjetas_requerem_aprovacao TINYINT(1) DEFAULT 0 COMMENT 'Se 1, gorjetas precisam ser aprovadas pelo admin';

-- =====================================================
-- 6. ATUALIZAR TABELA SERVICOS_REALIZADOS
-- =====================================================

-- Adicionar campos para preço customizado (se não existirem)
ALTER TABLE servicos_realizados
ADD COLUMN IF NOT EXISTS preco_customizado DECIMAL(10,2) NULL COMMENT 'Preço customizado para este atendimento específico',
ADD COLUMN IF NOT EXISTS usa_preco_customizado TINYINT(1) DEFAULT 0 COMMENT 'Se deve usar preço customizado ao invés do preço padrão';

-- Renomear coluna 'nome' para 'nome_servico' para consistência
ALTER TABLE servicos_realizados
CHANGE COLUMN `nome` `nome_servico` VARCHAR(100) DEFAULT NULL COMMENT 'Nome do serviço realizado';

-- =====================================================
-- 7. CRIAR TABELA CLIENTES_RAPIDOS
-- =====================================================

CREATE TABLE IF NOT EXISTS clientes_rapidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    data_nascimento DATE NULL,
    observacoes TEXT NULL,
    criado_por INT NOT NULL COMMENT 'ID do admin/recepcionista que criou',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_nome (nome),
    INDEX idx_telefone (telefone),
    INDEX idx_data_nascimento (data_nascimento),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 8. CRIAR TABELA LEMBRETES_ANIVERSARIO
-- =====================================================

CREATE TABLE IF NOT EXISTS lembretes_aniversario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    cliente_rapido_id INT NULL,
    nome VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    ultimo_lembrete DATE NULL COMMENT 'Data do último lembrete enviado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_data_nascimento (data_nascimento),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_rapido_id) REFERENCES clientes_rapidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 9. CRIAR TABELA FECHAMENTOS_CAIXA
-- =====================================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 10. ADICIONAR FOREIGN KEY EM AGENDAMENTOS
-- =====================================================

-- Adicionar foreign key para cliente_rapido_id
ALTER TABLE agendamentos
ADD CONSTRAINT IF NOT EXISTS fk_agendamentos_cliente_rapido
FOREIGN KEY (cliente_rapido_id) REFERENCES clientes_rapidos(id) ON DELETE SET NULL;

-- =====================================================
-- 11. CRIAR VIEW VW_CLIENTES_UNIFICADO
-- =====================================================

CREATE OR REPLACE VIEW vw_clientes_unificado AS
SELECT
    u.id as usuario_id,
    NULL as cliente_rapido_id,
    u.nome,
    COALESCE(u.telefone_principal, u.telefone) as telefone,
    u.email,
    u.data_nascimento,
    'usuario' as tipo_cliente
FROM usuarios u
WHERE u.tipo = 'cliente'
UNION ALL
SELECT
    NULL as usuario_id,
    cr.id as cliente_rapido_id,
    cr.nome,
    cr.telefone,
    NULL as email,
    cr.data_nascimento,
    'rapido' as tipo_cliente
FROM clientes_rapidos cr;

-- =====================================================
-- 12. ATUALIZAR DADOS EXISTENTES (Opcional)
-- =====================================================

-- Atualizar valores padrão nas configurações existentes
UPDATE configuracoes
SET
    permitir_cadastro_cliente = COALESCE(permitir_cadastro_cliente, 1),
    mostrar_landing_page = COALESCE(mostrar_landing_page, 1),
    agenda_centralizada_ativa = COALESCE(agenda_centralizada_ativa, 1),
    lembrar_aniversarios = COALESCE(lembrar_aniversarios, 1),
    agendamento_sem_profissional = COALESCE(agendamento_sem_profissional, 0),
    profissional_ve_propria_agenda = COALESCE(profissional_ve_propria_agenda, 0),
    tipo_fechamento = COALESCE(tipo_fechamento, 'mensal'),
    gorjetas_requerem_aprovacao = COALESCE(gorjetas_requerem_aprovacao, 0)
WHERE id = 1;

-- Atualizar status de gorjetas antigas (caso existam valores antigos)
UPDATE gorjetas
SET status = 'aprovado'
WHERE status = 'aprovada';

UPDATE gorjetas
SET status = 'negado'
WHERE status = 'negada';

-- Marcar gorjetas/vales sem status como pendentes
UPDATE gorjetas
SET status = 'pendente'
WHERE status IS NULL OR status = '';

UPDATE vales
SET status = 'pendente'
WHERE status IS NULL OR status = '';

COMMIT;

-- =====================================================
-- VERIFICAÇÕES PÓS-MIGRATION
-- =====================================================

-- Verificar estrutura das tabelas principais
SHOW COLUMNS FROM agendamentos;
SHOW COLUMNS FROM usuarios;
SHOW COLUMNS FROM gorjetas;
SHOW COLUMNS FROM vales;
SHOW COLUMNS FROM configuracoes;

-- Verificar se as novas tabelas existem
SHOW TABLES LIKE '%clientes_rapidos%';
SHOW TABLES LIKE '%fechamentos_caixa%';
SHOW TABLES LIKE '%lembretes_aniversario%';

-- Verificar se a view foi criada
SHOW CREATE VIEW vw_clientes_unificado;

-- =====================================================
-- FIM DA MIGRAÇÃO
-- =====================================================
