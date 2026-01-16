-- =====================================================
-- MIGRAÇÃO: Sistema de Emails Automáticos
-- Data: 2026-01-16
-- Descrição: Tabelas para logs de emails e lembretes
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. CRIAR TABELA DE LOGS DE EMAIL
-- =====================================================

CREATE TABLE IF NOT EXISTS logs_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatario VARCHAR(255) NOT NULL COMMENT 'Email do destinatário',
    assunto VARCHAR(255) NOT NULL COMMENT 'Assunto do email',
    status ENUM('enviado', 'erro') NOT NULL DEFAULT 'enviado',
    erro TEXT NULL COMMENT 'Mensagem de erro (se houver)',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_destinatario (destinatario),
    INDEX idx_status (status),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Log de todos os emails enviados pelo sistema';

-- =====================================================
-- 2. ATUALIZAR TABELA CONFIGURACOES_EMAIL
-- =====================================================

-- Adicionar campos faltantes se não existirem
ALTER TABLE configuracoes_email
ADD COLUMN IF NOT EXISTS smtp_host VARCHAR(255) DEFAULT NULL COMMENT 'Servidor SMTP',
ADD COLUMN IF NOT EXISTS smtp_porta INT DEFAULT 587 COMMENT 'Porta SMTP',
ADD COLUMN IF NOT EXISTS smtp_usuario VARCHAR(255) DEFAULT NULL COMMENT 'Usuário/Email SMTP',
ADD COLUMN IF NOT EXISTS smtp_senha VARCHAR(255) DEFAULT NULL COMMENT 'Senha SMTP',
ADD COLUMN IF NOT EXISTS smtp_seguranca ENUM('tls', 'ssl') DEFAULT 'tls' COMMENT 'Tipo de criptografia',
ADD COLUMN IF NOT EXISTS smtp_remetente VARCHAR(255) DEFAULT NULL COMMENT 'Email remetente',
ADD COLUMN IF NOT EXISTS smtp_nome_remetente VARCHAR(255) DEFAULT NULL COMMENT 'Nome do remetente',
ADD COLUMN IF NOT EXISTS smtp_responder_para VARCHAR(255) DEFAULT NULL COMMENT 'Email para resposta',
ADD COLUMN IF NOT EXISTS smtp_ativo TINYINT(1) DEFAULT 0 COMMENT 'Se SMTP está ativo',
ADD COLUMN IF NOT EXISTS smtp_debug TINYINT(1) DEFAULT 0 COMMENT 'Modo debug ativado';

-- =====================================================
-- 3. VERIFICAR CONFIGURAÇÃO DE ANIVERSÁRIOS
-- =====================================================

-- Garantir que a coluna existe em configuracoes
ALTER TABLE configuracoes
ADD COLUMN IF NOT EXISTS lembrar_aniversarios TINYINT(1) DEFAULT 1 COMMENT 'Enviar emails de aniversário';

-- =====================================================
-- 4. ATUALIZAR TABELA LEMBRETES_ANIVERSARIO
-- =====================================================

-- Adicionar índice único para evitar duplicatas
ALTER TABLE lembretes_aniversario
ADD UNIQUE INDEX IF NOT EXISTS idx_usuario_unico (usuario_id),
ADD UNIQUE INDEX IF NOT EXISTS idx_cliente_rapido_unico (cliente_rapido_id);

COMMIT;

-- =====================================================
-- VERIFICAÇÕES PÓS-MIGRATION
-- =====================================================

-- Verificar se as tabelas foram criadas
SHOW TABLES LIKE 'logs_email';

-- Verificar estrutura da tabela de logs
SHOW COLUMNS FROM logs_email;

-- Verificar configurações de email
SELECT * FROM configuracoes_email WHERE id = 1;

-- Verificar configuração de aniversários
SELECT lembrar_aniversarios FROM configuracoes WHERE id = 1;

-- =====================================================
-- FIM DA MIGRAÇÃO
-- =====================================================
