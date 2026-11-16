<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/fluxo.php';
if (empty($_SESSION['padrinho_id']) || ($_SESSION['perfil'] ?? 'padrinho') !== 'padrinho') {
    header('Location: login.php');
    exit;
}
$db = db();
$padrinhoId = (int) $_SESSION['padrinho_id'];
$erro = '';
$sucesso = '';
try {
    fluxo_garantir_tabelas($db);
} catch (Throwable $e) {
    error_log('Preparação das tabelas da área do padrinho: ' . $e->getMessage());
    $erro = 'Não foi possível preparar as tabelas de solicitações e doações. Verifique a permissão CREATE do usuário MySQL.';
}
if (!$erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!fluxo_validar_csrf())
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    else {
        $acao = (string) ($_POST['acao'] ?? '');
        try {
            if ($acao === 'solicitar') {
                $criancaId = filter_var($_POST['crianca_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$criancaId || $criancaId < 1)
                    throw new RuntimeException('Selecione uma criança válida.');
                $q = $db->prepare("SELECT a.id,a.status,a.id_instituicao,a.idade,a.sexo,a.observacoes_necessidades,u.nome AS instituicao
                    FROM apadrinhado a
                    JOIN instituicao i ON i.id=a.id_instituicao
                    JOIN usuarios u ON u.id=i.id_usuario
                    WHERE a.id=?
                      AND NOT EXISTS (
                          SELECT 1
                          FROM solicitacoes_apadrinhamento sa
                          WHERE sa.crianca_id=a.id AND sa.status='Aprovado'
                      )
                      AND NOT EXISTS (
                          SELECT 1
                          FROM apadrinhamento ap
                          WHERE ap.id_apadrinhado=a.id AND ap.status='Ativo'
                      )
                    LIMIT 1");
                $q->execute([$criancaId]);
                $crianca = $q->fetch();
                if (!$crianca || !fluxo_status_disponivel($crianca['status']))
                    throw new RuntimeException('Esta criança não está disponível para solicitação.');
                $q = $db->prepare("SELECT id FROM solicitacoes_apadrinhamento WHERE padrinho_id=? AND crianca_id=? AND status IN ('Pendente','Aprovado') LIMIT 1");
                $q->execute([$padrinhoId, $criancaId]);
                if ($q->fetch())
                    throw new RuntimeException('Você já possui uma solicitação pendente ou um apadrinhamento aprovado para esta criança.');
                $q = $db->prepare("INSERT INTO solicitacoes_apadrinhamento
                    (padrinho_id,crianca_id,instituicao_id,observacoes,status)
                    VALUES (?,?,?,?,'Pendente')");
                $q->execute([
                    $padrinhoId,
                    $criancaId,
                    (int) $crianca['id_instituicao'],
                    (string) $crianca['observacoes_necessidades']
                ]);
                $sucesso = 'Solicitação enviada! Ela ficará aguardando a análise da instituição responsável.';
            }
        } catch (Throwable $e) {
            $erro = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível concluir a operação. Confira se a atualização SQL foi executada.';
            error_log('Fluxo padrinho: ' . $e->getMessage());
        }
    }
}
try {
    if ($erro !== '')
        throw new RuntimeException('Preparação das tabelas auxiliares falhou.');
    $sql = "SELECT a.id,a.idade,a.sexo,a.observacoes_necessidades,a.status,a.id_instituicao,u.nome AS instituicao
            FROM apadrinhado a
            JOIN instituicao i ON i.id=a.id_instituicao
            JOIN usuarios u ON u.id=i.id_usuario
            WHERE LOWER(TRIM(CAST(a.status AS CHAR))) IN ('1','ativo','ativa','disponivel','disponível')
              AND NOT EXISTS (
                  SELECT 1
                  FROM solicitacoes_apadrinhamento sa
                  WHERE sa.crianca_id=a.id AND sa.status='Aprovado'
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM apadrinhamento ap
                  WHERE ap.id_apadrinhado=a.id AND ap.status='Ativo'
              )
            ORDER BY a.id DESC";
    $criancas = $db->query($sql)->fetchAll();
    $q = $db->prepare("SELECT s.id,s.status,s.criado_em,a.id AS crianca_id,CASE WHEN s.status='Aprovado' THEN a.nome ELSE NULL END AS nome_apadrinhado,a.idade,a.sexo,u.nome AS instituicao
        FROM solicitacoes_apadrinhamento s JOIN apadrinhado a ON a.id=s.crianca_id
        JOIN instituicao i ON i.id=s.instituicao_id JOIN usuarios u ON u.id=i.id_usuario
        WHERE s.padrinho_id=? ORDER BY s.criado_em DESC");
    $q->execute([$padrinhoId]);
    $solicitacoes = $q->fetchAll();
    $apadrinhados = array_values(array_filter($solicitacoes, static fn($s) => $s['status'] === 'Aprovado'));
} catch (Throwable $e) {
    error_log('Consulta área padrinho: ' . $e->getMessage());
    $criancas = [];
    $solicitacoes = [];
    $apadrinhados = [];
    $erro = $erro ?: 'Não foi possível carregar os dados. Confira as colunas das tabelas-base e consulte o log do PHP para identificar a coluna ou tabela exata.';
}
$csrf = fluxo_csrf();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Minha conta — Padrinho Solidário</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .account-page {
            min-height: 100vh;
            padding: 34px 0 70px;
            background: var(--bg)
        }

        .account-wrap {
            width: min(1120px, calc(100% - 36px));
            margin: auto
        }

        .account-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 30px
        }

        .account-section {
            margin: 26px 0;
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow)
        }

        .account-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px
        }

        .account-card {
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--bg)
        }

        .account-card h3 {
            margin: 8px 0
        }

        .account-card p {
            color: var(--muted)
        }

        .account-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 30px;
            background: #fff1d6;
            color: #855500;
            font-size: 12px;
            font-weight: 700
        }

        .account-status.ok {
            background: #e6f6ec;
            color: #246b48
        }

        .account-alert {
            padding: 14px;
            margin: 16px 0;
            border-radius: 10px;
            background: #edf8f1;
            color: #246b48
        }

        .account-error {
            padding: 14px;
            margin: 16px 0;
            border-radius: 10px;
            background: #fff0ed;
            color: #963e2d
        }

        .account-form {
            display: grid;
            gap: 10px;
            margin-top: 14px
        }

        .account-form select {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--surface);
            color: var(--text)
        }

        .account-empty {
            color: var(--muted)
        }

        @media(max-width:600px) {
            .account-head {
                align-items: flex-start;
                flex-direction: column
            }
        }
    </style>
