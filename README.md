# Padrinho Solidário — PHP, HTML5, CSS3, JavaScript e MySQL

## Instalação
1. Coloque a pasta do projeto no diretório público do seu servidor local (por exemplo, `htdocs` do XAMPP).
2. Inicie Apache e MySQL.
3. Crie/importe o banco `bd_padrinhosolidario` com as tabelas-base já utilizadas pelo projeto: `usuarios`, `padrinho`, `instituicao` e `apadrinhado`.
4. Confira as credenciais em `db.php`.
5. Confira se `instituicao_id` existe em `solicitacoes_apadrinhamento` e `doacoes`, e se `status` existe em `doacoes`. Se faltar algum campo, execute `sql/atualizacao_apadrinhamento_doacoes.sql`.
6. Depois de substituir os arquivos PHP, e somente se quiser remover as colunas redundantes, execute `sql/remover_colunas_redundantes.sql`. Faça backup antes.
6. Acesse `index.php` pelo navegador.

## Fluxo de apadrinhamento
- O usuário autenticado visualiza as crianças cadastradas na tabela `apadrinhado` e a instituição associada pelo campo `id_instituicao`.
- Ao solicitar apadrinhamento, o sistema grava `padrinho_id`, `crianca_id` e `instituicao_id` em `solicitacoes_apadrinhamento`, com status inicial `Pendente`.
- O painel da instituição mostra somente solicitações destinadas à instituição autenticada.
- A instituição pode aceitar ou recusar uma solicitação pendente.
- Solicitações aprovadas aparecem em **Meus apadrinhados** na conta do padrinho. Uma criança não pode ter duas solicitações aprovadas pelo fluxo da aplicação.

## Doações
- O botão **Doar** aparece para vínculos aprovados.
- O usuário registra o tipo de doação e a descrição; para dinheiro, informa também o valor.
- O registro fica associado ao padrinho, à criança e à instituição responsável.
- A instituição visualiza as doações no painel e pode marcar o recebimento.
- O sistema registra informações declaradas pelo usuário. Não processa pagamentos, não recebe transferências e não comprova que bens ou valores foram entregues. A confirmação de recebimento é feita pela instituição.

## Estrutura das tabelas de fluxo
As tabelas `solicitacoes_apadrinhamento` e `doacoes` já existem no banco do projeto. O PHP usa a estrutura simplificada descrita abaixo. As tabelas-base precisam conter as colunas que o restante do projeto já utiliza:
- `usuarios`: `id`, `nome`, `email`, `senha`, `telefone`.
- `padrinho`: `id`, `id_usuario` (além dos dados cadastrais).
- `instituicao`: `id`, `id_usuario`, `cep`, `responsavel`, `necessidades`.
- `apadrinhado`: `id`, `id_instituicao`, `idade`, `sexo`, `observacoes_necessidades`, `status`.

A migração não cria chaves estrangeiras físicas porque os tipos exatos das chaves das tabelas-base precisam corresponder ao dump real. Os relacionamentos são validados nas consultas e nas operações PHP.

## Configuração do banco
As credenciais ficam em `db.php`. O padrão atual é `127.0.0.1:3306`, banco `bd_padrinhosolidario`, usuário `root` e senha vazia. Ajuste conforme seu ambiente.

## Observações
- Faça backup do banco antes de executar qualquer migração.
- A aplicação usa sessões, consultas preparadas e token CSRF nos formulários de alteração.
- Para ambiente de produção, configure HTTPS, credenciais restritas do MySQL, política de privacidade/LGPD, backups e auditoria.


## Correção de carregamento do painel institucional
- Ao abrir o painel, o sistema garante a existência das tabelas auxiliares de solicitações e doações.
- Se o painel ainda não carregar, verifique o log de erros do PHP/Apache (no XAMPP, normalmente `xampp/apache/logs/error.log`). A mensagem registrada começa com `Carga painel instituição`, `Preparação das tabelas do painel institucional` ou `Consulta de vínculos`.
- Se o erro mencionar uma coluna ausente em `instituicao`, `padrinho`, `usuarios` ou `apadrinhado`, será necessário comparar os nomes reais dessas colunas com o banco instalado. Não altere nem exclua dados para tentar corrigir sem antes conferir a estrutura.


