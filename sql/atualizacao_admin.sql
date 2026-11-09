-- Tabela auxiliar exclusiva para autenticação do painel administrativo.
-- Não altera as tabelas do modelo acadêmico: usuarios, padrinho, instituicao,
-- apadrinhado e apadrinhamento permanecem exatamente como no dump enviado.
CREATE TABLE IF NOT EXISTS administradores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
