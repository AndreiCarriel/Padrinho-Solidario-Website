<?php
declare(strict_types=1);
session_start();
if (empty($_SESSION['padrinho_id'])) {
    header('Location: login.php?redirect=doacao.php');
    exit;
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
require_once __DIR__ . '/db.php';
$instituicoes = db()->query("SELECT nome_instituicao FROM instituicoes WHERE status <> 'Rejeitada' ORDER BY nome_instituicao")->fetchAll(PDO::FETCH_COLUMN);
$tipos = ['Dinheiro','Alimentos','Roupas','Brinquedos','Higiene','Outros'];
$errors = [];
$success = false;
$values = ['instituicao'=>'','tipo'=>'','valor'=>'','descricao'=>''];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) $errors[] = 'Sessão expirada. Atualize a página.';
    if (!in_array($values['instituicao'], $instituicoes, true)) $errors[] = 'Selecione uma instituição válida.';
    if (!in_array($values['tipo'], $tipos, true)) $errors[] = 'Selecione um tipo de doação válido.';
    if ($values['tipo'] === 'Dinheiro') {
        $valor = str_replace(',', '.', $values['valor']);
        if ($valor === '' || !is_numeric($valor) || (float)$valor <= 0) $errors[] = 'Informe um valor financeiro maior que zero.';
    } else {
        $valor = null;
    }
    if ($values['descricao'] === '') $errors[] = 'Descreva brevemente a doação.';
    if (!$errors) {
        try {
            require_once __DIR__ . '/db.php';
            $db = db();
            $stmt = $db->prepare("INSERT INTO doacoes (padrinho_id,padrinho_nome,instituicao,tipo,valor,descricao,data_doacao)
                VALUES (:id,:nome,:instituicao,:tipo,:valor,:descricao,:data)");
            $stmt->execute([
                ':id'=>(int)$_SESSION['padrinho_id'],
                ':nome'=>(string)$_SESSION['padrinho_nome'],
                ':instituicao'=>$values['instituicao'],
                ':tipo'=>$values['tipo'],
                ':valor'=>$valor === null ? null : (float)$valor,
                ':descricao'=>$values['descricao'],
                ':data'=>date('Y-m-d H:i:s')
            ]);
            $_SESSION['flash_doacao'] = 'Sua doação foi registrada com sucesso. Obrigado por contribuir!';
            header('Location: doacao.php');
            exit;
        } catch (Throwable $ex) {
            $errors[] = 'Não foi possível registrar a doação. Verifique a conexão MySQL e a extensão PDO_MYSQL.';
        }
    }
}
if (!empty($_SESSION['flash_doacao'])) { $success = true; unset($_SESSION['flash_doacao']); }
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar doação — Padrinho Solidário</title><link rel="stylesheet" href="assets/css/style.css">
<style>
.donation-page{min-height:100vh;padding:38px 20px 70px;background:var(--bg)}
.donation-wrap{width:min(720px,100%);margin:auto}
.donation-card{margin-top:26px;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:clamp(22px,5vw,40px);box-shadow:var(--shadow)}
.donation-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.donation-field{display:flex;flex-direction:column;gap:7px}
.donation-field.full{grid-column:1/-1}
.donation-field label{font-size:13px;font-weight:700}
.donation-field input,.donation-field select,.donation-field textarea{width:100%;padding:12px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font:inherit}
.donation-field textarea{min-height:105px;resize:vertical}
.donation-field [hidden]{display:none}
.donation-submit{width:100%;margin-top:22px;cursor:pointer}
.donation-alert{padding:14px;border-radius:11px;margin:16px 0;font-size:14px}
.donation-alert.error{background:#fff0ed;color:#963e2d}.donation-alert.ok{background:#eaf6ef;color:#216747}
@media(max-width:600px){.donation-grid{grid-template-columns:1fr}.donation-field.full{grid-column:auto}}
</style></head><body><main class="donation-page"><div class="donation-wrap">
<a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
<section class="donation-card"><span class="eyebrow">Faça a diferença</span><h1 style="font:800 30px Manrope,sans-serif;margin:12px 0 6px">Registrar uma doação</h1>
<p style="color:var(--muted);font-size:14px">Olá, <?=e((string)$_SESSION['padrinho_nome'])?>. Informe os detalhes da sua contribuição.</p>
<?php if($success): ?><div class="donation-alert ok" role="status"><strong>Doação registrada!</strong><br>Sua contribuição foi salva no sistema. Obrigado pela solidariedade.</div><?php endif; ?>
<?php if($errors): ?><div class="donation-alert error" role="alert"><strong>Verifique os campos:</strong><ul><?php foreach($errors as $error): ?><li><?=e($error)?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="doacao.php">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<div class="donation-grid">
<div class="donation-field full"><label for="instituicao">Instituição *</label><select id="instituicao" name="instituicao" required><option value="">Selecione uma instituição</option><?php foreach($instituicoes as $item): ?><option value="<?=e($item)?>" <?= $values['instituicao']===$item?'selected':'' ?>><?=e($item)?></option><?php endforeach; ?></select></div>
<div class="donation-field"><label for="tipo">Tipo de doação *</label><select id="tipo" name="tipo" required><option value="">Selecione</option><?php foreach($tipos as $tipo): ?><option value="<?=e($tipo)?>" <?= $values['tipo']===$tipo?'selected':'' ?>><?=e($tipo)?></option><?php endforeach; ?></select></div>
<div class="donation-field" id="amountField" <?= $values['tipo']==='Dinheiro'?'':'hidden' ?>><label for="valor">Valor (R$) *</label><input type="number" id="valor" name="valor" min="0.01" step="0.01" value="<?=e($values['valor'])?>" placeholder="0,00"></div>
<div class="donation-field full"><label for="descricao">Descrição da doação *</label><textarea id="descricao" name="descricao" required placeholder="Descreva a doação, quantidade ou outras informações..."><?=e($values['descricao'])?></textarea></div>
</div>
<button class="button donation-submit" type="submit">Registrar doação</button>
</form><a href="boas-vindas.php" style="display:inline-block;margin-top:18px;color:var(--primary-dark);font-weight:700;font-size:13px">Voltar à área do padrinho</a>
</section></div></main>
<script>
const typeSelect=document.getElementById('tipo');
const amountField=document.getElementById('amountField');
const amountInput=document.getElementById('valor');
function updateAmount(){const money=typeSelect.value==='Dinheiro';amountField.hidden=!money;amountInput.required=money;if(!money)amountInput.value='';}
typeSelect.addEventListener('change',updateAmount);updateAmount();
</script></body></html>
