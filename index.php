<?php
declare(strict_types=1);
session_start();
$loggedIn = !empty($_SESSION['padrinho_id']);

$aboutItems = [
 ['icon'=>'🛡️','title'=>'Transparência','desc'=>'Toda movimentação é auditável e visível para padrinhos e instituições.'],
 ['icon'=>'🎁','title'=>'Gestão de Doações','desc'=>'Controle financeiro e de itens com histórico completo de entrada e saída.'],
 ['icon'=>'💗','title'=>'Acompanhamento de Crianças','desc'=>'Histórico afetivo, evolução e comunicação direta com a instituição.'],
 ['icon'=>'📊','title'=>'Relatórios','desc'=>'Prestação de contas clara, relatórios automáticos e exportáveis.'],
];
$features = [
 ['icon'=>'👥','title'=>'Gestão de Usuários','items'=>['Cadastro e login seguro','Perfis de padrinhos','Administradores','Instituições parceiras']],
 ['icon'=>'🤝','title'=>'Sistema de Apadrinhamento','items'=>['Vínculo padrinho ↔ criança','Histórico de acompanhamento','Status do apadrinhamento','Apoio afetivo e financeiro']],
 ['icon'=>'📦','title'=>'Gestão de Doações','items'=>['Doações financeiras','Roupas, alimentos, brinquedos','Controle de estoque','Histórico completo']],
 ['icon'=>'📈','title'=>'Dashboard Administrativo','items'=>['Gráficos e métricas','Número de crianças','Quantidade de doações','Padrinhos ativos e relatórios']],
 ['icon'=>'🔒','title'=>'Segurança','items'=>['Proteção de dados (LGPD)','Autenticação segura (a implementar)','Privacidade das crianças','Auditoria de acessos']],
 ['icon'=>'🏢','title'=>'Instituições','items'=>['Cadastro de abrigos','Aprovação de padrinhos','Comunicação interna','Prestação de contas']],
];
$steps = [
 ['icon'=>'👤','title'=>'Cadastro do padrinho','desc'=>'Crie sua conta segura e complete seu perfil.'],
 ['icon'=>'💗','title'=>'Escolha do apadrinhado','desc'=>'Conheça crianças disponíveis nas instituições parceiras.'],
 ['icon'=>'✅','title'=>'Aprovação da instituição','desc'=>'Análise pelos gestores do abrigo.'],
 ['icon'=>'🤝','title'=>'Acompanhamento e doações','desc'=>'Convivência afetiva, cartas, encontros e contribuições.'],
 ['icon'=>'📊','title'=>'Relatórios e transparência','desc'=>'Acompanhe o impacto em relatórios mensais.'],
];
$techs = ['PHP','HTML5','CSS3','JavaScript','MySQL (opcional)','API REST (futura)'];
$testimonials = [
 ['name'=>'Marina Silva','role'=>'Madrinha há 2 anos','text'=>'Apadrinhar o João transformou meu olhar sobre o mundo. A plataforma torna cada passo simples, transparente e cheio de afeto.'],
 ['name'=>'Ricardo Mendes','role'=>'Gestor do Lar Esperança','text'=>'Conseguimos profissionalizar nossa gestão. Hoje temos relatórios claros, controle de doações e padrinhos engajados.'],
 ['name'=>'Helena Costa','role'=>'Voluntária','text'=>'Ver o impacto em números, fotos e histórias me motiva todos os dias. É solidariedade com tecnologia bem feita.'],
];
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Padrinho Solidário conecta padrinhos, doadores e instituições para apoiar crianças com transparência e solidariedade.">
  <meta name="theme-color" content="#b95f3e">
  <title>Padrinho Solidário — Apadrinhamento e Doações</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="assets/js/main.js" defer></script>
</head>
<body>
<header class="site-header" id="siteHeader">
  <nav class="nav container" aria-label="Navegação principal">
    <a class="brand" href="#top"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false">☰</button>
    <ul class="nav-links" id="navLinks">
      <li><a href="#sobre">Sobre</a></li><li><a href="#funcionalidades">Funcionalidades</a></li>
      <li><a href="#dashboard">Dashboard</a></li><li><a href="#impacto">Impacto</a></li>
      <li><a href="#tecnologias">Tecnologias</a></li><li><a href="#depoimentos">Depoimentos</a></li>
    </ul>
    <div class="nav-actions"><button class="theme-toggle" id="themeToggle" aria-label="Alternar tema">☾</button><a class="button button-small" href="cadastro.php">Quero apadrinhar</a><a class="button button-small button-outline" href="login.php">Entrar</a></div>
  </nav>
