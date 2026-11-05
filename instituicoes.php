<?php
declare(strict_types=1);
$instituicoes = [
    ['nome'=>'Lar Pequeno Príncipe','email'=>'contato@larpequenoprincipe.org.br','descricao'=>'Instituição parceira demonstrativa'],
    ['nome'=>'Abrigo Esperança','email'=>'contato@abrigoesperanca.org.br','descricao'=>'Instituição parceira demonstrativa'],
    ['nome'=>'Casa Acolhedora Sementes','email'=>'contato@casasementes.org.br','descricao'=>'Instituição parceira demonstrativa'],
];
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instituições parceiras — Padrinho Solidário</title><link rel="stylesheet" href="assets/css/style.css">
<style>
.simple-page{min-height:100vh;padding:38px 0 70px}.simple-wrap{width:min(900px,calc(100% - 40px));margin:auto}
.simple-card{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px;box-shadow:var(--shadow)}
.institution-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:24px}
.institution-item{border:1px solid var(--border);border-radius:14px;padding:20px;background:var(--surface)}
.institution-item h2{font:700 18px Manrope,sans-serif;margin:0 0 7px}.institution-item p{font-size:13px;color:var(--muted)}
.institution-item a{color:var(--primary-dark);font-weight:700;overflow-wrap:anywhere}
@media(max-width:650px){.institution-list{grid-template-columns:1fr}}
</style></head><body><main class="simple-page"><div class="simple-wrap">
<a class="brand" href="index.php"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
<section class="simple-card" style="margin-top:28px"><span class="eyebrow">Doações</span><h1>Entre em contato com uma instituição</h1>
<p>Você informou que ainda não possui cadastro. Selecione uma instituição e entre em contato diretamente pelo e-mail para combinar sua doação.</p>
<div class="institution-list"><?php foreach($instituicoes as $item): ?><article class="institution-item"><h2><?=e($item['nome'])?></h2><p><?=e($item['descricao'])?></p><a href="mailto:<?=e($item['email'])?>"><?=e($item['email'])?></a></article><?php endforeach; ?></div>
<p style="font-size:12px;color:var(--muted);margin-top:22px">Os nomes e endereços de e-mail acima são dados demonstrativos do protótipo. Substitua-os pelos contatos oficiais antes da publicação.</p>
<a class="button button-outline" href="index.php" style="margin-top:12px">Voltar ao início</a></section></div></main></body></html>
