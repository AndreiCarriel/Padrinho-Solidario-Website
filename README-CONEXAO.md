# Padrinho Solidário — conexão e autenticação

## Configuração MySQL

O arquivo `db.php` centraliza a conexão PDO com:

- Host: `127.0.0.1`
- Porta: `3306`
- Banco: `bd_padrinhosolidario`
- Usuário: `root`
- Senha: vazia

Se o MySQL do computador usar outra porta ou credencial, altere somente as variáveis no início da função `db()`.

No XAMPP, habilite `extension=pdo_mysql` no `php.ini` utilizado pelo Apache e reinicie o Apache.

## Estrutura usada na autenticação

O login consulta as tabelas do modelo:

- `usuarios`: `id`, `nome`, `email`, `senha`, `telefone`
- `padrinho`: `id`, `cpf`, `renda`, `endereco`, `id_usuario`
- `instituicao`: `id`, `cep`, `responsavel`, `necessidades`, `id_usuario`

O e-mail e o hash da senha ficam em `usuarios`. O login une `usuarios` ao perfil correspondente por `id_usuario`. A senha é validada com `password_verify()`.

## Testes

O arquivo temporário `teste.php` foi removido do pacote. Para testar a conexão, use um arquivo temporário com `require_once __DIR__ . '/db.php';` e `$db = db();`, e depois remova esse arquivo.

## Limite do esquema atual

A exportação SQL analisada não contém a tabela `doacao` nem uma tabela de solicitações de apadrinhamento. As páginas não devem tentar gravar nesses módulos até que as tabelas correspondentes sejam adicionadas oficialmente ao banco. O módulo de doações exibe um aviso em vez de executar uma consulta em tabela inexistente.
