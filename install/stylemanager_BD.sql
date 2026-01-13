-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/01/2026 às 02:46
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `stylemanager`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `status` enum('agendado','confirmado','em_atendimento','finalizado','cancelado') DEFAULT 'agendado',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamento_itens`
--

CREATE TABLE `agendamento_itens` (
  `id` int(11) NOT NULL,
  `agendamento_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimentos`
--

CREATE TABLE `atendimentos` (
  `id` int(11) NOT NULL,
  `agendamento_item_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `valor_servico` decimal(8,2) NOT NULL,
  `comissao_servico` decimal(8,2) NOT NULL,
  `metodo_pagamento` enum('dinheiro','cartao_credito','cartao_debito','pix','vale') DEFAULT 'dinheiro',
  `status` enum('pendente','concluido','cancelado') DEFAULT 'concluido',
  `data_atendimento` datetime DEFAULT current_timestamp(),
  `gorjeta` float DEFAULT NULL,
  `cliente_nome` varchar(100) DEFAULT NULL,
  `valor_produto` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `atendimentos`
--

INSERT INTO `atendimentos` (`id`, `agendamento_item_id`, `profissional_id`, `servico_id`, `cliente_id`, `valor_servico`, `comissao_servico`, `metodo_pagamento`, `status`, `data_atendimento`, `gorjeta`, `cliente_nome`, `valor_produto`) VALUES
(2, NULL, 3, 3, 14, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:19:32', NULL, NULL, NULL),
(3, NULL, 3, 3, 14, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:19:51', NULL, NULL, NULL),
(4, NULL, 3, 3, 14, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:20:37', NULL, NULL, NULL),
(5, NULL, 3, 3, 14, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:20:46', NULL, NULL, NULL),
(6, NULL, 3, 3, 14, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:22:02', NULL, NULL, NULL),
(7, NULL, 3, 3, 14, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:28:00', NULL, NULL, NULL),
(8, NULL, 3, 3, NULL, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:36:51', NULL, NULL, NULL),
(9, NULL, 3, 3, NULL, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:37:28', NULL, NULL, NULL),
(10, NULL, 3, 3, NULL, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:42:53', NULL, NULL, NULL),
(11, NULL, 3, 3, NULL, 30.00, 0.00, 'dinheiro', 'concluido', '2026-01-12 16:47:24', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `comissoes`
--

CREATE TABLE `comissoes` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `tipo` enum('servico','produto') NOT NULL,
  `percentual` decimal(5,2) NOT NULL,
  `servico` decimal(10,2) DEFAULT NULL,
  `produto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `horario_abertura` time DEFAULT NULL,
  `horario_fechamento` time DEFAULT NULL,
  `intervalo_slot` int(11) DEFAULT 15,
  `prazo_cancelamento_horas` int(11) DEFAULT 24,
  `agendamento_ativo` tinyint(1) DEFAULT 1,
  `tipo_empresa` enum('barbearia','salao','manicure','estetica') DEFAULT 'barbearia',
  `cor_primaria` varchar(7) DEFAULT '#1a1a1a',
  `cor_secundaria` varchar(7) DEFAULT '#d4af37',
  `logo` varchar(255) DEFAULT NULL,
  `cor_fundo` varchar(7) DEFAULT '#f5f5f5',
  `periodo_pagamento` enum('semanal','quinzenal','mensal') DEFAULT 'semanal',
  `dia_fechamento` int(11) DEFAULT 1 COMMENT 'Dia da semana (1-7) para semanal, dia do mês (1-31) para quinzenal/mensal',
  `controle_horario` tinyint(1) DEFAULT 1 COMMENT 'Bloquear registros fora do horário de funcionamento',
  `aprovar_gorjetas` tinyint(1) DEFAULT 1 COMMENT 'Requer aprovação do admin para gorjetas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `horario_abertura`, `horario_fechamento`, `intervalo_slot`, `prazo_cancelamento_horas`, `agendamento_ativo`, `tipo_empresa`, `cor_primaria`, `cor_secundaria`, `logo`, `cor_fundo`, `periodo_pagamento`, `dia_fechamento`, `controle_horario`, `aprovar_gorjetas`) VALUES
(1, '09:00:00', '19:00:00', 15, 24, 1, 'barbearia', '#1a1a1a', '#d4af37', 'logo.png', '#f5f5f5', 'semanal', 1, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_email`
--

CREATE TABLE `configuracoes_email` (
  `id` int(11) NOT NULL,
  `host` varchar(255) NOT NULL,
  `porta` int(11) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `remetente_nome` varchar(255) NOT NULL,
  `remetente_email` varchar(255) NOT NULL,
  `criptografia` enum('tls','ssl','nenhuma') DEFAULT 'tls',
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamentos`
--

CREATE TABLE `fechamentos` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `periodo_tipo` enum('semanal','quinzenal','mensal') NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `total_servicos` decimal(10,2) DEFAULT 0.00,
  `total_produtos` decimal(10,2) DEFAULT 0.00,
  `comissao_servicos` decimal(10,2) DEFAULT 0.00,
  `comissao_produtos` decimal(10,2) DEFAULT 0.00,
  `total_gorjetas` decimal(10,2) DEFAULT 0.00,
  `total_vales` decimal(10,2) DEFAULT 0.00,
  `valor_liquido` decimal(10,2) DEFAULT 0.00,
  `quantidade_atendimentos` int(11) DEFAULT 0,
  `quantidade_vendas` int(11) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `status` enum('aberto','fechado','pago') DEFAULT 'aberto',
  `fechado_por` int(11) DEFAULT NULL,
  `data_fechamento` datetime DEFAULT NULL,
  `pago_em` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fotos_profissional`
--

CREATE TABLE `fotos_profissional` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `url_foto` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `gorjetas`
--

CREATE TABLE `gorjetas` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `atendimento_id` int(11) DEFAULT NULL,
  `valor` decimal(8,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','cartao','pix','app') DEFAULT 'dinheiro',
  `status` enum('pendente','aprovada','negada') DEFAULT 'pendente',
  `motivo_negacao` text DEFAULT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `data_aprovacao` datetime DEFAULT NULL,
  `lido_profissional` tinyint(1) DEFAULT 0,
  `data_gorjeta` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `gorjetas`
--

INSERT INTO `gorjetas` (`id`, `profissional_id`, `cliente_id`, `atendimento_id`, `valor`, `forma_pagamento`, `status`, `motivo_negacao`, `aprovado_por`, `data_aprovacao`, `lido_profissional`, `data_gorjeta`) VALUES
(2, 3, 14, NULL, 11.11, 'dinheiro', 'pendente', NULL, NULL, NULL, 0, '2026-01-12 16:15:35'),
(3, 3, 14, 2, 111.11, 'dinheiro', 'pendente', NULL, NULL, NULL, 0, '2026-01-12 16:19:32'),
(4, 3, 14, 4, 1.11, 'dinheiro', 'pendente', NULL, NULL, NULL, 0, '2026-01-12 16:20:37'),
(5, 3, 14, 5, 1.11, 'dinheiro', 'pendente', NULL, NULL, NULL, 0, '2026-01-12 16:20:46'),
(6, 3, 14, 6, 999999.99, 'dinheiro', 'pendente', NULL, NULL, NULL, 0, '2026-01-12 16:22:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `tabela` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('foto','video','texto') DEFAULT 'foto',
  `legenda` text DEFAULT NULL,
  `midia_url` varchar(255) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `comentarios_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `preco_venda` decimal(8,2) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `preco_venda`, `ativo`) VALUES
(1, 'Pomada Matte', 29.90, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissional_servicos`
--

CREATE TABLE `profissional_servicos` (
  `profissional_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recomendacoes`
--

CREATE TABLE `recomendacoes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL,
  `nota` int(11) DEFAULT NULL CHECK (`nota` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT 0,
  `data_avaliacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `seguidores`
--

CREATE TABLE `seguidores` (
  `id` int(11) NOT NULL,
  `seguidor_id` int(11) NOT NULL,
  `seguido_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `duracao_min` int(11) NOT NULL,
  `preco` decimal(8,2) NOT NULL,
  `categoria` enum('barbearia','cabelo','unhas','estetica') NOT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `descricao`, `duracao_min`, `preco`, `categoria`, `ativo`) VALUES
(3, 'Cabelo', NULL, 20, 30.00, 'barbearia', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos_realizados`
--

CREATE TABLE `servicos_realizados` (
  `id` int(11) NOT NULL,
  `atendimento_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `preco` decimal(8,2) NOT NULL,
  `comissao` decimal(8,2) NOT NULL,
  `data_realizacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `servicos_realizados`
--

INSERT INTO `servicos_realizados` (`id`, `atendimento_id`, `profissional_id`, `servico_id`, `cliente_id`, `nome`, `preco`, `comissao`, `data_realizacao`) VALUES
(2, 2, 3, 3, 14, 'Cabelo', 30.00, 0.00, '2026-01-12 16:19:32'),
(3, 3, 3, 3, 14, 'Cabelo', 30.00, 0.00, '2026-01-12 16:19:51'),
(4, 4, 3, 3, 14, 'Cabelo', 30.00, 0.00, '2026-01-12 16:20:37'),
(5, 5, 3, 3, 14, 'Cabelo', 30.00, 0.00, '2026-01-12 16:20:46'),
(6, 6, 3, 3, 14, 'Cabelo', 30.00, 0.00, '2026-01-12 16:22:02'),
(7, 7, 3, 3, 14, 'Cabelo', 30.00, 0.00, '2026-01-12 16:28:00'),
(8, 8, 3, 3, NULL, 'Cabelo', 30.00, 0.00, '2026-01-12 16:36:51'),
(9, 9, 3, 3, NULL, 'Cabelo', 30.00, 0.00, '2026-01-12 16:37:28'),
(10, 10, 3, 3, NULL, 'Cabelo', 30.00, 0.00, '2026-01-12 16:42:53'),
(11, 11, 3, 3, NULL, 'Cabelo', 30.00, 0.00, '2026-01-12 16:47:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('cliente','profissional','admin') NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `comissao_padrao` decimal(5,2) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `tipo`, `telefone`, `foto`, `avatar`, `bio`, `comissao_padrao`, `ativo`, `created_at`) VALUES
(1, 'Admin', 'admin@salao.com', '$2y$10$tmcD.C28UVKDiYuXBm2AnuK.NMLmsVnNgJR1NeBQtSDa5X/dJSkCC', 'admin', NULL, NULL, NULL, NULL, NULL, 1, '2026-01-12 14:17:25'),
(3, 'Rondineli', 'rondi.rio@hotmail.com', '$2y$10$Q5Fb9HGsOoMWsyG68ms2euO2gK/SQ4VwO.46s3Yfh2D.b5wI.DWz.', 'profissional', '', NULL, NULL, NULL, NULL, 1, '2026-01-12 14:37:55'),
(14, 'asd', 'tesdte@stylemanager.com', '', 'cliente', NULL, NULL, NULL, NULL, NULL, 1, '2026-01-12 16:15:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vales`
--

CREATE TABLE `vales` (
  `id` int(11) NOT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `valor` decimal(8,2) NOT NULL,
  `motivo` text DEFAULT NULL,
  `data_vale` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vales`
--

INSERT INTO `vales` (`id`, `profissional_id`, `valor`, `motivo`, `data_vale`) VALUES
(2, 3, 11.11, 'Vale retirado durante atendimento', '2026-01-12'),
(3, 3, 11.11, 'Vale retirado durante atendimento', '2026-01-12'),
(4, 3, 1.11, 'Vale retirado durante atendimento', '2026-01-12'),
(5, 3, 1.11, 'Vale retirado durante atendimento', '2026-01-12'),
(6, 3, 999999.99, 'Vale retirado durante atendimento', '2026-01-12'),
(7, 3, 1.11, 'Vale retirado durante atendimento', '2026-01-12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vendas_produtos`
--

CREATE TABLE `vendas_produtos` (
  `id` int(11) NOT NULL,
  `atendimento_id` int(11) DEFAULT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `valor_unitario` decimal(8,2) DEFAULT NULL,
  `valor_total` decimal(8,2) NOT NULL,
  `comissao_produto` decimal(8,2) NOT NULL,
  `data_venda` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vendas_produtos`
--

INSERT INTO `vendas_produtos` (`id`, `atendimento_id`, `profissional_id`, `produto_id`, `cliente_id`, `quantidade`, `valor_unitario`, `valor_total`, `comissao_produto`, `data_venda`) VALUES
(1, 6, 3, 1, 14, 12222222, 29.90, 999999.99, 0.00, '2026-01-12 16:22:02');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `agendamento_itens`
--
ALTER TABLE `agendamento_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agendamento_id` (`agendamento_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- Índices de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agendamento_item_id` (`agendamento_item_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `servico_id` (`servico_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `comissoes`
--
ALTER TABLE `comissoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`profissional_id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes_email`
--
ALTER TABLE `configuracoes_email`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fechado_por` (`fechado_por`),
  ADD KEY `idx_profissional_periodo` (`profissional_id`,`data_inicio`,`data_fim`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `fotos_profissional`
--
ALTER TABLE `fotos_profissional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`profissional_id`);

--
-- Índices de tabela `gorjetas`
--
ALTER TABLE `gorjetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `atendimento_id` (`atendimento_id`),
  ADD KEY `aprovado_por` (`aprovado_por`),
  ADD KEY `idx_profissional_status` (`profissional_id`,`status`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `profissional_servicos`
--
ALTER TABLE `profissional_servicos`
  ADD PRIMARY KEY (`profissional_id`,`servico_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- Índices de tabela `recomendacoes`
--
ALTER TABLE `recomendacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- Índices de tabela `seguidores`
--
ALTER TABLE `seguidores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`seguidor_id`,`seguido_id`),
  ADD KEY `seguido_id` (`seguido_id`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `servicos_realizados`
--
ALTER TABLE `servicos_realizados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atendimento_id` (`atendimento_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `servico_id` (`servico_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `vales`
--
ALTER TABLE `vales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profissional_id` (`profissional_id`);

--
-- Índices de tabela `vendas_produtos`
--
ALTER TABLE `vendas_produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atendimento_id` (`atendimento_id`),
  ADD KEY `profissional_id` (`profissional_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `agendamento_itens`
--
ALTER TABLE `agendamento_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `atendimentos`
--
ALTER TABLE `atendimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `comissoes`
--
ALTER TABLE `comissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes_email`
--
ALTER TABLE `configuracoes_email`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamentos`
--
ALTER TABLE `fechamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `fotos_profissional`
--
ALTER TABLE `fotos_profissional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `gorjetas`
--
ALTER TABLE `gorjetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `recomendacoes`
--
ALTER TABLE `recomendacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `seguidores`
--
ALTER TABLE `seguidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `servicos_realizados`
--
ALTER TABLE `servicos_realizados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `vales`
--
ALTER TABLE `vales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `vendas_produtos`
--
ALTER TABLE `vendas_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `agendamentos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `agendamento_itens`
--
ALTER TABLE `agendamento_itens`
  ADD CONSTRAINT `agendamento_itens_ibfk_1` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agendamento_itens_ibfk_2` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `agendamento_itens_ibfk_3` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `atendimentos`
--
ALTER TABLE `atendimentos`
  ADD CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`agendamento_item_id`) REFERENCES `agendamento_itens` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `atendimentos_ibfk_2` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `atendimentos_ibfk_3` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `atendimentos_ibfk_4` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `comissoes`
--
ALTER TABLE `comissoes`
  ADD CONSTRAINT `comissoes_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `fechamentos`
--
ALTER TABLE `fechamentos`
  ADD CONSTRAINT `fechamentos_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fechamentos_ibfk_2` FOREIGN KEY (`fechado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `fotos_profissional`
--
ALTER TABLE `fotos_profissional`
  ADD CONSTRAINT `fotos_profissional_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `gorjetas`
--
ALTER TABLE `gorjetas`
  ADD CONSTRAINT `gorjetas_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gorjetas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gorjetas_ibfk_3` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gorjetas_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `profissional_servicos`
--
ALTER TABLE `profissional_servicos`
  ADD CONSTRAINT `profissional_servicos_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profissional_servicos_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `recomendacoes`
--
ALTER TABLE `recomendacoes`
  ADD CONSTRAINT `recomendacoes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `recomendacoes_ibfk_2` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `recomendacoes_ibfk_3` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`);

--
-- Restrições para tabelas `seguidores`
--
ALTER TABLE `seguidores`
  ADD CONSTRAINT `seguidores_ibfk_1` FOREIGN KEY (`seguidor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguidores_ibfk_2` FOREIGN KEY (`seguido_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `servicos_realizados`
--
ALTER TABLE `servicos_realizados`
  ADD CONSTRAINT `servicos_realizados_ibfk_1` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `servicos_realizados_ibfk_2` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servicos_realizados_ibfk_3` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servicos_realizados_ibfk_4` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `vales`
--
ALTER TABLE `vales`
  ADD CONSTRAINT `vales_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `vendas_produtos`
--
ALTER TABLE `vendas_produtos`
  ADD CONSTRAINT `vendas_produtos_ibfk_1` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendas_produtos_ibfk_2` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `vendas_produtos_ibfk_3` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  ADD CONSTRAINT `vendas_produtos_ibfk_4` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
