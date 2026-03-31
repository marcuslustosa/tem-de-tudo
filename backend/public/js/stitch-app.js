/**
 * Stitch Integration Layer (Tem de Tudo)
 * Objetivo: manter comportamento atual, com código mais organizado e claro.
 * Módulos internos: api, auth, ui, render, pages (cliente/empresa/admin/shared).
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
      pageStateEl.innerHTML = `<div class="border ${palette[type] || palette.info} rounded-xl px-4 py-3 shadow-sm text-sm">${type === 'loading' ? '⏳ ' : ''}${message}</div>`;
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
        ui.message('Seu navegador não suporta notificações push.', 'warning');
        return;
      }
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        ui.message('Permissão de push negada ou não concedida.', 'warning');
        return;
      }
      const reg = await navigator.serviceWorker.register('/sw-push.js');
      const publicKey = await getPublicKey();
      if (!publicKey) {
        ui.message('Chave pública de push não configurada.', 'warning');
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
        ui.message('Sessão expirada. Faça login novamente.', 'warning');
        setTimeout(() => (window.location.href = '/entrar.html'), 300);
      }
      if (res.status === 403) ui.message('Acesso negado para este perfil.', 'warning');
      if (res.status === 404) ui.message('Recurso não encontrado.', 'warning');
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
              <p class="mt-2 text-2xl font-bold text-on-surface">${m.value ?? '—'}</p>
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

  // ---------------------- Notificações internas ---------------------- //
  const notifications = (() => {
    async function fetchAll() {
      const { data } = await api.request('/notifications');
      return data?.data?.data || data?.data || [];
    }

    async function markAllRead() {
      await api.request('/notifications/read', { method: 'POST' });
    }

    function renderList(items, title = 'Notificações') {
      if (!items.length) {
        ui.setPageState('empty', 'Sem notificações.');
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

    async function load(title = 'Notificações') {
      const items = await fetchAll();
      renderList(items, title);
    }

    return { load, markAllRead };
  })();

  // ---------------------- Páginas: Cliente ---------------------- //
  const cliente = {
    async dashboard() {
      if (!(await auth.guard(['cliente']))) return;
      ui.setPageState('loading', 'Carregando dashboard...');
      const { data: dadosResp } = await api.request('/pontos/meus-dados');
      const dados = dadosResp?.data || {};
      ui.clearPageState();
      render.summary('Sua conta', [
        { label: 'Pontos', value: dados.pontos_total },
        { label: 'Pendentes', value: dados.pontos_pendentes },
        { label: 'Nível', value: dados.nivel },
        { label: 'Check-ins', value: dados.checkins_total },
        { label: 'Cupons ativos', value: dados.cupons_ativos },
        { label: 'Usuário', value: auth.getStored().user?.email },
      ]);

      await notifications.load('Notificações');

      const historico = await api.request('/pontos/historico');
      const itens = historico.data?.data?.data || historico.data?.data || [];
      if (Array.isArray(itens) && itens.length) {
        render.section(
          'Histórico de pontos',
          itens
            .slice(0, 10)
            .map(
              (item) => `
              <div class="px-4 py-3 flex items-center justify-between text-sm">
                <div>
                  <p class="font-semibold">${item?.empresa?.nome || 'Empresa'}</p>
                  <p class="text-on-surface-variant">${new Date(item.created_at).toLocaleString('pt-BR')}</p>
                </div>
                <span class="text-primary font-bold">${item.pontos || 0} pts</span>
              </div>`
            )
            .join('')
        );
      } else {
        ui.setPageState('empty', 'Nenhum histórico de pontos ainda.');
      }
    },

    async parceiros() {
      if (!(await auth.guard(['cliente']))) return;
      const host = document.querySelector('main') || document.body;
      const searchBox = document.createElement('section');
      searchBox.className = 'max-w-5xl mx-auto px-4 pt-4';
      searchBox.innerHTML = `
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 justify-between">
          <h3 class="text-lg font-semibold text-on-surface">Parcerias</h3>
          <div class="flex gap-2 items-center">
            <input id="parceiroBusca" class="border rounded-lg px-3 py-2 w-64" placeholder="Buscar por nome" />
            <button id="parceiroBuscarBtn" class="px-3 py-2 bg-primary text-white rounded-lg text-sm">Buscar</button>
          </div>
        </div>`;
      host.prepend(searchBox);

      const load = async (busca = '') => {
        ui.setPageState('loading', 'Carregando parceiros...');
        const qs = busca ? `?busca=${encodeURIComponent(busca)}` : '';
        const { data } = await api.request(`/cliente/empresas${qs}`);
        ui.clearPageState();
        const lista = data?.data || data || [];
        if (!lista.length) return ui.setPageState('empty', 'Nenhum parceiro encontrado.');
        render.section(
          'Parceiros',
          lista
            .map(
              (e) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${e.nome}</p>
                <p class="text-on-surface-variant">${e.categoria || e.ramo || ''}</p>
              </div>
              <a class="text-primary font-semibold hover:underline" href="/detalhe_do_parceiro.html?id=${e.id}">Ver detalhes</a>
            </div>`
            )
            .join('')
        );
      };

      searchBox.querySelector('#parceiroBuscarBtn')?.addEventListener('click', () => {
        const term = searchBox.querySelector('#parceiroBusca').value;
        load(term);
      });

      await load();
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

      if (info) {
        render.summary('Estabelecimento', [
          { label: 'Nome', value: info.nome },
          { label: 'Categoria', value: info.categoria },
          { label: 'Status', value: info.ativo === false ? 'Inativo' : 'Ativo' },
        ]);
      }

      const promoList = promos.data?.data || [];
      if (promoList.length) {
        render.section(
          'Promoções',
          promoList
            .map(
              (p) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${p.titulo || p.nome}</p>
                <p class="text-on-surface-variant">${p.descricao || ''}</p>
              </div>
              <span class="text-primary font-semibold">${p.desconto ? p.desconto + '% off' : ''}</span>
            </div>`
            )
            .join('')
        );
      }

      const listaProdutos = produtos.data?.data || produtos.data || [];
      if (listaProdutos.length) {
        render.section(
          'Produtos',
          listaProdutos
            .map(
              (p) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${p.nome || p.titulo}</p>
                <p class="text-on-surface-variant">${p.descricao || ''}</p>
              </div>
              ${p.preco ? `<span class="font-semibold text-primary">R$ ${Number(p.preco).toFixed(2)}</span>` : ''}
            </div>`
            )
            .join('')
        );
      }
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
                <p class="text-on-surface-variant">Válido até: ${c.expira_em ? new Date(c.expira_em).toLocaleDateString('pt-BR') : '—'}</p>
              </div>
              <span class="font-semibold ${c.status === 'used' ? 'text-amber-600' : 'text-primary'}">${c.status}</span>
            </div>`
            )
            .join('')
        );
      } else {
        ui.setPageState('empty', 'Nenhum cupom disponível ainda.');
      }

      // Formulário simples de resgate
      const host = document.querySelector('main') || document.body;
      const formWrap = document.createElement('section');
      formWrap.className = 'max-w-6xl mx-auto px-4 pt-4';
      formWrap.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <h3 class="text-lg font-semibold text-on-surface">Resgatar recompensa</h3>
          <div class="grid gap-3 md:grid-cols-3">
            <input id="resgateDescricao" class="border rounded-lg px-3 py-2" placeholder="Descrição" />
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
        if (!descricao || !pontos) return ui.message('Preencha descrição e custo em pontos.', 'warning');
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
      ui.setPageState('loading', 'Carregando histórico...');
      const { data } = await api.request('/pontos/historico');
      const itens = data?.data?.data || data?.data || [];
      if (!itens.length) return ui.setPageState('empty', 'Nenhum histórico encontrado.');
      ui.clearPageState();
      render.section(
        'Histórico completo',
        itens
          .map(
            (i) => `
          <div class="px-4 py-3 flex items-center justify-between text-sm">
            <div>
              <p class="font-semibold">${i?.empresa?.nome || 'Empresa'}</p>
              <p class="text-on-surface-variant">${i.descricao || ''}</p>
            </div>
            <span class="font-semibold text-primary">${i.pontos || 0} pts</span>
          </div>`
          )
          .join('')
      );
    },

    async perfil() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      ui.setPageState('loading', 'Carregando perfil...');
      const user = await auth.ensure();
      ui.clearPageState();
      const host = document.querySelector('main') || document.body;
      const form = document.createElement('section');
      form.className = 'max-w-3xl mx-auto px-4 pt-4';
      form.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <h3 class="text-lg font-semibold text-on-surface">Meu perfil</h3>
          <div class="grid gap-3 md:grid-cols-2">
            <input id="pfNome" class="border rounded-lg px-3 py-2" placeholder="Nome" value="${user?.name || user?.nome || ''}" />
            <input id="pfEmail" class="border rounded-lg px-3 py-2" placeholder="Email" value="${user?.email || ''}" />
            <input id="pfTelefone" class="border rounded-lg px-3 py-2" placeholder="Telefone" value="${user?.telefone || ''}" />
            <input id="pfCpf" class="border rounded-lg px-3 py-2" placeholder="CPF" value="${user?.cpf || ''}" />
            <input id="pfNascimento" class="border rounded-lg px-3 py-2" type="date" value="${user?.data_nascimento || ''}" />
          </div>
          <button id="pfSalvar" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold">Salvar</button>
        </div>
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3 mt-4">
          <h3 class="text-lg font-semibold text-on-surface">Alterar senha</h3>
          <input id="pwAtual" type="password" class="border rounded-lg px-3 py-2 w-full" placeholder="Senha atual" />
          <input id="pwNova" type="password" class="border rounded-lg px-3 py-2 w-full" placeholder="Nova senha" />
          <input id="pwConf" type="password" class="border rounded-lg px-3 py-2 w-full" placeholder="Confirmar nova senha" />
          <button id="pwSalvar" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold">Atualizar senha</button>
        </div>`;
      host.prepend(form);
      form.querySelector('#pfSalvar')?.addEventListener('click', async () => {
        const payload = {
          name: form.querySelector('#pfNome').value,
          email: form.querySelector('#pfEmail').value,
          telefone: form.querySelector('#pfTelefone').value,
          cpf: form.querySelector('#pfCpf').value,
          data_nascimento: form.querySelector('#pfNascimento').value,
        };
        const { res, data } = await api.request('/perfil', { method: 'PUT', body: JSON.stringify(payload) });
        if (res.ok && data?.success) {
          ui.message('Perfil atualizado.', 'success');
          auth.save(auth.getStored().token, data.data);
        } else {
          ui.message(data?.message || 'Erro ao atualizar perfil.', 'error');
        }
      });

      form.querySelector('#pwSalvar')?.addEventListener('click', async () => {
        const payload = {
          current_password: form.querySelector('#pwAtual').value,
          password: form.querySelector('#pwNova').value,
          password_confirmation: form.querySelector('#pwConf').value,
        };
        ui.setPageState('loading', 'Atualizando senha...');
        const { res, data } = await api.request('/auth/change-password', { method: 'POST', body: JSON.stringify(payload) });
        ui.clearPageState();
        if (res.ok && data?.success) ui.message('Senha alterada.', 'success');
        else ui.message(data?.message || 'Erro ao alterar senha.', 'error');
      });
    },

    async validarResgate() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      const host = document.querySelector('main') || document.body;
      const box = document.createElement('section');
      box.className = 'max-w-xl mx-auto px-4 pt-4';
      box.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <h3 class="text-lg font-semibold text-on-surface">Validar/usar cupom</h3>
          <input id="cupomId" class="border rounded-lg px-3 py-2 w-full" placeholder="ID do cupom" />
          <button id="usarCupomBtn" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold w-full">Marcar como usado</button>
        </div>`;
      host.prepend(box);
      box.querySelector('#usarCupomBtn').addEventListener('click', async () => {
        const id = box.querySelector('#cupomId').value;
        if (!id) return ui.message('Informe o ID do cupom.', 'warning');
        const { res, data } = await api.request(`/pontos/usar-cupom/${id}`, { method: 'POST' });
        if (res.ok && data?.success) ui.message('Cupom marcado como usado.', 'success');
        else ui.message(data?.message || 'Não foi possível usar o cupom.', 'error');
      });
    },
  };

  // ---------------------- Páginas: Estabelecimento ---------------------- //
  const empresa = {
    async dashboard() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando painel da empresa...');
      const promos = await api.request('/empresa/promocoes');
      const clientes = await api.request('/empresa/clientes');
      render.summary('Painel do estabelecimento', [
        { label: 'Promoções', value: Array.isArray(promos.data?.data || promos.data) ? (promos.data?.data || promos.data).length : '—' },
        { label: 'Clientes fidelizados', value: clientes.data?.data?.length || clientes.data?.data?.total || '—' },
        { label: 'Responsável', value: auth.getStored().user?.email },
      ]);

      const relatorio = await api.request('/empresa/relatorio-pontos');
      ui.clearPageState();
      if (relatorio.data?.data?.totais) {
        const t = relatorio.data.data.totais;
        render.section(
          'Pontos / Resgates (últimos 30 dias)',
          `
          <div class="px-4 py-3 text-sm flex justify-between">
            <span class="font-semibold">Total distribuído</span>
            <span class="font-semibold text-primary">${t.total_distribuido || 0}</span>
          </div>
          <div class="px-4 py-3 text-sm flex justify-between">
            <span class="font-semibold">Total resgatado</span>
            <span class="font-semibold text-primary">${t.total_resgatado || 0}</span>
          </div>
          <div class="px-4 py-3 text-sm flex justify-between">
            <span class="font-semibold">Clientes únicos</span>
            <span class="font-semibold text-primary">${t.total_clientes || 0}</span>
          </div>`
        );
      }

      await notifications.load('Notificações');

      // Check-in / QR Code manual (fallback)
      const host = document.querySelector('main') || document.body;
      const checkinBox = document.createElement('section');
      checkinBox.className = 'max-w-xl mx-auto px-4 pt-4';
      checkinBox.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <h3 class="text-lg font-semibold text-on-surface">Registrar check-in (QR)</h3>
          <p class="text-sm text-on-surface-variant">Cole o conteúdo do QR Code lido no app.</p>
          <input id="qrCliente" class="border rounded-lg px-3 py-2 w-full" placeholder="CLIENT_{id}_hash" />
          <button id="qrRegistrar" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold w-full">Registrar check-in</button>
        </div>`;
      host.prepend(checkinBox);
      checkinBox.querySelector('#qrRegistrar').addEventListener('click', async () => {
        const code = checkinBox.querySelector('#qrCliente').value;
        if (!code) return ui.message('Informe o código/QR.', 'warning');
        ui.setPageState('loading', 'Registrando check-in...');
        const { res, data } = await api.request('/empresa/escanear-cliente', { method: 'POST', body: JSON.stringify({ qrcode: code }) });
        ui.clearPageState();
        if (res.ok && data?.success) ui.message('Check-in registrado.', 'success');
        else ui.message(data?.message || 'Erro ao registrar check-in.', 'error');
      });

      // Resgates recentes
      const resgates = await api.request('/empresa/resgates');
      const lista = resgates.data?.data?.data || resgates.data?.data || [];
      if (lista.length) {
        render.section(
          'Resgates recentes',
          lista
            .slice(0, 10)
            .map(
              (r) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${r.promocao || 'Promoção'}</p>
                <p class="text-on-surface-variant">${r.cliente || ''} (${r.cliente_email || ''})</p>
              </div>
              <span class="text-primary font-semibold">${r.status || ''}</span>
            </div>`
            )
            .join('')
        );
      }
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
                <p class="text-xs text-on-surface-variant">Última visita: ${c.ultima_visita ? new Date(c.ultima_visita).toLocaleDateString('pt-BR') : '—'}</p>
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
      ui.setPageState('loading', 'Carregando promoções...');
      const { data } = await api.request('/empresa/promocoes');
      const lista = data?.data || data || [];
      if (!lista.length) ui.setPageState('empty', 'Nenhuma promoção cadastrada ainda.');
      else ui.clearPageState();

      if (lista.length) {
        const wrap = render.section(
          'Promoções',
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

      // Formulário rápido de criação (JSON – EmpresaAPIController)
      const host = document.querySelector('main') || document.body;
      let editingId = null;
      const form = document.createElement('section');
      form.className = 'max-w-6xl mx-auto px-4 pt-4';
      form.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-on-surface" id="promoFormTitle">Nova promoção</h3>
            <button id="promoReset" class="text-sm text-on-surface-variant underline">Limpar</button>
          </div>
          <div class="grid gap-3 md:grid-cols-2">
            <input id="promoTitulo" class="border rounded-lg px-3 py-2" placeholder="Título" />
            <input id="promoDesconto" class="border rounded-lg px-3 py-2" placeholder="Desconto (%)" type="number" min="0" max="100" />
          </div>
          <textarea id="promoDescricao" class="border rounded-lg px-3 py-2 w-full" rows="3" placeholder="Descrição"></textarea>
          <input id="promoImagem" type="file" accept="image/*" class="border rounded-lg px-3 py-2 w-full" />
          <input id="promoImagemUrl" type="url" class="border rounded-lg px-3 py-2 w-full" placeholder="Ou URL da imagem" />
          <button id="promoCriarBtn" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold">Criar</button>
        </div>`;
      host.prepend(form);

      form.querySelector('#promoReset')?.addEventListener('click', (e) => {
        e.preventDefault();
        editingId = null;
        form.querySelector('#promoFormTitle').textContent = 'Nova promoção';
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
        if (!titulo || Number.isNaN(desconto)) return ui.message('Informe título e desconto.', 'warning');

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
          ui.message(editingId ? 'Promoção atualizada.' : 'Promoção criada.', 'success');
          location.reload();
        } else {
          ui.message(data?.message || 'Erro ao salvar promoção.', 'error');
        }
      });

      // Preencher form para edição
      document.querySelectorAll('[data-promocao]').forEach((row, idx) => {
        const p = lista[idx];
        row.addEventListener('click', (ev) => {
          if (ev.target?.dataset?.action) return; // botões de ativar/pausar tratam separado
          editingId = p.id;
          form.querySelector('#promoFormTitle').textContent = `Editar promoção #${p.id}`;
          form.querySelector('#promoCriarBtn').textContent = 'Salvar';
          form.querySelector('#promoTitulo').value = p.titulo || p.nome || '';
          form.querySelector('#promoDesconto').value = p.desconto || 0;
          form.querySelector('#promoDescricao').value = p.descricao || '';
          form.querySelector('#promoImagemUrl').value = p.imagem && !p.imagem.startsWith('/storage') ? p.imagem : '';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      });

      // Histórico detalhado de resgates
      const hist = document.createElement('section');
      hist.className = 'max-w-6xl mx-auto px-4 pt-6';
      hist.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 space-y-3">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <h3 class="text-lg font-semibold text-on-surface">Histórico de resgates</h3>
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
        ui.setPageState('loading', 'Carregando histórico de resgates...');
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
              <p class="font-semibold">${r.promocao || 'Promoção'} — ${r.cliente || ''}</p>
              <p class="text-on-surface-variant">${r.codigo || ''} · ${new Date(r.created_at).toLocaleString('pt-BR')}</p>
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
        ui.message('Promoção atualizada.', 'success');
        location.reload();
      } else {
        ui.message(data?.message || 'Erro ao atualizar promoção.', 'error');
      }
    },
  };

  // ---------------------- Páginas: Admin ---------------------- //
  const admin = {
    async dashboard() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando dashboard admin...');
      const stats = await api.request('/admin/dashboard-stats');
      const recent = await api.request('/admin/recent-activity');
      const empresas = await api.request('/empresas', {}, { requireAuth: false });
      ui.clearPageState();

      render.summary('Admin Master', [
        { label: 'Empresas', value: empresas.data?.data?.length || empresas.data?.data?.total || '—' },
        { label: 'Eventos recentes', value: recent.data?.data?.length || 0 },
        { label: 'Usuário', value: auth.getStored().user?.email },
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

      await notifications.load('Notificações');
    },

    async empresas() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando estabelecimentos...');
      const { data } = await api.request('/empresas', {}, { requireAuth: false });
      const lista = data?.data || data || [];
      if (!lista.length) return ui.setPageState('empty', 'Nenhum estabelecimento cadastrado.');
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
      ui.setPageState('loading', 'Carregando usuários...');
      const { res, data } = await api.request('/admin/users-report');
      if (!res.ok) return ui.setPageState('error', 'Endpoint /admin/users-report indisponível ou bloqueado.');
      const lista = data?.data || [];
      if (!lista.length) return ui.setPageState('empty', 'Nenhum usuário retornado.');
      ui.clearPageState();
      render.section(
        'Usuários',
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
      ui.setPageState('loading', 'Carregando relatórios...');
      const stats = await api.request('/admin/dashboard-stats');
      const checkins = await api.request('/admin/pontos/estatisticas');
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
        ui.message('Sem estatísticas de pontos disponíveis.', 'warning');
      }
    },
  };

  // ---------------------- Login (público) ---------------------- //
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
        ui.message(data?.message || 'Não foi possível entrar.', 'error');
      }
    });
  }

  // ---------------------- Dispatcher ---------------------- //
  const handlers = {
    // Público / shared
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
        else ui.message(data?.message || 'Erro ao solicitar recuperação.', 'error');
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
          ui.message('Senha redefinida. Faça login.', 'success');
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
          ui.message('Conta criada. Faça login.', 'success');
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
      ui.message('Banners/categorias: nenhum endpoint disponível identificado. Necessário backend.', 'warning');
    },
  };

  document.addEventListener('DOMContentLoaded', async () => {
    const handler = handlers[page];
    // Autoregistrar push em páginas logadas principais
    const loggedPages = ['meus_pontos', 'dashboard_parceiro', 'dashboard_admin_master'];
    if (loggedPages.includes(page)) {
      push.register().catch(() => {});
    }
    if (handler) {
      try {
        await handler();
      } catch (err) {
        console.error(err);
        ui.message('Erro ao carregar página.', 'error');
      }
    }
  });
})();
