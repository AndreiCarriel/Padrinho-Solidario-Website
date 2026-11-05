document.addEventListener('DOMContentLoaded', () => {
  const header = document.getElementById('siteHeader');
  const menuToggle = document.getElementById('menuToggle');
  const navLinks = document.getElementById('navLinks');
  const themeToggle = document.getElementById('themeToggle');

  const updateHeader = () => header.classList.toggle('scrolled', window.scrollY > 16);
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  menuToggle?.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Fechar menu' : 'Abrir menu');
    menuToggle.textContent = isOpen ? '✕' : '☰';
  });
  navLinks?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    navLinks.classList.remove('open');
    menuToggle?.setAttribute('aria-expanded', 'false');
    if (menuToggle) { menuToggle.textContent = '☰'; menuToggle.setAttribute('aria-label', 'Abrir menu'); }
  }));

  const savedTheme = localStorage.getItem('ps-theme');
  if (savedTheme === 'dark') document.body.classList.add('dark');
  const updateThemeIcon = () => { if (themeToggle) themeToggle.textContent = document.body.classList.contains('dark') ? '☀' : '☾'; };
  updateThemeIcon();
  themeToggle?.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('ps-theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    updateThemeIcon();
    if (window.psCharts) window.psCharts.forEach(chart => { chart.options.scales && Object.values(chart.options.scales).forEach(scale => { scale.ticks.color = getComputedStyle(document.body).getPropertyValue('--muted').trim(); scale.grid.color = getComputedStyle(document.body).getPropertyValue('--border').trim(); }); chart.update(); });
  });

  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); revealObserver.unobserve(entry.target); }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

  const counterObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = Number(el.dataset.count || 0);
      const duration = 1300;
      const start = performance.now();
      const tick = now => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(target * eased).toLocaleString('pt-BR');
        if (progress < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
      counterObserver.unobserve(el);
    });
  }, { threshold: 0.3 });
  document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));

  if (typeof Chart !== 'undefined') {
    const styles = getComputedStyle(document.body);
    const primary = styles.getPropertyValue('--primary').trim() || '#bd6746';
    const secondary = styles.getPropertyValue('--secondary').trim() || '#39836a';
    const muted = styles.getPropertyValue('--muted').trim() || '#776d65';
    const border = styles.getPropertyValue('--border').trim() || '#ece4dc';
    Chart.defaults.color = muted;
    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.plugins.legend.display = false;
    Chart.defaults.plugins.tooltip.backgroundColor = styles.getPropertyValue('--surface').trim() || '#fff';
    Chart.defaults.plugins.tooltip.titleColor = styles.getPropertyValue('--text').trim() || '#302722';
    Chart.defaults.plugins.tooltip.bodyColor = styles.getPropertyValue('--text').trim() || '#302722';
    Chart.defaults.plugins.tooltip.borderColor = border;
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    const money = value => 'R$ ' + Number(value).toLocaleString('pt-BR');
    const donation = document.getElementById('donationChart');
    const sponsor = document.getElementById('sponsorChart');
    const category = document.getElementById('categoryChart');
    window.psCharts = [];
    if (donation) window.psCharts.push(new Chart(donation, {
      type: 'line',
      data: { labels: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago'], datasets: [{ data: [8200,9100,11400,10200,13800,15600,17200,18900], borderColor: primary, backgroundColor: primary + '24', fill: true, tension: .38, borderWidth: 2.5, pointRadius: 3, pointBackgroundColor: primary }] },
      options: { responsive:true, maintainAspectRatio:false, interaction:{intersect:false,mode:'index'}, scales:{ y:{beginAtZero:false,grid:{color:border},ticks:{callback:v=>'R$ '+(v/1000).toFixed(0)+' mil',font:{size:10}}}, x:{grid:{display:false},ticks:{font:{size:10}}} }, plugins:{tooltip:{callbacks:{label:ctx=>money(ctx.raw)}}} }
    }));
    if (sponsor) window.psCharts.push(new Chart(sponsor, {
      type:'doughnut',
      data:{labels:['Apadrinhados','Aguardando','Em avaliação'],datasets:[{data:[64,22,14],backgroundColor:[primary,secondary,'#d8a74d'],borderWidth:0,hoverOffset:4}]},
      options:{responsive:true,maintainAspectRatio:false,cutout:'67%',plugins:{tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${ctx.raw}%`}}}}
    }));
    if (category) window.psCharts.push(new Chart(category, {
      type:'bar',
      data:{labels:['Alimentos','Roupas','Brinquedos','Higiene','Outros'],datasets:[{data:[320,280,190,240,110],backgroundColor:secondary,borderRadius:7,maxBarThickness:35}]},
      options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,grid:{color:border},ticks:{font:{size:10}}},x:{grid:{display:false},ticks:{font:{size:10}}}},plugins:{tooltip:{callbacks:{label:ctx=>`${ctx.raw} itens`}}}}
    }));
  }

  // Navegação interna do painel administrativo
  const dashboardMain = document.getElementById('dashboardMain');
  const sideLinks = document.querySelectorAll('.dashboard-sidebar .side-link[data-view]');
  if (dashboardMain && sideLinks.length) {
    const overviewHTML = dashboardMain.innerHTML;
    const viewContent = {
      sponsors: {title:'Padrinhos', subtitle:'Gerencie os padrinhos cadastrados e acompanhe seus vínculos.', action:'＋ Novo padrinho', headers:['Nome','Contato','Apadrinhamento','Status'], rows:[['Marina Silva','marina@email.com','João, 7 anos','Ativo'],['Carlos Ribeiro','carlos@email.com','Sofia, 9 anos','Ativo'],['Ana Souza','ana@email.com','Aguardando vínculo','Pendente'],['Pedro Lima','pedro@email.com','Miguel, 8 anos','Ativo']]},
      children: {title:'Crianças', subtitle:'Visualize os perfis e a situação dos apadrinhamentos.', action:'＋ Cadastrar criança', headers:['Nome','Idade','Instituição','Situação'], rows:[['João','7 anos','Abrigo Esperança','Apadrinhado'],['Sofia','9 anos','Lar Pequeno Príncipe','Apadrinhada'],['Miguel','8 anos','Casa Acolhedora','Aguardando'],['Laura','6 anos','Abrigo Esperança','Em avaliação']]},
      donations: {title:'Doações', subtitle:'Acompanhe contribuições financeiras e doações de itens.', action:'＋ Registrar doação', headers:['Data','Doador / origem','Categoria','Valor / quantidade'], rows:[['26/10/2026','Abrigo Esperança','Financeira','R$ 2.400,00'],['25/10/2026','Marina Silva','Alimentos','35 itens'],['24/10/2026','Carlos Ribeiro','Roupas','18 itens'],['22/10/2026','Comunidade local','Brinquedos','42 itens']]},
      institutions: {title:'Instituições', subtitle:'Consulte instituições parceiras e acompanhe seus cadastros.', action:'＋ Nova instituição', headers:['Instituição','Cidade','Responsável','Status'], rows:[['Abrigo Esperança','Sorocaba, SP','Marcos Almeida','Aprovada'],['Lar Pequeno Príncipe','São Paulo, SP','Renata Costa','Aprovada'],['Casa Acolhedora','Campinas, SP','Paula Santos','Em análise']]},
      reports: {title:'Relatórios', subtitle:'Resumo demonstrativo dos indicadores do projeto.', action:'⇩ Exportar CSV', headers:['Indicador','Total','Período'], rows:[['Crianças acolhidas','1.240','Atual'],['Padrinhos ativos','860','Atual'],['Doações recebidas','R$ 18.900','Este mês'],['Instituições cadastradas','72','Atual']]}
    };
    function renderView(view) {
      sideLinks.forEach(link => {
        const active = link.dataset.view === view;
        link.classList.toggle('active', active);
        if (active) link.setAttribute('aria-current','page'); else link.removeAttribute('aria-current');
      });
      if (view === 'overview') {
        dashboardMain.innerHTML = overviewHTML;
        // Recria os gráficos quando o painel geral é reaberto.
        if (window.Chart) {
          const canvases = dashboardMain.querySelectorAll('canvas');
          if (canvases.length) {
            const primary = getComputedStyle(document.body).getPropertyValue('--primary').trim() || '#bd6545';
            const secondary = getComputedStyle(document.body).getPropertyValue('--secondary').trim() || '#3a876f';
            const border = getComputedStyle(document.body).getPropertyValue('--border').trim() || '#eaded3';
            const money = v => 'R$ ' + Number(v).toLocaleString('pt-BR');
            window.psCharts = [];
            const d = dashboardMain.querySelector('#donationChart'), sp = dashboardMain.querySelector('#sponsorChart'), c = dashboardMain.querySelector('#categoryChart');
            if(d) window.psCharts.push(new Chart(d,{type:'line',data:{labels:['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago'],datasets:[{data:[8200,9100,11400,10200,13800,15500,17200,18900],borderColor:primary,backgroundColor:primary+'22',fill:true,tension:.35,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:border},ticks:{callback:v=>money(v)}},x:{grid:{display:false}}}}}));
            if(sp) window.psCharts.push(new Chart(sp,{type:'doughnut',data:{labels:['Apadrinhados','Aguardando','Em avaliação'],datasets:[{data:[64,22,14],backgroundColor:[primary,secondary,'#d8a74d'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'67%',plugins:{legend:{display:false}}}}));
            if(c) window.psCharts.push(new Chart(c,{type:'bar',data:{labels:['Alimentos','Roupas','Brinquedos','Higiene','Outros'],datasets:[{data:[320,280,190,240,110],backgroundColor:secondary,borderRadius:7,maxBarThickness:35}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:border}},x:{grid:{display:false}}}}}));
          }
        }
        return;
      }
      const data = viewContent[view];
      if (!data) return;
      const rows = data.rows.map(row => '<tr>'+row.map((cell,i)=>'<td>'+(i===row.length-1?'<span class="table-status">'+escapeHTML(cell)+'</span>':escapeHTML(cell))+'</td>').join('')+'</tr>').join('');
      dashboardMain.innerHTML = `<div class="dashboard-title"><div><h3>${data.title}</h3><p>${data.subtitle}</p></div><span class="status-pill">● Dados demonstrativos</span></div>
      <div class="module-toolbar"><input class="module-search" type="search" placeholder="Pesquisar nesta lista..." aria-label="Pesquisar nesta lista"><button class="module-action" type="button">${data.action}</button></div>
      <div class="module-table-wrap"><table class="module-table"><thead><tr>${data.headers.map(h=>'<th>'+h+'</th>').join('')}</tr></thead><tbody>${rows}</tbody></table><p class="module-note">Esta tela contém dados fictícios para demonstração. O cadastro, a edição e a persistência serão conectados ao banco de dados em uma próxima etapa.</p></div>`;
      const search = dashboardMain.querySelector('.module-search');
      search?.addEventListener('input', () => dashboardMain.querySelectorAll('tbody tr').forEach(row => row.hidden = !row.textContent.toLocaleLowerCase('pt-BR').includes(search.value.toLocaleLowerCase('pt-BR'))));
      dashboardMain.querySelector('.module-action')?.addEventListener('click', () => {
        if(view === 'reports') {
          const csv = [data.headers,...data.rows].map(row=>row.map(v=>'"'+v.replaceAll('"','""')+'"').join(';')).join('\r\n');
          const url = URL.createObjectURL(new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8;'}));
          const a = document.createElement('a'); a.href=url; a.download='relatorio-padrinho-solidario.csv'; a.click(); URL.revokeObjectURL(url);
        } else {
          alert('O formulário de cadastro será conectado ao banco de dados na próxima etapa. Esta versão ainda utiliza dados demonstrativos.');
        }
      });
    }
    function escapeHTML(value) { return String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char])); }
    sideLinks.forEach(link => link.addEventListener('click', event => {
      event.preventDefault();
      renderView(link.dataset.view);
      document.getElementById('dashboard')?.scrollIntoView({behavior:'smooth',block:'start'});
    }));
  }

});