</header>
<main>
<section class="hero section" id="top">
  <div class="hero-glow glow-one"></div><div class="hero-glow glow-two"></div>
  <div class="container hero-grid">
    <div class="hero-copy reveal">
      <span class="eyebrow"><span>✦</span> Plataforma de impacto social</span>
      <h1>Transformando vidas através do <span class="gradient-text">apadrinhamento e da solidariedade</span></h1>
      <p class="hero-description">Conectamos padrinhos, doadores e abrigos em uma única plataforma — transparente, segura e humanizada. Cada gesto vira história, cada doação vira esperança.</p>
      <div class="button-row"><a class="button" href="cadastro.php">♡ Quero apadrinhar</a><button class="button button-outline" type="button" data-donation-open>＋ Fazer doação</button></div>
      <div class="hero-stats">
        <div><strong data-count="1240">0</strong><span>Crianças acolhidas</span></div><i></i>
        <div><strong data-count="860">0</strong><span>Padrinhos ativos</span></div><i class="desktop-only"></i>
        <div class="desktop-only"><strong data-count="72">0</strong><span>Instituições</span></div>
      </div>
    </div>
    <div class="hero-visual reveal">
      <div class="image-glow"></div><img class="hero-image" src="assets/hero-solidariedade.jpg" alt="Imagem de divulgação do projeto Padrinho Solidário">
      <div class="floating-card donation-card"><span class="floating-icon">♥</span><div><small>Doações hoje</small><strong>R$ 12.480</strong></div></div>
      <div class="floating-card live-card"><span class="live-dot"></span><span>3 novos apadrinhamentos</span></div>
    </div>
  </div>
</section>

<section class="section" id="sobre"><div class="container">
  <div class="section-heading reveal"><span class="eyebrow">Sobre o projeto</span><h2>Um sistema feito para fortalecer vínculos</h2><p>Desenvolvido para gerenciar apadrinhamentos afetivos e financeiros, controlar doações e dar voz à transparência das instituições de acolhimento.</p></div>
  <div class="grid grid-4">
    <?php foreach ($aboutItems as $item): ?>
    <article class="info-card reveal"><div class="icon-box"><?= e($item['icon']) ?></div><h3><?= e($item['title']) ?></h3><p><?= e($item['desc']) ?></p></article>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section section-warm" id="funcionalidades"><div class="container">
  <div class="section-heading reveal"><span class="eyebrow">Funcionalidades</span><h2>Tudo o que sua instituição precisa</h2><p>Módulos para uma gestão social moderna e humanizada.</p></div>
  <div class="grid grid-3">
    <?php foreach ($features as $feature): ?>
    <article class="feature-card reveal"><div class="icon-box"><?= e($feature['icon']) ?></div><h3><?= e($feature['title']) ?></h3><ul><?php foreach ($feature['items'] as $item): ?><li><span>✓</span><?= e($item) ?></li><?php endforeach; ?></ul></article>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section" id="dashboard"><div class="container">
  <div class="section-heading reveal"><span class="eyebrow">Dashboard demonstrativo</span><h2>Painel administrativo</h2><p>Uma visão ilustrativa para acompanhar impacto, doações e vínculos. Os números abaixo são dados de demonstração, não dados reais de um banco.</p></div>
  <div class="dashboard reveal">
    <aside class="dashboard-sidebar"><a class="brand" href="#top"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a>
      <a class="side-link active" href="#dashboard" data-view="overview">▦ &nbsp; Visão geral</a><a class="side-link" href="#dashboard" data-view="sponsors">♙ &nbsp; Padrinhos</a><a class="side-link" href="#dashboard" data-view="children">♡ &nbsp; Crianças</a><a class="side-link" href="#dashboard" data-view="donations">🎁 &nbsp; Doações</a><a class="side-link" href="#dashboard" data-view="institutions">⌂ &nbsp; Instituições</a><a class="side-link" href="#dashboard" data-view="reports">▤ &nbsp; Relatórios</a>
    </aside>
    <div class="dashboard-main" id="dashboardMain">
      <div class="dashboard-title"><div><h3>Visão geral</h3><p>Dados demonstrativos · Atualização ilustrativa</p></div><span class="status-pill">● 12 atividades hoje</span></div>
      <div class="kpi-grid">
        <article class="kpi"><span>Crianças acolhidas</span><strong>1.240</strong><small>↗ +8% este mês</small></article>
        <article class="kpi"><span>Padrinhos ativos</span><strong>860</strong><small>↗ +12 novos</small></article>
        <article class="kpi"><span>Doações do mês</span><strong>R$ 18.900</strong><small>↗ +24% vs. mês anterior</small></article>
        <article class="kpi"><span>Instituições</span><strong>72</strong><small>5 estados</small></article>
      </div>
      <div class="charts-grid">
        <article class="chart-card chart-wide"><div class="chart-heading"><h4>Evolução de doações</h4><span>8 meses</span></div><div class="chart-wrap"><canvas id="donationChart" aria-label="Gráfico de evolução de doações" role="img"></canvas></div></article>
        <article class="chart-card"><h4>Status dos apadrinhamentos</h4><div class="chart-wrap chart-doughnut"><canvas id="sponsorChart" aria-label="Gráfico de status de apadrinhamentos" role="img"></canvas></div><div class="legend"><span>● Apadrinhados <b>64%</b></span><span>● Aguardando <b>22%</b></span><span>● Em avaliação <b>14%</b></span></div></article>
        <article class="chart-card chart-wide"><h4>Doações por categoria</h4><div class="chart-wrap chart-bars"><canvas id="categoryChart" aria-label="Gráfico de doações por categoria" role="img"></canvas></div></article>
        <article class="chart-card"><h4>Últimas atividades</h4><ul class="activity-list"><li><span>🤝</span><div><b>Marina S.</b> apadrinhou <b>João, 7 anos</b><small>há 12 min</small></div></li><li><span>🎁</span><div><b>Abrigo Esperança</b> registrou doação de <b>R$ 2.400</b><small>há 38 min</small></div></li><li><span>💌</span><div><b>Carlos R.</b> enviou carta para <b>Sofia, 9 anos</b><small>há 1 h</small></div></li><li><span>✓</span><div><b>Admin</b> aprovou cadastro de <b>Lar Pequeno Príncipe</b><small>há 2 h</small></div></li></ul></article>
      </div>
    </div>
  </div>
