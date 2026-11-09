<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['padrinho_nome']) || empty($_SESSION['padrinho_id'])) {
    header('Location: login.php');
    exit;
}
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
$nome = (string)$_SESSION['padrinho_nome'];

// Registros fictícios para demonstração. Não representam crianças reais.
$criancas = [
    [
        'idade' => 7, 'sexo' => 'Masculino', 'status' => 'Ativo',
        'instituicao' => 'Lar Pequeno Príncipe', 'email_instituicao' => 'contato@larpequenoprincipe.org.br',
        'observacao' => 'Necessita de apoio com materiais escolares e acompanhamento educacional.'
    ],
    [
        'idade' => 9, 'sexo' => 'Feminino', 'status' => 'Ativo',
        'instituicao' => 'Abrigo Esperança', 'email_instituicao' => 'contato@abrigoesperanca.org.br',
        'observacao' => 'Precisa de roupas infantis, calçados e incentivo às atividades de leitura.'
    ],
    [
        'idade' => 5, 'sexo' => 'Masculino', 'status' => 'Não ativo',
        'instituicao' => 'Casa Acolhedora Sementes', 'email_instituicao' => 'contato@casasementes.org.br',
        'observacao' => 'Aguardando disponibilidade para novo apadrinhamento. Necessita de itens de higiene.'
    ],
    [
        'idade' => 11, 'sexo' => 'Feminino', 'status' => 'Ativo',
        'instituicao' => 'Lar Pequeno Príncipe', 'email_instituicao' => 'contato@larpequenoprincipe.org.br',
        'observacao' => 'Necessita de materiais para atividades artísticas e acompanhamento escolar.'
    ],
];
$requestMessage = '';
$requestError = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_sponsorship') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $childIndex = filter_var($_POST['child_index'] ?? null, FILTER_VALIDATE_INT);
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $requestError = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } elseif ($childIndex === false || !isset($criancas[$childIndex])) {
        $requestError = 'Não foi possível identificar a criança selecionada.';
    } elseif ($criancas[$childIndex]['status'] !== 'Ativo') {
        $requestError = 'Este cadastro não está ativo para receber solicitações no momento.';
    } else {
        try {
            require_once __DIR__ . '/db.php';
            $db = db();
            $stmt = $db->prepare("INSERT INTO solicitacoes_apadrinhamento
                (padrinho_id,padrinho_nome,crianca_id,idade_crianca,sexo_crianca,instituicao,observacoes)
                VALUES (:padrinho_id,:padrinho_nome,:crianca_id,:idade,:sexo,:instituicao,:observacoes)");
            $child = $criancas[$childIndex];
            $stmt->execute([
                ':padrinho_id'=>(int)$_SESSION['padrinho_id'],
                ':padrinho_nome'=>(string)$_SESSION['padrinho_nome'],
                ':crianca_id'=>$childIndex,
                ':idade'=>$child['idade'],
                ':sexo'=>$child['sexo'],
                ':instituicao'=>$child['instituicao'],
                ':observacoes'=>$child['observacao']
            ]);
            $_SESSION['flash_solicitacao'] = 'Sua solicitação foi enviada à instituição ' . $child['instituicao'] . '. A equipe entrará em contato para conversar sobre os próximos passos. E-mail da instituição: ' . $child['email_instituicao'];
            header('Location: boas-vindas.php');
            exit;
        } catch (Throwable $ex) {
            $requestError = 'Não foi possível registrar a solicitação. Verifique a conexão com o banco MySQL.';
        }
    }
}
if (!empty($_SESSION['flash_solicitacao'])) {
    $requestMessage = (string)$_SESSION['flash_solicitacao'];
    unset($_SESSION['flash_solicitacao']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#b95f3e">
<title>Boas-vindas — Padrinho Solidário</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.welcome-page{min-height:100vh;padding:34px 0 70px;background:var(--bg)}
.welcome-container{width:min(1120px,calc(100% - 40px));margin:auto}
.welcome-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:42px}
.welcome-header .brand{font-size:15px}
.welcome-intro{padding:clamp(26px,5vw,48px);border-radius:22px;background:var(--gradient);color:white;margin-bottom:36px;box-shadow:var(--shadow)}
.welcome-intro .eyebrow{color:white;background:#ffffff25}
.welcome-intro h1{font:800 clamp(28px,4vw,42px) Manrope,sans-serif;margin:14px 0 8px}
.welcome-intro p{margin:0;max-width:700px;color:#fff}
.children-heading{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:20px}
.children-heading h2{font:800 25px Manrope,sans-serif;margin:0 0 5px}
.children-heading p{color:var(--muted);margin:0;font-size:14px}
.children-count{white-space:nowrap;background:var(--warm);color:var(--primary-dark);border-radius:999px;padding:8px 13px;font-size:12px;font-weight:700}
.children-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.child-card{background:var(--surface);border:1px solid var(--border);border-radius:17px;padding:23px;box-shadow:var(--shadow);transition:transform .2s,border-color .2s,box-shadow .2s}
.child-card:hover{transform:translateY(-3px);border-color:var(--primary);box-shadow:0 16px 38px rgba(73,45,30,.13)}
.child-open{display:block;width:100%;text-align:left;border:0;background:transparent;color:inherit;padding:0;cursor:pointer;font:inherit}
.child-open:focus-visible{outline:3px solid #bd674655;outline-offset:5px;border-radius:8px}
.child-open-hint{display:block;margin-top:16px;padding-top:12px;border-top:1px solid var(--border);font-size:12px;font-weight:700;color:var(--primary-dark)}
.child-modal{width:min(540px,calc(100% - 28px));max-height:85vh;overflow:auto;border:1px solid var(--border);border-radius:20px;padding:0;background:var(--surface);color:var(--text);box-shadow:0 24px 80px #21160f55}
.child-modal::backdrop{background:rgba(35,25,20,.58);backdrop-filter:blur(3px)}
.modal-content{padding:clamp(22px,4vw,32px)}
.modal-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px}
.modal-close{border:0;border-radius:10px;background:var(--warm);color:var(--text);width:38px;height:38px;font-size:22px;cursor:pointer}
.modal-title{font:800 27px Manrope,sans-serif;margin:0 0 5px}
.modal-subtitle{font-size:13px;color:var(--muted);margin:0}
.modal-details{margin-top:20px}
.modal-actions{display:flex;gap:10px;margin-top:26px;flex-wrap:wrap}
.modal-actions .button{width:100%;text-align:center}
.button-disabled{opacity:.48;pointer-events:none;filter:grayscale(.5)}
.modal-disclaimer{font-size:12px;line-height:1.5;color:var(--muted);margin:18px 0 0}

.child-card-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
.child-symbol{width:44px;height:44px;border-radius:14px;background:var(--warm);display:grid;place-items:center;color:var(--primary);font-size:20px}
.child-status{padding:6px 10px;border-radius:999px;font-size:11px;font-weight:700}
.child-status.active{background:#e6f4ec;color:#28734e}
.child-status.inactive{background:#f4eee7;color:#806b58}
.child-age{font:800 23px Manrope,sans-serif;margin:0 0 4px}
.child-sex{font-size:13px;color:var(--muted);margin:0 0 18px}
.child-detail{padding-top:13px;border-top:1px solid var(--border);margin-top:12px}
.child-detail strong{display:block;font-size:12px;margin-bottom:4px}
.child-detail p{font-size:13px;color:var(--muted);margin:0;line-height:1.55}
.welcome-note{margin-top:24px;padding:17px 19px;border:1px solid var(--border);border-radius:13px;background:var(--surface);color:var(--muted);font-size:13px}
.welcome-footer{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:30px}
@media(max-width:700px){.children-grid{grid-template-columns:1fr}.children-heading{align-items:flex-start;flex-direction:column}.welcome-header{align-items:flex-start}.welcome-footer{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<main class="welcome-page">
<div class="welcome-container">
<header class="welcome-header">
<a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
<div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap"><a class="back-link" href="doacao.php">Fazer doação</a><a class="back-link" href="index.php">Voltar ao início</a><a class="back-link" href="sair.php">Sair</a></div>
</header>

<section class="welcome-intro">
<span class="eyebrow">Cadastro concluído</span>
<h1>Boas-vindas, <?= e(explode(' ', trim($nome))[0]) ?>!</h1>
<p>Seu cadastro foi recebido. Conheça abaixo algumas crianças vinculadas às instituições parceiras e suas necessidades atuais.</p>
</section>
<?php if ($requestMessage !== ''): ?><div class="welcome-note" role="status" style="border-color:#9acdb0;background:#edf8f1;color:#246b48;margin-bottom:24px"><strong>Solicitação enviada!</strong><br><?= e($requestMessage) ?></div><?php endif; ?>
<?php if ($requestError !== ''): ?><div class="welcome-note" role="alert" style="border-color:#e5b5a8;color:#963e2d;margin-bottom:24px"><?= e($requestError) ?></div><?php endif; ?>

<section aria-labelledby="children-title">
<div class="children-heading">
<div><h2 id="children-title">Crianças disponíveis</h2><p>Informações básicas para conhecer as necessidades de cada criança.</p></div>
<span class="children-count"><?= count($criancas) ?> cadastros demonstrativos</span>
</div>
<div class="children-grid">
<?php foreach ($criancas as $indice => $crianca): ?>
<article class="child-card">
<button class="child-open" type="button" data-child-index="<?= (int)$indice ?>" aria-label="Ver informações da criança, <?= (int)$crianca['idade'] ?> anos">
  <div class="child-card-top">
    <span class="child-symbol" aria-hidden="true">♡</span>
    <span class="child-status <?= $crianca['status'] === 'Ativo' ? 'active' : 'inactive' ?>"><?= e($crianca['status']) ?></span>
  </div>
  <h3 class="child-age"><?= (int)$crianca['idade'] ?> anos</h3>
  <p class="child-sex"><?= e($crianca['sexo']) ?></p>
  <div class="child-detail"><strong>Instituição</strong><p><?= e($crianca['instituicao']) ?></p></div>
  <div class="child-detail"><strong>Observações / necessidades</strong><p><?= e($crianca['observacao']) ?></p></div>
  <span class="child-open-hint">Ver informações e opções →</span>
</button>
</article>
<?php endforeach; ?>
</div>
<p class="welcome-note"><strong>Importante:</strong> esta página apresenta dados fictícios para demonstração. O status “Ativo” indica um cadastro ativo na listagem, não confirma que a criança esteja disponível para apadrinhamento. A disponibilidade real deve ser validada pela instituição responsável.</p>
</section>

<footer class="welcome-footer">
<span class="privacy-note">Padrinho Solidário · Conectando pessoas e construindo histórias.</span>
<a class="button button-outline" href="index.php">Conhecer o projeto</a>
</footer>
</div>
</main>

<dialog class="child-modal" id="childModal" aria-labelledby="modalChildAge">
  <div class="modal-content">
    <div class="modal-header">
      <span class="child-symbol" aria-hidden="true">♡</span>
      <button class="modal-close" type="button" id="closeChildModal" aria-label="Fechar janela">×</button>
    </div>
    <h2 class="modal-title" id="modalChildAge">Informações da criança</h2>
    <p class="modal-subtitle" id="modalChildStatus"></p>
    <div class="modal-details">
      <div class="child-detail"><strong>Sexo</strong><p id="modalChildSex"></p></div>
      <div class="child-detail"><strong>Instituição</strong><p id="modalChildInstitution"></p></div>
      <div class="child-detail"><strong>Observações / necessidades</strong><p id="modalChildNeeds"></p></div>
    </div>
    <div class="modal-actions">
      <form method="post" action="boas-vindas.php" id="sponsorForm" style="flex:1;min-width:150px">
      <input type="hidden" name="action" value="request_sponsorship">
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="child_index" id="sponsorChildIndex" value="">
      <button class="button" id="sponsorAction" type="submit" style="width:100%">♡ Apadrinhar</button>
    </form>
    </div>
    <p class="modal-disclaimer" id="modalActionNote">Estas opções iniciam um contato com a equipe do projeto. A solicitação não confirma apadrinhamento nem adoção; a instituição responsável deverá orientar e avaliar os próximos passos.</p>
  </div>
</dialog>
<script>
const childrenData = <?= json_encode(array_map(static function (array $child): array {
    return [
        'idade' => (int)$child['idade'],
        'sexo' => $child['sexo'],
        'status' => $child['status'],
        'instituicao' => $child['instituicao'],
        'observacao' => $child['observacao']
    ];
}, $criancas), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

const childModal = document.getElementById('childModal');
const closeChildModal = document.getElementById('closeChildModal');
const sponsorAction = document.getElementById('sponsorAction');
const sponsorForm = document.getElementById('sponsorForm');
const sponsorChildIndex = document.getElementById('sponsorChildIndex');

document.querySelectorAll('.child-open').forEach((button) => {
  button.addEventListener('click', () => {
    const child = childrenData[Number(button.dataset.childIndex)];
    if (!child) return;

    document.getElementById('modalChildAge').textContent = child.idade + ' anos';
    document.getElementById('modalChildStatus').textContent = 'Status: ' + child.status;
    document.getElementById('modalChildSex').textContent = child.sexo;
    document.getElementById('modalChildInstitution').textContent = child.instituicao;
    document.getElementById('modalChildNeeds').textContent = child.observacao;

    const available = child.status === 'Ativo';
    sponsorAction.disabled = !available;
    sponsorAction.classList.toggle('button-disabled', !available);
    sponsorChildIndex.value = String(Number(button.dataset.childIndex));

    document.getElementById('modalActionNote').textContent = available
      ? 'Ao solicitar o apadrinhamento, o pedido será registrado para análise da instituição. A equipe poderá aceitar ou recusar e entrará em contato para conversar sobre os próximos passos.'
      : 'Este cadastro está marcado como não ativo. As opções de contato estão indisponíveis no momento.';
    childModal.showModal();
  });
});

<?php if ($requestMessage !== ''): ?>
alert(<?= json_encode($requestMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
<?php endif; ?>

closeChildModal.addEventListener('click', () => childModal.close());
childModal.addEventListener('click', (event) => {
  if (event.target === childModal) childModal.close();
});
</script>

</body>
</html>
