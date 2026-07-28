/* Shell compartilhado do prototipo: sidebar, topbar e dock, por PERFIL.
   Cada pagina so escreve o proprio conteudo.
   body[data-profile] = cliente | empresa | admin   (ausente = cliente)
   body[data-shell="none"] = telas de autenticacao (sem shell) */
(function () {
  var body = document.body;
  var profile = body.dataset.profile || 'cliente';
  var page = body.dataset.page || '';
  var title = body.dataset.title || '';
  var sub = body.dataset.sub || '';
  var back = body.dataset.back || '';
  var noShell = body.dataset.shell === 'none';

  var PROFILES = {
    cliente: {
      sub: 'Programa de fidelidade',
      cta: { href: 'ler-qr.html', icon: 'qr_code_scanner', label: 'Ler QR da empresa' },
      who: { av: 'CD', name: 'Cliente Demo', meta: 'Membro Prata · 320 pts', href: 'perfil.html' },
      nav: [
        { id: 'inicio', href: 'inicio.html', icon: 'home', label: 'Início' },
        { id: 'parceiro', href: 'parceiro.html', icon: 'storefront', label: 'Empresas' },
        { id: 'beneficios', href: 'beneficios.html', icon: 'redeem', label: 'Benefícios', badge: '3' },
        { id: 'historico', href: 'historico.html', icon: 'history', label: 'Histórico' },
        { id: 'perfil', href: 'perfil.html', icon: 'person', label: 'Perfil' }
      ],
      /* botao central = mostrar o meu cartao (uso diario no balcao);
         ler o QR da empresa e o CTA da sidebar / da tela do cartao */
      dockCta: { href: 'cartao.html', icon: 'qr_code_2' },
      dock: ['inicio', 'parceiro', 'beneficios', 'perfil']
    },
    empresa: {
      sub: 'Painel da empresa',
      cta: { href: 'empresa-validar.html', icon: 'qr_code_scanner', label: 'Validar cliente' },
      who: { av: 'MG', name: 'Malagueta Galpão', meta: 'Plano ativo · até 12/2026', href: '#' },
      nav: [
        { id: 'empresa-painel', href: 'empresa-painel.html', icon: 'dashboard', label: 'Painel' },
        { id: 'empresa-clientes', href: 'empresa-clientes.html', icon: 'group', label: 'Clientes' },
        { id: 'empresa-ofertas', href: 'empresa-ofertas.html', icon: 'local_offer', label: 'Ofertas', badge: '2' },
        { id: 'empresa-validar', href: 'empresa-validar.html', icon: 'qr_code_scanner', label: 'Validar' },
        { id: 'empresa-config', href: 'empresa-config.html', icon: 'settings', label: 'Configurações' }
      ],
      dockCta: { href: 'empresa-validar.html', icon: 'qr_code_scanner' },
      dock: ['empresa-painel', 'empresa-clientes', 'empresa-ofertas', 'empresa-config']
    },
    admin: {
      sub: 'Painel master',
      cta: { href: 'admin-empresas.html', icon: 'add_business', label: 'Nova empresa' },
      who: { av: 'AD', name: 'Admin Demo', meta: 'Acesso master', href: '#' },
      nav: [
        { id: 'admin-painel', href: 'admin-painel.html', icon: 'monitoring', label: 'Painel' },
        { id: 'admin-empresas', href: 'admin-empresas.html', icon: 'storefront', label: 'Empresas', badge: '4' },
        { id: 'admin-usuarios', href: 'admin-usuarios.html', icon: 'group', label: 'Usuários' },
        { id: 'admin-relatorios', href: 'admin-relatorios.html', icon: 'bar_chart', label: 'Relatórios' },
        { id: 'admin-conteudo', href: 'admin-conteudo.html', icon: 'wallpaper', label: 'Conteúdo' },
        { id: 'admin-tickets', href: 'admin-tickets.html', icon: 'support_agent', label: 'Suporte', badge: '6' },
        { id: 'admin-config', href: 'admin-config.html', icon: 'settings', label: 'Configurações' }
      ],
      dockCta: { href: 'admin-empresas.html', icon: 'add_business' },
      dock: ['admin-painel', 'admin-empresas', 'admin-usuarios', 'admin-tickets']
    },
    revenda: {
      sub: 'Painel de revenda',
      cta: { href: 'revenda.html', icon: 'add_business', label: 'Cadastrar empresa' },
      who: { av: 'RS', name: 'Revenda Sul', meta: 'Saldo R$ 1.240,00', href: '#' },
      nav: [
        { id: 'revenda', href: 'revenda.html', icon: 'dashboard', label: 'Painel' },
        { id: 'revenda-empresas', href: 'revenda.html', icon: 'storefront', label: 'Minhas empresas' },
        { id: 'revenda-creditos', href: 'revenda.html', icon: 'account_balance_wallet', label: 'Créditos' },
        { id: 'revenda-suporte', href: 'admin-tickets.html', icon: 'support_agent', label: 'Suporte' }
      ],
      dockCta: { href: 'revenda.html', icon: 'add_business' },
      dock: ['revenda', 'revenda-empresas', 'revenda-creditos', 'revenda-suporte']
    }
  };

  /* ---- tema: aplica sempre, mesmo sem shell ---- */
  var root = document.documentElement;
  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    try { localStorage.setItem('proto-theme', theme); } catch (e) {}
    document.querySelectorAll('[data-theme-switch]').forEach(function (sw) {
      sw.classList.toggle('is-on', theme === 'dark');
    });
    document.querySelectorAll('#themeIcon').forEach(function (i) {
      i.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    });
    document.querySelectorAll('#themeLabel').forEach(function (l) {
      l.textContent = theme === 'dark' ? 'Claro' : 'Escuro';
    });
  }
  function toggleTheme() {
    applyTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
  }

  if (!noShell) {
    var P = PROFILES[profile] || PROFILES.cliente;

    function navItems() {
      return P.nav.map(function (n) {
        var on = n.id === page ? ' is-active' : '';
        var badge = n.badge ? '<span class="ap-nav__badge">' + n.badge + '</span>' : '';
        return '<a href="' + n.href + '" class="ap-nav__item' + on + '">' +
          '<span class="material-symbols-outlined">' + n.icon + '</span> ' + n.label + badge + '</a>';
      }).join('');
    }

    var side = document.createElement('aside');
    side.className = 'ap-side';
    side.innerHTML =
      '<div class="ap-brand">' +
        '<div class="ap-brand__mark">TT</div>' +
        '<div><div class="ap-brand__name">Tem de Tudo</div>' +
        '<div class="ap-brand__sub">' + P.sub + '</div></div>' +
      '</div>' +
      '<a href="' + P.cta.href + '" class="ap-side__cta">' +
        '<span class="material-symbols-outlined">' + P.cta.icon + '</span> ' + P.cta.label + '</a>' +
      '<div class="ap-side__label">Menu</div>' +
      '<nav class="ap-nav">' + navItems() + '</nav>' +
      '<a href="' + P.who.href + '" class="ap-side__foot">' +
        '<div class="ap-side__avatar">' + P.who.av + '</div>' +
        '<div><div class="ap-side__who">' + P.who.name + '</div>' +
        '<div class="ap-side__tier">' + P.who.meta + '</div></div>' +
      '</a>';

    var top = document.createElement('header');
    top.className = 'ap-top';
    top.innerHTML =
      '<div class="ap-top__in">' +
        (back ? '<a href="' + back + '" class="ap-top__back"><span class="material-symbols-outlined">arrow_back</span></a>' : '') +
        '<div><div class="ap-top__hi">' + title + '</div><div class="ap-top__sub">' + sub + '</div></div>' +
        '<div class="ap-top__acts">' +
          '<button class="ap-themebtn" id="themeBtn" type="button">' +
            '<span class="material-symbols-outlined" id="themeIcon">dark_mode</span>' +
            '<span id="themeLabel">Escuro</span></button>' +
          '<button class="ap-iconbtn" type="button" aria-label="Notificações">' +
            '<span class="material-symbols-outlined">notifications</span><span class="ap-iconbtn__dot"></span></button>' +
        '</div>' +
      '</div>';

    var dock = document.createElement('nav');
    dock.className = 'ap-dock';
    var byId = {};
    P.nav.forEach(function (n) { byId[n.id] = n; });
    var d = P.dock.map(function (id) { return byId[id]; }).filter(Boolean);
    function dockItem(n) {
      var on = n.id === page ? ' is-active' : '';
      return '<a href="' + n.href + '" class="ap-dock__i' + on + '">' +
        '<span class="material-symbols-outlined">' + n.icon + '</span><span>' + n.label + '</span></a>';
    }
    dock.innerHTML =
      dockItem(d[0]) + dockItem(d[1]) +
      '<a href="' + P.dockCta.href + '" class="ap-dock__qr" aria-label="' + P.cta.label + '">' +
        '<span class="material-symbols-outlined">' + P.dockCta.icon + '</span></a>' +
      dockItem(d[2]) + dockItem(d[3]);

    body.insertBefore(side, body.firstChild);
    side.insertAdjacentElement('afterend', top);
    body.appendChild(dock);

    document.getElementById('themeBtn').addEventListener('click', toggleTheme);
  }

  applyTheme(root.getAttribute('data-theme') || 'light');

  document.querySelectorAll('[data-theme-switch], [data-theme-btn]').forEach(function (el) {
    el.addEventListener('click', toggleTheme);
  });

  /* ---- controles genericos do prototipo ---- */
  document.querySelectorAll('.ap-seg').forEach(function (seg) {
    seg.addEventListener('click', function (e) {
      var b = e.target.closest('.ap-seg__i');
      if (!b) return;
      seg.querySelectorAll('.ap-seg__i').forEach(function (i) { i.classList.remove('is-active'); });
      b.classList.add('is-active');
    });
  });
  document.querySelectorAll('.ap-switch:not([data-theme-switch])').forEach(function (sw) {
    sw.addEventListener('click', function () { sw.classList.toggle('is-on'); });
  });
  document.querySelectorAll('.ap-choice').forEach(function (group) {
    group.addEventListener('click', function (e) {
      var i = e.target.closest('.ap-choice__i');
      if (!i) return;
      group.querySelectorAll('.ap-choice__i').forEach(function (x) { x.classList.remove('is-on'); });
      i.classList.add('is-on');
    });
  });

  /* Gemea acessivel: alterna grafico <-> tabela com os mesmos valores */
  document.querySelectorAll('[data-table-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var table = document.getElementById(b.dataset.tableToggle);
      var chart = document.getElementById(b.dataset.chartToggle);
      if (!table) return;
      var showingTable = table.classList.toggle('is-hidden') === false;
      if (chart) chart.classList.toggle('is-hidden', showingTable);
      b.textContent = showingTable ? 'Ver gráfico' : 'Ver tabela';
    });
  });
})();
