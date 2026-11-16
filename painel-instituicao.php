<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/fluxo.php';
if (empty($_SESSION['instituicao_id']) || ($_SESSION['perfil'] ?? '') !== 'instituicao') {
    header('Location: login.php?perfil=instituicao');
    exit;
}
$db = db();
$instituicaoId = (int) $_SESSION['instituicao_id'];
$erro = '';
$sucesso = '';
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
try {
    fluxo_garantir_tabelas($db);
} catch (Throwable $e) {
    error_log('Preparação das tabelas do painel institucional: ' . $e->getMessage());
    $erro = 'Estrutura do banco incompatível: ' . $e->getMessage() . ' Execute a atualização SQL corrigida e recarregue o painel.';
}
if (!$erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');
    if (!fluxo_validar_csrf())
        $erro = 'Sessão expirada. Atualize a página.';
    else
        try {
            $id = filter_var($_POST['solicitacao_id'] ?? null, FILTER_VALIDATE_INT);

            if ($acao === 'cadastrar_crianca') {
                $nome = trim((string) ($_POST['nome'] ?? ''));
                $idade = filter_var($_POST['idade'] ?? null, FILTER_VALIDATE_INT);
                $sexo = (string) ($_POST['sexo'] ?? '');
                $obs = trim((string) ($_POST['observacoes_necessidades'] ?? ''));
                if ($nome === '' || mb_strlen($nome) > 100 || $idade === false || $idade < 0 || !in_array($sexo, ['Masculino', 'Feminino', 'Outro'], true) || $obs === '')
                    throw new RuntimeException('Preencha corretamente nome, idade, sexo e necessidades.');
                $q = $db->prepare("INSERT INTO apadrinhado (nome,idade,sexo,observacoes_necessidades,status,id_instituicao) VALUES (?,?,?,?,1,?)");
                $q->execute([$nome, $idade, $sexo, $obs, $instituicaoId]);
                $sucesso = 'Criança cadastrada e vinculada à sua instituição.';
            } elseif ($acao === 'alternar_status_crianca') {
                $criancaId = filter_var($_POST['crianca_id'] ?? null, FILTER_VALIDATE_INT);
                $novoStatus = filter_var($_POST['novo_status'] ?? null, FILTER_VALIDATE_INT);
                if (!$criancaId || !in_array($novoStatus, [0, 1], true))
                    throw new RuntimeException('Dados inválidos para alterar a situação da criança.');
                $q = $db->prepare("UPDATE apadrinhado SET status=? WHERE id=? AND id_instituicao=?");
                $q->execute([$novoStatus, $criancaId, $instituicaoId]);
                if (!$q->rowCount())
                    throw new RuntimeException('Criança não encontrada ou já estava nessa situação.');
                $sucesso = $novoStatus === 1 ? 'Criança ativada.' : 'Criança inativada e retirada da lista de disponíveis.';
            } elseif ($acao === 'cancelar_apadrinhamento') {
                $db->beginTransaction();
                $q = $db->prepare("SELECT ap.id,ap.id_apadrinhado FROM apadrinhamento ap JOIN apadrinhado a ON a.id=ap.id_apadrinhado WHERE ap.id=? AND a.id_instituicao=? AND ap.status='Ativo' FOR UPDATE");
                $q->execute([$id, $instituicaoId]);
                $vinculo = $q->fetch();
                if (!$vinculo)
                    throw new RuntimeException('Apadrinhamento ativo não encontrado para esta instituição.');
                $q = $db->prepare("UPDATE apadrinhamento SET status='Cancelado',data_fim=CURDATE() WHERE id=? AND status='Ativo'");
                $q->execute([$id]);
                $q = $db->prepare("UPDATE solicitacoes_apadrinhamento SET status='Cancelado' WHERE crianca_id=? AND status='Aprovado'");
                $q->execute([(int) $vinculo['id_apadrinhado']]);
                $db->commit();
                $sucesso = 'Apadrinhamento cancelado. A criança poderá voltar a ficar disponível se estiver ativa.';
            } elseif (!$id || !in_array($acao, ['aprovar', 'recusar', 'recebida', 'nao_recebida'], true))
                throw new RuntimeException('Ação inválida.');

            if (in_array($acao, ['recebida', 'nao_recebida'], true)) {
                $statusDoacao = $acao === 'recebida' ? 'Recebida' : 'Não Recebida';
                $q = $db->prepare("UPDATE doacoes SET status=? WHERE id=? AND instituicao_id=?");
                $q->execute([$statusDoacao, $id, $instituicaoId]);
                $q = $db->prepare('SELECT id FROM doacoes WHERE id=? AND instituicao_id=?');
                $q->execute([$id, $instituicaoId]);
                if (!$q->fetch())
                    throw new RuntimeException('Doação não encontrada para esta instituição.');
                $sucesso = 'Status da doação atualizado para: ' . $statusDoacao . '.';
            } elseif (in_array($acao, ['aprovar', 'recusar'], true)) {
                $novo = $acao === 'aprovar' ? 'Aprovado' : 'Recusado';
                if ($acao === 'aprovar') {
                    $db->beginTransaction();
                    $q = $db->prepare("SELECT crianca_id,padrinho_id FROM solicitacoes_apadrinhamento WHERE id=? AND instituicao_id=? AND status='Pendente' LIMIT 1 FOR UPDATE");
                    $q->execute([$id, $instituicaoId]);
                    $req = $q->fetch();
                    if (!$req)
                        throw new RuntimeException('Solicitação não encontrada ou já processada.');
                    // Bloqueia a linha da criança durante a aprovação para evitar duas aprovações simultâneas.
                    $q = $db->prepare("SELECT id FROM apadrinhado WHERE id=? AND id_instituicao=? LIMIT 1 FOR UPDATE");
                    $q->execute([(int) $req['crianca_id'], $instituicaoId]);
                    if (!$q->fetch())
                        throw new RuntimeException('A criança não pertence a esta instituição.');

                    $q = $db->prepare("SELECT id FROM solicitacoes_apadrinhamento WHERE crianca_id=? AND status='Aprovado' LIMIT 1");
                    $q->execute([(int) $req['crianca_id']]);
                    if ($q->fetch())
                        throw new RuntimeException('Esta criança já possui um padrinho ou madrinha aprovado.');

                    $q = $db->prepare("SELECT id FROM apadrinhamento WHERE id_apadrinhado=? AND status='Ativo' LIMIT 1");
                    $q->execute([(int) $req['crianca_id']]);
                    if ($q->fetch())
                        throw new RuntimeException('Esta criança já possui um apadrinhamento ativo.');
                    // Registra o vínculo aprovado na tabela de histórico de apadrinhamento.
                    $q = $db->prepare("INSERT INTO apadrinhamento (data_inicio,data_fim,tipo_apadrinhamento,status,id_padrinho,id_apadrinhado) VALUES (CURDATE(),DATE_ADD(CURDATE(), INTERVAL 100 YEAR),'Afetivo','Ativo',?,?)");
                    $q->execute([(int) $req['padrinho_id'], (int) $req['crianca_id']]);
                    $q = $db->prepare("UPDATE solicitacoes_apadrinhamento SET status='Aprovado' WHERE id=? AND instituicao_id=? AND status='Pendente'");
                    $q->execute([$id, $instituicaoId]);
                    if (!$q->rowCount())
                        throw new RuntimeException('Não foi possível atualizar a solicitação.');

                    // As demais solicitações pendentes para a mesma criança deixam de ser válidas.
                    $q = $db->prepare("UPDATE solicitacoes_apadrinhamento
        SET status='Recusado'
        WHERE crianca_id=? AND id<>? AND status='Pendente'");
                    $q->execute([(int) $req['crianca_id'], $id]);

                    $db->commit();
                } else {
                    $q = $db->prepare("UPDATE solicitacoes_apadrinhamento SET status='Recusado' WHERE id=? AND instituicao_id=? AND status='Pendente'");
                    $q->execute([$id, $instituicaoId]);
                    if (!$q->rowCount())
                        throw new RuntimeException('Não foi possível atualizar a solicitação.');
                }
                $sucesso = 'Solicitação ' . $novo . ' com sucesso.';
            }
        } catch (Throwable $e) {
            if ($db->inTransaction())
                $db->rollBack();
            $erro = $e instanceof RuntimeException ? $e->getMessage() : 'Falha ao processar. Confira se a atualização SQL foi executada.';
            error_log('Painel instituição: ' . $e->getMessage());
        }
    if ($isAjax && $acao === 'cadastrar_crianca') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($erro ? 422 : 201);
        echo json_encode(['success' => $erro === '', 'message' => $erro ?: $sucesso, 'child' => $erro ? null : ['nome' => $nome, 'idade' => (int) $idade, 'sexo' => $sexo, 'status' => 'ativo']], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['acao'] ?? '') === 'cadastrar_crianca' && $erro) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $erro], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    if ($erro !== '')
        throw new RuntimeException('A preparação das tabelas auxiliares falhou.');
    $q = $db->prepare('SELECT s.id,s.status,s.criado_em,a.id AS crianca_id,a.nome AS nome_apadrinhado,a.idade,a.sexo,u.nome AS padrinho_nome,u.email AS padrinho_email FROM solicitacoes_apadrinhamento s JOIN apadrinhado a ON a.id=s.crianca_id JOIN padrinho p ON p.id=s.padrinho_id JOIN usuarios u ON u.id=p.id_usuario WHERE s.instituicao_id=? ORDER BY s.criado_em DESC');
    $q->execute([$instituicaoId]);
    $solicitacoes = $q->fetchAll();
    $q = $db->prepare('SELECT d.id,d.tipo,d.valor,d.descricao,d.status,d.data_doacao,u.nome AS padrinho_nome,a.id AS crianca_id FROM doacoes d JOIN padrinho p ON p.id=d.padrinho_id JOIN usuarios u ON u.id=p.id_usuario LEFT JOIN apadrinhado a ON a.id=d.id_apadrinhado WHERE d.instituicao_id=? ORDER BY d.data_doacao DESC');
    $q->execute([$instituicaoId]);
    $doacoes = $q->fetchAll();
    // Relação de crianças da instituição e, quando houver, seu padrinho/madrinha aprovado.
    $q = $db->prepare("SELECT a.id,a.nome,a.idade,a.sexo,a.status,
        padrinho_usuario.nome AS padrinho_nome,padrinho_usuario.email AS padrinho_email,
        ap.id AS apadrinhamento_id,ap.data_inicio AS data_apadrinhamento
    FROM apadrinhado a
    LEFT JOIN (
        SELECT id_apadrinhado,MAX(id) AS apadrinhamento_id
        FROM apadrinhamento
        WHERE status='Ativo'
        GROUP BY id_apadrinhado
    ) vinculo ON vinculo.id_apadrinhado=a.id
    LEFT JOIN apadrinhamento ap ON ap.id=vinculo.apadrinhamento_id
    LEFT JOIN padrinho p ON p.id=ap.id_padrinho
    LEFT JOIN usuarios padrinho_usuario ON padrinho_usuario.id=p.id_usuario
    WHERE a.id_instituicao=?
    ORDER BY a.id DESC");
    $q->execute([$instituicaoId]);
    $criancasInstituicao = $q->fetchAll();
    $q = $db->prepare('SELECT u.nome,u.email,u.telefone,i.cep,i.responsavel,i.necessidades FROM instituicao i JOIN usuarios u ON u.id=i.id_usuario WHERE i.id=? LIMIT 1');
    $q->execute([$instituicaoId]);
    $dados = $q->fetch() ?: [];
} catch (Throwable $e) {
    error_log('Carga painel instituição: ' . $e->getMessage());
    $solicitacoes = $solicitacoes ?? [];
    $doacoes = $doacoes ?? [];
    $criancasInstituicao = $criancasInstituicao ?? [];
    $dados = $dados ?? [];
    $erro = $erro ?: 'Falha ao consultar os dados do painel: ' . $e->getMessage();
}
$csrf = fluxo_csrf();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel da instituição — Padrinho Solidário</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .inst-page {
            min-height: 100vh;
            padding: 34px 0 70px;
            background: var(--bg)
        }

        .inst-wrap {
            width: min(1150px, calc(100% - 36px));
            margin: auto
        }

        .inst-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px
        }

        .inst-section {
            margin: 24px 0;
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow)
        }

        .inst-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 15px
        }

        .inst-card {
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: var(--bg)
        }

        .inst-card.card-inativa {
            border: 2px solid #dc3545
        }

        .inst-card p {
            color: var(--muted)
        }

        .inst-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px
        }

        .inst-actions button {
            border: 0;
            cursor: pointer
        }

        .inst-alert {
            padding: 13px;
            border-radius: 9px;
            margin: 16px 0;
            background: #edf8f1;
            color: #246b48
        }

        .inst-error {
            padding: 13px;
            border-radius: 9px;
            margin: 16px 0;
            background: #fff0ed;
            color: #963e2d
        }

        .inst-table-wrap {
            overflow-x: auto
        }

        .inst-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 30px;
            background: #fff1d6;
            color: #855500;
            font-size: 12px;
            font-weight: 700
        }

        .inst-pill.ok {
            background: #e6f6ec;
            color: #246b48
        }

        .inst-theme-toggle {
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            font-size: 21px;
            cursor: pointer
        }

        .doacao-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap
        }

        .doacao-actions form {
            margin: 0
        }

        .button.button-received {
            background: #39836a
        }

        .button.button-not-received {
            background: #a94442
        }

        .doacao-status {
            display: inline-block;
            font-weight: 700;
            font-size: 12px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #f1f3f5;
            color: #59636e
        }

        .doacao-status.recebido {
            background: #e6f6ec;
            color: #21834f
        }

        .doacao-status.nao-recebido {
            background: #fdeaea;
            color: #c62828
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        td,
        th {
            padding: 11px;
            text-align: left;
            border-bottom: 1px solid var(--border)
        }

        .inst-register-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 12px
        }

        .inst-register-form input,
        .inst-register-form select,
        .inst-register-form textarea {
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            font: inherit
        }

        .inst-register-form input,
        .inst-register-form select {
            min-height: 44px
        }

        .inst-register-form textarea {
            min-height: 62px;
            resize: vertical
        }

        .inst-register-form input:focus,
        .inst-register-form select:focus,
        .inst-register-form textarea:focus {
            outline: 2px solid var(--primary);
            outline-offset: 1px
        }

        .inst-register-form button {
            align-self: end;
            justify-self: start;
            min-width: 190px
        }

        @media(max-width:560px) {
            .inst-register-form {
                grid-template-columns: 1fr
            }

            .inst-register-form button {
                width: 100%
            }
        }
    </style>
