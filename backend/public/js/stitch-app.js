/**
 * Stitch Integration Layer (Tem de Tudo)
 * Objetivo: manter comportamento atual, com cÃ³digo mais organizado e claro.
 * MÃ³dulos internos: api, auth, ui, render, pages (cliente/empresa/admin/shared).
 */
(function () {
  // ---------------------- Constantes ---------------------- //
  const API_BASE = `${window.location.origin}/api`;
  const STORAGE = { token: 'tem_de_tudo_token', user: 'tem_de_tudo_user' };
  const redirectMap = { cliente: '/dashboard-cliente.html', empresa: '/dashboard-empresa.html', admin: '/dashboard-admin.html' };
  const page = document.body?.dataset?.page || location.pathname.replace(/\//g, '').replace('.html', '');
  const VAPID_CACHE_KEY = 'vapid_public_key';

  // ---------------------- UI / Estado ---------------------- //
  const ui = (() => {
    let pageStateEl = null;
    const palette = {
      loading: 'border-blue-200 bg-blue-50 text-blue-800',
      empty: 'border-amber-200 bg-amber-50 text-amber-800',
      error: 'border-rose-200 bg-rose-50 text-rose-800',
      info: 'border-gray-200 bg-gray-50 text-gray-800',
    };

    function setPageState(type, message) {
      if (!pageStateEl) {
        pageStateEl = document.createElement('div');
        pageStateEl.id = 'page-state';
        pageStateEl.className = 'max-w-4xl mx-auto mt-6 px-4';
        (document.querySelector('main') || document.body).prepend(pageStateEl);
      }
      pageStateEl.innerHTML = `<div class="border ${palette[type] || palette.info} rounded-xl px-4 py-3 shadow-sm text-sm">${type === 'loading' ? 'â³ ' : ''}${message}</div>`;
    }

    function clearPageState() {
      if (pageStateEl) pageStateEl.remove();
      pageStateEl = null;
    }

    function message(text, variant = 'info') {
      const mPalette = {
        info: 'bg-blue-50 text-blue-800 border-blue-100',
        success: 'bg-emerald-50 text-emerald-800 border-emerald-100',
        warning: 'bg-amber-50 text-amber-800 border-amber-100',
        error: 'bg-rose-50 text-rose-800 border-rose-100',
      };
      const box = document.createElement('div');
      box.className = `max-w-4xl mx-auto mt-4 px-4 py-3 rounded-xl border ${mPalette[variant] || mPalette.info} shadow-sm text-sm`;
      box.textContent = text;
      (document.querySelector('main') || document.body).prepend(box);
      setTimeout(() => box.remove(), 6000);
    }

    return { setPageState, clearPageState, message };
  })();

  // ---------------------- Auth ---------------------- //
  const auth = (() => {
    let userCache = null;

    const parseJSON = (str) => {
      try {
        return JSON.parse(str);
      } catch {
        return null;
      }
    };

    const getStored = () => ({
      token: localStorage.getItem(STORAGE.token),
      user: parseJSON(localStorage.getItem(STORAGE.user) || '{}'),
    });

    const save = (token, user) => {
      if (token) localStorage.setItem(STORAGE.token, token);
      if (user) localStorage.setItem(STORAGE.user, JSON.stringify(user));
      userCache = user;
    };

    const clear = () => {
      localStorage.removeItem(STORAGE.token);
      localStorage.removeItem(STORAGE.user);
      userCache = null;
    };

    const ensure = async () => {
      if (userCache) return userCache;
      const stored = getStored();
      if (stored.user && stored.user.id) {
        userCache = stored.user;
        return userCache;
      }
      const { res, data } = await api.request('/me');
      if (res.ok && data?.data) {
        save(stored.token, data.data);
        return data.data;
      }
      clear();
      window.location.href = '/entrar.html';
      return null;
    };

    const guard = async (perfis = []) => {
      const user = await ensure();
      if (!user) return false;
      if (perfis.length && !perfis.includes(user.perfil)) {
        window.location.href = redirectMap[user.perfil] || '/entrar.html';
        return false;
      }
      return true;
    };

    return { getStored, save, clear, ensure, guard };
  })();

  // ---------------------- Push ---------------------- //
  const push = (() => {
    async function getPublicKey() {
      const cached = localStorage.getItem(VAPID_CACHE_KEY);
      if (cached) return cached;
      const res = await fetch(`${API_BASE}/push/public-key`);
      const data = await res.json();
      if (data?.vapidPublicKey) {
        localStorage.setItem(VAPID_CACHE_KEY, data.vapidPublicKey);
        return data.vapidPublicKey;
      }
      return null;
    }

    async function register() {
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        ui.message('Seu navegador nÃ£o suporta notificaÃ§Ãµes push.', 'warning');
        return;
      }
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        ui.message('PermissÃ£o de push negada ou nÃ£o concedida.', 'warning');
        return;
      }
      const reg = await navigator.serviceWorker.register('/sw-push.js');
      const publicKey = await getPublicKey();
      if (!publicKey) {
        ui.message('Chave pÃºblica de push nÃ£o configurada.', 'warning');
        return;
      }
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
      });
      await api.request(
        '/push/subscribe',
        { method: 'POST', body: JSON.stringify(sub) }
      );
    }

    async function unregister() {
      if (!('serviceWorker' in navigator)) return;
      const reg = await navigator.serviceWorker.getRegistration('/sw-push.js');
      const sub = await reg?.pushManager.getSubscription();
      if (sub) {
        await api.request('/push/unsubscribe', {
          method: 'DELETE',
          body: JSON.stringify({ endpoint: sub.endpoint }),
        });
        await sub.unsubscribe();
      }
    }

    function urlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);
      for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
      return outputArray;
    }

    return { register, unregister };
  })();

  // ---------------------- API ---------------------- //
  const api = (() => {
    async function request(path, options = {}, { requireAuth = true } = {}) {
      const stored = auth.getStored();
      const isFormData = options.body instanceof FormData;
      const headers = {
        Accept: 'application/json',
        ...(options.body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
        ...(requireAuth && stored.token ? { Authorization: `Bearer ${stored.token}` } : {}),
        ...(options.headers || {}),
      };
      const res = await fetch(`${API_BASE}${path}`, { ...options, headers });
      let data = null;
      try {
        data = await res.json();
      } catch {
        data = null;
      }
      if (res.status === 401) {
        auth.clear();
        ui.message('SessÃ£o expirada. FaÃ§a login novamente.', 'warning');
        setTimeout(() => (window.location.href = '/entrar.html'), 300);
      }
      if (res.status === 403) ui.message('Acesso negado para este perfil.', 'warning');
      if (res.status === 404) ui.message('Recurso nÃ£o encontrado.', 'warning');
      if (res.status >= 500) ui.message('Erro no servidor. Tente novamente em instantes.', 'error');
      return { res, data };
    }
    return { request };
  })();

  // ---------------------- Render helpers ---------------------- //
  const render = (() => {
    function summary(title, metrics) {
      const host = document.querySelector('main') || document.body;
      const wrap = document.createElement('section');
      wrap.className = 'max-w-6xl mx-auto px-4 pt-4';
      wrap.innerHTML = `
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-xl font-semibold text-on-surface">${title}</h2>
          <button id="logoutBtn" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Sair</button>
        </div>
        <div class="grid gap-3 md:grid-cols-3">
          ${metrics
            .map(
              (m) => `
            <div class="rounded-2xl p-4 bg-white/80 shadow-sm border border-surface-variant/30">
              <p class="text-xs uppercase tracking-widest text-on-surface-variant font-semibold">${m.label}</p>
              <p class="mt-2 text-2xl font-bold text-on-surface">${m.value ?? 'â€”'}</p>
              ${m.hint ? `<p class="text-xs text-on-surface-variant mt-1">${m.hint}</p>` : ''}
            </div>`
            )
            .join('')}
        </div>`;
      host.prepend(wrap);
      wrap.querySelector('#logoutBtn')?.addEventListener('click', () => {
        auth.clear();
        window.location.href = '/entrar.html';
      });
    }

    function section(title, inner) {
      const host = document.querySelector('main') || document.body;
      const wrap = document.createElement('section');
      wrap.className = 'max-w-6xl mx-auto px-4 pt-4';
      wrap.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm">
          <div class="px-4 py-3 border-b border-surface-variant/30 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-on-surface">${title}</h2>
          </div>
          <div class="divide-y divide-surface-variant/30">${inner}</div>
        </div>`;
      host.prepend(wrap);
      return wrap;
    }

    return { summary, section };
  })();

  // ---------------------- NotificaÃ§Ãµes internas ---------------------- //
  const notifications = (() => {
    async function fetchAll() {
      const { data } = await api.request('/notifications');
      return data?.data?.data || data?.data || [];
    }

    async function markAllRead() {
      await api.request('/notifications/read', { method: 'POST' });
    }

    function renderList(items, title = 'NotificaÃ§Ãµes') {
      if (!items.length) {
        ui.setPageState('empty', 'Sem notificaÃ§Ãµes.');
        return;
      }
      const inner = items
        .map(
          (n) => `
        <div class="px-4 py-3 flex items-center justify-between text-sm ${n.read_at ? 'opacity-70' : ''}">
          <div>
            <p class="font-semibold">${n.title}</p>
            <p class="text-on-surface-variant">${n.message}</p>
          </div>
          <span class="text-xs text-on-surface-variant">${new Date(n.created_at).toLocaleString('pt-BR')}</span>
        </div>`
        )
        .join('');
      render.section(title, inner);
    }

    async function load(title = 'NotificaÃ§Ãµes') {
      const items = await fetchAll();
      renderList(items, title);
    }

    return { load, markAllRead };
  })();

  // ---------------------- PÃ¡ginas: Cliente ---------------------- //
  const cliente = {
    async dashboard() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando painel da empresa...');
      const [promos, clientes, relatorio, resgates] = await Promise.all([
        api.request('/empresa/promocoes'),
        api.request('/empresa/clientes'),
        api.request('/empresa/relatorio-pontos'),
        api.request('/empresa/resgates'),
      ]);

      const kpiVolume = document.getElementById('kpiVolume');
      const kpiClientes = document.getElementById('kpiClientes');
      const kpiResgates = document.getElementById('kpiResgates');
      const campanhasBox = document.getElementById('campanhasAtivas');
      const campanhasEmpty = document.getElementById('campanhasEmpty');
      const movDistribuido = document.getElementById('movDistribuido');
      const movResgatado = document.getElementById('movResgatado');
      const movClientes = document.getElementById('movClientes');
      const movMsg = document.getElementById('movMsg');
      ui.clearPageState();

      const totals = relatorio.data?.data?.totais || {};
      const fmtMoeda = (n) => 'R$ ' + (n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
      if (kpiVolume) kpiVolume.textContent = fmtMoeda(totals.total_resgatado || 0);
      if (kpiClientes) kpiClientes.textContent = (clientes.data?.data?.length || clientes.data?.data?.total || 0).toString();
      if (kpiResgates) kpiResgates.textContent = (totals.total_resgatado || 0).toString();

      if (movDistribuido) movDistribuido.textContent = (totals.total_distribuido || 0).toLocaleString('pt-BR');
      if (movResgatado) movResgatado.textContent = (totals.total_resgatado || 0).toLocaleString('pt-BR');
      if (movClientes) movClientes.textContent = (totals.total_clientes || 0).toLocaleString('pt-BR');
      if (movMsg) movMsg.textContent = 'Dados dos últimos 30 dias';

      const listaPromos = promos.data?.data || promos.data || [];
      if (campanhasBox) {
        campanhasBox.innerHTML = '';
        if (!listaPromos.length) {
          if (campanhasEmpty) campanhasEmpty.classList.remove('hidden');
        } else {
          if (campanhasEmpty) campanhasEmpty.classList.add('hidden');
          listaPromos.slice(0, 4).forEach((p) => {
            const card = document.createElement('div');
            card.className = 'bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm flex';
            const img = p.imagem_url || p.imagem || '/img/placeholder-promo.jpg';
            const statusAtivo = !(p.status === 'pausada' || p.ativo === false);
            const status = statusAtivo ? 'Ativa' : 'Pausada';
            card.innerHTML = `
              <div class="w-24 h-24 flex-shrink-0">
                <img alt="${p.nome || 'Promoção'}" class="w-full h-full object-cover" src="${img}"/>
              </div>
              <div class="p-4 flex flex-col justify-between flex-grow">
                <div>
                  <div class="flex justify-between items-start">
                    <h4 class="font-headline font-bold text-sm text-on-surface">${p.nome || 'Promoção'}</h4>
                    <span class="glass-badge px-2 py-0.5 rounded-full text-[9px] font-bold text-primary uppercase">${status}</span>
                  </div>
                  <p class="text-xs text-on-surface-variant line-clamp-2">${p.descricao || ''}</p>
                </div>
                <div class="flex items-center justify-between mt-2 text-[10px] text-on-surface-variant">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full ${statusAtivo ? 'bg-[#00C2D1]' : 'bg-outline'}"></span>
                    <span class="font-label font-bold uppercase">${status}</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">calendar_today</span>
                    <span>${p.validade || p.fim_vigencia || ''}</span>
                  </div>
                </div>
              </div>`;
            card.addEventListener('click', () => (window.location.href = '/gest_o_de_ofertas_parceiro.html'));
            campanhasBox.appendChild(card);
          });
        }
      }

      document.getElementById('empresaNotifBtn')?.addEventListener('click', () => {
        ui.message('Notificações da empresa serão exibidas aqui em breve.', 'info');
      });
    },


    async parceiros() {
      if (!(await auth.guard(['cliente']))) return;

      const grid = document.getElementById('partners-grid');
      const searchInput = document.getElementById('parceiroBusca');
      const emptyMsg = document.getElementById('partners-empty');
      const loading = document.getElementById('partners-loading');

      const renderCards = (lista = []) => {
        if (!grid) return;
        grid.innerHTML = '';
        if (!lista.length) {
          emptyMsg?.classList.remove('hidden');
          return;
        }
        emptyMsg?.classList.add('hidden');
        const tpl = (e) => `
          <article class="bg-surface-container-lowest rounded-xl p-4 flex flex-col gap-4 shadow-[0_8px_32px_rgba(11,31,58,0.06)] hover:bg-surface-container-high transition-colors">
            <div class="flex gap-4">
              <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container">
                <img class="w-full h-full object-cover" src="${e.logo || '/assets/img/logo.png'}" alt="${e.nome || 'Parceiro'}" loading="lazy" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start gap-2">
                  <div>
                    <p class="font-label text-label-sm text-tertiary font-bold tracking-wider mb-1 uppercase">${e.categoria || e.ramo || 'Parceiro'}</p>
                    <h3 class="font-headline font-bold text-title-md text-on-surface truncate">${e.nome || ''}</h3>
                  </div>
                </div>
                <div class="inline-flex items-center gap-1.5 mt-2 px-2 py-1 bg-secondary-container/30 rounded-lg border border-secondary-container/50">
                  <span class="material-symbols-outlined text-secondary text-sm" data-icon="stars" style="font-variation-settings: 'FILL' 1;">stars</span>
                  <span class="text-secondary font-bold text-xs">${e.points_multiplier ? `${e.points_multiplier}x pontos` : 'Parceiro'}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-surface-container">
              <div class="flex items-center gap-1 text-outline text-xs">
                <span class="material-symbols-outlined text-xs" data-icon="location_on">location_on</span>
                <span>${e.endereco || "—"}</span>
              </div>
              <a class="bg-primary text-on-primary px-4 py-2 rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity" href="/detalhe_do_parceiro.html?id=${e.id}">Ver parceiro</a>
            </div>
          </article>`;
        grid.innerHTML = lista.map(tpl).join('');
      };

      const load = async (busca = '') => {
        loading?.classList.remove('hidden');
        const qs = busca ? `?busca=${encodeURIComponent(busca)}` : '';
        const { data } = await api.request(`/cliente/empresas${qs}`);
        loading?.classList.add('hidden');
        const lista = data?.data || data || [];
        renderCards(lista);
      };

      const triggerLoad = () => load(searchInput?.value || '');
      searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          triggerLoad();
        }
      });

      await load();
      return;
    },

    async detalheParceiro() {
      if (!(await auth.guard(['cliente']))) return;
      const empresaId = new URLSearchParams(window.location.search).get('id');
      if (!empresaId) return ui.setPageState('empty', 'Nenhuma empresa selecionada.');

      ui.setPageState('loading', 'Carregando estabelecimento...');
      const detalhe = await api.request(`/empresas/${empresaId}`, {}, { requireAuth: false });
      const produtos = await api.request(`/empresas/${empresaId}/produtos`, {}, { requireAuth: false });
      const promos = await api.request(`/cliente/promocoes?empresa_id=${empresaId}`);
      const info = detalhe.data?.data || detalhe.data;
      ui.clearPageState();

      const heroName = document.getElementById('partner-name');
      const heroCat = document.getElementById('partner-category');
      const heroLogo = document.getElementById('partner-logo');
      const heroBadge = document.getElementById('partner-badge');
      const heroDist = document.getElementById('partner-distance');

      if (info) {
        if (heroName) heroName.textContent = info.nome || 'Parceiro';
        if (heroCat) heroCat.textContent = (info.categoria || info.ramo || 'Categoria').toUpperCase();
        if (heroLogo && info.logo) heroLogo.setAttribute('src', info.logo);
        if (heroBadge) heroBadge.textContent = info.points_multiplier ? `${info.points_multiplier}x pontos` : 'Parceiro';
        if (heroDist) heroDist.textContent = info.endereco || '—';
      }

      const promoList = promos.data?.data || promos.data || [];
      const promoBox = document.getElementById('promos-list');
      const promoEmpty = document.getElementById('promos-empty');
      const promoLoad = document.getElementById('promos-loading');
      promoLoad?.classList.add('hidden');
      if (promoList.length && promoBox) {
        promoEmpty?.classList.add('hidden');
        const tpl = (p) => `
          <article class="bg-surface-container-lowest rounded-xl p-4 flex justify-between gap-3 shadow-[0_6px_20px_rgba(11,31,58,0.06)]">
            <div class="space-y-1">
              <p class="font-label text-label-sm text-tertiary font-bold uppercase">Promoção</p>
              <h4 class="font-headline font-bold text-title-sm">${p.titulo || p.nome || 'Promoção'}</h4>
              <p class="text-on-surface-variant text-sm">${p.descricao || ''}</p>
            </div>
            <div class="text-right min-w-[80px]">
              ${p.desconto ? `<span class=\"text-primary font-bold\">${p.desconto}% OFF</span>` : ''}
              ${p.status ? `<p class=\"text-xs text-outline mt-1\">${p.status}</p>` : ''}
            </div>
          </article>`;
        promoBox.innerHTML = promoList.map(tpl).join('');
      } else {
        promoEmpty?.classList.remove('hidden');
      }

      const listaProdutos = produtos.data?.data || produtos.data || [];
      const prodBox = document.getElementById('products-list');
      const prodEmpty = document.getElementById('products-empty');
      const prodLoad = document.getElementById('products-loading');
      prodLoad?.classList.add('hidden');
      if (listaProdutos.length && prodBox) {
        prodEmpty?.classList.add('hidden');
        const tplP = (p) => `
          <article class="bg-surface-container-lowest rounded-xl p-4 flex justify-between items-start shadow-[0_6px_20px_rgba(11,31,58,0.06)]">
            <div>
              <p class="font-headline font-semibold">${p.nome || p.titulo || 'Produto'}</p>
              <p class="text-on-surface-variant text-sm">${p.descricao || ''}</p>
            </div>
            ${p.preco ? `<span class=\"font-bold text-primary\">R$ ${Number(p.preco).toFixed(2)}</span>` : ''}
          </article>`;
        prodBox.innerHTML = listaProdutos.map(tplP).join('');
      } else {
        prodEmpty?.classList.remove('hidden');
      }

      return;
    },

    async recompensas() {
      if (!(await auth.guard(['cliente']))) return;
      ui.setPageState('loading', 'Carregando recompensas...');
      const { data: pontosResp } = await api.request('/pontos/meus-dados');
      const { data: cuponsResp } = await api.request('/pontos/meus-cupons');
      ui.clearPageState();

      render.summary('Saldo e cupons', [
        { label: 'Pontos', value: pontosResp?.data?.pontos_total },
        { label: 'Pendentes', value: pontosResp?.data?.pontos_pendentes },
        { label: 'Cupons ativos', value: cuponsResp?.data?.filter((c) => c.status === 'active')?.length || 0 },
      ]);

      const cupons = cuponsResp?.data || [];
      if (cupons.length) {
        render.section(
          'Meus cupons',
          cupons
            .map(
              (c) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${c.descricao || c.codigo}</p>
                <p class="text-on-surface-variant">VÃ¡lido atÃ©: ${c.expira_em ? new Date(c.expira_em).toLocaleDateString('pt-BR') : 'â€”'}</p>
              </div>
              <span class="font-semibold ${c.status === 'used' ? 'text-amber-600' : 'text-primary'}">${c.status}</span>
            </div>`
            )
            .join('')
        );
      } else {
        ui.setPageState('empty', 'Nenhum cupom disponÃ­vel ainda.');
      }

      // FormulÃ¡rio simples de resgate
      const host = document.querySelector('main') || document.body;
      const formWrap = document.createElement('section');
      formWrap.className = 'max-w-6xl mx-auto px-4 pt-4';
      formWrap.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <h3 class="text-lg font-semibold text-on-surface">Resgatar recompensa</h3>
          <div class="grid gap-3 md:grid-cols-3">
            <input id="resgateDescricao" class="border rounded-lg px-3 py-2" placeholder="DescriÃ§Ã£o" />
            <input id="resgateTipo" class="border rounded-lg px-3 py-2" placeholder="Tipo (ex: desconto)" />
            <input id="resgatePontos" type="number" class="border rounded-lg px-3 py-2" placeholder="Custo em pontos" />
          </div>
          <button id="resgatarBtn" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold">Resgatar</button>
        </div>`;
      host.prepend(formWrap);
      formWrap.querySelector('#resgatarBtn')?.addEventListener('click', async () => {
        const descricao = formWrap.querySelector('#resgateDescricao').value;
        const tipo = formWrap.querySelector('#resgateTipo').value || 'voucher';
        const pontos = Number(formWrap.querySelector('#resgatePontos').value);
        if (!descricao || !pontos) return ui.message('Preencha descriÃ§Ã£o e custo em pontos.', 'warning');
        const { res, data } = await api.request('/pontos/resgatar', {
          method: 'POST',
          body: JSON.stringify({ recompensa_tipo: tipo, custo_pontos: pontos, descricao }),
        });
        if (res.ok && data?.success) {
          ui.message('Resgate realizado!', 'success');
          location.reload();
        } else {
          ui.message(data?.message || 'Falha ao resgatar.', 'error');
        }
      });
    },

        async historico() {
      if (!(await auth.guard(['cliente']))) return;
      const loading = document.getElementById('timeline-loading');
      const empty = document.getElementById('timeline-empty');
      const list = document.getElementById('timeline-list');
      const summaryText = document.getElementById('timeline-summary-text');
      const filterButtons = document.querySelectorAll('[data-filter]');

      const setActiveFilter = (f) => {
        filterButtons.forEach((btn) => {
          if (btn.dataset.filter === f) btn.classList.add('bg-primary', 'text-on-primary', 'shadow-md');
          else btn.classList.remove('bg-primary', 'text-on-primary', 'shadow-md');
        });
      };

      const render = (items, filter = 'todas') => {
        if (!list) return;
        list.innerHTML = '';
        const filtered = items.filter((i) => {
          const tipo = (i.tipo || '').toLowerCase();
          if (filter === 'ganhos') return tipo.includes('gan') || (i.pontos || 0) > 0;
          if (filter === 'resgates') return tipo.includes('resg') || (i.pontos || 0) < 0;
          if (filter === 'cupons') return tipo.includes('cup');
          return true;
        });
        if (!filtered.length) {
          empty?.classList.remove('hidden');
          return;
        }
        empty?.classList.add('hidden');

        const grouped = filtered.reduce((acc, i) => {
          const d = i.created_at ? new Date(i.created_at) : new Date();
          const label = d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
          (acc[label] = acc[label] || []).push(i);
          return acc;
        }, {});

        const iconFor = (tipo) => {
          const t = (tipo || '').toLowerCase();
          if (t.includes('resg')) return { icon: 'redeem', cls: 'text-secondary', bg: 'bg-secondary-container/30' };
          if (t.includes('cup')) return { icon: 'confirmation_number', cls: 'text-tertiary', bg: 'bg-tertiary-container/30' };
          return { icon: 'shopping_bag', cls: 'text-primary', bg: 'bg-primary-container/20' };
        };

        Object.entries(grouped).forEach(([day, arr]) => {
          const cards = arr
            .map((i) => {
              const { icon, cls, bg } = iconFor(i.tipo);
              const pts = i.pontos || 0;
              const sign = pts > 0 ? '+' : '';
              return `
              <div class="bg-surface-container-lowest p-4 rounded-xl flex items-center justify-between hover:bg-surface-container-high transition-all active:scale-[0.98]">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-full ${bg} flex items-center justify-center ${cls}">
                    <span class="material-symbols-outlined" data-icon="${icon}">${icon}</span>
                  </div>
                  <div>
                    <p class="font-headline font-bold text-on-surface text-[15px]">${i?.empresa?.nome || 'Empresa'}</p>
                    <p class="text-on-surface-variant text-xs">${i.descricao || ''}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-headline font-extrabold ${pts >= 0 ? 'text-primary' : 'text-secondary'} text-base">${sign}${pts} pts</p>
                  ${i.status ? `<span class="text-[10px] font-bold uppercase text-tertiary">${i.status}</span>` : ''}
                </div>
              </div>`;
            })
            .join('');

          list.innerHTML += `
            <div class="space-y-3">
              <h3 class="font-label text-[11px] font-bold text-outline uppercase tracking-widest flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-secondary"></span>${day}
              </h3>
              ${cards}
            </div>`;
        });
      };

      ui.setPageState('loading', 'Carregando histórico...');
      const { data } = await api.request('/pontos/historico');
      loading?.classList.add('hidden');
      const itens = data?.data?.data || data?.data || [];
      if (!itens.length) {
        ui.setPageState('empty', 'Nenhum histórico encontrado.');
        return;
      }
      ui.clearPageState();
      if (summaryText) summaryText.textContent = `Você tem ${itens.length} atividades registradas.`;
      render(itens, 'todas');
      setActiveFilter('todas');
      filterButtons.forEach((btn) =>
        btn.addEventListener('click', () => {
          const f = btn.dataset.filter || 'todas';
          setActiveFilter(f);
          render(itens, f);
        })
      );
    },

        async perfil() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      ui.setPageState('loading', 'Carregando perfil...');
      const user = await auth.ensure();
      let dados = {};
      if (user?.perfil === 'cliente') {
        try {
          const resp = await api.request('/pontos/meus-dados');
          dados = resp.data?.data || {};
        } catch (_) {}
      }
      ui.clearPageState();

      const heroName = document.getElementById('hero-name');
      const heroLevel = document.getElementById('hero-level');
      const heroStatus = document.getElementById('hero-status');
      const heroPoints = document.getElementById('hero-points');
      const heroProgressText = document.getElementById('hero-progress-text');
      const heroProgressBar = document.getElementById('hero-progress-bar');

      const pontos = dados.pontos_total ?? user?.pontos ?? 0;
      const pend = dados.pontos_pendentes ?? 0;
      const nextTarget = Math.max(1000, pontos + 2000);
      const perc = Math.min(100, Math.round((pontos / nextTarget) * 100));

      if (heroName) heroName.textContent = user?.name || user?.nome || 'Usuário';
      if (heroLevel) heroLevel.textContent = user?.perfil ? user.perfil.toUpperCase() : 'MEMBRO';
      if (heroStatus) heroStatus.textContent = user?.status || 'Ativo';
      if (heroPoints) heroPoints.textContent = pontos;
      if (heroProgressText) heroProgressText.textContent = `Faltam ${nextTarget - pontos} para o próximo nível`;
      if (heroProgressBar) heroProgressBar.style.width = `${perc}%`;

      const pf = (id) => document.getElementById(id);
      pf('pfNome')?.setAttribute('value', user?.name || user?.nome || '');
      pf('pfEmail')?.setAttribute('value', user?.email || '');
      pf('pfTelefone')?.setAttribute('value', user?.telefone || '');
      pf('pfCpf')?.setAttribute('value', user?.cpf || '');
      pf('pfNascimento')?.setAttribute('value', user?.data_nascimento || '');

      pf('pfSalvar')?.addEventListener('click', async () => {
        const payload = {
          name: pf('pfNome')?.value,
          email: pf('pfEmail')?.value,
          telefone: pf('pfTelefone')?.value,
          cpf: pf('pfCpf')?.value,
          data_nascimento: pf('pfNascimento')?.value,
        };
        const { res, data } = await api.request('/perfil', { method: 'PUT', body: JSON.stringify(payload) });
        if (res.ok && data?.success) {
          ui.message('Perfil atualizado.', 'success');
          auth.save(auth.getStored().token, data.data);
        } else {
          ui.message(data?.message || 'Erro ao atualizar perfil.', 'error');
        }
      });

      pf('pwSalvar')?.addEventListener('click', async () => {
        const payload = {
          current_password: pf('pwAtual')?.value,
          password: pf('pwNova')?.value,
          password_confirmation: pf('pwConf')?.value,
        };
        ui.setPageState('loading', 'Atualizando senha...');
        const { res, data } = await api.request('/auth/change-password', { method: 'POST', body: JSON.stringify(payload) });
        ui.clearPageState();
        if (res.ok && data?.success) ui.message('Senha alterada.', 'success');
        else ui.message(data?.message || 'Erro ao alterar senha.', 'error');
      });

      document.getElementById('logoutBtn')?.addEventListener('click', () => {
        auth.logout();
        ui.message('Sessão encerrada.', 'success');
        setTimeout(() => (window.location.href = '/entrar.html'), 400);
      });
    },

    async validarResgate() {
      if (!(await auth.guard(['empresa', 'admin']))) return;
      const input = document.getElementById('cupomId');
      const btn = document.getElementById('usarCupomBtn');
      const list = document.getElementById('validacoesRecentes');
      const empty = document.getElementById('validacoesEmpty');
      if (!input || !btn) return;

      const renderItem = (item) => {
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-4 bg-surface-container-lowest rounded-xl shadow-[0_2px_8px_rgba(11,31,58,0.04)]';
        div.innerHTML = `
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-tertiary/10 flex items-center justify-center text-tertiary">
              <span class="material-symbols-outlined">check_circle</span>
            </div>
            <div>
              <p class="text-sm font-bold text-on-surface">${item.cliente || 'Cliente'}</p>
              <p class="text-[10px] text-on-surface-variant uppercase">Resgate: ${item.beneficio || item.codigo}</p>
            </div>
          </div>
          <p class="text-[10px] font-semibold text-outline">${item.hora}</p>`;
        return div;
      };

      const pushItem = (item) => {
        if (empty) empty.classList.add('hidden');
        list?.prepend(renderItem(item));
      };

      btn.addEventListener('click', async () => {
        const codigo = input.value.trim();
        if (!codigo) return ui.message('Informe o código do cupom.', 'warning');
        btn.disabled = true;
        btn.classList.add('opacity-60');
        const { res, data } = await api.request(`/pontos/usar-cupom/${encodeURIComponent(codigo)}`, { method: 'POST' });
        btn.disabled = false;
        btn.classList.remove('opacity-60');
        if (res.ok && data?.success) {
          ui.message('Cupom validado/uso registrado.', 'success');
          const info = data.data || {};
          pushItem({
            cliente: info.cliente_nome || info.cliente || 'Cliente',
            beneficio: info.promocao || info.recompensa || info.cupom || 'Cupom',
            codigo: codigo,
            hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
          });
        } else {
          ui.message(data?.message || 'NÃ£o foi possÃ­vel usar o cupom.', 'error');
        }
      });
    },
  };

  // ---------------------- PÃ¡ginas: Estabelecimento ---------------------- //
  const empresa = {
    async dashboard() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando painel da empresa...');
      const [promos, clientes, relatorio, resgates] = await Promise.all([
        api.request('/empresa/promocoes'),
        api.request('/empresa/clientes'),
        api.request('/empresa/relatorio-pontos'),
        api.request('/empresa/resgates'),
      ]);

      const kpiVolume = document.getElementById('kpiVolume');
      const kpiClientes = document.getElementById('kpiClientes');
      const kpiResgates = document.getElementById('kpiResgates');
      const campanhasBox = document.getElementById('campanhasAtivas');
      const campanhasEmpty = document.getElementById('campanhasEmpty');
      const movDistribuido = document.getElementById('movDistribuido');
      const movResgatado = document.getElementById('movResgatado');
      const movClientes = document.getElementById('movClientes');
      const movMsg = document.getElementById('movMsg');
      ui.clearPageState();

      const totals = relatorio.data?.data?.totais || {};
      const fmtMoeda = (n) => 'R$ ' + (n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
      if (kpiVolume) kpiVolume.textContent = fmtMoeda(totals.total_resgatado || 0);
      if (kpiClientes) kpiClientes.textContent = (clientes.data?.data?.length || clientes.data?.data?.total || 0).toString();
      if (kpiResgates) kpiResgates.textContent = (totals.total_resgatado || 0).toString();

      if (movDistribuido) movDistribuido.textContent = (totals.total_distribuido || 0).toLocaleString('pt-BR');
      if (movResgatado) movResgatado.textContent = (totals.total_resgatado || 0).toLocaleString('pt-BR');
      if (movClientes) movClientes.textContent = (totals.total_clientes || 0).toLocaleString('pt-BR');
      if (movMsg) movMsg.textContent = 'Dados dos últimos 30 dias';

      const listaPromos = promos.data?.data || promos.data || [];
      if (campanhasBox) {
        campanhasBox.innerHTML = '';
        if (!listaPromos.length) {
          if (campanhasEmpty) campanhasEmpty.classList.remove('hidden');
        } else {
          if (campanhasEmpty) campanhasEmpty.classList.add('hidden');
          listaPromos.slice(0, 4).forEach((p) => {
            const card = document.createElement('div');
            card.className = 'bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm flex';
            const img = p.imagem_url || p.imagem || '/img/placeholder-promo.jpg';
            const statusAtivo = !(p.status === 'pausada' || p.ativo === false);
            const status = statusAtivo ? 'Ativa' : 'Pausada';
            card.innerHTML = `
              <div class="w-24 h-24 flex-shrink-0">
                <img alt="${p.nome || 'Promoção'}" class="w-full h-full object-cover" src="${img}"/>
              </div>
              <div class="p-4 flex flex-col justify-between flex-grow">
                <div>
                  <div class="flex justify-between items-start">
                    <h4 class="font-headline font-bold text-sm text-on-surface">${p.nome || 'Promoção'}</h4>
                    <span class="glass-badge px-2 py-0.5 rounded-full text-[9px] font-bold text-primary uppercase">${status}</span>
                  </div>
                  <p class="text-xs text-on-surface-variant line-clamp-2">${p.descricao || ''}</p>
                </div>
                <div class="flex items-center justify-between mt-2 text-[10px] text-on-surface-variant">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full ${statusAtivo ? 'bg-[#00C2D1]' : 'bg-outline'}"></span>
                    <span class="font-label font-bold uppercase">${status}</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">calendar_today</span>
                    <span>${p.validade || p.fim_vigencia || ''}</span>
                  </div>
                </div>
              </div>`;
            card.addEventListener('click', () => (window.location.href = '/gest_o_de_ofertas_parceiro.html'));
            campanhasBox.appendChild(card);
          });
        }
      }

      document.getElementById('empresaNotifBtn')?.addEventListener('click', () => {
        ui.message('Notificações da empresa serão exibidas aqui em breve.', 'info');
      });
    },


    async clientes() {
      if (!(await auth.guard(['empresa']))) return;
      const host = document.querySelector('main') || document.body;
      const search = document.createElement('section');
      search.className = 'max-w-5xl mx-auto px-4 pt-4';
      search.innerHTML = `
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <h3 class="text-lg font-semibold text-on-surface">Clientes fidelizados</h3>
          <div class="flex gap-2 items-center text-sm">
            <input id="cliBusca" class="border rounded-lg px-3 py-2" placeholder="Buscar nome ou email" />
            <button id="cliBuscarBtn" class="px-3 py-2 bg-primary text-white rounded-lg">Buscar</button>
          </div>
        </div>`;
      host.prepend(search);

      const load = async (term = '') => {
        ui.setPageState('loading', 'Carregando clientes...');
        const qs = term ? `?busca=${encodeURIComponent(term)}` : '';
        const { data } = await api.request(`/empresa/clientes${qs}`);
        const lista = data?.data?.data || data?.data || data || [];
        if (!lista.length) return ui.setPageState('empty', 'Nenhum cliente fidelizado ainda.');
        ui.clearPageState();
        render.section(
          'Clientes fidelizados',
          lista
            .map(
              (c) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${c.name || c.nome}</p>
                <p class="text-on-surface-variant">${c.email || ''}</p>
              </div>
              <div class="text-right">
                <p class="text-primary font-semibold">${c.total_ganho || 0} pts</p>
                <p class="text-xs text-on-surface-variant">Ãšltima visita: ${c.ultima_visita ? new Date(c.ultima_visita).toLocaleDateString('pt-BR') : 'â€”'}</p>
              </div>
            </div>`
            )
            .join('')
        );
      };

      search.querySelector('#cliBuscarBtn')?.addEventListener('click', () => {
        const term = search.querySelector('#cliBusca').value;
        load(term);
      });

      await load();
    },

    async promocoes() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando promoÃ§Ãµes...');
      const { data } = await api.request('/empresa/promocoes');
      const lista = data?.data || data || [];
      if (!lista.length) ui.setPageState('empty', 'Nenhuma promoÃ§Ã£o cadastrada ainda.');
      else ui.clearPageState();

      if (lista.length) {
        const wrap = render.section(
          'PromoÃ§Ãµes',
          lista
            .map(
              (p) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm" data-promocao="${p.id}">
              <div>
                <p class="font-semibold">${p.titulo || p.nome}</p>
                <p class="text-on-surface-variant">${p.status || 'ativa'}</p>
              </div>
              <div class="flex gap-2">
                <button class="px-3 py-1 rounded-lg bg-primary text-white text-xs" data-action="ativar">Ativar</button>
                <button class="px-3 py-1 rounded-lg bg-amber-500 text-white text-xs" data-action="pausar">Pausar</button>
              </div>
            </div>`
            )
            .join('')
        );
        wrap.querySelectorAll('[data-promocao]').forEach((row) => {
          const id = row.getAttribute('data-promocao');
          row.querySelector('[data-action="ativar"]')?.addEventListener('click', () => empresa.togglePromocao(id, 'ativar'));
          row.querySelector('[data-action="pausar"]')?.addEventListener('click', () => empresa.togglePromocao(id, 'pausar'));
        });
      }

      // FormulÃ¡rio rÃ¡pido de criaÃ§Ã£o (JSON â€“ EmpresaAPIController)
      const host = document.querySelector('main') || document.body;
      let editingId = null;
      const form = document.createElement('section');
      form.className = 'max-w-6xl mx-auto px-4 pt-4';
      form.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-on-surface" id="promoFormTitle">Nova promoÃ§Ã£o</h3>
            <button id="promoReset" class="text-sm text-on-surface-variant underline">Limpar</button>
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <input id="promoTitulo" class="border rounded-lg px-3 py-2" placeholder="TÃ­tulo" />
            <input id="promoDesconto" class="border rounded-lg px-3 py-2" placeholder="Desconto (%)" type="number" min="0" max="100" />
          </div>
          <textarea id="promoDescricao" class="border rounded-lg px-3 py-2 w-full" rows="3" placeholder="DescriÃ§Ã£o"></textarea>
          <input id="promoImagem" type="file" accept="image/*" class="border rounded-lg px-3 py-2 w-full" />
          <input id="promoImagemUrl" type="url" class="border rounded-lg px-3 py-2 w-full" placeholder="Ou URL da imagem" />
          <button id="promoCriarBtn" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold">Criar</button>
        </div>`;
      host.prepend(form);

      form.querySelector('#promoReset')?.addEventListener('click', (e) => {
        e.preventDefault();
        editingId = null;
        form.querySelector('#promoFormTitle').textContent = 'Nova promoÃ§Ã£o';
        form.querySelector('#promoCriarBtn').textContent = 'Criar';
        form.querySelector('#promoTitulo').value = '';
        form.querySelector('#promoDesconto').value = '';
        form.querySelector('#promoDescricao').value = '';
        form.querySelector('#promoImagem').value = '';
        form.querySelector('#promoImagemUrl').value = '';
      });

      form.querySelector('#promoCriarBtn')?.addEventListener('click', async () => {
        const titulo = form.querySelector('#promoTitulo').value;
        const desconto = Number(form.querySelector('#promoDesconto').value);
        const descricao = form.querySelector('#promoDescricao').value;
        const imagemFile = form.querySelector('#promoImagem').files[0];
        const imagemUrl = form.querySelector('#promoImagemUrl').value;
        if (!titulo || Number.isNaN(desconto)) return ui.message('Informe tÃ­tulo e desconto.', 'warning');

        let body;
        let headers = {};
        if (imagemFile) {
          body = new FormData();
          body.append('titulo', titulo);
          body.append('desconto', desconto);
          body.append('descricao', descricao);
          body.append('imagem', imagemFile);
        } else {
          const payload = { titulo, desconto, descricao };
          if (imagemUrl) payload.imagem_url = imagemUrl;
          body = JSON.stringify(payload);
          headers['Content-Type'] = 'application/json';
        }

        const path = editingId ? `/empresa/promocoes/${editingId}` : '/empresa/promocoes';
        const method = editingId ? 'PUT' : 'POST';
        const { res, data } = await api.request(path, { method, body, headers });
        if (res.ok && data?.success) {
          ui.message(editingId ? 'PromoÃ§Ã£o atualizada.' : 'PromoÃ§Ã£o criada.', 'success');
          location.reload();
        } else {
          ui.message(data?.message || 'Erro ao salvar promoÃ§Ã£o.', 'error');
        }
      });

      // Preencher form para ediÃ§Ã£o
      document.querySelectorAll('[data-promocao]').forEach((row, idx) => {
        const p = lista[idx];
        row.addEventListener('click', (ev) => {
          if (ev.target?.dataset?.action) return; // botÃµes de ativar/pausar tratam separado
          editingId = p.id;
          form.querySelector('#promoFormTitle').textContent = `Editar promoÃ§Ã£o #${p.id}`;
          form.querySelector('#promoCriarBtn').textContent = 'Salvar';
          form.querySelector('#promoTitulo').value = p.titulo || p.nome || '';
          form.querySelector('#promoDesconto').value = p.desconto || 0;
          form.querySelector('#promoDescricao').value = p.descricao || '';
          form.querySelector('#promoImagemUrl').value = p.imagem && !p.imagem.startsWith('/storage') ? p.imagem : '';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      });

      // HistÃ³rico detalhado de resgates
      const hist = document.createElement('section');
      hist.className = 'max-w-6xl mx-auto px-4 pt-6';
      hist.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <h3 class="text-lg font-semibold text-on-surface">HistÃ³rico de resgates</h3>
            <div class="flex flex-wrap gap-2 text-sm items-center">
              <select id="resgStatus" class="border rounded-lg px-3 py-2">
                <option value="">Todos</option>
                <option value="usado">Usados</option>
                <option value="pendente">Pendentes</option>
              </select>
              <input id="resgIni" type="date" class="border rounded-lg px-3 py-2" />
              <input id="resgFim" type="date" class="border rounded-lg px-3 py-2" />
              <button id="resgFiltrar" class="px-3 py-2 bg-primary text-white rounded-lg">Filtrar</button>
            </div>
          </div>
          <div id="resgLista" class="divide-y divide-surface-variant/50"></div>
        </div>`;
      host.append(hist);

      const loadResgates = async () => {
        const status = hist.querySelector('#resgStatus').value;
        const di = hist.querySelector('#resgIni').value;
        const df = hist.querySelector('#resgFim').value;
        const params = new URLSearchParams();
        if (status) params.set('status', status);
        if (di) params.set('data_inicio', di);
        if (df) params.set('data_fim', df);
        ui.setPageState('loading', 'Carregando histÃ³rico de resgates...');
        const { data } = await api.request(`/empresa/resgates?${params.toString()}`);
        ui.clearPageState();
        const items = data?.data?.data || data?.data || data || [];
        const box = hist.querySelector('#resgLista');
        if (!items.length) {
          box.innerHTML = '<p class="text-on-surface-variant text-sm">Nenhum resgate encontrado.</p>';
          return;
        }
        box.innerHTML = items
          .map(
            (r) => `
          <div class="py-3 flex items-center justify-between text-sm">
            <div>
              <p class="font-semibold">${r.promocao || 'PromoÃ§Ã£o'} â€” ${r.cliente || ''}</p>
              <p class="text-on-surface-variant">${r.codigo || ''} Â· ${new Date(r.created_at).toLocaleString('pt-BR')}</p>
            </div>
            <div class="text-right">
              <p class="font-semibold text-primary">${r.status || ''}</p>
              ${r.data_uso ? `<p class="text-on-surface-variant text-xs">Usado em ${new Date(r.data_uso).toLocaleString('pt-BR')}</p>` : ''}
            </div>
          </div>`
          )
          .join('');
      };

      hist.querySelector('#resgFiltrar').addEventListener('click', loadResgates);
      loadResgates();
    },

    async togglePromocao(id, action) {
      const endpoint = action === 'ativar' ? `/empresa/promocoes/${id}/ativar` : `/empresa/promocoes/${id}/pausar`;
      const { res, data } = await api.request(endpoint, { method: 'PATCH' });
      if (res.ok && data?.success !== false) {
        ui.message('PromoÃ§Ã£o atualizada.', 'success');
        location.reload();
      } else {
        ui.message(data?.message || 'Erro ao atualizar promoÃ§Ã£o.', 'error');
      }
    },
  };

  // ---------------------- PÃ¡ginas: Admin ---------------------- //
  const admin = {
    async dashboard() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando dashboard admin...');
      const [stats, recent, empresas] = await Promise.all([
        api.request('/admin/dashboard-stats'),
        api.request('/admin/recent-activity'),
        api.request('/empresas', {}, { requireAuth: false }),
      ]);
      ui.clearPageState();

      render.summary('Admin Master', [
        { label: 'Empresas', value: empresas.data?.data?.length || empresas.data?.data?.total || 'â€”' },
        { label: 'Eventos recentes', value: recent.data?.data?.length || 0 },
        { label: 'UsuÃ¡rio', value: auth.getStored().user?.email },
      ]);

      const atividades = recent.data?.data || [];
      if (atividades.length) {
        render.section(
          'Atividades recentes',
          atividades
            .slice(0, 10)
            .map(
              (a) => `
            <div class="px-4 py-3 text-sm">
              <p class="font-semibold">${a.titulo || a.type || 'Evento'}</p>
              <p class="text-on-surface-variant">${a.descricao || a.message || ''}</p>
            </div>`
            )
            .join('')
        );
      } else {
        render.section('Atividades recentes', '<p class="text-sm text-on-surface-variant">Sem atividades recentes.</p>');
      }

      if (stats.data?.data) {
        const s = stats.data.data;
        render.section(
          'Indicadores',
          Object.entries(s)
            .map(
              ([k, v]) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <p class="font-semibold">${k}</p>
              <span class="font-semibold text-primary">${typeof v === 'number' ? v : JSON.stringify(v)}</span>
            </div>`
            )
            .join('')
        );
      }

      await notifications.load('NotificaÃ§Ãµes');
    },

    async empresas() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando estabelecimentos...');
      const { data } = await api.request('/empresas', {}, { requireAuth: false });
      const lista = data?.data || data || [];
      if (!lista.length) {
        render.section('Estabelecimentos', '<p class="text-sm text-on-surface-variant">Nenhum estabelecimento cadastrado.</p>');
        return;
      }
      ui.clearPageState();
      render.section(
        'Estabelecimentos',
        lista
          .map(
            (e) => `
          <div class="px-4 py-3 flex items-center justify-between text-sm">
            <div>
              <p class="font-semibold">${e.nome}</p>
              <p class="text-on-surface-variant">${e.categoria || ''}</p>
            </div>
            <span class="font-semibold ${e.ativo === false ? 'text-amber-600' : 'text-primary'}">${e.ativo === false ? 'Inativo' : 'Ativo'}</span>
          </div>`
          )
          .join('')
      );
    },

    async usuarios() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando usuÃ¡rios...');
      const { res, data } = await api.request('/admin/users-report');
      if (!res.ok) return ui.setPageState('error', 'Endpoint /admin/users-report indisponÃ­vel ou bloqueado.');
      const lista = data?.data || [];
      if (!lista.length) {
        render.section('UsuÃ¡rios', '<p class="text-sm text-on-surface-variant">Nenhum usuÃ¡rio retornado.</p>');
        return;
      }
      ui.clearPageState();
      render.section(
        'UsuÃ¡rios',
        lista
          .map(
            (u) => `
          <div class="px-4 py-3 flex items-center justify-between text-sm">
            <div>
              <p class="font-semibold">${u.name || u.email}</p>
              <p class="text-on-surface-variant">${u.email}</p>
            </div>
              <span class="font-semibold text-primary">${u.perfil || u.role || ''}</span>
          </div>`
          )
          .join('')
      );
    },

    async clientes() {
      return admin.usuarios();
    },

    async relatorios() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando relatÃ³rios...');
      const [stats, checkins] = await Promise.all([
        api.request('/admin/dashboard-stats'),
        api.request('/admin/pontos/estatisticas'),
      ]);
      ui.clearPageState();

      if (stats.data?.data) {
        render.section(
          'Dashboard',
          Object.entries(stats.data.data)
            .map(
              ([k, v]) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <span class="font-semibold">${k}</span>
              <span class="font-semibold text-primary">${typeof v === 'number' ? v : JSON.stringify(v)}</span>
            </div>`
            )
            .join('')
        );
      } else {
        render.section('Dashboard', '<p class="text-sm text-on-surface-variant">Sem dados de relatÃ³rio.</p>');
      }

      if (checkins.data?.data) {
        render.section(
          'Pontos / Check-ins',
          Object.entries(checkins.data.data)
            .map(
              ([k, v]) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <span class="font-semibold">${k}</span>
              <span class="font-semibold text-primary">${v}</span>
            </div>`
            )
            .join('')
        );
      } else {
        ui.message('Sem estatÃ­sticas de pontos disponÃ­veis.', 'warning');
      }
    },
  };

  // ---------------------- Login (pÃºblico) ---------------------- //
  async function handleLogin() {
    const form = document.querySelector('form');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const inputs = form.querySelectorAll('input');
      const email = inputs[0]?.value?.trim();
      const password = inputs[1]?.value;
      if (!email || !password) return ui.message('Informe email e senha.', 'warning');
      ui.setPageState('loading', 'Autenticando...');
      const { res, data } = await api.request(
        '/auth/login',
        { method: 'POST', body: JSON.stringify({ email, password }) },
        { requireAuth: false }
      );
      if (res.ok && data?.success && data?.data?.token) {
        auth.save(data.data.token, data.data.user);
        const target = redirectMap[data.data.user?.perfil] || data.data.redirect_to || '/';
        ui.clearPageState();
        ui.message('Login realizado, redirecionando...', 'success');
        setTimeout(() => (window.location.href = target), 500);
      } else {
        ui.clearPageState();
        ui.message(data?.message || 'NÃ£o foi possÃ­vel entrar.', 'error');
      }
    });
  }

  // ---------------------- Dispatcher ---------------------- //
  const handlers = {
    // PÃºblico / shared
    acessar_conta: handleLogin,
    home_tem_de_tudo: () => {},
    oferta_especial: cliente.detalheParceiro,
    tudo_vibrante: () => {},
    forgot_password: async () => {
      const form = document.querySelector('form');
      form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('fpEmail').value;
        if (!email) return ui.message('Informe o e-mail.', 'warning');
        ui.setPageState('loading', 'Enviando link...');
        const { res, data } = await api.request('/auth/forgot-password', { method: 'POST', body: JSON.stringify({ email }) }, { requireAuth: false });
        ui.clearPageState();
        if (res.ok && data?.success !== false) ui.message('Se o e-mail existir, enviaremos um link.', 'success');
        else ui.message(data?.message || 'Erro ao solicitar recuperaÃ§Ã£o.', 'error');
      });
    },
    reset_password: async () => {
      const params = new URLSearchParams(window.location.search);
      if (params.get('email')) document.getElementById('rpEmail').value = params.get('email');
      if (params.get('token')) document.getElementById('rpToken').value = params.get('token');
      const form = document.querySelector('form');
      form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
          email: document.getElementById('rpEmail').value,
          token: document.getElementById('rpToken').value,
          password: document.getElementById('rpPass').value,
          password_confirmation: document.getElementById('rpPassConf').value,
        };
        ui.setPageState('loading', 'Redefinindo senha...');
        const { res, data } = await api.request('/auth/reset-password', { method: 'POST', body: JSON.stringify(payload) }, { requireAuth: false });
        ui.clearPageState();
        if (res.ok && data?.success !== false) {
          ui.message('Senha redefinida. FaÃ§a login.', 'success');
          setTimeout(() => (window.location.href = '/entrar.html'), 800);
        } else ui.message(data?.message || 'Erro ao redefinir senha.', 'error');
      });
    },

    // Cliente
    meus_pontos: cliente.dashboard,
    parceiros_tem_de_tudo: cliente.parceiros,
    detalhe_do_parceiro: cliente.detalheParceiro,
    recompensas: cliente.recompensas,
    hist_rico_de_uso: cliente.historico,
    meu_perfil: cliente.perfil,
    validar_resgate: cliente.validarResgate,
    criar_conta: async () => {
      const form = document.getElementById('signupForm');
      if (!form) return;
      const perfilSel = document.getElementById('sgPerfil');
      const cnpj = document.getElementById('sgCnpj');
      const end = document.getElementById('sgEndereco');
      const blocoEmpresa = document.getElementById('empresaFields');

      perfilSel?.addEventListener('change', () => {
        if (perfilSel.value === 'empresa') blocoEmpresa.classList.remove('hidden');
        else blocoEmpresa.classList.add('hidden');
      });

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const perfil = perfilSel.value;
        const payload = {
          perfil,
          name: document.getElementById('sgNome').value,
          email: document.getElementById('sgEmail').value,
          telefone: document.getElementById('sgTelefone').value,
          password: document.getElementById('sgSenha').value,
          password_confirmation: document.getElementById('sgSenhaConf').value,
          terms: document.getElementById('sgTerms').checked,
        };
        if (perfil === 'empresa') {
          payload.cnpj = cnpj.value;
          payload.endereco = end.value;
        }
        ui.setPageState('loading', 'Criando conta...');
        const { res, data } = await api.request('/auth/register', {
          method: 'POST',
          body: JSON.stringify(payload),
        }, { requireAuth: false });
        ui.clearPageState();
        if (res.ok && data?.success !== false) {
          ui.message('Conta criada. FaÃ§a login.', 'success');
          setTimeout(() => (window.location.href = '/entrar.html'), 800);
        } else {
          const errs = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
          ui.message(data?.message || errs || 'Erro ao criar conta.', 'error');
        }
      });
    },

    // Empresa
    dashboard_parceiro: empresa.dashboard,
    clientes_fidelizados_loja: empresa.clientes,
    gest_o_de_ofertas_parceiro: empresa.promocoes,
    minhas_campanhas_loja: empresa.promocoes,

    // Admin
    dashboard_admin_master: admin.dashboard,
    gest_o_de_estabelecimentos: admin.empresas,
    gest_o_de_clientes_master: admin.clientes,
    gest_o_de_usu_rios_master: admin.usuarios,
    relat_rios_gerais_master: admin.relatorios,
    banners_e_categorias_master: async () => {
      if (!(await auth.guard(['admin']))) return;
      ui.message('Banners/categorias: nenhum endpoint disponÃ­vel identificado. NecessÃ¡rio backend.', 'warning');
    },
  };

  document.addEventListener('DOMContentLoaded', async () => {
    const handler = handlers[page];
    // Autoregistrar push em pÃ¡ginas logadas principais
    const loggedPages = ['meus_pontos', 'dashboard_parceiro', 'dashboard_admin_master'];
    if (loggedPages.includes(page)) {
      push.register().catch(() => {});
    }
    if (handler) {
      try {
        await handler();
      } catch (err) {
        console.error(err);
        ui.message('Erro ao carregar pÃ¡gina.', 'error');
      }
    }
  });
})();