</head>

<body>
    <main class="account-page">
        <div class="account-wrap">
            <header class="account-head"><a class="brand" href="index.php"><span
                        class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
                <div><strong>Olá, <?= fluxo_e((string) $_SESSION['padrinho_nome']) ?></strong> &nbsp; <a
                        class="button button-outline" href="sair.php">Sair</a></div>
            </header>
            <h1>Minha área de padrinho</h1>
            <p>Acompanhe suas solicitações e conheça crianças vinculadas às instituições.</p>
            <?php if ($sucesso): ?>
                <div class="account-alert" role="status"><?= fluxo_e($sucesso) ?></div><?php endif ?><?php if ($erro): ?>
                <div class="account-error" role="alert"><?= fluxo_e($erro) ?></div><?php endif ?>
            <section class="account-section">
                <h2>Meus apadrinhados</h2><?php if (!$apadrinhados): ?>
                    <p class="account-empty">Quando uma instituição aprovar sua solicitação, a criança aparecerá aqui.</p>
                <?php else: ?>
                    <div class="account-grid"><?php foreach ($apadrinhados as $item): ?>
                            <article class="account-card"><span class="account-status ok">Apadrinhamento aprovado</span>
                                <h3><?= fluxo_e((string) $item['nome_apadrinhado']) ?> — <?= (int) $item['idade'] ?> anos</h3>
                                <p><?= fluxo_e((string) $item['sexo']) ?> · <?= fluxo_e((string) $item['instituicao']) ?></p><a
                                    class="button" href="doacao.php?solicitacao_id=<?= (int) $item['id'] ?>">Doar</a>
                            </article><?php endforeach ?>
                    </div><?php endif ?>
            </section>
            <section class="account-section">
                <h2>Minhas solicitações</h2><?php if (!$solicitacoes): ?>
                    <p class="account-empty">Você ainda não enviou solicitações.</p><?php else: ?>
                    <div class="account-grid"><?php foreach ($solicitacoes as $item): ?>
                            <article class="account-card"><span
                                    class="account-status <?= $item['status'] === 'Aprovado' ? 'ok' : '' ?>"><?= fluxo_e((string) $item['status']) ?></span>
                                <h3><?php if ($item['status'] === 'Aprovado'): ?><?= fluxo_e((string) $item['nome_apadrinhado']) ?><?php else: ?>Criança
                                        #<?= (int) $item['crianca_id'] ?><?php endif ?> — <?= (int) $item['idade'] ?> anos</h3>
                                <p><?= fluxo_e((string) $item['instituicao']) ?></p><small>Enviada em
                                    <?= date('d/m/Y H:i', strtotime((string) $item['criado_em'])) ?></small>
                            </article><?php endforeach ?>
                    </div><?php endif ?>
            </section>
            <section class="account-section">
                <h2>Crianças disponíveis para apadrinhamento</h2><?php if (!$criancas): ?>
                    <p class="account-empty">Nenhuma criança disponível no momento.</p><?php else: ?>
                    <div class="account-grid"><?php foreach ($criancas as $c): ?>
                            <article class="account-card"><span class="account-status ok">Disponível</span>
                                <h3><?= (int) $c['idade'] ?> anos</h3>
                                <p><?= fluxo_e((string) $c['sexo']) ?></p>
                                <p><strong>Instituição:</strong> <?= fluxo_e((string) $c['instituicao']) ?></p>
                                <p><?= fluxo_e((string) ($c['observacoes_necessidades'] ?? '')) ?></p>
                                <form class="account-form" method="post"><input type="hidden" name="csrf_token"
                                        value="<?= fluxo_e($csrf) ?>"><input type="hidden" name="acao" value="solicitar"><input
                                        type="hidden" name="crianca_id" value="<?= (int) $c['id'] ?>"><button class="button"
                                        type="submit">♡ Solicitar apadrinhamento</button></form>
                            </article><?php endforeach ?>
                    </div><?php endif ?>
            </section>
        </div>
    </main>
</body>

</html>