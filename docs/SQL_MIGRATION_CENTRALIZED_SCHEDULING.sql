-- =====================================================
-- MIGRAÇÃO: Sistema de Agendamento Centralizado
-- Data: 2026-01-15
-- Descrição: Adiciona funcionalidades de agendamento centralizado,
--            recepcionista, busca de clientes, aniversários e controles sociais
-- =====================================================

-- 1. Adicionar tipo de usuário 'recepcionista'
ALTER TABLE usuarios
MODIFY COLUMN tipo ENUM('admin', 'profissional', 'cliente', 'recepcionista') NOT NULL DEFAULT 'cliente';

-- 2. Adicionar campo de data de nascimento para clientes
ALTER TABLE usuarios
ADD COLUMN data_nascimento DATE NULL AFTER email,
ADD COLUMN telefone_principal VARCHAR(20) NULL AFTER telefone;

-- 3. Criar índices para busca rápida de clientes
ALTER TABLE usuarios
ADD INDEX idx_nome (nome),
ADD INDEX idx_telefone (telefone),
ADD INDEX idx_telefone_principal (telefone_principal),
ADD INDEX idx_data_nascimento (data_nascimento);

-- 4. Adicionar configurações para controle de rede social e agenda centralizada
ALTER TABLE configuracoes
ADD COLUMN permitir_cadastro_cliente TINYINT(1) DEFAULT 1 COMMENT 'Permite clientes se cadastrarem no sistema',
ADD COLUMN mostrar_landing_page TINYINT(1) DEFAULT 1 COMMENT 'Mostra landing page para novos visitantes',
ADD COLUMN agenda_centralizada_ativa TINYINT(1) DEFAULT 1 COMMENT 'Ativa agenda centralizada para admin/recepcionista',
ADD COLUMN lembrar_aniversarios TINYINT(1) DEFAULT 1 COMMENT 'Sistema de lembretes de aniversário ativo';

-- 5. Adicionar campo para preço customizado nos serviços realizados
ALTER TABLE servicos_realizados
ADD COLUMN preco_customizado DECIMAL(10,2) NULL COMMENT 'Preço customizado para este atendimento específico',
ADD COLUMN usa_preco_customizado TINYINT(1) DEFAULT 0 COMMENT 'Se deve usar preço customizado ao invés do preço padrão';

-- 6. Criar tabela de clientes rápidos (não cadastrados como usuários)
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

-- 7. Modificar tabela de agendamentos para suportar clientes rápidos
ALTER TABLE agendamentos
ADD COLUMN cliente_rapido_id INT NULL AFTER cliente_id,
ADD COLUMN cliente_nome VARCHAR(150) NULL COMMENT 'Nome do cliente (se for cliente rápido)',
ADD COLUMN cliente_telefone VARCHAR(20) NULL COMMENT 'Telefone do cliente (se for cliente rápido)',
ADD FOREIGN KEY (cliente_rapido_id) REFERENCES clientes_rapidos(id) ON DELETE SET NULL;

-- 8. Criar tabela de lembretes de aniversário
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

-- 9. Criar view para facilitar busca unificada de clientes
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

-- 10. Adicionar permissões para recepcionista (comentário para referência)
-- O recepcionista terá acesso a:
-- - Agenda geral (view_agenda_geral.php)
-- - Agendamento centralizado (agendar_centralizado.php)
-- - Busca de clientes
-- NÃO terá acesso a:
-- - Configurações do sistema
-- - Gestão de profissionais/serviços/produtos
-- - Relatórios financeiros
-- - Aprovação de gorjetas/vales
-- - Fechamento de caixa

-- =====================================================
-- FIM DA MIGRAÇÃO
-- =====================================================

-- Para reverter (CUIDADO - perderá dados):
/*
DROP VIEW IF EXISTS vw_clientes_unificado;
DROP TABLE IF EXISTS lembretes_aniversario;
ALTER TABLE agendamentos DROP FOREIGN KEY agendamentos_ibfk_cliente_rapido;
ALTER TABLE agendamentos DROP COLUMN cliente_rapido_id, DROP COLUMN cliente_nome, DROP COLUMN cliente_telefone;
DROP TABLE IF EXISTS clientes_rapidos;
ALTER TABLE servicos_realizados DROP COLUMN preco_customizado, DROP COLUMN usa_preco_customizado;
ALTER TABLE configuracoes DROP COLUMN permitir_cadastro_cliente, DROP COLUMN mostrar_landing_page, DROP COLUMN agenda_centralizada_ativa, DROP COLUMN lembrar_aniversarios;
ALTER TABLE usuarios DROP INDEX idx_nome, DROP INDEX idx_telefone, DROP INDEX idx_telefone_principal, DROP INDEX idx_data_nascimento;
ALTER TABLE usuarios DROP COLUMN data_nascimento, DROP COLUMN telefone_principal;
ALTER TABLE usuarios MODIFY COLUMN tipo ENUM('admin', 'profissional', 'cliente') NOT NULL DEFAULT 'cliente';
*/
