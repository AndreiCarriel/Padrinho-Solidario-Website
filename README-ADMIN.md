# Painel administrativo — estrutura alinhada ao dump SQL

## Banco utilizado
`bd_padrinhosolidario`, conforme `bd_padrinhosolidario.sql`.

O arquivo `db.php` apenas conecta ao MySQL. Ele **não cria, renomeia nem altera** as tabelas principais.

## Tabelas principais utilizadas exatamente como exportadas
- `usuarios`: `id`, `nome`, `email`, `senha`, `telefone`
- `padrinho`: `id`, `cpf`, `renda`, `endereco`, `id_usuario`
- `instituicao`: `id`, `cep`, `responsavel`, `necessidades`, `id_usuario`
- `apadrinhado`: `id`, `idade`, `sexo`, `observacoes_necessidades`, `status`, `id_instituicao`
- `apadrinhamento`: `id`, `data_inicio`, `data_fim`, `tipo_apadrinhamento`, `status`, `id_padrinho`, `id_apadrinhado`

O painel não adiciona colunas auxiliares nessas tabelas. Os cadastros de padrinho e instituição criam primeiro o registro em `usuarios` e depois o perfil correspondente, vinculando por `id_usuario`.

## Administrador
A autenticação do painel usa uma tabela auxiliar chamada `administradores`, criada pelo módulo de login/setup. Ela não modifica as cinco tabelas principais do modelo. Acesse `admin/setup.php` uma única vez para criar o primeiro administrador e depois `admin/login.php`.

## Doações
A documentação descreve a entidade `doacao`, mas o dump SQL anexado não contém uma tabela `doacao` nem seus índices. Para respeitar o dump exatamente, o painel não cria uma tabela de doações nem inventa colunas. O módulo de doações só poderá ser implementado após a tabela constar na exportação/importação do banco.

## Observação importante
O dump fornecido não declara constraints de chave estrangeira no SQL exportado; o painel utiliza os campos `id_usuario`, `id_instituicao`, `id_padrinho` e `id_apadrinhado` conforme o modelo lógico. O campo `data_fim` em `apadrinhamento` é obrigatório no dump, por isso o formulário administrativo exige essa data.
