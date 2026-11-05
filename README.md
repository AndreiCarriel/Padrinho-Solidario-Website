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


## Solicitações de apadrinhamento

Na janela de detalhes, o botão **Apadrinhar** agora envia um POST protegido por token CSRF. O servidor grava a solicitação na tabela `solicitacoes_apadrinhamento`, associando o ID/nome do padrinho e os dados exibidos da criança, com status inicial `Pendente`. O usuário recebe uma confirmação na página de boas-vindas após o redirecionamento. A criança marcada como não ativa não aceita solicitações. Ainda não existe painel de instituição para aceitar/recusar; a tabela já guarda o status para essa futura etapa.

## Fluxo de doações

- O botão **Fazer doação** abre uma janela perguntando se a pessoa possui cadastro.
- Com cadastro, direciona ao login e, após autenticar, retorna a `doacao.php`. Se já estiver logada, abre diretamente o formulário.
- Sem cadastro, abre `instituicoes.php`, com os contatos demonstrativos das instituições.
- O formulário autenticado registra instituição, tipo, valor (quando dinheiro), descrição, padrinho e data na tabela `doacoes` do mesmo SQLite.
- As doações não movimentam dinheiro nem comprovam transferência: o registro é uma anotação da contribuição informada pelo usuário.

**Atenção:** os e-mails e instituições de exemplo são fictícios e devem ser substituídos pelos contatos oficiais. A solicitação de apadrinhamento fica registrada como pendente, mas a instituição ainda não recebe notificação automática por e-mail nem possui tela para decidir. Para produção, implementar painel institucional, autenticação por perfil, notificações, auditoria e controles LGPD.


## Ajuste da janela de apadrinhamento

A janela de informações da criança agora apresenta somente o botão **Apadrinhar**. Após a confirmação, a solicitação é registrada com status `Pendente` e o usuário recebe um alerta com o nome da instituição e o e-mail de contato correspondente. Os e-mails atuais são demonstrativos e devem ser substituídos pelos endereços oficiais. O registro no SQLite não envia, por si só, uma mensagem de e-mail à instituição; a notificação automática depende de integração de e-mail ou de um painel institucional.
