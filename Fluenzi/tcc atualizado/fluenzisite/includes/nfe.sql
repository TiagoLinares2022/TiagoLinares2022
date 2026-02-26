-- Localização: fluenzisite/nfe.sql (Conteúdo Completo Atualizado)

-- Criação do banco de dados (execute manualmente no phpMyAdmin ou MySQL Workbench)
-- CREATE DATABASE fluenzisite_db;
-- USE fluenzisite_db;

-- Tabela de Clientes
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Produtos
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `ncm` varchar(8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Notas Fiscais
CREATE TABLE `notas_fiscais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `data_emissao` datetime NOT NULL,
  `xml_path` varchar(255) DEFAULT NULL, 
  `pdf_path` varchar(255) DEFAULT NULL, 
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Itens da Nota Fiscal
CREATE TABLE `itens_nfe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nota_fiscal_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`nota_fiscal_id`) REFERENCES `notas_fiscais`(`id`),
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Transações (Fluxo de Caixa) - ESSENCIAL PARA A IA
CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descricao` varchar(255) NOT NULL,
  `tipo` enum('receita', 'despesa') NOT NULL, -- Receita ou Despesa
  `valor` decimal(10,2) NOT NULL, -- Valor líquido (negativo para despesa)
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL, -- NULL se não foi paga/recebida
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adicionar a Tabela de Usuários
-- Observação: Em um sistema real, a senha (password) deve ser armazenada com hash (ex: password_hash())
-- Para o TCC, usaremos uma senha de texto simples para simplificar a demonstração.

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL, -- O ideal é usar um campo mais longo para hashes
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir um usuário de exemplo para testes
INSERT INTO `usuarios` (`username`, `password`, `nome`) VALUES
('admin', '123456', 'Administrador Fluenzi');