</div></section>

<section class="section impact-section" id="impacto"><div class="container">
  <div class="section-heading light reveal"><span class="eyebrow">Impacto social</span><h2>Solidariedade que vira número, número que vira história</h2><p>Cada métrica representa um vínculo real construído entre pessoas que decidiram transformar o mundo.</p></div>
  <div class="grid grid-4 impact-stats"><div class="reveal"><strong><span data-count="1240">0</span>+</strong><span>Crianças ajudadas</span></div><div class="reveal"><strong><span data-count="860">0</span>+</strong><span>Padrinhos ativos</span></div><div class="reveal"><strong>R$ <span data-count="18900">0</span></strong><span>Doações realizadas</span></div><div class="reveal"><strong><span data-count="72">0</span></strong><span>Instituições cadastradas</span></div></div>
  <blockquote>“Quando alguém apadrinha uma criança, não muda só a vida dela — muda também a própria.”</blockquote>
</div></section>

<section class="section" id="fluxo"><div class="container">
  <div class="section-heading reveal"><span class="eyebrow">Como funciona</span><h2>Fluxo do sistema em 5 etapas</h2></div>
  <div class="steps-grid"><?php foreach ($steps as $i => $step): ?><article class="step-card reveal"><div class="step-icon"><?= e($step['icon']) ?><span><?= $i+1 ?></span></div><h3><?= e($step['title']) ?></h3><p><?= e($step['desc']) ?></p></article><?php endforeach; ?></div>
</div></section>

<section class="section section-warm" id="tecnologias"><div class="container">
  <div class="section-heading reveal"><span class="eyebrow">Stack técnica</span><h2>Tecnologias utilizadas nesta versão</h2><p>Uma base simples, acessível e preparada para evoluir com novas funcionalidades.</p></div>
  <div class="tech-grid"><?php foreach ($techs as $tech): ?><div class="tech-card reveal"><span>⌘</span><strong><?= e($tech) ?></strong></div><?php endforeach; ?></div>
  <div class="section-heading architecture-heading reveal"><span class="eyebrow">Arquitetura</span><h2>Como tudo pode se conectar</h2></div>
  <div class="architecture-card"><div><span class="architecture-icon">🖥️</span><h3>Interface</h3><p>HTML5 + CSS3 + JavaScript</p></div><span class="architecture-arrow">↔</span><div><span class="architecture-icon">⚙️</span><h3>Servidor</h3><p>PHP para lógica e validação</p></div><span class="architecture-arrow">↔</span><div><span class="architecture-icon">🗄️</span><h3>Banco de dados</h3><p>MySQL, quando integrado</p></div></div>
  <p class="note">Observação: esta conversão contém a interface demonstrativa. Login real, persistência de dados, autorização e integração com banco de dados ainda precisam ser implementados antes de uso em produção.</p>
