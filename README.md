# Padrinho Solidário — PHP, HTML5, CSS3 e JavaScript

## Como executar
1. Extraia a pasta do projeto.
2. Abra o terminal dentro da pasta `padrinho-solidario-php`.
3. Execute `php -S localhost:8000`.
4. Acesse http://localhost:8000.

Também é possível colocar a pasta em `htdocs` e iniciar o Apache pelo XAMPP.

## Correções nesta versão
- Navegação lateral funcional entre Visão geral, Padrinhos, Crianças, Doações, Instituições e Relatórios.
- Cada opção apresenta uma tela própria dentro do painel.
- Busca local nas tabelas demonstrativas.
- Exportação CSV na tela de relatórios.
- Botões de cadastro informam que a persistência ainda precisa ser implementada.

## Limitações
Os dados são fictícios e ficam apenas no front-end. Não há autenticação nem banco de dados conectado nesta versão. O Chart.js é carregado por CDN, exigindo conexão com a internet para os gráficos.


## Cadastro de padrinhos

O botão "Quero apadrinhar" abre `cadastro.php`. O formulário valida nome, e-mail, senha, telefone, CPF, renda e endereço no navegador e no servidor. A senha é armazenada com `password_hash`; os dados são inseridos em SQLite usando consultas preparadas. O arquivo do banco é criado fora da pasta do projeto, em `padrinho-solidario-data/cadastros.sqlite`.

### Requisito para persistência
É necessário PHP com as extensões `PDO` e `PDO_SQLITE` habilitadas. Em instalações XAMPP, confira no `php.ini` se `extension=pdo_sqlite` e `extension=sqlite3` estão habilitadas e reinicie o Apache. Sem `PDO_SQLITE`, a tela abre, mas o cadastro não será gravado.

### Antes de publicar
Este projeto ainda é uma base acadêmica. Configure HTTPS, política de privacidade, controle de acesso administrativo, backups protegidos, retenção/exclusão de dados e demais medidas de segurança e conformidade com a LGPD. CPF, renda e endereço são dados pessoais e devem ser coletados apenas quando necessários.


## Página de boas-vindas e listagem demonstrativa de crianças

Após um cadastro válido, `cadastro.php` grava os dados no SQLite e redireciona para `boas-vindas.php`. A página mostra uma saudação personalizada e cartões sem fotografias, contendo somente idade, sexo, status, instituição e observações/necessidades. Os dados das crianças são fictícios e estão definidos no próprio arquivo `boas-vindas.php`; ainda não há integração com banco de dados de crianças, filtros ou processo real de seleção/apadrinhamento.

A página de boas-vindas exige a sessão criada durante o cadastro. A listagem não apresenta nomes nem imagens das crianças para preservar a privacidade no protótipo.


## Login de padrinhos

- `login.php` autentica com e-mail e senha usando `password_verify()` contra o hash armazenado no SQLite.
- Após autenticação, a sessão é regenerada e o usuário segue para `boas-vindas.php`, a mesma listagem demonstrativa de crianças.
- `sair.php` encerra a sessão.
- A página inicial e o formulário de cadastro oferecem acesso ao login.

**Preservação do banco:** o arquivo SQLite é criado fora da pasta do projeto, em `padrinho-solidario-data/cadastros.sqlite`. Ao atualizar os arquivos, mantenha essa pasta no mesmo local relativo ao diretório do projeto para que os cadastros anteriores continuem acessíveis. Não apague essa pasta se quiser preservar as contas.

Requisito: PHP com PDO_SQLITE habilitado. O protótipo não inclui recuperação de senha, confirmação de e-mail, limitação de tentativas ou painel de administração; esses recursos e medidas adicionais de segurança devem ser implementados antes de uso público.
