/* tdt-app.js — camada de interface (2026-07)
   Carregado no <head>, antes da pintura, para o tema nao piscar.
   Nao depende do bundle stitch-app: so tema, anel do saldo e o botao. */
(function () {
  'use strict';

  var KEY = 'tdt-theme';
  var root = document.documentElement;

  function stored() {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
  }

  function systemPrefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  function apply(theme) {
    root.setAttribute('data-theme', theme);
    try { localStorage.setItem(KEY, theme); } catch (e) {}

    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'dark' ? '#0d0d11' : '#ffffff');

    document.querySelectorAll('[data-tdt-theme-icon]').forEach(function (el) {
      el.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    });
    document.querySelectorAll('[data-tdt-theme-label]').forEach(function (el) {
      el.textContent = theme === 'dark' ? 'Claro' : 'Escuro';
    });
    document.querySelectorAll('[data-tdt-theme-switch]').forEach(function (el) {
      el.classList.toggle('is-on', theme === 'dark');
      el.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    });
  }

  function toggle() {
    apply(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
  }

  /* 1. Tema antes da pintura (o script e sincrono no <head>). */
  apply(stored() || (systemPrefersDark() ? 'dark' : 'light'));
  window.tdtToggleTheme = toggle;

  document.addEventListener('DOMContentLoaded', function () {
    /* 2. Botao de tema na app bar, sem precisar editar cada pagina. */
    var actions = document.querySelector('.tdt-appbar__actions');
    if (actions && !actions.querySelector('.tdt-theme-btn')) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'tdt-icon-btn tdt-theme-btn';
      btn.setAttribute('aria-label', 'Alternar tema claro e escuro');
      btn.innerHTML = '<span class="material-symbols-outlined" data-tdt-theme-icon>dark_mode</span>';
      btn.addEventListener('click', toggle);
      actions.insertBefore(btn, actions.firstChild);
    }

    document.querySelectorAll('[data-tdt-theme-toggle]').forEach(function (el) {
      el.addEventListener('click', toggle);
    });

    apply(root.getAttribute('data-theme') || 'light');

    /* 3. Anel do saldo: espelha a largura da barra de progresso que o bundle
       ja calcula, para nao duplicar chamada de API nem regra de nivel. */
    var bar = document.getElementById('hero-progress-bar');
    var hero = document.querySelector('.wallet-card');
    var num = document.querySelector('[data-tdt-ring-num]');
    var arc = document.querySelector('[data-tdt-ring-arc]');
    if (!bar || !hero) return;

    var sync = function () {
      var pct = parseFloat((bar.style.width || '0').replace('%', ''));
      if (isNaN(pct)) pct = 0;
      pct = Math.max(0, Math.min(100, pct));
      hero.style.setProperty('--p', String(pct));
      if (num) num.textContent = Math.round(pct) + '%';
      // Arco SVG: 2 * PI * r, com r = 46 -> 289. O quanto falta e o offset.
      if (arc) arc.setAttribute('stroke-dashoffset', String(289 - (289 * pct) / 100));
    };

    sync();
    new MutationObserver(sync).observe(bar, { attributes: true, attributeFilter: ['style'] });
  });
})();