</div></section>

<section class="section" id="depoimentos"><div class="container">
  <div class="section-heading reveal"><span class="eyebrow">Depoimentos ilustrativos</span><h2>Histórias de quem vive a plataforma</h2><p>Exemplos de depoimentos usados na apresentação do projeto.</p></div>
  <div class="grid grid-3"><?php foreach ($testimonials as $item): ?><article class="testimonial-card reveal"><div class="quote-mark">“</div><p><?= e($item['text']) ?></p><div class="testimonial-person"><span><?= e(implode('', array_map(fn($part) => mb_substr($part, 0, 1), explode(' ', $item['name'])))) ?></span><div><strong><?= e($item['name']) ?></strong><small><?= e($item['role']) ?></small></div></div></article><?php endforeach; ?></div>
</div></section>

<section class="section cta-section" id="contato"><div class="container"><div class="cta-card reveal"><span class="eyebrow">Faça parte dessa mudança</span><h2>Pronto para transformar uma história?</h2><p>Junte-se a pessoas e instituições construindo um futuro mais solidário.</p><div class="button-row center"><a class="button button-white" href="cadastro.php">♡ Quero apadrinhar</a><button class="button button-cta-outline" type="button" data-donation-open>＋ Fazer doação</button></div></div></div></section>
</main>
<footer class="site-footer"><div class="container footer-grid"><div class="footer-about"><a class="brand" href="#top"><span class="brand-mark">♥</span><span>Padrinho Solidário</span></a><p>Plataforma de gestão social que conecta padrinhos, doadores e abrigos com transparência e afeto.</p></div><div><h4>Links rápidos</h4><a href="#sobre">Sobre</a><a href="#funcionalidades">Funcionalidades</a><a href="#dashboard">Dashboard</a><a href="#impacto">Impacto</a></div><div><h4>Contato</h4><a href="mailto:contato@solidario.app">contato@solidario.app</a><span>Projeto acadêmico demonstrativo</span></div></div><div class="footer-bottom"><div class="container">© <?= date('Y') ?> Padrinho Solidário. Todos os direitos reservados. <span>Feito com ♥ para transformar vidas.</span></div></div></footer>


<dialog id="donationChoice" class="donation-choice">
  <div class="donation-choice-inner">
    <button type="button" class="donation-choice-close" id="donationChoiceClose" aria-label="Fechar">×</button>
    <span class="eyebrow">Faça parte dessa mudança</span>
    <h2>Você tem cadastro no site?</h2>
    <p>Escolha como deseja continuar para registrar ou encaminhar sua doação.</p>
    <div class="donation-choice-actions">
      <a class="button" href="<?= $loggedIn ? 'doacao.php' : 'login.php?redirect=doacao.php' ?>">Sim, tenho cadastro</a>
      <a class="button button-outline" href="instituicoes.php">Não tenho cadastro</a>
    </div>
  </div>
</dialog>
<style>
.donation-choice{width:min(480px,calc(100% - 28px));border:1px solid var(--border);border-radius:20px;padding:0;background:var(--surface);color:var(--text);box-shadow:0 24px 80px #21160f55}
.donation-choice::backdrop{background:rgba(35,25,20,.58);backdrop-filter:blur(3px)}
.donation-choice-inner{padding:32px;position:relative}
.donation-choice-inner h2{font:800 25px Manrope,sans-serif;margin:14px 0 8px}
.donation-choice-inner p{color:var(--muted);font-size:14px}
.donation-choice-close{position:absolute;right:18px;top:16px;border:0;border-radius:9px;background:var(--warm);color:var(--text);width:36px;height:36px;font-size:22px;cursor:pointer}
.donation-choice-actions{display:flex;flex-direction:column;gap:10px;margin-top:22px}
.donation-choice-actions .button{text-align:center}
</style>
<script>
const donationChoice = document.getElementById('donationChoice');
document.querySelectorAll('[data-donation-open]').forEach(button => button.addEventListener('click', () => donationChoice.showModal()));
document.getElementById('donationChoiceClose').addEventListener('click', () => donationChoice.close());
donationChoice.addEventListener('click', event => { if(event.target === donationChoice) donationChoice.close(); });
</script>

</body>
</html>