</head>

<body>
    <main class="inst-page">
        <div class="inst-wrap">
            <header class="inst-head"><a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho
                        Solidário</span></a>
                <div style="display:flex;align-items:center;gap:10px"><button type="button" class="inst-theme-toggle"
                        id="themeToggle" aria-label="Alternar tema">☾</button><a class="button button-outline"
                        href="sair.php">Sair</a></div>
            </header>
            <h1>Bem-vinda(o), <?= fluxo_e((string) ($_SESSION['instituicao_nome'] ?? 'Instituição')) ?></h1>
            <p>Gerencie solicitações de apadrinhamento e acompanhe as doações destinadas à sua instituição.</p>
            <?php if ($sucesso): ?>
                <div class="inst-alert" role="status"><?= fluxo_e($sucesso) ?></div><?php endif ?><?php if ($erro): ?>
                <div class="inst-error" role="alert"><?= fluxo_e($erro) ?></div><?php endif ?>
            <section class="inst-section">
                <h2>Solicitações de apadrinhamento</h2><?php if (!$solicitacoes): ?>
                    <p>Nenhuma solicitação recebida ainda.</p><?php else: ?>
                    <div class="inst-grid"><?php foreach ($solicitacoes as $s): ?>
                            <article class="inst-card"><strong><?= fluxo_e((string) $s['padrinho_nome']) ?></strong>
                                <p><?= fluxo_e((string) $s['padrinho_email']) ?></p>
                                <p><strong><?= fluxo_e((string) $s['nome_apadrinhado']) ?></strong> · <?= (int) $s['idade'] ?> anos ·
                                    <?= fluxo_e((string) $s['sexo']) ?></p>
                                <p>Status: <strong><?= fluxo_e((string) $s['status']) ?></strong></p>
                                <small><?= date('d/m/Y H:i', strtotime((string) $s['criado_em'])) ?></small><?php if ($s['status'] === 'Pendente'): ?>
                                    <div class="inst-actions">
                                        <form method="post"><input type="hidden" name="csrf_token"
                                                value="<?= fluxo_e($csrf) ?>"><input type="hidden" name="solicitacao_id"
                                                value="<?= (int) $s['id'] ?>"><input type="hidden" name="acao" value="aprovar"><button
                                                class="button" type="submit">Aceitar</button></form>
                                        <form method="post"><input type="hidden" name="csrf_token"
                                                value="<?= fluxo_e($csrf) ?>"><input type="hidden" name="solicitacao_id"
                                                value="<?= (int) $s['id'] ?>"><input type="hidden" name="acao" value="recusar"><button
                                                class="button button-outline" type="submit">Recusar</button></form>
                                    </div><?php endif ?>
                            </article><?php endforeach ?>
                    </div><?php endif ?>
            </section>
            <section class="inst-section inst-register-panel">
                <h2>Cadastrar criança</h2>
                <form method="post" class="inst-register-form" id="form-cadastrar-crianca"><input type="hidden"
                        name="csrf_token" value="<?= fluxo_e($csrf) ?>"><input type="hidden" name="acao"
                        value="cadastrar_crianca"><input name="nome" maxlength="100" required
                        placeholder="Nome da criança (restrito)" aria-label="Nome da criança"><input type="number"
                        name="idade" min="0" required placeholder="Idade" aria-label="Idade"><select name="sexo"
                        required aria-label="Sexo">
                        <option value="">Sexo</option>
                        <option>Masculino</option>
                        <option>Feminino</option>
                        <option>Outro</option>
                    </select><textarea name="observacoes_necessidades" required placeholder="Observações / necessidades"
                        aria-label="Observações e necessidades"></textarea><button class="button"
                        type="submit">Cadastrar criança</button></form>
            </section>
            <section class="inst-section">
                <h2>Crianças da instituição e seus padrinhos</h2>
                <div class="inst-filters" style="display:flex;gap:14px;flex-wrap:wrap;margin:14px 0">
                    <label><input type="checkbox" class="filtro-crianca" value="ativo" checked> Ativos</label>
                    <label><input type="checkbox" class="filtro-crianca" value="inativo" checked> Inativados</label>
                    <label><input type="checkbox" class="filtro-crianca" value="com-padrinho" checked> Com
                        padrinho</label>
                    <label><input type="checkbox" class="filtro-crianca" value="sem-padrinho" checked> Sem
                        padrinho</label>
                    <label><input type="checkbox" class="filtro-crianca" value="Masculino" checked> Homem</label>
                    <label><input type="checkbox" class="filtro-crianca" value="Feminino" checked> Mulher</label>
                </div>
                <?php if (empty($criancasInstituicao)): ?>
                    <p id="sem-criancas">Nenhuma criança cadastrada nesta instituição.</p>
                    <div class="inst-grid" id="lista-criancas"></div><?php else: ?>
                    <div class="inst-grid" id="lista-criancas"><?php foreach ($criancasInstituicao as $crianca): ?>
                            <article class="inst-card card-crianca <?= ((int) $crianca['status'] === 1 ? '' : 'card-inativa') ?>"
                                data-status="<?= ((int) $crianca['status'] === 1 ? 'ativo' : 'inativo') ?>"
                                data-padrinho="<?= !empty($crianca['padrinho_nome']) ? 'com-padrinho' : 'sem-padrinho' ?>"
                                data-sexo="<?= fluxo_e((string) $crianca['sexo']) ?>">
                                <strong><?= fluxo_e((string) $crianca['nome']) ?></strong>
                                <p><?= (int) $crianca['idade'] ?> anos · <?= fluxo_e((string) $crianca['sexo']) ?></p>
                                <?php if (!empty($crianca['padrinho_nome'])): ?>
                                    <p><strong>Padrinho/madrinha:</strong> <?= fluxo_e((string) $crianca['padrinho_nome']) ?></p>
                                    <p><?= fluxo_e((string) $crianca['padrinho_email']) ?></p><span class="inst-pill ok">Apadrinhamento
                                        ativo</span>
                                    <form method="post"
                                        onsubmit="return confirm('Deseja realmente cancelar este apadrinhamento? A criança ficará sem padrinho e poderá voltar a ficar disponível se estiver ativa.');">
                                        <input type="hidden" name="csrf_token" value="<?= fluxo_e($csrf) ?>"><input type="hidden"
                                            name="acao" value="cancelar_apadrinhamento"><input type="hidden" name="solicitacao_id"
                                            value="<?= (int) $crianca['apadrinhamento_id'] ?>"><button class="button button-outline"
                                            type="submit">Cancelar apadrinhamento</button></form><?php else: ?>
                                    <p><strong>Padrinho/madrinha:</strong> Ainda não definido</p><span class="inst-pill">Sem
                                        apadrinhamento aprovado</span><?php endif ?>
                                <form method="post"><input type="hidden" name="csrf_token" value="<?= fluxo_e($csrf) ?>"><input
                                        type="hidden" name="acao" value="alternar_status_crianca"><input type="hidden"
                                        name="crianca_id" value="<?= (int) $crianca['id'] ?>"><input type="hidden"
                                        name="novo_status" value="<?= ((int) $crianca['status'] === 1 ? 0 : 1) ?>"><button
                                        class="button button-outline"
                                        type="submit"><?= ((int) $crianca['status'] === 1 ? 'Inativar criança' : 'Ativar criança') ?></button>
                                </form>
                            </article><?php endforeach ?>
                    </div><?php endif ?>
            </section>
            <section class="inst-section">
                <h2>Doações recebidas</h2><?php if (!$doacoes): ?>
                    <p>Ainda não há doações registradas para esta instituição.</p><?php else: ?>
                    <div class="inst-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Padrinho</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Descrição</th>
                                    <th>Status</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($doacoes as $d): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime((string) $d['data_doacao'])) ?></td>
                                        <td><?= fluxo_e((string) $d['padrinho_nome']) ?></td>
                                        <td><?= fluxo_e((string) $d['tipo']) ?></td>
                                        <td><?= $d['valor'] !== null ? 'R$ ' . number_format((float) $d['valor'], 2, ',', '.') : '—' ?></td>
                                        <td><?= fluxo_e((string) $d['descricao']) ?></td>
                                        <?php $statusDoacao = trim((string) $d['status']);
                                        $doacaoRecebida = $statusDoacao === 'Recebida';
                                        $doacaoNaoRecebida = $statusDoacao === 'Não Recebida'; ?>
                                        <td><span
                                                class="doacao-status <?= ($doacaoRecebida ? 'recebido' : ($doacaoNaoRecebida ? 'nao-recebido' : 'pendente')) ?>"><?= fluxo_e($statusDoacao) ?></span>
                                        </td>
                                        <td>
                                            <div class="doacao-actions"><?php if (!$doacaoRecebida): ?>
                                                    <form method="post"><input type="hidden" name="csrf_token"
                                                            value="<?= fluxo_e($csrf) ?>"><input type="hidden" name="solicitacao_id"
                                                            value="<?= (int) $d['id'] ?>"><input type="hidden" name="acao"
                                                            value="recebida"><button class="button button-received"
                                                            type="submit">Recebido</button></form>
                                                <?php endif ?>        <?php if (!$doacaoNaoRecebida): ?>
                                                    <form method="post"><input type="hidden" name="csrf_token"
                                                            value="<?= fluxo_e($csrf) ?>"><input type="hidden" name="solicitacao_id"
                                                            value="<?= (int) $d['id'] ?>"><input type="hidden" name="acao"
                                                            value="nao_recebida"><button class="button button-not-received"
                                                            type="submit">Não recebido</button></form><?php endif ?>
                                            </div>
                                        </td>
                                    </tr><?php endforeach ?>
                            </tbody>
                        </table>
                    </div><?php endif ?>
            </section>
            <?php if ($dados): ?>
                <section class="inst-section">
                    <h2>Dados da instituição</h2>
                    <p><strong>Responsável:</strong> <?= fluxo_e((string) $dados['responsavel']) ?> · <strong>E-mail:</strong>
                        <?= fluxo_e((string) $dados['email']) ?> · <strong>Telefone:</strong>
                        <?= fluxo_e((string) $dados['telefone']) ?></p>
                    <p><strong>Necessidades:</strong> <?= nl2br(fluxo_e((string) $dados['necessidades'])) ?></p>
                </section><?php endif ?>
        </div>
    </main>
    <script>
        (function () {
            const themeToggle = document.getElementById('themeToggle');
            const aplicarTema = () => { const escuro = localStorage.getItem('ps-theme') === 'dark'; document.body.classList.toggle('dark', escuro); if (themeToggle) themeToggle.textContent = escuro ? '☀' : '☾'; };
            aplicarTema();
            themeToggle?.addEventListener('click', () => { localStorage.setItem('ps-theme', document.body.classList.contains('dark') ? 'light' : 'dark'); aplicarTema(); });
            const checks = [...document.querySelectorAll('.filtro-crianca')];
            const cards = [...document.querySelectorAll('.card-crianca')];
            function filtrar() {
                const marcados = new Set(checks.filter(c => c.checked).map(c => c.value));
                cards.forEach(card => {
                    const statusOk = marcados.has(card.dataset.status);
                    const padrinhoOk = marcados.has(card.dataset.padrinho);
                    const sexo = card.dataset.sexo;
                    const sexoOk = (sexo === 'Masculino' && marcados.has('Masculino')) || (sexo === 'Feminino' && marcados.has('Feminino')) || (sexo === 'Outro' && (marcados.has('Masculino') || marcados.has('Feminino')));
                    card.hidden = !(statusOk && padrinhoOk && sexoOk);
                });
            }
            checks.forEach(c => c.addEventListener('change', filtrar)); filtrar();

            const formCadastro = document.getElementById('form-cadastrar-crianca');
            if (formCadastro) {
                formCadastro.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const botao = formCadastro.querySelector('button[type="submit"]');
                    const textoOriginal = botao.textContent;
                    botao.disabled = true; botao.textContent = 'Cadastrando...';
                    let aviso = document.getElementById('retorno-cadastro-crianca');
                    if (!aviso) { aviso = document.createElement('div'); aviso.id = 'retorno-cadastro-crianca'; aviso.className = 'inst-alert'; aviso.setAttribute('role', 'status'); formCadastro.before(aviso); }
                    aviso.hidden = true;
                    try {
                        const resposta = await fetch(window.location.href, { method: 'POST', body: new FormData(formCadastro), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                        const dados = await resposta.json();
                        if (!resposta.ok || !dados.success) throw new Error(dados.message || 'Não foi possível cadastrar a criança.');
                        aviso.textContent = dados.message; aviso.className = 'inst-alert'; aviso.hidden = false;
                        const lista = document.getElementById('lista-criancas');
                        const vazio = document.getElementById('sem-criancas');
                        if (vazio) vazio.remove();
                        if (lista) {
                            const card = document.createElement('article'); card.className = 'inst-card card-crianca';
                            card.dataset.status = 'ativo'; card.dataset.padrinho = 'sem-padrinho'; card.dataset.sexo = dados.child.sexo;
                            const nome = document.createElement('strong'); nome.textContent = dados.child.nome;
                            const info = document.createElement('p'); info.textContent = dados.child.idade + ' anos · ' + dados.child.sexo;
                            const padrinho = document.createElement('p'); const label = document.createElement('strong'); label.textContent = 'Padrinho/madrinha: '; padrinho.append(label, document.createTextNode('Ainda não definido'));
                            const status = document.createElement('span'); status.className = 'inst-pill'; status.textContent = 'Sem apadrinhamento aprovado';
                            card.append(nome, info, padrinho, status); lista.prepend(card);
                            if (typeof filtrar === 'function') filtrar();
                        }
                        formCadastro.reset();
                    } catch (error) {
                        aviso.textContent = error.message || 'Erro de comunicação com o servidor.'; aviso.className = 'inst-error'; aviso.hidden = false;
                    } finally { botao.disabled = false; botao.textContent = textoOriginal; }
                });
            }
        })();
    </script>
</body>

</html>