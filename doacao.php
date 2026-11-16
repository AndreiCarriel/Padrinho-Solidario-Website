<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/fluxo.php';
if (empty($_SESSION['padrinho_id']) || ($_SESSION['perfil'] ?? '') !== 'padrinho') {
    header('Location: login.php?redirect=doacao.php');
    exit;
}
$db = db();
$padrinhoId = (int) $_SESSION['padrinho_id'];
$erro = '';
$sucesso = '';
try {
    fluxo_garantir_tabelas($db);
} catch (Throwable $e) {
    error_log('Preparação das tabelas de doações: ' . $e->getMessage());
    $erro = 'Não foi possível preparar a tabela de doações. Verifique a permissão CREATE do usuário MySQL.';
}
$vinculos = [];
if (!$erro) {
    try {
        $q = $db->prepare("SELECT s.id AS solicitacao_id,s.crianca_id,s.instituicao_id,a.nome AS nome_apadrinhado,a.idade,u.nome AS instituicao FROM solicitacoes_apadrinhamento s JOIN apadrinhado a ON a.id=s.crianca_id JOIN instituicao i ON i.id=s.instituicao_id JOIN usuarios u ON u.id=i.id_usuario WHERE s.padrinho_id=? AND s.status='Aprovado' ORDER BY u.nome,a.idade");
        $q->execute([$padrinhoId]);
        $vinculos = $q->fetchAll();
    } catch (Throwable $e) {
        error_log('Consulta de vínculos para doação: ' . $e->getMessage());
        $erro = 'Não foi possível carregar seus apadrinhamentos. Consulte o log do PHP para identificar a coluna ou tabela ausente.';
    }
}
if (!$erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!fluxo_validar_csrf())
        $erro = 'Sua sessão expirou. Atualize a página.';
    else
        try {
            $solicitacaoId = filter_var($_POST['solicitacao_id'] ?? null, FILTER_VALIDATE_INT);
            $tipo = (string) ($_POST['tipo'] ?? '');
            $descricao = trim((string) ($_POST['descricao'] ?? ''));
            $valorRaw = str_replace(',', '.', trim((string) ($_POST['valor'] ?? '')));
            $tipos = ['Dinheiro', 'Alimentos', 'Roupas', 'Higiene', 'Material escolar', 'Outros'];
            if (!$solicitacaoId)
                throw new RuntimeException('Selecione um apadrinhamento aprovado.');
            if (!in_array($tipo, $tipos, true))
                throw new RuntimeException('Selecione um tipo de doação válido.');
            if ($descricao === '')
                throw new RuntimeException('Descreva a doação.');
            $valor = null;
            if ($tipo === 'Dinheiro') {
                if ($valorRaw === '' || !is_numeric($valorRaw) || (float) $valorRaw <= 0)
                    throw new RuntimeException('Informe um valor válido para a doação em dinheiro.');
                $valor = number_format((float) $valorRaw, 2, '.', '');
            }
            $q = $db->prepare("SELECT crianca_id,instituicao_id FROM solicitacoes_apadrinhamento WHERE id=? AND padrinho_id=? AND status='Aprovado' LIMIT 1");
            $q->execute([$solicitacaoId, $padrinhoId]);
            $v = $q->fetch();
            if (!$v)
                throw new RuntimeException('O vínculo selecionado não está aprovado ou não pertence à sua conta.');
            $q = $db->prepare("INSERT INTO doacoes
   (padrinho_id,id_apadrinhado,instituicao_id,tipo,valor,descricao,data_doacao,status)
   VALUES (?,?,?,?,?,?,NOW(),'Informada')");
            $q->execute([
                $padrinhoId,
                (int) $v['crianca_id'],
                (int) $v['instituicao_id'],
                $tipo,
                $valor,
                $descricao
            ]);
            $sucesso = 'Doação registrada e enviada para a área de doações da instituição.';
        } catch (Throwable $e) {
            $erro = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível registrar a doação. Execute a atualização SQL e tente novamente.';
            error_log('Registro doação: ' . $e->getMessage());
        }
}
$csrf = fluxo_csrf();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fazer doação — Padrinho Solidário</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .donation-page {
            min-height: 100vh;
            padding: 38px 18px;
            background: var(--bg)
        }

        .donation-wrap {
            max-width: 720px;
            margin: auto
        }

        .donation-card {
            margin: 30px 0;
            padding: clamp(22px, 4vw, 36px);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow)
        }

        .donation-field {
            display: grid;
            gap: 7px;
            margin: 17px 0
        }

        .donation-field label {
            font-weight: 700;
            font-size: 13px
        }

        .donation-field input,
        .donation-field select,
        .donation-field textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            font: inherit
        }

        .donation-field textarea {
            min-height: 110px
        }

        .donation-alert {
            padding: 13px;
            border-radius: 9px;
            margin: 15px 0;
            background: #edf8f1;
            color: #246b48
        }

        .donation-error {
            padding: 13px;
            border-radius: 9px;
            margin: 15px 0;
            background: #fff0ed;
            color: #963e2d
        }
    </style>
