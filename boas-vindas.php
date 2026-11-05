<?php
declare(strict_types=1);
session_start();

if (empty($_SESSION['padrinho_nome'])) {
    header('Location: cadastro.php');
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
        'instituicao' => 'Lar Pequeno Príncipe',
        'observacao' => 'Necessita de apoio com materiais escolares e acompanhamento educacional.'
    ],
    [
        'idade' => 9, 'sexo' => 'Feminino', 'status' => 'Ativo',
        'instituicao' => 'Abrigo Esperança',
        'observacao' => 'Precisa de roupas infantis, calçados e incentivo às atividades de leitura.'
    ],
    [
        'idade' => 5, 'sexo' => 'Masculino', 'status' => 'Não ativo',
        'instituicao' => 'Casa Acolhedora Sementes',
        'observacao' => 'Aguardando disponibilidade para novo apadrinhamento. Necessita de itens de higiene.'
    ],
    [
        'idade' => 11, 'sexo' => 'Feminino', 'status' => 'Ativo',
        'instituicao' => 'Lar Pequeno Príncipe',
        'observacao' => 'Necessita de materiais para atividades artísticas e acompanhamento escolar.'
    ],
];
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
.child-card{background:var(--surface);border:1px solid var(--border);border-radius:17px;padding:23px;box-shadow:var(--shadow)}
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
<div style="display:flex;gap:16px;align-items:center"><a class="back-link" href="index.php">Voltar ao início</a><a class="back-link" href="sair.php">Sair</a></div>
</header>

<section class="welcome-intro">
<span class="eyebrow">Cadastro concluído</span>
<h1>Boas-vindas, <?= e(explode(' ', trim($nome))[0]) ?>!</h1>
<p>Seu cadastro foi recebido. Conheça abaixo algumas crianças vinculadas às instituições parceiras e suas necessidades atuais.</p>
</section>

<section aria-labelledby="children-title">
<div class="children-heading">
<div><h2 id="children-title">Crianças disponíveis</h2><p>Informações básicas para conhecer as necessidades de cada criança.</p></div>
<span class="children-count"><?= count($criancas) ?> cadastros demonstrativos</span>
</div>
<div class="children-grid">
<?php foreach ($criancas as $crianca): ?>
<article class="child-card">
<div class="child-card-top">
<span class="child-symbol" aria-hidden="true">♡</span>
<span class="child-status <?= $crianca['status'] === 'Ativo' ? 'active' : 'inactive' ?>"><?= e($crianca['status']) ?></span>
</div>
<h3 class="child-age"><?= (int)$crianca['idade'] ?> anos</h3>
<p class="child-sex"><?= e($crianca['sexo']) ?></p>
<div class="child-detail"><strong>Instituição</strong><p><?= e($crianca['instituicao']) ?></p></div>
<div class="child-detail"><strong>Observações / necessidades</strong><p><?= e($crianca['observacao']) ?></p></div>
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
</body>
</html>