## Compatibilidade com o dump SQL de 15/11/2026
- `solicitacoes_apadrinhamento`: o PHP utiliza `id`, `padrinho_id`, `crianca_id`, `instituicao_id`, `observacoes`, `status` e `criado_em`. Não utiliza mais `padrinho_nome`, `idade_crianca`, `sexo_crianca` ou `instituicao`.
- `doacoes`: o PHP utiliza `id`, `padrinho_id`, `id_apadrinhado`, `instituicao_id`, `tipo`, `valor`, `descricao`, `data_doacao` e `status`. Não utiliza mais a coluna textual `instituicao`.
- `apadrinhamento` mantém `id` como chave primária, conforme o dump. `id_apadrinhado` não é chave primária nesse arquivo. A aprovação grava o vínculo usando a estrutura real e uma data final futura, pois `data_fim` está definida como NOT NULL.
- O dump contém solicitações antigas com `crianca_id=0` e nomes de instituições que não existem na tabela `instituicao` atual. Esses registros históricos não podem ser ligados automaticamente a uma instituição/criança válida; são preservados. Solicitações novas terão os relacionamentos completos.


## Exclusividade do apadrinhamento
- A área de crianças disponíveis exclui qualquer criança que já tenha uma solicitação com status `Aprovado`.
- A aprovação é feita dentro de transação e bloqueia a linha da criança durante a operação, evitando duas aprovações simultâneas para a mesma criança.
- Quando uma solicitação é aprovada, outras solicitações ainda pendentes para aquela criança são marcadas como `Recusado`.
- O painel institucional lista as crianças vinculadas à instituição e exibe o padrinho/madrinha da solicitação aprovada.
- O padrinho visualiza seus apadrinhados somente na própria conta, a partir das solicitações aprovadas associadas ao seu `padrinho_id`.


## Exclusividade e visibilidade dos apadrinhamentos
- Crianças com solicitação aprovada ou vínculo ativo em `apadrinhamento` não aparecem na lista de crianças disponíveis para outros padrinhos.
- A mesma regra é validada no envio da solicitação, além da listagem visual.
- A aprovação usa transação e bloqueio da linha da criança para impedir duas aprovações simultâneas.
- Se uma solicitação for aprovada, outras solicitações pendentes para a mesma criança são automaticamente marcadas como `Recusado`.
- O painel da instituição lista suas crianças e consulta o vínculo ativo em `apadrinhamento` para mostrar o nome e o e-mail do padrinho/madrinha.
- O padrinho vê somente os apadrinhamentos vinculados ao seu próprio `padrinho_id`.


## Nome da criança e controle de visibilidade
- O banco `apadrinhado` deve conter a coluna `nome` (já adicionada manualmente pelo responsável).
- O formulário administrativo exige o nome e grava o campo `apadrinhado.nome`.
- Na listagem de crianças disponíveis, o nome não é selecionado nem exibido.
- Na lista de solicitações do padrinho, o nome só é consultado para o próprio padrinho quando a solicitação tem status `Aprovado`; em `Pendente` ou `Recusado`, só aparece o identificador da criança.
- Na seção **Meus apadrinhados** e no formulário de doação de vínculos aprovados, o nome é exibido ao padrinho autenticado.
- A instituição autenticada visualiza o nome das crianças sob seus cuidados, inclusive nos pedidos e no quadro de vínculos. O administrador também pode visualizar nomes.
- O nome de crianças não aprovadas nunca deve ser incluído em páginas públicas ou respostas de API.
- Não é necessário executar uma migração SQL adicional caso `apadrinhado.nome` já exista.


## Sessão persistente
- As páginas principais e a área administrativa utilizam `session.php`.
- O cookie de sessão dura até 30 dias, inclusive entre reinicializações do navegador, desde que o usuário não clique em Sair nem limpe os cookies.
- Para produção, use HTTPS para que o cookie seja marcado como Secure.

## Gestão de crianças pela instituição
- A instituição pode cadastrar crianças diretamente no próprio painel; o `id_instituicao` é obtido da sessão autenticada e não é enviado pelo formulário.
- A instituição pode ativar/inativar apenas crianças vinculadas ao seu próprio ID.
- Crianças inativas permanecem visíveis no painel institucional, mas o status impede que apareçam entre as disponíveis para apadrinhamento.
- Os filtros da listagem são checkboxes combináveis: ativo/inativo, com/sem padrinho e sexo.

## Cancelamento de apadrinhamento
- A instituição pode cancelar um vínculo ativo após confirmação no navegador.
- O vínculo em `apadrinhamento` recebe status `Cancelado` e `data_fim` igual à data do cancelamento.
- A solicitação correspondente recebe status `Cancelado`.
- Se a criança continuar ativa, poderá voltar à lista de disponíveis.