</head>

<body>
    <main class="donation-page">
        <div class="donation-wrap"><a class="brand" href="boas-vindas.php"><span
                    class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
            <section class="donation-card"><span class="eyebrow">Apoio solidário</span>
                <h1>Registrar uma doação</h1>
                <p>As doações são destinadas à instituição responsável pelo apadrinhamento aprovado. Este registro não
                    processa pagamentos nem comprova transferência financeira.</p>
                <?php if ($sucesso): ?>
                    <div class="donation-alert" role="status"><?= fluxo_e($sucesso) ?></div><?php endif ?><?php if ($erro): ?>
                    <div class="donation-error" role="alert"><?= fluxo_e($erro) ?></div><?php endif ?>
                <?php if (!$vinculos): ?>
                    <p>Você ainda não possui apadrinhamentos aprovados. O botão de doação ficará disponível depois que a
                        instituição aceitar uma solicitação.</p><?php else: ?>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= fluxo_e($csrf) ?>">
                        <div class="donation-field"><label for="solicitacao_id">Criança e instituição</label><select
                                id="solicitacao_id" name="solicitacao_id" required>
                                <option value="">Selecione</option><?php foreach ($vinculos as $v): ?>
                                    <option value="<?= (int) $v['solicitacao_id'] ?>"><?= fluxo_e((string) $v['nome_apadrinhado']) ?>
                                        — <?= (int) $v['idade'] ?> anos · <?= fluxo_e((string) $v['instituicao']) ?></option>
                                <?php endforeach ?>
                            </select></div>
                        <div class="donation-field"><label for="tipo">Tipo de doação</label><select id="tipo" name="tipo"
                                required>
                                <option value="">Selecione</option>
                                <?php foreach (['Dinheiro', 'Alimentos', 'Roupas', 'Higiene', 'Material escolar', 'Outros'] as $t): ?>
                                    <option value="<?= fluxo_e($t) ?>"><?= fluxo_e($t) ?></option><?php endforeach ?>
                            </select></div>
                        <div class="donation-field" id="valor-field"><label for="valor">Valor (R$), para doação em
                                dinheiro</label><input id="valor" name="valor" type="number" min="0.01" step="0.01"></div>
                        <div class="donation-field"><label for="descricao">Descrição da doação</label><textarea
                                id="descricao" name="descricao" maxlength="2000" required
                                placeholder="Informe os itens, quantidade ou detalhes da contribuição"></textarea></div>
                        <button class="button" type="submit">Enviar registro de doação</button>
                    </form><?php endif ?>
                <p style="margin-top:20px"><a href="boas-vindas.php">← Voltar para minha conta</a></p>
            </section>
        </div>
    </main>
    <script>
        const tipo = document.getElementById('tipo'); const valor = document.getElementById('valor'); function atualizaValor() { if (!tipo) return; const dinheiro = tipo.value === 'Dinheiro'; valor.required = dinheiro; valor.disabled = !dinheiro; } if (tipo) { tipo.addEventListener('change', atualizaValor); atualizaValor(); }
    </script>
</body>

</html>