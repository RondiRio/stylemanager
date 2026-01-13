-- setup.sql
CREATE DATABASE barbearia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barbearia;

-- Usuários (3 níveis)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('cliente','profissional','admin') NOT NULL,
    telefone VARCHAR(20),
    foto VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Serviços
CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    duracao_min INT NOT NULL,        -- minutos
    preco DECIMAL(8,2) NOT NULL,
    categoria ENUM('barbearia','cabelo','unhas','estetica') NOT NULL,
    ativo TINYINT(1) DEFAULT 1
);

-- Profissionais x Serviços (habilitação)
CREATE TABLE profissional_servico (
    profissional_id INT,
    servico_id INT,
    PRIMARY KEY (profissional_id, servico_id),
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
);

-- Comissões (percentual por tipo)
CREATE TABLE comissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT,
    tipo ENUM('servico','produto') NOT NULL,
    percentual DECIMAL(5,2) NOT NULL,   -- ex: 45.00
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco_venda DECIMAL(8,2) NOT NULL,
    ativo TINYINT(1) DEFAULT 1
);

-- Agendamentos
CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    data DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    status ENUM('agendado','confirmado','em_atendimento','finalizado','cancelado') DEFAULT 'agendado',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Itens do Agendamento (múltiplos serviços)
CREATE TABLE agendamento_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT,
    profissional_id INT,
    servico_id INT,
    FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL
);

-- Atendimentos (registro final)
CREATE TABLE atendimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agendamento_item_id INT,
    valor_servico DECIMAL(8,2) NOT NULL,
    comissao_servico DECIMAL(8,2) NOT NULL,
    data_atendimento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agendamento_item_id) REFERENCES agendamento_itens(id) ON DELETE SET NULL
);

-- Vendas de Produto
CREATE TABLE vendas_produto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT,
    produto_id INT,
    quantidade INT NOT NULL DEFAULT 1,
    valor_total DECIMAL(8,2) NOT NULL,
    comissao_produto DECIMAL(8,2) NOT NULL,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Vales (adiantamentos)
CREATE TABLE vales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profissional_id INT,
    valor DECIMAL(8,2) NOT NULL,
    motivo TEXT,
    data_vale DATE NOT NULL,
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id)
);

-- Recomendações / Avaliações
CREATE TABLE recomendacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    profissional_id INT,
    servico_id INT,
    nota INT CHECK (nota BETWEEN 1 AND 5),
    comentario TEXT,
    aprovado TINYINT(1) DEFAULT 0,
    data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (profissional_id) REFERENCES usuarios(id),
    FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

-- Configurações do Salão
CREATE TABLE configuracoes (
    id INT PRIMARY KEY,
    horario_abertura TIME,
    horario_fechamento TIME,
    intervalo_slot INT DEFAULT 15,   -- minutos
    prazo_cancelamento_horas INT DEFAULT 24
);
-- INSERT INTO configuracoes (id) VALUES (1);

-- Após as tabelas existentes
ALTER TABLE configuracoes 
ADD COLUMN tipo_empresa ENUM('barbearia','salao','manicure','estetica') DEFAULT 'barbearia',
ADD COLUMN cor_primaria VARCHAR(7) DEFAULT '#1a1a1a',
ADD COLUMN cor_secundaria VARCHAR(7) DEFAULT '#d4af37',
ADD COLUMN cor_fundo VARCHAR(7) DEFAULT '#f5f5f5';

INSERT INTO configuracoes (id, horario_abertura, horario_fechamento, intervalo_slot, prazo_cancelamento_horas,
    tipo_empresa, cor_primaria, cor_secundaria, cor_fundo)
VALUES (1, '09:00:00', '19:00:00', 15, 24,
    'barbearia', '#1a1a1a', '#d4af37', '#f5f5f5')
ON DUPLICATE KEY UPDATE
    horario_abertura = VALUES(horario_abertura),
    tipo_empresa = VALUES(tipo_empresa);