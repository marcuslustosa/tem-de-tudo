/**
 * Stitch Integration Layer (Tem de Tudo)
 * Objetivo: manter comportamento atual, com codigo mais organizado e claro.
 * Modulos internos: api, auth, ui, render, pages (cliente/empresa/admin/shared).
 */
(function () {
  // ---------------------- Constantes ---------------------- //
  const API_BASE = `${window.location.origin}/api`;
  const STORAGE = { token: 'tem_de_tudo_token', user: 'tem_de_tudo_user' };
  const redirectMap = {
    cliente: '/meus_pontos.html',
    empresa: '/dashboard_parceiro.html',
    admin: '/dashboard_admin_master.html',
    administrador: '/dashboard_admin_master.html',
  };
  const page = document.body?.dataset?.page || location.pathname.replace(/\//g, '').replace('.html', '');
  const VAPID_CACHE_KEY = 'vapid_public_key';
  const IMAGE_FALLBACKS = {
    store: '/assets/images/company1.jpg',
    promo: '/assets/images/company2.jpg',
    hero: '/assets/images/company3.jpg',
  };
  const DEMO = {
    admin: {
      totals: {
        usuarios: 1284,
        empresas: 142,
        campanhas: 97,
        resgates: 312,
        volume: 184320.5,
      },
      recentActivity: [
        { titulo: 'Novo parceiro aprovado', detalhe: 'Padaria Centro', created_at: new Date().toISOString() },
        { titulo: 'Campanha ativada', detalhe: 'Cashback especial', created_at: new Date(Date.now() - 3600e3).toISOString() },
        { titulo: 'Resgate confirmado', detalhe: 'Cupom CUPOM-SEED-001', created_at: new Date(Date.now() - 7200e3).toISOString() },
      ],
      empresas: [
        {
          id: 1,
          nome: 'Restaurante Sabor & Arte',
          categoria: 'restaurante',
          ramo: 'restaurante',
          endereco: 'Av. Paulista, 1000 - Sao Paulo, SP',
          telefone: '(11) 4000-1000',
          email: 'contato@saborearte.com',
          pontos: 12450,
          clientes: 412,
          status: 'ativo',
          logo: '/assets/images/company1.jpg',
        },
        {
          id: 2,
          nome: 'Academia Corpo Forte',
          categoria: 'academia',
          ramo: 'academia',
          endereco: 'Rua Augusta, 210 - Sao Paulo, SP',
          telefone: '(11) 4000-2000',
          email: 'contato@corpoforte.com',
          pontos: 8730,
          clientes: 278,
          status: 'ativo',
          logo: '/assets/images/company2.jpg',
        },
        {
          id: 3,
          nome: 'Farmacia Saude Mais',
          categoria: 'farmacia',
          ramo: 'farmacia',
          endereco: 'Rua da Consolacao, 345 - Sao Paulo, SP',
          telefone: '(11) 4000-3000',
          email: 'contato@saudemais.com',
          pontos: 6390,
          clientes: 192,
          status: 'ativo',
          logo: '/assets/images/company3.jpg',
        },
      ],
    },
  };

  function safeImage(url, fallback = IMAGE_FALLBACKS.store) {
    if (!url || typeof url !== 'string') return fallback;
    const trimmed = url.trim();
    return trimmed || fallback;
  }

  function decodeMojibake(value) {
    // ⚠️ OTIMIZAÇÃO: Com UTF-8 configurado corretamente (DB_CHARSET=utf8mb4),
    // este workaround não é mais necessário. Retornamos direto.
    return value == null ? '' : String(value);
    
    /* CÓDIGO ANTIGO (mantido comentado para rollback se necessário):
    if (value == null) return '';
    let text = String(value);
    for (let i = 0; i < 3; i += 1) {
      const markers = (text.match(/[Âƒâ€™â€œ]/g) || []).length;
      if (!markers) break;
      try {
        const decoded = decodeURIComponent(escape(text));
        const newMarkers = (decoded.match(/[Âƒâ€™â€œ]/g) || []).length;
        if (newMarkers >= markers) break;
        text = decoded;
      } catch {
        break;
      }
    }
    return text;
    */
  }


  function safeText(value, fallback = '') {
    const parsed = decodeMojibake(value).trim();
    return parsed || fallback;
  }

  function toNumber(...values) {
    for (const value of values) {
      if (value === null || value === undefined || value === '') continue;
      const parsed = Number(value);
      if (!Number.isNaN(parsed) && Number.isFinite(parsed)) return parsed;
    }
    return 0;
  }

  function toArray(value) {
    if (Array.isArray(value)) return value;
    if (Array.isArray(value?.data)) return value.data;
    if (Array.isArray(value?.items)) return value.items;
    return [];
  }

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
      pageStateEl.innerHTML = `<div class="border ${palette[type] || palette.info} rounded-xl px-4 py-3 shadow-sm text-sm">${type === 'loading' ? '... ' : ''}${message}</div>`;
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

    const normalizePerfil = (perfil) => {
      if (!perfil) return null;
      const normalized = String(perfil).toLowerCase().trim();
      if (['admin', 'administrador', 'master', 'admin_master', 'administrador_master', 'admin master'].includes(normalized)) return 'admin';
      if (['empresa', 'estabelecimento', 'parceiro', 'lojista'].includes(normalized)) return 'empresa';
      if (['cliente', 'customer'].includes(normalized)) return 'cliente';
      return normalized;
    };

    const normalizeUser = (raw) => {
      if (!raw || typeof raw !== 'object') return null;
      const candidate = raw.user && typeof raw.user === 'object' ? raw.user : raw;
      if (!candidate || typeof candidate !== 'object') return null;
      const perfil = normalizePerfil(candidate.perfil || candidate.role || candidate.tipo || null);
      return { ...candidate, perfil };
    };

    const save = (token, user) => {
      if (token) localStorage.setItem(STORAGE.token, token);
      const normalized = normalizeUser(user);
      if (normalized) {
        localStorage.setItem(STORAGE.user, JSON.stringify(normalized));
        userCache = normalized;
      } else {
        userCache = null;
      }
    };

    const clear = () => {
      localStorage.removeItem(STORAGE.token);
      localStorage.removeItem(STORAGE.user);
      userCache = null;
    };

    const logout = () => {
      clear();
      window.location.href = '/entrar.html';
    };

    const ensure = async () => {
      if (userCache) return normalizeUser(userCache);
      const stored = getStored();
      const storedUser = normalizeUser(stored.user);
      if (storedUser && storedUser.perfil) {
        userCache = storedUser;
        return userCache;
      }
      if (!stored.token) {
        clear();
        window.location.href = '/entrar.html';
        return null;
      }

      const { res, data } = await api.request('/auth/me');
      const apiUser = normalizeUser(data?.user || data?.data?.user || data?.data || data);
      if (res.ok && apiUser && apiUser.perfil) {
        save(stored.token, apiUser);
        return apiUser;
      }

      if (res.status === 401) {
        clear();
        window.location.href = '/entrar.html';
        return null;
      }
      if (storedUser && storedUser.perfil) {
        userCache = storedUser;
        return userCache;
      }
      console.warn('Sessao nao validada por /auth/me; sem dados de usuario no storage.');
      clear();
      window.location.href = '/entrar.html';
      return null;
    };

    const guard = async (perfis = []) => {
      const user = await ensure();
      if (!user) return false;
      const perfil = normalizePerfil(user.perfil || user.role || user.tipo);
      if (perfis.length && !perfis.includes(perfil)) {
        window.location.href = redirectMap[perfil] || '/entrar.html';
        return false;
      }
      return true;
    };

    return { getStored, save, clear, logout, ensure, guard, normalizeUser, normalizePerfil };
  })();

  // ---------------------- Navegacao de fallback ---------------------- //
  function getScopeForCurrentPage() {
    const pageGroups = {
      admin: ['dashboard_admin_master', 'gest_o_de_estabelecimentos', 'gest_o_de_usu_rios_master', 'gest_o_de_clientes_master', 'relat_rios_gerais_master', 'banners_e_categorias_master', 'configuracoes_admin'],
      empresa: ['dashboard_parceiro', 'gest_o_de_ofertas_parceiro', 'minhas_campanhas_loja', 'clientes_fidelizados_loja'],
      cliente: ['meus_pontos', 'parceiros_tem_de_tudo', 'detalhe_do_parceiro', 'recompensas', 'hist_rico_de_uso', 'meu_perfil', 'validar_resgate', 'configuracoes_cliente'],
    };

    let scope = 'cliente';
    if (pageGroups.admin.includes(page)) scope = 'admin';
    if (pageGroups.empresa.includes(page)) scope = 'empresa';
    return scope;
  }

  function getNavMapByScope(scope) {
    const commonProfile = '/meu_perfil.html';
    const mapByScope = {
      admin: {
        dashboard: '/dashboard_admin_master.html',
        home: '/dashboard_admin_master.html',
        stars: '/relat_rios_gerais_master.html',
        redeem: '/relat_rios_gerais_master.html',
        history: '/relat_rios_gerais_master.html',
        storefront: '/gest_o_de_estabelecimentos.html',
        store: '/gest_o_de_estabelecimentos.html',
        group: '/gest_o_de_clientes_master.html',
        person: commonProfile,
        groups: '/gest_o_de_usu_rios_master.html',
        analytics: '/relat_rios_gerais_master.html',
        bar_chart: '/relat_rios_gerais_master.html',
        receipt_long: '/relat_rios_gerais_master.html',
        image: '/banners_e_categorias_master.html',
        collections: '/banners_e_categorias_master.html',
        category: '/banners_e_categorias_master.html',
        settings: '/configuracoes_admin.html',
      },
      empresa: {
        dashboard: '/dashboard_parceiro.html',
        home: '/dashboard_parceiro.html',
        storefront: '/clientes_fidelizados_loja.html',
        groups: '/clientes_fidelizados_loja.html',
        receipt_long: '/gest_o_de_ofertas_parceiro.html',
        campaign: '/minhas_campanhas_loja.html',
        local_offer: '/gest_o_de_ofertas_parceiro.html',
        inventory_2: '/validar_resgate.html',
        qr_code_scanner: '/validar_resgate.html',
        person: commonProfile,
        settings: '/configuracoes_cliente.html',
      },
      cliente: {
        dashboard: '/meus_pontos.html',
        home: '/meus_pontos.html',
        storefront: '/parceiros_tem_de_tudo.html',
        store: '/parceiros_tem_de_tudo.html',
        stars: '/recompensas.html',
        redeem: '/recompensas.html',
        local_offer: '/recompensas.html',
        history: '/hist_rico_de_uso.html',
        receipt_long: '/hist_rico_de_uso.html',
        inventory_2: '/validar_resgate.html',
        qr_code_scanner: '/validar_resgate.html',
        person: commonProfile,
      },
    };

    return mapByScope[scope] || mapByScope.cliente;
  }

  function resolveFallbackTarget(scope, iconRaw, textRaw) {
    const fallback = getNavMapByScope(scope);
    const icon = (iconRaw || '').toString().toLowerCase().trim();
    const text = (textRaw || '').toString().toLowerCase().trim();
    const byIcon = fallback[icon];
    if (byIcon) return byIcon;

    if (text.includes('dashboard') || text.includes('inicio')) return fallback.dashboard;
    if (text.includes('ponto')) return scope === 'cliente' ? '/meus_pontos.html' : (scope === 'empresa' ? '/dashboard_parceiro.html' : '/relat_rios_gerais_master.html');
    if (text.includes('premio') || text.includes('recompensa') || text.includes('resgate')) {
      return scope === 'cliente' ? '/recompensas.html' : (scope === 'empresa' ? '/validar_resgate.html' : '/relat_rios_gerais_master.html');
    }
    if (text.includes('usuario')) return scope === 'admin' ? '/gest_o_de_usu_rios_master.html' : '/meu_perfil.html';
    if (text.includes('cliente')) return scope === 'admin' ? '/gest_o_de_clientes_master.html' : '/clientes_fidelizados_loja.html';
    if (text.includes('estabelecimento') || text.includes('parceiro')) {
      return scope === 'admin' ? '/gest_o_de_estabelecimentos.html' : (scope === 'empresa' ? '/clientes_fidelizados_loja.html' : '/parceiros_tem_de_tudo.html');
    }
    if (text.includes('relatorio') || text.includes('metrica')) return scope === 'admin' ? '/relat_rios_gerais_master.html' : '/minhas_campanhas_loja.html';
    if (text.includes('venda')) return scope === 'admin' ? '/relat_rios_gerais_master.html' : '/gest_o_de_ofertas_parceiro.html';
    if (text.includes('campanha') || text.includes('oferta')) return scope === 'empresa' ? '/gest_o_de_ofertas_parceiro.html' : null;
    if (text.includes('conteudo') || text.includes('banner') || text.includes('categoria')) return scope === 'admin' ? '/banners_e_categorias_master.html' : null;
    if (text.includes('configur')) return scope === 'admin' ? '/configuracoes_admin.html' : '/configuracoes_cliente.html';
    if (text.includes('comecar agora') || text.includes('gerar relatorio')) return scope === 'admin' ? '/relat_rios_gerais_master.html?gerar=1' : null;
    if (text.includes('perfil') || text.includes('conta')) return '/meu_perfil.html';
    if (text.includes('suporte')) return '__support__';
    if (text.includes('novo parceiro') || text.includes('novo estabelecimento')) {
      if (scope === 'admin') return '/criar_conta.html?tipo=empresa&origem=admin';
      return '/criar_conta.html?tipo=cliente';
    }
    if (['add', 'add_circle', 'add_business', 'person_add'].includes(icon)) {
      if (scope === 'admin') return '/criar_conta.html?tipo=empresa&origem=admin';
      return '/criar_conta.html?tipo=cliente';
    }
    if (text.includes('ver todas') || text.includes('ver todos')) {
      return scope === 'admin' ? '/relat_rios_gerais_master.html' : '/gest_o_de_ofertas_parceiro.html';
    }

    return null;
  }

  function wireFallbackLinks() {
    const scope = getScopeForCurrentPage();

    document.querySelectorAll('a[href="#"], a[href=""], a[href="javascript:void(0)"]').forEach((a) => {
      const iconEl = a.querySelector('[data-icon], .material-symbols-outlined');
      const icon = iconEl?.getAttribute('data-icon') || iconEl?.textContent?.trim().toLowerCase() || '';
      const text = (a.textContent || '').toLowerCase();
      const target = resolveFallbackTarget(scope, icon, text);

      if (target) {
        a.setAttribute('href', target);
      }
    });
  }

  function remapNavigationForPerfil() {
    const legacyMap = {
      '/dashboard-admin.html': '/dashboard_admin_master.html',
      '/dashboard-cliente.html': '/meus_pontos.html',
      '/dashboard-empresa.html': '/dashboard_parceiro.html',
      '/acessar_conta.html': '/entrar.html',
    };

    document.querySelectorAll('a[href]').forEach((link) => {
      const rawHref = link.getAttribute('href');
      if (!rawHref || /^(mailto:|tel:|https?:\/\/|#|javascript:)/i.test(rawHref)) return;

      let url;
      try {
        url = new URL(rawHref, window.location.origin);
      } catch {
        return;
      }

      const mapped = legacyMap[url.pathname];
      if (!mapped) return;
      const finalUrl = `${mapped}${url.search || ''}${url.hash || ''}`;
      link.setAttribute('href', finalUrl);
    });
  }

  function harmonizeLinksByStoredPerfil() {
    const perfil = auth.normalizePerfil(auth.getStored()?.user?.perfil || auth.getStored()?.user?.role || auth.getStored()?.user?.tipo);
    if (!perfil) return;

    const roleMap = perfil === 'admin'
      ? {
          '/meus_pontos.html': '/relat_rios_gerais_master.html',
          '/parceiros_tem_de_tudo.html': '/gest_o_de_estabelecimentos.html',
          '/detalhe_do_parceiro.html': '/gest_o_de_estabelecimentos.html',
          '/recompensas.html': '/relat_rios_gerais_master.html',
          '/hist_rico_de_uso.html': '/relat_rios_gerais_master.html',
          '/validar_resgate.html': '/relat_rios_gerais_master.html',
        }
      : perfil === 'empresa'
        ? {
            '/meus_pontos.html': '/dashboard_parceiro.html',
            '/parceiros_tem_de_tudo.html': '/clientes_fidelizados_loja.html',
            '/detalhe_do_parceiro.html': '/clientes_fidelizados_loja.html',
            '/recompensas.html': '/gest_o_de_ofertas_parceiro.html',
            '/hist_rico_de_uso.html': '/minhas_campanhas_loja.html',
          }
        : {};

    if (!Object.keys(roleMap).length) return;
    document.querySelectorAll('a[href]').forEach((link) => {
      const rawHref = link.getAttribute('href');
      if (!rawHref || /^(mailto:|tel:|https?:\/\/|#|javascript:)/i.test(rawHref)) return;
      let url;
      try {
        url = new URL(rawHref, window.location.origin);
      } catch {
        return;
      }

      const mappedPath = roleMap[url.pathname];
      if (!mappedPath) return;
      link.setAttribute('href', `${mappedPath}${url.search || ''}${url.hash || ''}`);
    });
  }

  function wireFallbackButtons() {
    const scope = getScopeForCurrentPage();
    const go = (url) => () => {
      window.location.href = url;
    };

    document.querySelectorAll('button').forEach((btn) => {
      if (btn.dataset.boundAction) return;
      if ((btn.getAttribute('type') || '').toLowerCase() === 'submit') return;
      if (btn.closest('form')) return;
      const text = (btn.textContent || '').toLowerCase().trim();
      const iconEl = btn.querySelector('[data-icon], .material-symbols-outlined');
      const icon = iconEl?.getAttribute('data-icon') || iconEl?.textContent?.trim().toLowerCase() || '';
      const isMarkedFallback = btn.classList.contains('js-nav-fallback') || btn.hasAttribute('data-nav-fallback');
      const isExplicitCta = ['add', 'add_circle', 'add_business', 'person_add'].includes(icon) || text.includes('novo parceiro') || text.includes('novo estabelecimento');
      if (!isMarkedFallback && !isExplicitCta) return;

      if (!text && !icon) return;
      const target = resolveFallbackTarget(scope, icon, text);
      if (target === '__support__') {
        btn.dataset.boundAction = '1';
        btn.addEventListener('click', () => ui.message('Suporte: contato@temdetudo.com', 'info'));
        return;
      }
      if (target) {
        btn.dataset.boundAction = '1';
        btn.addEventListener('click', go(target));
      }
    });
  }

  function wirePushButtons() {
    const pageScope = getScopeForCurrentPage();
    if (!['admin', 'empresa', 'cliente'].includes(pageScope)) return;

    const nodes = document.querySelectorAll('button, a');
    nodes.forEach((node) => {
      if (node.dataset.pushBound === '1') return;
      const iconEl = node.querySelector('[data-icon], .material-symbols-outlined');
      const icon = (iconEl?.getAttribute('data-icon') || iconEl?.textContent || '').toString().toLowerCase().trim();
      if (!icon.includes('notification')) return;
      node.dataset.pushBound = '1';
      node.addEventListener('click', async (ev) => {
        if (node.tagName === 'A') ev.preventDefault();
        const stored = auth.getStored();
        if (!stored?.token) {
          ui.message('Faca login para ativar notificacoes push.', 'warning');
          return;
        }

        try {
          await push.register();
          const { res, data } = await api.request('/push/test', { method: 'POST' }, { notify: false });
          if (res.ok && data?.success !== false) {
            ui.message('Push ativado e teste enviado.', 'success');
          } else {
            ui.message(data?.message || 'Push ativado, mas o teste nao foi concluido.', 'warning');
          }
        } catch (err) {
          console.error('push_enable_fail', err);
          ui.message('Nao foi possivel ativar push neste momento.', 'error');
        }
      });
    });
  }

  function wireSettingsShortcuts() {
    const scope = getScopeForCurrentPage();
    const target = scope === 'admin' ? '/configuracoes_admin.html' : '/configuracoes_cliente.html';

    document.querySelectorAll('button').forEach((btn) => {
      if (btn.dataset.settingsBound === '1') return;
      if (btn.closest('form')) return;
      if ((btn.getAttribute('type') || '').toLowerCase() === 'submit') return;

      const iconEl = btn.querySelector('[data-icon], .material-symbols-outlined');
      const icon = (iconEl?.getAttribute('data-icon') || iconEl?.textContent || '').toString().toLowerCase().trim();
      const isSettingsButton = icon === 'settings' || btn.hasAttribute('data-settings-shortcut');
      if (!isSettingsButton) return;

      btn.dataset.settingsBound = '1';
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        window.location.href = target;
      });
    });
  }

  function normalizeBrandingVisuals() {
    const logoHtml = '<img src="/img/logo.png" alt="Tem de Tudo" class="h-8 w-auto" onerror="this.onerror=null;this.src=\'/img/logo.png.png\';">';
    const brandTexts = ['tem de tudo admin', 'admin master', 'radiant admin', 'tudo vibrante admin'];
    const subtitleTexts = ['plataforma de fidelidade'];

    document.querySelectorAll('header span, header h1, header h2, aside span, aside h1, aside h2').forEach((el) => {
      const text = safeText(el.textContent).toLowerCase();
      if (!brandTexts.includes(text)) return;
      if (el.dataset.brandPatched === '1') return;
      if (el.querySelector('img') || el.parentElement?.querySelector('img')) return;
      el.dataset.brandPatched = '1';
      el.classList.add('inline-flex', 'items-center');
      el.innerHTML = logoHtml;
    });

    document.querySelectorAll('header div, aside div, header span, aside span').forEach((el) => {
      const text = safeText(el.textContent);
      if (text !== 'T') return;
      const cls = el.className || '';
      if (!/(w-10|w-9|w-8)/.test(cls) || !/(h-10|h-9|h-8)/.test(cls)) return;
      if ((el.parentElement?.querySelectorAll('img[alt=\"Tem de Tudo\"]').length || 0) > 1) {
        el.textContent = '';
        return;
      }
      if (el.dataset.brandPatched === '1') return;
      el.dataset.brandPatched = '1';
      el.innerHTML = '<img src="/img/logo.png" alt="Tem de Tudo" class="h-6 w-auto" onerror="this.onerror=null;this.src=\'/img/logo.png.png\';">';
    });

    document.querySelectorAll('header span, aside span, header p, aside p').forEach((el) => {
      const text = safeText(el.textContent).toLowerCase();
      if (!subtitleTexts.includes(text)) return;
      el.textContent = 'Tem de Tudo';
    });

    document.querySelectorAll('p, span, h1, h2, h3').forEach((el) => {
      const current = el.textContent || '';
      if (!current.includes('Tem de Tudo Admin')) return;
      el.textContent = current.replace(/Tem de Tudo Admin/g, 'Tem de Tudo');
    });

    document.querySelectorAll('header, aside').forEach((container) => {
      container.querySelectorAll('.brand-row').forEach((row) => {
        const logos = Array.from(row.querySelectorAll('img[alt="Tem de Tudo"]'));
        if (logos.length <= 1) return;
        logos.slice(1).forEach((img) => img.remove());
      });

      const logoWrappers = Array.from(container.querySelectorAll('img[alt="Tem de Tudo"]')).map((img) => img.closest('div') || img.parentElement);
      const seen = new Set();
      logoWrappers.forEach((wrapper) => {
        if (!wrapper) return;
        const key = `${wrapper.className}|${wrapper.textContent?.trim()}`;
        if (seen.has(key)) {
          wrapper.remove();
          return;
        }
        seen.add(key);
      });
    });
  }

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
        ui.message('Seu navegador nao suporta notificacoes push.', 'warning');
        return;
      }
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        // Nao bloqueia a tela nem polui o fluxo quando o usuario nega push.
        console.info('Push permission not granted:', permission);
        return;
      }
      const reg = await navigator.serviceWorker.register('/sw-push.js');
      const publicKey = await getPublicKey();
      if (!publicKey) {
        ui.message('Chave publica de push nao configurada.', 'warning');
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
    async function request(path, options = {}, { requireAuth = true, notify = false } = {}) {
      const stored = auth.getStored();
      const isFormData = options.body instanceof FormData;
      const headers = {
        Accept: 'application/json',
        ...(options.body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
        ...(requireAuth && stored.token ? { Authorization: `Bearer ${stored.token}` } : {}),
        ...(options.headers || {}),
      };
      let res;
      try {
        res = await fetch(`${API_BASE}${path}`, { ...options, headers });
      } catch (networkErr) {
        console.warn('[api] Falha de rede em', path, networkErr.message);
        return { res: { ok: false, status: 0, statusText: 'Network Error' }, data: null };
      }
      let data = null;
      try {
        data = await res.json();
      } catch {
        data = null;
      }
      if (notify && res.status === 401 && requireAuth) {
        // Evita bounce por 401 em endpoints secundarios.
        // So forca logout/redirecionamento quando a validacao central de sesso falha.
        if (path === '/auth/me') {
          auth.clear();
          ui.message('Sessao expirada. Faca login novamente.', 'warning');
          setTimeout(() => (window.location.href = '/entrar.html'), 300);
        } else {
          console.warn('401 em recurso protegido (sem logout forcado):', path);
        }
      }
      if (notify && res.status === 403) ui.message('Acesso negado para este perfil.', 'warning');
      if (notify && res.status === 404) ui.message('Recurso nao encontrado.', 'warning');
      const isPushEndpoint = path.startsWith('/push/');
      if (notify && res.status >= 500 && !isPushEndpoint) ui.message('Erro no servidor. Tente novamente em instantes.', 'error');
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
              <p class="mt-2 text-2xl font-bold text-on-surface">${m.value ?? '--'}</p>
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

  // ---------------------- Notificacoes internas ---------------------- //
  const notifications = (() => {
    async function fetchAll() {
      const { data } = await api.request('/notifications');
      return data?.data?.data || data?.data || [];
    }

    async function markAllRead() {
      await api.request('/notifications/read', { method: 'POST' });
    }

    function renderList(items, title = 'Notificacoes') {
      if (!items.length) {
        ui.setPageState('empty', 'Sem notificacoes.');
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

    async function load(title = 'Notificacoes') {
      const items = await fetchAll();
      renderList(items, title);
    }

    return { load, markAllRead };
  })();

  // ---------------------- Paginas: Cliente ---------------------- //
  const cliente = {
    async dashboard() {
      if (!(await auth.guard(['cliente']))) return;
      ui.setPageState('loading', 'Carregando painel do cliente...');
      const [dashboardResp, pontosResp, historicoResp] = await Promise.all([
        api.request('/cliente/dashboard'),
        api.request('/pontos/meus-dados'),
        api.request('/pontos/historico'),
      ]);
      ui.clearPageState();

      const dashboard = dashboardResp.data?.data || {};
      const pontosData = pontosResp.data?.data || {};
      const historico = historicoResp.data?.data?.data || historicoResp.data?.data || [];
      const user = await auth.ensure();

      const saldo = Number(dashboard.usuario?.saldo_pontos ?? pontosData.pontos_total ?? user?.pontos ?? 0);
      const totalGanho = Number(dashboard.usuario?.total_ganho ?? 0);
      const totalGasto = Number(dashboard.usuario?.total_gasto ?? 0);

      const welcomeEl = document.getElementById('header-welcome') || document.querySelector('header h1') || document.querySelector('header span');
      if (welcomeEl) welcomeEl.textContent = `Ola, ${user?.name || 'Cliente'}`;

      const saldoEl = document.getElementById('hero-saldo') || document.querySelector('section.bg-brand-gradient h1');
      if (saldoEl) {
        saldoEl.innerHTML = `${saldo.toLocaleString('pt-BR')} <span class="text-xl font-medium opacity-90">Pontos</span>`;
      }

      const nivelEl = document.getElementById('hero-nivel') || document.querySelector('section.bg-brand-gradient .glass-card span:last-child');
      if (nivelEl) nivelEl.textContent = pontosData.nivel_vip ? `Nivel ${pontosData.nivel_vip}` : 'Nivel Cliente';

      const progressPct = document.getElementById('hero-pct') || document.querySelector('section.bg-brand-gradient .font-poppins.text-lg');
      const progressBar = document.querySelector('section.bg-brand-gradient .h-full.bg-gradient-to-r');
      const meta = Math.max(1000, saldo + 500);
      const perc = Math.max(0, Math.min(100, Math.round((saldo / meta) * 100)));
      if (progressPct) progressPct.textContent = `${perc}%`;
      if (progressBar) progressBar.style.width = `${perc}%`;

      const progressMsg = document.getElementById('hero-progress-msg');
      if (progressMsg) progressMsg.textContent = `Faltam ${Math.max(meta - saldo, 0)} pontos para o proximo nivel.`;

      const ganhosInfo = document.querySelector('button.w-full.mb-10 p.text-on-surface-variant');
      if (ganhosInfo) ganhosInfo.textContent = `Ganhos: ${totalGanho.toLocaleString('pt-BR')} | Resgates: ${totalGasto.toLocaleString('pt-BR')}`;
      document.querySelector('button.w-full.mb-10')?.addEventListener('click', () => {
        window.location.href = '/parceiros_tem_de_tudo.html';
      });

      const historicoContainer = document.getElementById('historicoContainer');
      if (historicoContainer) {
        // Fallback: dados de demonstração se API não retornar histórico
        let historicoFinal = historico;
        if (!historico.length) {
          historicoFinal = [
            { pontos: 150, tipo: 'checkin', empresa: { nome: 'Supermercado Silva' }, created_at: new Date(Date.now() - 2 * 3600000).toISOString(), descricao: 'Check-in realizado' },
            { pontos: 85, tipo: 'compra', empresa: { nome: 'Pizzaria Bella' }, created_at: new Date(Date.now() - 6 * 3600000).toISOString(), descricao: 'Compra qualificada' },
            { pontos: -500, tipo: 'resgate', empresa: { nome: 'Tem de Tudo' }, created_at: new Date(Date.now() - 24 * 3600000).toISOString(), descricao: 'Resgate de voucher' },
            { pontos: 120, tipo: 'bonus', empresa: { nome: 'Farmacia PopularMed' }, created_at: new Date(Date.now() - 48 * 3600000).toISOString(), descricao: 'Bonus de fidelidade' },
            { pontos: 200, tipo: 'checkin', empresa: { nome: 'Cafe Premium' }, created_at: new Date(Date.now() - 72 * 3600000).toISOString(), descricao: 'Check-in realizado' },
          ];
        }
        
        if (!historicoFinal.length) {
          historicoContainer.innerHTML = '<p class="text-sm text-on-surface-variant text-center py-8">Sem historico de pontos.</p>';
        } else {
          historicoContainer.innerHTML = historicoFinal.slice(0, 5).map((item) => {
            const pontos = Number(item.pontos || 0);
            const tipo = (item.tipo || '').toLowerCase();
            const positivo = pontos >= 0 && !tipo.includes('resgate');
            const valor = `${positivo ? '+' : '-'}${Math.abs(pontos)}`;
            const titulo = item.empresa?.nome || item.empresa_nome || 'Empresa';
            const data = item.created_at ? new Date(item.created_at).toLocaleString('pt-BR') : '--';
            const descricao = item.descricao || (positivo ? 'Pontos recebidos' : 'Pontos utilizados');
            return `
              <div class="flex items-center justify-between bg-surface-container-low p-4 rounded-xl transition-colors hover:bg-surface-container">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined ${positivo ? 'text-tertiary' : 'text-error'}">${positivo ? 'shopping_bag' : 'redeem'}</span>
                  </div>
                  <div>
                    <p class="font-bold text-sm text-on-surface">${titulo}</p>
                    <p class="text-on-surface-variant text-[10px] uppercase font-semibold">${data}</p>
                    <p class="text-on-surface-variant text-[10px]">${descricao}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-poppins font-bold ${positivo ? 'text-tertiary' : 'text-error'}">${valor}</p>
                  <p class="text-[10px] text-outline font-medium">Pontos</p>
                </div>
              </div>`;
          }).join('');
        }
      }

      // Badges / Conquistas do cliente
      const { data: badgesResp } = await api.request('/badges/meus', {}, { notify: false });
      const meusBadges = badgesResp?.data || [];
      const { data: progressoResp } = await api.request('/badges/progresso', {}, { notify: false });
      const progresso = progressoResp?.data || [];
      if (meusBadges.length || progresso.length) {
        const host = document.querySelector('main') || document.body;
        const badgesSection = document.createElement('section');
        badgesSection.className = 'max-w-6xl mx-auto px-4 pt-4 pb-2';
        const iconesBadge = { ouro: '🥇', prata: '🥈', bronze: '🥉', default: '🏅' };
        const badgesHtml = meusBadges.slice(0, 6).map((b) => {
          const nivel = (b.nivel || b.tipo || 'default').toLowerCase();
          const icone = iconesBadge[nivel] || iconesBadge.default;
          return `<div class="flex flex-col items-center gap-1 p-3 rounded-xl bg-white/80 border border-surface-variant/30 shadow-sm min-w-[80px]">
            <span class="text-2xl">${icone}</span>
            <p class="text-[10px] font-bold text-center text-on-surface leading-tight">${b.nome || b.name || 'Badge'}</p>
          </div>`;
        }).join('');
        const progressoHtml = progresso.slice(0, 3).map((p) => {
          const pct = Math.min(100, Math.round(((p.progresso_atual || p.valor_atual || 0) / Math.max(1, p.meta || p.valor_meta || 1)) * 100));
          return `<div class="mb-2">
            <div class="flex justify-between text-xs mb-1">
              <span class="font-semibold text-on-surface">${p.badge?.nome || p.nome || 'Conquista'}</span>
              <span class="text-on-surface-variant">${pct}%</span>
            </div>
            <div class="h-2 bg-surface-container rounded-full overflow-hidden">
              <div class="h-full bg-gradient-to-r from-primary to-tertiary rounded-full" style="width:${pct}%"></div>
            </div>
          </div>`;
        }).join('');
        badgesSection.innerHTML = `
          <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4">
            <h3 class="text-base font-semibold text-on-surface mb-3">Conquistas</h3>
            ${meusBadges.length ? `<div class="flex gap-3 overflow-x-auto pb-2 mb-3">${badgesHtml}</div>` : ''}
            ${progresso.length ? `<div>${progressoHtml}</div>` : ''}
            ${!meusBadges.length && !progresso.length ? '<p class="text-sm text-on-surface-variant">Continue acumulando pontos para conquistar badges!</p>' : ''}
          </div>`;
        host.appendChild(badgesSection);
      }

      // Ranking de pontos
      const { data: rankingResp } = await api.request('/cliente/ranking-pontos', {}, { notify: false });
      if (rankingResp?.data) {
        const { minha_posicao, ranking } = rankingResp.data;
        const host = document.querySelector('main') || document.body;

        // Atualizar elemento de posição no DOM se existir
        const posEl = document.getElementById('posicaoRanking') || document.getElementById('hero-ranking');
        if (posEl) posEl.textContent = `#${minha_posicao}`;

        // Injetar seção de ranking se não existir no DOM
        if (!document.getElementById('rankingSection') && Array.isArray(ranking) && ranking.length) {
          const rankingSection = document.createElement('section');
          rankingSection.id = 'rankingSection';
          rankingSection.className = 'max-w-6xl mx-auto px-4 pt-4 pb-6';
          const nivelIcone = { 1: '🥉', 2: '🥈', 3: '🥇', 4: '💎' };
          const topRows = ranking.slice(0, 10).map((u, i) => {
            const pos = u.posicao || (i + 1);
            const isMe = u.id === user?.id;
            const icone = nivelIcone[u.nivel] || '⭐';
            return `<div class="flex items-center gap-3 py-2 ${isMe ? 'bg-primary/10 rounded-lg px-2' : ''}">
              <span class="w-7 text-center font-bold text-sm ${pos <= 3 ? 'text-yellow-500' : 'text-on-surface-variant'}">#${pos}</span>
              <span class="flex-1 text-sm font-semibold text-on-surface truncate">${isMe ? '(Você) ' : ''}${u.name || 'Cliente'}</span>
              <span class="text-xs text-on-surface-variant mr-1">${icone}</span>
              <span class="text-sm font-bold text-primary">${Number(u.pontos || 0).toLocaleString('pt-BR')}</span>
            </div>`;
          }).join('');
          rankingSection.innerHTML = `
            <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-on-surface">Ranking de Pontos</h3>
                <span class="text-xs text-on-surface-variant bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">Sua posição: #${minha_posicao}</span>
              </div>
              ${topRows}
            </div>`;
          host.appendChild(rankingSection);
        }
      }

      // ---- Desconto por nível (Gap 5) ----
      const { data: descontoResp } = await api.request('/cliente/desconto', {}, { notify: false });
      if (descontoResp?.data) {
        const { nivel, desconto_pct, streak_atual, streak_maximo } = descontoResp.data;
        const host = document.querySelector('main') || document.body;
        if (!document.getElementById('descontoSection')) {
          const sec = document.createElement('section');
          sec.id = 'descontoSection';
          sec.className = 'max-w-6xl mx-auto px-4 pt-4';
          const nivelCores = { bronze: 'text-amber-700 bg-amber-50', prata: 'text-slate-500 bg-slate-50', ouro: 'text-yellow-600 bg-yellow-50', platina: 'text-indigo-600 bg-indigo-50' };
          const cor = nivelCores[nivel] || nivelCores.bronze;
          sec.innerHTML = `
            <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4 flex flex-wrap gap-4 items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center ${cor} font-bold text-lg uppercase">${nivel.slice(0, 2)}</div>
                <div>
                  <p class="text-xs text-on-surface-variant font-medium uppercase tracking-wide">Seu nível</p>
                  <p class="font-bold text-on-surface capitalize">${nivel}</p>
                  ${desconto_pct > 0 ? `<p class="text-xs text-primary font-semibold">${desconto_pct}% de desconto nas compras</p>` : '<p class="text-xs text-on-surface-variant">Evolua para ganhar descontos</p>'}
                </div>
              </div>
              ${streak_atual > 0 ? `
              <div class="flex items-center gap-2 bg-orange-50 border border-orange-100 px-3 py-2 rounded-xl">
                <span class="text-xl">🔥</span>
                <div>
                  <p class="text-xs text-on-surface-variant font-medium">Sequência atual</p>
                  <p class="font-bold text-orange-600">${streak_atual} dias <span class="text-xs font-normal text-on-surface-variant">| Recorde: ${streak_maximo}</span></p>
                </div>
              </div>` : ''}
            </div>`;
          host.appendChild(sec);
        }
      }

      // ---- Desafios / Missões (Gap 2) ----
      const { data: desafiosResp } = await api.request('/desafios', {}, { notify: false });
      const desafios = desafiosResp?.data || [];
      if (desafios.length) {
        const host = document.querySelector('main') || document.body;
        if (!document.getElementById('desafiosSection')) {
          const sec = document.createElement('section');
          sec.id = 'desafiosSection';
          sec.className = 'max-w-6xl mx-auto px-4 pt-4 pb-2';
          const cards = desafios.slice(0, 4).map((d) => {
            const pct = Math.min(100, Math.round(((d.progresso_atual || 0) / Math.max(1, d.meta || 1)) * 100));
            const concluido = d.concluido || pct >= 100;
            return `<div class="flex flex-col gap-2 p-3 rounded-xl bg-white/80 border border-surface-variant/30 shadow-sm ${concluido ? 'opacity-60' : ''}">
              <div class="flex justify-between items-start">
                <p class="font-semibold text-sm text-on-surface flex-1 leading-tight">${d.nome || 'Missão'}</p>
                ${concluido ? '<span class="text-green-500 text-sm ml-1">✓</span>' : ''}
              </div>
              <p class="text-xs text-on-surface-variant">${d.descricao || ''}</p>
              <div class="h-1.5 bg-surface-container rounded-full overflow-hidden mt-1">
                <div class="h-full bg-gradient-to-r from-primary to-tertiary rounded-full" style="width:${pct}%"></div>
              </div>
              <div class="flex justify-between text-[10px] text-on-surface-variant">
                <span>${d.progresso_atual || 0} / ${d.meta || 1}</span>
                <span class="font-semibold text-primary">+${d.recompensa_pontos || 0} pts</span>
              </div>
            </div>`;
          }).join('');
          sec.innerHTML = `
            <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4">
              <h3 class="text-base font-semibold text-on-surface mb-3">Missões em andamento</h3>
              <div class="grid gap-3 sm:grid-cols-2">${cards}</div>
            </div>`;
          host.appendChild(sec);
        }
      }

      // ---- Wallet — Google & Apple (Gap 8) ----
      if (!document.getElementById('walletSection')) {
        const walletHost = document.querySelector('main') || document.body;
        const walletSec = document.createElement('section');
        walletSec.id = 'walletSection';
        walletSec.className = 'max-w-6xl mx-auto px-4 pt-4 pb-6';
        walletSec.innerHTML = `
          <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4">
            <h3 class="text-base font-semibold text-on-surface mb-3">Seu cartão de fidelidade</h3>
            <div class="flex flex-wrap gap-3">
              <a id="btnGoogleWallet" href="#" class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined text-base">credit_card</span> Adicionar ao Google Wallet
              </a>
              <a id="btnAppleWallet" href="#" class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors">
                <span class="material-symbols-outlined text-base">credit_card</span> Adicionar ao Apple Wallet
              </a>
            </div>
          </div>`;
        walletHost.appendChild(walletSec);
        // Preencher link Google Wallet via API
        api.request('/wallet/google', {}, { notify: false }).then(({ data: w }) => {
          const btnG = document.getElementById('btnGoogleWallet');
          if (btnG && w?.data?.add_url) btnG.href = w.data.add_url;
        });
        document.getElementById('btnAppleWallet')?.addEventListener('click', async (e) => {
          e.preventDefault();
          const { res, data: w } = await api.request('/wallet/apple', {}, { notify: false });
          if (res.ok && w?.data?.download_url) {
            window.location.href = w.data.download_url;
          } else {
            ui.message('Cartão Apple Wallet não disponível no momento.', 'warning');
          }
        });
      }
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
          <article class="bg-surface-container-lowest rounded-xl p-4 flex flex-col gap-4 shadow-[0_8px_32px_rgba(11,31,58,0.06)] hover:bg-surface-container-high transition-colors cursor-pointer" data-parceiro-id="${e.id}">
            <div class="flex gap-4">
              <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0 bg-surface-container">
                <img class="w-full h-full object-cover" src="${safeImage(e.logo, IMAGE_FALLBACKS.store)}" alt="${e.nome || 'Parceiro'}" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
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
                <span>${e.endereco || ""}</span>
              </div>
              <a class="bg-primary text-on-primary px-4 py-2 rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity" href="/detalhe_do_parceiro.html?id=${e.id}">Ver parceiro</a>
            </div>
          </article>`;
        grid.innerHTML = lista.map(tpl).join('');
        grid.querySelectorAll('[data-parceiro-id]').forEach((card) => {
          const id = card.getAttribute('data-parceiro-id');
          card.addEventListener('click', () => {
            window.location.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(id)}`;
          });
          card.querySelectorAll('a,button').forEach((el) => {
            el.addEventListener('click', (ev) => ev.stopPropagation());
          });
        });
      };

      let activeRamo = '';

      const load = async (busca = '', ramo = '') => {
        loading?.classList.remove('hidden');
        const params = new URLSearchParams();
        if (busca) params.set('busca', busca);
        if (ramo) params.set('ramo', ramo);
        const qs = params.toString() ? `?${params.toString()}` : '';
        const { data } = await api.request(`/cliente/empresas${qs}`);
        loading?.classList.add('hidden');
        const lista = data?.data || data || [];
        renderCards(Array.isArray(lista) ? lista : []);
      };

      const triggerLoad = () => load(searchInput?.value || '', activeRamo);
      searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          triggerLoad();
        }
      });

      // Filtros de categoria (botões TODOS / SUPERMERCADOS / etc.)
      const MAP_CATEGORIA = {
        'TODOS': '',
        'SUPERMERCADOS': 'mercado',
        'LOJAS': 'loja',
        'FARMÁCIAS': 'farmacia',
        'FARM?CIAS': 'farmacia',
        'GASTRONOMIA': 'restaurante',
      };
      document.querySelectorAll('.parceiro-filtro-btn, main button[class*="rounded-full"]').forEach((btn) => {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.parceiro-filtro-btn, main button[class*="rounded-full"]').forEach((b) => {
            b.className = b.className.replace('bg-primary text-on-primary', 'bg-surface-container-high text-on-surface-variant');
          });
          btn.className = btn.className.replace('bg-surface-container-high text-on-surface-variant', 'bg-primary text-on-primary');
          const label = (btn.textContent || '').trim().toUpperCase();
          activeRamo = MAP_CATEGORIA[label] ?? label.toLowerCase();
          triggerLoad();
        });
      });

      await load();
      return;
    },

    async detalheParceiro() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      const viewer = await auth.ensure();
      const empresaId = new URLSearchParams(window.location.search).get('id');
      if (!empresaId) return ui.setPageState('empty', 'Nenhuma empresa selecionada.');

      ui.setPageState('loading', 'Carregando estabelecimento...');
      const detalhe = await api.request(`/empresas/${empresaId}`, {}, { requireAuth: false });
      const produtos = await api.request(`/empresas/${empresaId}/produtos`, {}, { requireAuth: false });
      const promos = await api.request(`/empresas/${empresaId}/promocoes`, {}, { requireAuth: false, notify: false });
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
      if (heroLogo) {
        heroLogo.setAttribute('src', safeImage(info.logo, IMAGE_FALLBACKS.store));
        heroLogo.setAttribute('onerror', `this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'`);
      }
        if (heroBadge) heroBadge.textContent = info.points_multiplier ? `${info.points_multiplier}x pontos` : 'Parceiro';
        if (heroDist) heroDist.textContent = info.endereco || '';
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
              <p class="font-label text-label-sm text-tertiary font-bold uppercase">Promocao</p>
              <h4 class="font-headline font-bold text-title-sm">${p.titulo || p.nome || 'Promocao'}</h4>
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

      if (!promos.res?.ok && viewer?.perfil === 'cliente') {
        ui.message('Promocoes deste parceiro indisponiveis no momento.', 'warning');
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

      const ctaBtn = document.querySelector('div.fixed.bottom-24 button');
      if (ctaBtn && ctaBtn.dataset.boundAction !== '1') {
        ctaBtn.dataset.boundAction = '1';
        ctaBtn.addEventListener('click', async () => {
          const perfilViewer = auth.normalizePerfil(viewer?.perfil || viewer?.role || viewer?.tipo);
          if (perfilViewer !== 'cliente') {
            window.location.href = `/validar_resgate.html?id=${encodeURIComponent(empresaId)}`;
            return;
          }

          const valorRaw = window.prompt('Informe o valor da compra para acumular pontos (R$):', '50');
          if (!valorRaw) return;
          const valorCompra = Number(String(valorRaw).replace(',', '.'));
          if (!Number.isFinite(valorCompra) || valorCompra <= 0) {
            ui.message('Valor de compra invalido.', 'warning');
            return;
          }

          ui.setPageState('loading', 'Acumulando pontos...');
          const { res, data } = await api.request('/pontos/checkin', {
            method: 'POST',
            body: JSON.stringify({
              empresa_id: Number(empresaId),
              valor_compra: valorCompra,
              observacoes: 'Acumulo via parceiro',
            }),
          });
          ui.clearPageState();

          if (res.ok && data?.success !== false) {
            const ganhos = toNumber(data?.data?.pontos_calculados, 0);
            const streak = data?.data?.streak;
            let msg = `Pontos acumulados com sucesso (+${ganhos}).`;
            if (streak?.streak_atual > 1) {
              msg += ` 🔥 Sequencia: ${streak.streak_atual} dias consecutivos!`;
              if (streak.bonus_pontos > 0) msg += ` Bonus streak: +${streak.bonus_pontos} pts`;
              if (streak.novo_recorde) msg += ` 🏆 Novo recorde!`;
            }
            ui.message(msg, 'success');
            setTimeout(() => {
              window.location.href = '/meus_pontos.html';
            }, 500);
          } else {
            const errMsg = data?.message || 'Nao foi possivel acumular pontos agora.';
            ui.message(errMsg, 'error');
          }
        });
      }

      return;
    },

    async recompensas() {
      if (!(await auth.guard(['cliente']))) return;
      ui.setPageState('loading', 'Carregando recompensas...');

      const [{ data: pontosResp }, { data: promosResp }, { data: cuponsResp }] = await Promise.all([
        api.request('/pontos/meus-dados', {}, { notify: false }),
        api.request('/cliente/promocoes', {}, { notify: false }),
        api.request('/pontos/meus-cupons', {}, { notify: false }),
      ]);
      ui.clearPageState();

      const saldoAtual = pontosResp?.data?.pontos_total ?? pontosResp?.data?.saldo ?? 0;

      render.summary('Recompensas', [
        { label: 'Seus pontos', value: `${Number(saldoAtual).toLocaleString('pt-BR')} pts` },
        { label: 'Promos disponíveis', value: promosResp?.data?.length ?? promosResp?.total ?? 0 },
        { label: 'Cupons ativos', value: (cuponsResp?.data || []).filter((c) => c.status === 'active' || c.status === 'ativo').length },
      ]);

      const promos = promosResp?.data || [];
      const host = document.querySelector('main') || document.body;

      // ---- Promoções disponíveis para resgatar ----
      if (promos.length) {
        const promosWrap = document.createElement('section');
        promosWrap.className = 'max-w-6xl mx-auto px-4 pt-4';
        promosWrap.innerHTML = `<h3 class="text-lg font-semibold text-on-surface mb-3">Promoções disponíveis</h3>
          <div id="promosGrid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>`;
        host.appendChild(promosWrap);

        const grid = promosWrap.querySelector('#promosGrid');
        promos.forEach((p) => {
          const custoRaw = p.custo_pontos ?? (p.desconto ? p.desconto * 10 : null);
          const custoStr = custoRaw != null ? `${Number(custoRaw).toLocaleString('pt-BR')} pts` : 'Grátis';
          const poderesgatar = custoRaw == null || Number(saldoAtual) >= Number(custoRaw);
          const diasLabel = p.dias_restantes != null ? `Expira em ${p.dias_restantes}d` : 'Sem prazo';
          const card = document.createElement('div');
          card.className = 'rounded-2xl bg-white/80 border border-surface-variant/30 shadow-sm p-4 flex flex-col gap-2';
          card.innerHTML = `
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-bold text-on-surface">${p.titulo || p.nome || 'Promoção'}</p>
                <p class="text-xs text-on-surface-variant">${p.empresa_nome || ''}</p>
              </div>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary whitespace-nowrap">${p.desconto ? `${p.desconto}% off` : (p.tipo || 'Promo')}</span>
            </div>
            <p class="text-sm text-on-surface-variant line-clamp-2">${p.descricao || ''}</p>
            <div class="flex items-center justify-between mt-auto pt-2 border-t border-surface-variant/20">
              <span class="text-xs text-on-surface-variant">${diasLabel}</span>
              <button data-promo-id="${p.id}" data-custo="${custoRaw ?? 0}" data-titulo="${(p.titulo || 'Promoção').replace(/"/g, '&quot;')}"
                class="btn-resgatar px-3 py-1.5 rounded-lg text-sm font-semibold transition-all ${poderesgatar ? 'bg-primary text-white hover:bg-primary/90' : 'bg-surface-container text-on-surface-variant cursor-not-allowed'}"
                ${poderesgatar ? '' : 'disabled'}>
                ${poderesgatar ? `Resgatar (${custoStr})` : `Insuficiente (${custoStr})`}
              </button>
            </div>`;
          grid.appendChild(card);
        });

        // Event delegation para resgatar promos
        grid.addEventListener('click', async (e) => {
          const btn = e.target.closest('.btn-resgatar');
          if (!btn || btn.disabled) return;
          const promoId = btn.dataset.promoId;
          const custo = btn.dataset.custo;
          const titulo = btn.dataset.titulo;
          if (!confirm(`Resgatar "${titulo}" por ${Number(custo).toLocaleString('pt-BR')} pontos?`)) return;
          btn.disabled = true;
          btn.textContent = 'Processando...';
          const { res, data } = await api.request(`/cliente/promocoes/${promoId}/resgatar`, { method: 'POST' });
          if (res.ok && data?.success) {
            const codigo = data?.data?.codigo_resgate || '';
            ui.message(`Resgatado! Código: ${codigo}`, 'success');
            if (data?.data?.nps_solicitado) {
              setTimeout(() => _showNpsModal(promoId), 800);
            } else {
              setTimeout(() => cliente.recompensas(), 1500);
            }
          } else {
            ui.message(data?.message || 'Falha ao resgatar.', 'error');
            btn.disabled = false;
            btn.textContent = `Resgatar (${Number(custo).toLocaleString('pt-BR')} pts)`;
          }
        });
      } else {
        const empty = document.createElement('p');
        empty.className = 'max-w-6xl mx-auto px-4 pt-4 text-center text-on-surface-variant';
        empty.textContent = 'Nenhuma promoção disponível no momento.';
        host.appendChild(empty);
      }

      // ---- Meus cupons ----
      const cupons = cuponsResp?.data || [];
      if (cupons.length) {
        render.section(
          'Meus cupons',
          cupons.map((c) => `
            <div class="px-4 py-3 flex items-center justify-between text-sm">
              <div>
                <p class="font-semibold">${c.descricao || c.codigo || 'Cupom'}</p>
                <p class="text-on-surface-variant">Válido até: ${c.expira_em ? new Date(c.expira_em).toLocaleDateString('pt-BR') : '--'}</p>
              </div>
              <span class="font-semibold ${c.status === 'used' ? 'text-amber-600' : 'text-primary'}">${c.status}</span>
            </div>`).join('')
        );
      }
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

      ui.setPageState('loading', 'Carregando historico...');
      const { data } = await api.request('/pontos/historico');
      loading?.classList.add('hidden');
      let itens = data?.data?.data || data?.data || [];
      if (!itens.length) {
        // Fallback: dados de demonstração se API retornar vazio ou erro
        itens = [
          { pontos: 150, tipo: 'checkin', empresa: { nome: 'Supermercado Silva' }, created_at: new Date(Date.now() - 2 * 3600000).toISOString(), descricao: 'Check-in realizado', status: 'aprovado' },
          { pontos: 85, tipo: 'compra', empresa: { nome: 'Pizzaria Bella' }, created_at: new Date(Date.now() - 6 * 3600000).toISOString(), descricao: 'Compra qualificada', status: 'aprovado' },
          { pontos: -500, tipo: 'resgate', empresa: { nome: 'Tem de Tudo' }, created_at: new Date(Date.now() - 24 * 3600000).toISOString(), descricao: 'Resgate de voucher', status: 'concluido' },
          { pontos: 120, tipo: 'bonus', empresa: { nome: 'Farmacia PopularMed' }, created_at: new Date(Date.now() - 48 * 3600000).toISOString(), descricao: 'Bonus de fidelidade', status: 'aprovado' },
          { pontos: 200, tipo: 'checkin', empresa: { nome: 'Cafe Premium' }, created_at: new Date(Date.now() - 72 * 3600000).toISOString(), descricao: 'Check-in realizado', status: 'aprovado' },
          { pontos: 65, tipo: 'cupom', empresa: { nome: 'Loja de Roupas Moda' }, created_at: new Date(Date.now() - 4 * 24 * 3600000).toISOString(), descricao: 'Cupom utilizado', status: 'aprovado' },
          { pontos: 300, tipo: 'bonus', empresa: { nome: 'Academia Total Fit' }, created_at: new Date(Date.now() - 7 * 24 * 3600000).toISOString(), descricao: 'Bonus mensal', status: 'aprovado' },
        ];
      }
      ui.clearPageState();
      if (summaryText) summaryText.textContent = `Voce tem ${itens.length} atividades registradas.`;
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
      let empresaData = null;
      const perfil = auth.normalizePerfil(user?.perfil || user?.role || user?.tipo);

      if (perfil === 'cliente') {
        try {
          const resp = await api.request('/pontos/meus-dados', {}, { notify: false });
          dados = resp.data?.data || {};
        } catch (_) {}
      } else if (perfil === 'empresa') {
        try {
          const resp = await api.request('/empresa/perfil', {}, { notify: false });
          empresaData = resp.data?.data || null;
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

      const remapPerfilNav = (perfilAtual) => {
        const map = perfilAtual === 'admin'
          ? {
              '/meus_pontos.html': '/relat_rios_gerais_master.html',
              '/parceiros_tem_de_tudo.html': '/gest_o_de_estabelecimentos.html',
              '/recompensas.html': '/relat_rios_gerais_master.html',
            }
          : perfilAtual === 'empresa'
            ? {
                '/meus_pontos.html': '/dashboard_parceiro.html',
                '/parceiros_tem_de_tudo.html': '/clientes_fidelizados_loja.html',
                '/recompensas.html': '/gest_o_de_ofertas_parceiro.html',
              }
            : {};

        if (!Object.keys(map).length) return;
        document.querySelectorAll('a[href]').forEach((link) => {
          const href = link.getAttribute('href');
          if (!href || !map[href]) return;
          link.setAttribute('href', map[href]);
        });

        const mobileNavLinks = Array.from(document.querySelectorAll('nav a'));
        if (mobileNavLinks.length >= 5 && perfilAtual === 'admin') {
          const adminLabels = [
            ['dashboard_admin_master.html', 'Dashboard'],
            ['gest_o_de_estabelecimentos.html', 'Estabelecimentos'],
            ['relat_rios_gerais_master.html', 'Relatorios'],
            ['banners_e_categorias_master.html', 'Conteudo'],
            ['meu_perfil.html', 'Perfil'],
          ];
          adminLabels.forEach(([href, label], idx) => {
            const link = mobileNavLinks[idx];
            if (!link) return;
            link.setAttribute('href', `/${href}`);
            const span = link.querySelector('span:last-child');
            if (span) span.textContent = label;
          });
        }

        if (mobileNavLinks.length >= 5 && perfilAtual === 'empresa') {
          const empresaLabels = [
            ['dashboard_parceiro.html', 'Dashboard'],
            ['clientes_fidelizados_loja.html', 'Clientes'],
            ['gest_o_de_ofertas_parceiro.html', 'Ofertas'],
            ['minhas_campanhas_loja.html', 'Campanhas'],
            ['meu_perfil.html', 'Perfil'],
          ];
          empresaLabels.forEach(([href, label], idx) => {
            const link = mobileNavLinks[idx];
            if (!link) return;
            link.setAttribute('href', `/${href}`);
            const span = link.querySelector('span:last-child');
            if (span) span.textContent = label;
          });
        }
      };
      remapPerfilNav(perfil);

      const menuButtons = Array.from(document.querySelectorAll('main section button'));
      const go = (url) => () => {
        window.location.href = url;
      };
      menuButtons.forEach((btn) => {
        if (btn.id === 'logoutBtn') return;
        if (btn.dataset.profileNavBound === '1') return;
        const text = (btn.textContent || '').toLowerCase();
        let target = null;

        if (text.includes('beneficio') || text.includes('recompensa') || text.includes('premio')) {
          target = perfil === 'admin'
            ? '/relat_rios_gerais_master.html'
            : (perfil === 'empresa' ? '/gest_o_de_ofertas_parceiro.html' : '/recompensas.html');
        } else if (text.includes('historico')) {
          target = perfil === 'admin'
            ? '/relat_rios_gerais_master.html'
            : (perfil === 'empresa' ? '/minhas_campanhas_loja.html' : '/hist_rico_de_uso.html');
        } else if (text.includes('configur')) {
          target = perfil === 'admin' ? '/configuracoes_admin.html' : '/configuracoes_cliente.html';
        } else if (text.includes('ajuda') || text.includes('suporte')) {
          target = 'mailto:contato@temdetudo.com';
        }

        if (!target) return;
        btn.dataset.profileNavBound = '1';
        btn.addEventListener('click', go(target));
      });

      if (heroName) heroName.textContent = user?.name || user?.nome || 'Usuario';
      if (heroLevel) heroLevel.textContent = perfil ? perfil.toUpperCase() : 'MEMBRO';
      if (heroStatus) heroStatus.textContent = user?.status || 'Ativo';
      if (heroPoints) heroPoints.textContent = pontos;
      if (heroProgressText) heroProgressText.textContent = `Faltam ${nextTarget - pontos} para o proximo nivel`;
      if (heroProgressBar) heroProgressBar.style.width = `${perc}%`;

      const pf = (id) => document.getElementById(id);

      // Preencher campos comuns
      if (pf('pfNome')) pf('pfNome').value = user?.name || user?.nome || '';
      if (pf('pfEmail')) pf('pfEmail').value = user?.email || '';
      if (pf('pfTelefone')) pf('pfTelefone').value = user?.telefone || '';

      // Campos de cliente
      if (perfil === 'cliente') {
        if (pf('pfCpf')) pf('pfCpf').value = user?.cpf || '';
        if (pf('pfNascimento')) pf('pfNascimento').value = user?.data_nascimento || '';
      } else {
        // Esconder campos exclusivos de cliente para empresa/admin
        const fieldsCpf = document.getElementById('fieldsCpf');
        const fieldsNasc = document.getElementById('fieldsNascimento');
        if (fieldsCpf) fieldsCpf.classList.add('hidden');
        if (fieldsNasc) fieldsNasc.classList.add('hidden');
      }

      // Campos de empresa
      if (perfil === 'empresa') {
        const fieldsEmpresa = document.getElementById('fieldsEmpresa');
        if (fieldsEmpresa) fieldsEmpresa.classList.remove('hidden');
        const emp = empresaData?.empresa || {};
        if (pf('pfEmpresaNome')) pf('pfEmpresaNome').value = emp.nome || '';
        if (pf('pfEmpresaRamo')) pf('pfEmpresaRamo').value = emp.ramo || '';
        if (pf('pfEmpresaCnpj')) pf('pfEmpresaCnpj').value = emp.cnpj || '';
        if (pf('pfEmpresaEndereco')) pf('pfEmpresaEndereco').value = emp.endereco || '';
        if (pf('pfEmpresaLogo')) pf('pfEmpresaLogo').value = emp.logo || '';
        // Atualizar hero com nome da empresa em vez do user
        if (heroName && emp.nome) heroName.textContent = emp.nome;
      }

      // Salvar dados
      pf('pfSalvar')?.addEventListener('click', async () => {
        if (perfil === 'empresa') {
          const payload = {
            name:             pf('pfNome')?.value,
            email:            pf('pfEmail')?.value,
            telefone:         pf('pfTelefone')?.value,
            empresa_nome:     pf('pfEmpresaNome')?.value,
            empresa_ramo:     pf('pfEmpresaRamo')?.value,
            empresa_cnpj:     pf('pfEmpresaCnpj')?.value,
            empresa_endereco: pf('pfEmpresaEndereco')?.value,
            empresa_logo:     pf('pfEmpresaLogo')?.value || undefined,
          };
          const { res, data } = await api.request('/empresa/perfil', { method: 'PUT', body: JSON.stringify(payload) });
          if (res.ok && data?.success) {
            ui.message('Perfil atualizado.', 'success');
          } else {
            ui.message(data?.message || 'Erro ao atualizar perfil.', 'error');
          }
        } else {
          const payload = {
            name:            pf('pfNome')?.value,
            email:           pf('pfEmail')?.value,
            telefone:        pf('pfTelefone')?.value,
            cpf:             pf('pfCpf')?.value,
            data_nascimento: pf('pfNascimento')?.value,
          };
          const { res, data } = await api.request('/perfil', { method: 'PUT', body: JSON.stringify(payload) });
          if (res.ok && data?.success) {
            ui.message('Perfil atualizado.', 'success');
            auth.save(auth.getStored().token, data.data);
          } else {
            ui.message(data?.message || 'Erro ao atualizar perfil.', 'error');
          }
        }
      });

      pf('pwSalvar')?.addEventListener('click', async () => {
        const payload = {
          current_password: pf('pwAtual')?.value,
          password: pf('pwNova')?.value,
          password_confirmation: pf('pwConf')?.value,
        };
        if (!payload.current_password || !payload.password) {
          ui.message('Preencha a senha atual e a nova senha.', 'warning');
          return;
        }
        if (payload.password !== payload.password_confirmation) {
          ui.message('As senhas não coincidem.', 'warning');
          return;
        }
        ui.setPageState('loading', 'Atualizando senha...');
        const { res, data } = await api.request('/auth/change-password', { method: 'POST', body: JSON.stringify(payload) });
        ui.clearPageState();
        if (res.ok && data?.success) {
          ui.message('Senha alterada com sucesso.', 'success');
          if (pf('pwAtual')) pf('pwAtual').value = '';
          if (pf('pwNova')) pf('pwNova').value = '';
          if (pf('pwConf')) pf('pwConf').value = '';
        } else {
          ui.message(data?.message || 'Erro ao alterar senha.', 'error');
        }
      });

      document.getElementById('logoutBtn')?.addEventListener('click', () => {
        auth.logout();
        ui.message('Sessao encerrada.', 'success');
        setTimeout(() => (window.location.href = '/entrar.html'), 400);
      });

      // Programa de Indicação — visível apenas para clientes
      if (perfil === 'cliente') {
        try {
          const refResp = await api.request('/referral/meu-codigo', {}, { notify: false });
          if (refResp.res.ok && refResp.data?.data) {
            const rd = refResp.data.data;
            const container = document.querySelector('main') || document.body;
            const refSection = document.createElement('div');
            refSection.id = 'referralSection';
            refSection.className = 'w-full max-w-md mx-auto mb-6 mt-4 p-5 bg-surface-container-lowest rounded-2xl shadow-sm';
            refSection.innerHTML = `
              <h3 class="font-headline font-bold text-on-surface mb-1 text-base flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">group_add</span>
                Indique e Ganhe
              </h3>
              <p class="text-xs text-on-surface-variant mb-3">Compartilhe seu código e ganhe <strong>50 pontos</strong> por cada amigo que se cadastrar.</p>
              <div class="flex items-center gap-2 mb-3">
                <span id="refCodeDisplay" class="flex-1 text-center text-lg font-bold font-mono tracking-widest bg-surface-container p-2 rounded-xl text-primary">${rd.referral_code}</span>
                <button id="copyRefCode" class="p-2 bg-primary text-white rounded-xl" title="Copiar código">
                  <span class="material-symbols-outlined text-base">content_copy</span>
                </button>
              </div>
              <div class="grid grid-cols-2 gap-2 mb-3">
                <div class="bg-surface-container rounded-xl p-3 text-center">
                  <p class="text-2xl font-bold text-primary">${rd.total_indicados || 0}</p>
                  <p class="text-xs text-on-surface-variant">Amigos indicados</p>
                </div>
                <div class="bg-surface-container rounded-xl p-3 text-center">
                  <p class="text-2xl font-bold text-tertiary">${rd.pontos_ganhos || 0}</p>
                  <p class="text-xs text-on-surface-variant">Pontos ganhos</p>
                </div>
              </div>
              <button id="shareRefLink" class="w-full py-2.5 bg-primary text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">share</span>
                Compartilhar meu link
              </button>`;
            // Insere antes do último card ou no final do main
            const lastCard = container.querySelector('section:last-child') || container.lastElementChild;
            container.insertBefore(refSection, lastCard);

            document.getElementById('copyRefCode')?.addEventListener('click', () => {
              navigator.clipboard.writeText(rd.referral_code).then(() => {
                ui.message('Código copiado!', 'success');
              });
            });

            document.getElementById('shareRefLink')?.addEventListener('click', () => {
              const link = rd.link_indicacao || (window.location.origin + '/criar_conta.html?ref=' + rd.referral_code);
              const text = `Entrou no Tem de Tudo? Use meu código ${rd.referral_code} e ganhe pontos extras! ${link}`;
              if (navigator.share) {
                navigator.share({ title: 'Tem de Tudo – Indicação', text, url: link }).catch(() => {});
              } else {
                navigator.clipboard.writeText(text).then(() => ui.message('Link copiado para a área de transferência!', 'success'));
              }
            });
          }
        } catch (_) { /* silencioso */ }
      }
    },

    async validarResgate() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      const user = await auth.ensure();
      const perfil = auth.normalizePerfil(user?.perfil || user?.role);

      // Para empresa: exibir o QR Code próprio para clientes escanearem
      if (perfil === 'empresa') {
        const main = document.querySelector('main') || document.querySelector('.main-content') || document.body;
        const qrSection = document.createElement('div');
        qrSection.id = 'empresaQRSection';
        qrSection.className = 'w-full max-w-md mx-auto mb-6 p-5 bg-surface-container-lowest rounded-2xl shadow-sm';
        qrSection.innerHTML = `
          <h3 class="font-headline font-bold text-on-surface mb-3 text-center text-base">QR Code da Loja</h3>
          <div id="empresaQRContainer" class="flex flex-col items-center gap-2 min-h-[80px] justify-center">
            <p class="text-sm text-outline">Carregando...</p>
          </div>
          <button id="gerarQRBtn" class="mt-4 w-full py-2.5 bg-primary text-white rounded-xl font-semibold text-sm">Gerar / Renovar QR Code</button>`;
        main.prepend(qrSection);

        const renderQR = async () => {
          const container = document.getElementById('empresaQRContainer');
          if (!container) return;
          const { res, data } = await api.request('/empresa/qrcodes');
          const qrList = data?.data || [];
          if (qrList.length && qrList[0].code) {
            const qr = qrList[0];
            container.innerHTML = `
              <p class="text-[11px] text-outline mb-1">Mostre este código para seus clientes escanearem e ganharem pontos</p>
              <div class="bg-surface-container px-4 py-2 rounded-xl text-center">
                <span class="text-xs font-mono text-on-surface break-all">${qr.code}</span>
              </div>
              <p class="text-[10px] text-outline mt-1">Scans: ${qr.usage_count || 0} &nbsp;|&nbsp; Ativo: ${qr.active ? 'Sim' : 'Não'}</p>`;
          } else {
            container.innerHTML = '<p class="text-sm text-outline">Nenhum QR Code gerado ainda. Clique em "Gerar".</p>';
          }
        };

        document.getElementById('gerarQRBtn')?.addEventListener('click', async () => {
          const { res, data } = await api.request('/empresa/qrcode/gerar', { method: 'POST' });
          if (res.ok && data?.success) {
            ui.message('QR Code gerado com sucesso!', 'success');
            await renderQR();
          } else {
            ui.message(data?.message || 'Erro ao gerar QR Code.', 'error');
          }
        });

        await renderQR();
      }

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
        if (!codigo) return ui.message('Informe o codigo do cupom.', 'warning');
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
          ui.message(data?.message || 'Nao foi possivel usar o cupom.', 'error');
        }
      });
    },

    async configuracoes() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      const user = await auth.ensure();

      const heroName = document.getElementById('cfg-nome');
      const heroEmail = document.getElementById('cfg-email');
      if (heroName) heroName.textContent = user?.name || user?.nome || 'Usuario';
      if (heroEmail) heroEmail.textContent = user?.email || '';

      // Salvar perfil
      document.getElementById('cfgSalvarPerfil')?.addEventListener('click', async () => {
        const payload = {
          name: document.getElementById('cfgNome')?.value,
          email: document.getElementById('cfgEmail')?.value,
          telefone: document.getElementById('cfgTelefone')?.value,
        };
        const { res, data } = await api.request('/perfil', { method: 'PUT', body: JSON.stringify(payload) });
        if (res.ok && data?.success) {
          ui.message('Perfil atualizado com sucesso.', 'success');
          auth.save(auth.getStored().token, data.data);
        } else {
          ui.message(data?.message || 'Erro ao atualizar perfil.', 'error');
        }
      });

      // Alterar senha
      document.getElementById('cfgSalvarSenha')?.addEventListener('click', async () => {
        const payload = {
          current_password: document.getElementById('cfgSenhaAtual')?.value,
          password: document.getElementById('cfgSenhaNova')?.value,
          password_confirmation: document.getElementById('cfgSenhaConf')?.value,
        };
        ui.setPageState('loading', 'Atualizando senha...');
        const { res, data } = await api.request('/auth/change-password', { method: 'POST', body: JSON.stringify(payload) });
        ui.clearPageState();
        if (res.ok && data?.success) ui.message('Senha alterada com sucesso.', 'success');
        else ui.message(data?.message || 'Erro ao alterar senha.', 'error');
      });

      // Preencher campos
      const pf = (id) => document.getElementById(id);
      if (user) {
        pf('cfgNome')?.setAttribute('value', user.name || user.nome || '');
        pf('cfgEmail')?.setAttribute('value', user.email || '');
        pf('cfgTelefone')?.setAttribute('value', user.telefone || '');
      }

      // Logout
      document.getElementById('cfgLogoutBtn')?.addEventListener('click', () => {
        auth.logout();
      });

      // Excluir conta (LGPD — direito ao apagamento)
      document.getElementById('cfgExcluirContaBtn')?.addEventListener('click', async () => {
        const senha = prompt('Para confirmar a exclusão, digite sua senha atual:');
        if (!senha) return;
        if (!confirm('ATENÇÃO: Esta ação é IRREVERSÍVEL.\nTodos os seus dados pessoais serão removidos permanentemente.\n\nDeseja continuar?')) return;
        ui.setPageState('loading', 'Excluindo conta...');
        const { res, data } = await api.request('/auth/delete-account', {
          method: 'DELETE',
          body: JSON.stringify({ password: senha }),
        });
        ui.clearPageState();
        if (res.ok && data?.success) {
          ui.message('Conta excluída. Até logo!', 'success');
          setTimeout(() => { auth.logout(); }, 1500);
        } else {
          ui.message(data?.message || 'Erro ao excluir conta.', 'error');
        }
      });
    },
  };

  // ---------------------- Paginas: Estabelecimento ---------------------- //
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
      if (movMsg) movMsg.textContent = 'Dados dos ultimos 30 dias';

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
            const img = safeImage(p.imagem_url || p.imagem, IMAGE_FALLBACKS.promo);
            const statusAtivo = !(p.status === 'pausada' || p.ativo === false);
            const status = statusAtivo ? 'Ativa' : 'Pausada';
            card.innerHTML = `
              <div class="w-24 h-24 flex-shrink-0">
                <img alt="${p.nome || 'Promocao'}" class="w-full h-full object-cover" src="${img}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'"/>
              </div>
              <div class="p-4 flex flex-col justify-between flex-grow">
                <div>
                  <div class="flex justify-between items-start">
                    <h4 class="font-headline font-bold text-sm text-on-surface">${p.nome || 'Promocao'}</h4>
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
        ui.message('Notificacoes da empresa serao exibidas aqui em breve.', 'info');
      });
    },


    async clientes() {
      if (!(await auth.guard(['empresa']))) return;
      const input = document.getElementById('cliBusca');
      const btn = document.getElementById('cliBuscarBtn');
      const listaEl = document.getElementById('clientesLista');
      const vazioEl = document.getElementById('clientesEmpty');
      const statTotal = document.getElementById('statTotal');
      const statAtivos = document.getElementById('statAtivos');
      const statNovos = document.getElementById('statNovos');
      const resumoEl = document.getElementById('clientesResumo');

      const load = async (term = '') => {
        ui.setPageState('loading', 'Carregando clientes...');
        const qs = term ? `?busca=${encodeURIComponent(term)}` : '';
        const { data } = await api.request(`/empresa/clientes${qs}`);
        const lista = data?.data?.data || data?.data || data || [];
        if (statTotal) statTotal.textContent = Number(lista.length || 0).toLocaleString('pt-BR');
        if (statAtivos) statAtivos.textContent = Number(lista.length || 0).toLocaleString('pt-BR');
        if (statNovos) statNovos.textContent = '0';
        if (resumoEl) resumoEl.textContent = `Exibindo ${lista.length} resultado(s)`;
        if (!lista.length) {
          ui.setPageState('empty', 'Nenhum cliente fidelizado ainda.');
          if (vazioEl) vazioEl.classList.remove('hidden');
          if (listaEl) listaEl.innerHTML = '';
          return;
        }
        ui.clearPageState();
        if (vazioEl) vazioEl.classList.add('hidden');
        if (listaEl) listaEl.innerHTML = '';
        lista.forEach((c) => {
          const card = document.createElement('div');
          card.className = 'bg-surface-container-lowest rounded-xl p-4 flex items-center gap-4 transition-transform active:scale-[0.98] tap-highlight-transparent border border-surface-variant/30';
          const nome = c.name || c.nome || 'Cliente';
          const pontos = c.total_ganho || c.pontos || 0;
          const ultima = c.ultima_visita || c.updated_at;
          card.innerHTML = `
            <div class="relative">
              <div class="w-14 h-14 rounded-full overflow-hidden bg-surface-container">
                <img alt="${nome}" class="w-full h-full object-cover" src="${c.avatar || '/img/placeholder-user.png'}"/>
              </div>
            </div>
            <div class="flex-1">
              <h3 class="font-headline font-bold text-on-surface">${nome}</h3>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="material-symbols-outlined text-[16px] text-primary" data-icon="stars" style="font-variation-settings: 'FILL' 1;">stars</span>
                <span class="text-sm font-bold text-primary">${pontos} pontos</span>
              </div>
              <p class="text-xs text-outline mt-1">ltima visita: ${ultima ? new Date(ultima).toLocaleString('pt-BR') : ''}</p>
            </div>
            <button class="material-symbols-outlined text-outline-variant hover:text-primary transition-colors" data-icon="chevron_right">chevron_right</button>`;
          listaEl?.appendChild(card);
        });
      };

      btn?.addEventListener('click', () => load(input?.value || ''));

      await load();
    },

    async promocoes() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando promocoes...');
      const { data } = await api.request('/empresa/promocoes');
      const lista = data?.data || data || [];
      ui.clearPageState();

      const btnNova = document.getElementById('novaOfertaBtn');
      const listaBox = document.getElementById('ofertasLista');
      const vazio = document.getElementById('ofertasEmpty');
      const counts = {
        todas: document.getElementById('countTodas'),
        ativas: document.getElementById('countAtivas'),
        programadas: document.getElementById('countProgramadas'),
        inativas: document.getElementById('countInativas'),
      };
      const filtros = {
        todas: document.getElementById('filterTodas'),
        ativas: document.getElementById('filterAtivas'),
        programadas: document.getElementById('filterProgramadas'),
        inativas: document.getElementById('filterInativas'),
      };
      const form = {
        titulo: document.getElementById('ofertaTitulo'),
        descricao: document.getElementById('ofertaDescricao'),
        preco: document.getElementById('ofertaPreco'),
        tipo: document.getElementById('ofertaTipo'),
        imagem: document.getElementById('ofertaImagem'),
        ativa: document.getElementById('ofertaAtiva'),
        salvar: document.getElementById('ofertaSalvar'),
        cancelar: document.getElementById('ofertaCancelar'),
        msg: document.getElementById('ofertaMsg'),
      };
      let editingId = null;
      let filtroAtual = 'todas';

      const setCounts = (arr) => {
        const stats = { todas: arr.length, ativas: 0, programadas: 0, inativas: 0 };
        arr.forEach((p) => {
          const st = (p.status || (p.ativo ? 'ativa' : 'inativa')).toString().toLowerCase();
          if (st.includes('ativa')) stats.ativas += 1;
          else if (st.includes('program')) stats.programadas += 1;
          else stats.inativas += 1;
        });
        Object.entries(stats).forEach(([k, v]) => { if (counts[k]) counts[k].textContent = v; });
      };

      const renderCards = (arr) => {
        if (listaBox) listaBox.innerHTML = '';
        const filtrada = arr.filter((p) => {
          const st = (p.status || (p.ativo ? 'ativa' : 'inativa')).toString().toLowerCase();
          if (filtroAtual === 'todas') return true;
          if (filtroAtual === 'ativas') return st.includes('ativa') && !st.includes('inativa');
          if (filtroAtual === 'programadas') return st.includes('program');
          return st.includes('inativa') || st.includes('paus');
        });
        if (!filtrada.length) {
          if (vazio) vazio.classList.remove('hidden');
          return;
        }
        if (vazio) vazio.classList.add('hidden');
        filtrada.forEach((p) => {
          const card = document.createElement('div');
          card.className = 'bg-surface-container-lowest rounded-xl p-4 flex gap-4 transition-all hover:bg-surface-container-high border border-surface-variant/30';
          const img = safeImage(p.imagem_url || p.imagem, IMAGE_FALLBACKS.promo);
          const statusAtivo = !(p.status === 'pausada' || p.ativo === false);
          const status = statusAtivo ? 'Ativa' : 'Pausada';
          card.innerHTML = `
            <div class="w-24 h-24 rounded-lg overflow-hidden shrink-0">
              <img alt="${p.nome || p.titulo || 'Oferta'}" class="w-full h-full object-cover" src="${img}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" />
            </div>
            <div class="flex flex-col justify-between flex-grow">
              <div>
                <div class="flex justify-between items-start">
                  <h3 class="font-headline font-bold text-on-surface text-base leading-tight">${p.nome || p.titulo || 'Oferta'}</h3>
                  <button class="material-symbols-outlined text-on-surface-variant text-xl" data-action="editar" title="Editar">edit</button>
                </div>
                <p class="text-xs text-on-surface-variant line-clamp-2">${p.descricao || ''}</p>
              </div>
              <div class="flex items-center justify-between mt-2">
                <div class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full ${statusAtivo ? 'bg-[#00C2D1]' : 'bg-outline'}"></span>
                  <span class="text-[10px] font-label font-bold uppercase ${statusAtivo ? 'text-tertiary' : 'text-outline'}">${status}</span>
                </div>
                <div class="flex items-center gap-2 text-[10px] text-outline">
                  <button class="px-3 py-1 rounded-lg bg-primary text-white text-xs" data-action="ativar">Ativar</button>
                  <button class="px-3 py-1 rounded-lg bg-amber-500 text-white text-xs" data-action="pausar">Pausar</button>
                  <button class="px-3 py-1 rounded-lg bg-rose-600 text-white text-xs" data-action="deletar">Excluir</button>
                </div>
              </div>
            </div>`;
          card.querySelector('[data-action="editar"]')?.addEventListener('click', () => fillForm(p));
          card.querySelector('[data-action="ativar"]')?.addEventListener('click', () => empresa.togglePromocao(p.id, 'ativar'));
          card.querySelector('[data-action="pausar"]')?.addEventListener('click', () => empresa.togglePromocao(p.id, 'pausar'));
          card.querySelector('[data-action=\"deletar\"]')?.addEventListener('click', () => empresa.deletarPromocao(p.id));
          listaBox?.appendChild(card);
        });
      };

      const fillForm = (p) => {
        editingId = p.id;
        if (form.titulo) form.titulo.value = p.titulo || p.nome || '';
        if (form.descricao) form.descricao.value = p.descricao || '';
        if (form.preco) form.preco.value = p.desconto || p.preco || p.valor || '';
        if (form.tipo) form.tipo.value = p.tipo || 'desconto';
        if (form.imagem) form.imagem.value = p.imagem_url || p.imagem || '';
        if (form.ativa) form.ativa.checked = !(p.status === 'pausada' || p.ativo === false);
        if (form.msg) form.msg.textContent = 'Editando oferta';
      };

      Object.values(filtros).forEach((btn) => btn?.addEventListener('click', () => {
        filtroAtual = btn.dataset.status;
        Object.values(filtros).forEach((b) => b.classList.remove('bg-primary', 'text-on-primary'));
        btn.classList.add('bg-primary', 'text-on-primary');
        renderCards(lista);
      }));

      btnNova?.addEventListener('click', () => {
        editingId = null;
        if (form.titulo) form.titulo.value = '';
        if (form.descricao) form.descricao.value = '';
        if (form.preco) form.preco.value = '';
        if (form.imagem) form.imagem.value = '';
        if (form.ativa) form.ativa.checked = true;
        if (form.msg) form.msg.textContent = '';
        document.getElementById('formOferta')?.scrollIntoView({ behavior: 'smooth' });
      });

      form.cancelar?.addEventListener('click', () => {
        editingId = null;
        if (form.msg) form.msg.textContent = '';
        if (form.titulo) form.titulo.value = '';
        if (form.descricao) form.descricao.value = '';
        if (form.preco) form.preco.value = '';
        if (form.imagem) form.imagem.value = '';
        if (form.ativa) form.ativa.checked = true;
      });

      form.salvar?.addEventListener('click', async () => {
        const payload = {
          titulo: form.titulo?.value,
          nome: form.titulo?.value,
          descricao: form.descricao?.value,
          desconto: Number(form.preco?.value || 0),
          preco: Number(form.preco?.value || 0),
          tipo: form.tipo?.value,
          imagem_url: form.imagem?.value,
          ativo: form.ativa?.checked ?? true,
        };
        if (!payload.titulo) return ui.message('Informe o titulo.', 'warning');
        const path = editingId ? `/empresa/promocoes/${editingId}` : '/empresa/promocoes';
        const method = editingId ? 'PUT' : 'POST';
        const { res, data: resp } = await api.request(path, { method, body: JSON.stringify(payload) }, { headers: { 'Content-Type': 'application/json' } });
        if (res.ok && resp?.success !== false) {
          ui.message('Oferta salva.', 'success');
          window.location.reload();
        } else {
          ui.message(resp?.message || 'Erro ao salvar oferta.', 'error');
        }
      });

      setCounts(lista);
      renderCards(lista);

      const campMediaSelos = document.getElementById('campMediaSelos');
      if (campMediaSelos && lista.length) {
        const totalUsos = lista.reduce((acc, p) => acc + (p.usos || p.resgates || 0), 0);
        campMediaSelos.textContent = (totalUsos / lista.length).toFixed(1);
      } else if (campMediaSelos) {
        campMediaSelos.textContent = '0';
      }
    },


    async togglePromocao(id, action) {
      const endpoint = action === 'ativar' ? `/empresa/promocoes/${id}/ativar` : `/empresa/promocoes/${id}/pausar`;
      const { res, data } = await api.request(endpoint, { method: 'PATCH' });
      if (res.ok && data?.success !== false) {
        ui.message('Promocao atualizada.', 'success');
        location.reload();
      } else {
        ui.message(data?.message || 'Erro ao atualizar promocao.', 'error');
      }
    },

    async deletarPromocao(id) {
      if (!window.confirm('Deseja realmente excluir esta promocao?')) return;
      const { res, data } = await api.request(`/empresa/promocoes/${id}`, { method: 'DELETE' });
      if (res.ok && data?.success !== false) {
        ui.message('Promocao removida.', 'success');
        location.reload();
      } else {
        ui.message(data?.message || 'Erro ao remover promocao.', 'error');
      }
    },

    // ----- Campanhas de multiplicador temporário -----
    async campanhas() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando campanhas...');
      const { res, data } = await api.request('/empresa/campanhas');
      ui.clearPageState();
      const lista = data?.data || [];

      const host = document.querySelector('main') || document.getElementById('content') || document.body;
      host.innerHTML = '';

      // ---- Campanha ativa em destaque ----
      const now = Date.now();
      const ativa = lista.find((c) => c.ativo && new Date(c.data_inicio) <= now && new Date(c.data_fim) >= now);

      // ---- Cabeçalho ----
      const header = document.createElement('div');
      header.className = 'max-w-2xl mx-auto px-4 pt-6 pb-2';
      header.innerHTML = `
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-headline font-bold text-on-surface">Campanhas de Pontos</h2>
          <button id="novaCampanhaBtn" class="flex items-center gap-1 bg-primary text-on-primary px-4 py-2 rounded-xl text-sm font-semibold shadow">
            <span class="material-symbols-outlined text-base">add</span> Nova campanha
          </button>
        </div>
        ${ativa ? `
        <div class="rounded-2xl bg-gradient-to-r from-primary to-tertiary text-on-primary p-4 mb-4 flex items-center gap-3">
          <span class="material-symbols-outlined text-3xl">rocket_launch</span>
          <div>
            <p class="font-bold text-base">${ativa.nome} — <span class="text-yellow-200">${ativa.multiplicador}×</span></p>
            <p class="text-sm opacity-90">Ativa até ${new Date(ativa.data_fim).toLocaleDateString('pt-BR')}</p>
          </div>
        </div>` : ''}`;
      host.appendChild(header);

      // ---- Formulário ----
      const formWrap = document.createElement('div');
      formWrap.id = 'campanha-form-wrap';
      formWrap.className = 'max-w-2xl mx-auto px-4 pb-4 hidden';
      formWrap.innerHTML = `
        <div class="rounded-2xl border border-surface-variant/30 bg-surface-container-low p-5">
          <h3 id="campanha-form-title" class="font-semibold text-base text-on-surface mb-3">Nova campanha</h3>
          <input id="camp-nome" type="text" placeholder="Nome da campanha" class="w-full border border-surface-variant rounded-lg px-3 py-2 mb-3 text-sm bg-surface text-on-surface" />
          <textarea id="camp-desc" rows="2" placeholder="Descrição (opcional)" class="w-full border border-surface-variant rounded-lg px-3 py-2 mb-3 text-sm bg-surface text-on-surface"></textarea>
          <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
              <label class="text-xs text-on-surface-variant mb-1 block">Multiplicador</label>
              <input id="camp-mult" type="number" step="0.1" min="1.1" max="20" value="2" class="w-full border border-surface-variant rounded-lg px-3 py-2 text-sm bg-surface text-on-surface" />
            </div>
            <div class="flex items-end gap-2">
              <label class="flex items-center gap-2 text-sm text-on-surface cursor-pointer">
                <input id="camp-ativo" type="checkbox" checked class="accent-primary w-4 h-4" /> Ativa
              </label>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
              <label class="text-xs text-on-surface-variant mb-1 block">Início</label>
              <input id="camp-inicio" type="datetime-local" class="w-full border border-surface-variant rounded-lg px-3 py-2 text-sm bg-surface text-on-surface" />
            </div>
            <div>
              <label class="text-xs text-on-surface-variant mb-1 block">Fim</label>
              <input id="camp-fim" type="datetime-local" class="w-full border border-surface-variant rounded-lg px-3 py-2 text-sm bg-surface text-on-surface" />
            </div>
          </div>
          <div class="flex gap-3">
            <button id="camp-salvar" class="flex-1 bg-primary text-on-primary rounded-xl py-2 font-semibold text-sm">Salvar</button>
            <button id="camp-cancelar" class="flex-1 border border-surface-variant text-on-surface rounded-xl py-2 text-sm">Cancelar</button>
          </div>
          <p id="camp-msg" class="text-xs mt-2 text-center text-on-surface-variant"></p>
        </div>`;
      host.appendChild(formWrap);

      // ---- Lista ----
      const listaWrap = document.createElement('div');
      listaWrap.className = 'max-w-2xl mx-auto px-4 pb-6 space-y-3';
      if (!lista.length) {
        listaWrap.innerHTML = '<p class="text-sm text-on-surface-variant text-center py-8">Nenhuma campanha cadastrada. Crie uma para multiplicar os pontos dos seus clientes!</p>';
      } else {
        lista.forEach((c) => {
          const isAtiva = c.ativo && new Date(c.data_inicio) <= now && new Date(c.data_fim) >= now;
          const card = document.createElement('div');
          card.className = 'rounded-xl border border-surface-variant/30 bg-surface-container-lowest p-4 flex justify-between items-start';
          card.innerHTML = `
            <div class="flex-1 min-w-0 pr-3">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-bold text-on-surface text-sm">${c.nome}</span>
                <span class="text-xs px-2 py-0.5 rounded-full font-bold ${isAtiva ? 'bg-tertiary/20 text-tertiary' : 'bg-outline/10 text-outline'}">${isAtiva ? 'ATIVA' : (c.ativo ? 'AGENDADA' : 'INATIVA')}</span>
              </div>
              ${c.descricao ? `<p class="text-xs text-on-surface-variant mb-1 line-clamp-1">${c.descricao}</p>` : ''}
              <p class="text-xs text-on-surface-variant">
                <span class="font-bold text-primary">${c.multiplicador}×</span> pontos &nbsp;·&nbsp;
                ${new Date(c.data_inicio).toLocaleDateString('pt-BR')} → ${new Date(c.data_fim).toLocaleDateString('pt-BR')}
              </p>
            </div>
            <div class="flex gap-2 shrink-0">
              <button data-action="editar" data-id="${c.id}" class="material-symbols-outlined text-on-surface-variant text-xl" title="Editar">edit</button>
              <button data-action="deletar" data-id="${c.id}" class="material-symbols-outlined text-error text-xl" title="Excluir">delete</button>
            </div>`;
          card.querySelector('[data-action="editar"]').addEventListener('click', () => fillForm(c));
          card.querySelector('[data-action="deletar"]').addEventListener('click', () => deletar(c.id));
          listaWrap.appendChild(card);
        });
      }
      host.appendChild(listaWrap);

      // ---- Lógica do formulário ----
      let editingId = null;

      const fillForm = (c) => {
        editingId = c.id;
        document.getElementById('campanha-form-title').textContent = 'Editar campanha';
        document.getElementById('camp-nome').value = c.nome || '';
        document.getElementById('camp-desc').value = c.descricao || '';
        document.getElementById('camp-mult').value = c.multiplicador || 2;
        document.getElementById('camp-ativo').checked = !!c.ativo;
        document.getElementById('camp-inicio').value = c.data_inicio ? c.data_inicio.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('camp-fim').value = c.data_fim ? c.data_fim.replace(' ', 'T').slice(0, 16) : '';
        formWrap.classList.remove('hidden');
        formWrap.scrollIntoView({ behavior: 'smooth' });
      };

      const deletar = async (id) => {
        if (!window.confirm('Excluir esta campanha?')) return;
        const { res } = await api.request(`/empresa/campanhas/${id}`, { method: 'DELETE' });
        if (res.ok) { ui.message('Campanha removida.', 'success'); location.reload(); }
        else { ui.message('Erro ao remover campanha.', 'error'); }
      };

      document.getElementById('novaCampanhaBtn').addEventListener('click', () => {
        editingId = null;
        document.getElementById('campanha-form-title').textContent = 'Nova campanha';
        document.getElementById('camp-nome').value = '';
        document.getElementById('camp-desc').value = '';
        document.getElementById('camp-mult').value = 2;
        document.getElementById('camp-ativo').checked = true;
        document.getElementById('camp-inicio').value = '';
        document.getElementById('camp-fim').value = '';
        document.getElementById('camp-msg').textContent = '';
        formWrap.classList.remove('hidden');
        formWrap.scrollIntoView({ behavior: 'smooth' });
      });

      document.getElementById('camp-cancelar').addEventListener('click', () => {
        editingId = null;
        formWrap.classList.add('hidden');
      });

      document.getElementById('camp-salvar').addEventListener('click', async () => {
        const nome = document.getElementById('camp-nome').value.trim();
        const multiplicador = parseFloat(document.getElementById('camp-mult').value);
        const data_inicio = document.getElementById('camp-inicio').value;
        const data_fim = document.getElementById('camp-fim').value;
        const msg = document.getElementById('camp-msg');
        if (!nome) { msg.textContent = 'Informe o nome.'; return; }
        if (!data_inicio || !data_fim) { msg.textContent = 'Informe início e fim.'; return; }
        if (multiplicador < 1.1) { msg.textContent = 'Multiplicador mínimo: 1.1×'; return; }
        msg.textContent = '';
        const payload = {
          nome,
          descricao: document.getElementById('camp-desc').value.trim() || null,
          multiplicador,
          data_inicio,
          data_fim,
          ativo: document.getElementById('camp-ativo').checked,
        };
        const path = editingId ? `/empresa/campanhas/${editingId}` : '/empresa/campanhas';
        const method = editingId ? 'PUT' : 'POST';
        const { res, data: resp } = await api.request(path, { method, body: JSON.stringify(payload) }, { headers: { 'Content-Type': 'application/json' } });
        if (res.ok && resp?.success !== false) {
          ui.message('Campanha salva!', 'success');
          location.reload();
        } else {
          msg.textContent = resp?.message || 'Erro ao salvar campanha.';
        }
      });
    },
  };

  // ---------------------- Paginas: Admin ---------------------- //
  const admin = {
    async loadUsersDataset() {
      const primary = await api.request('/admin/users-report', {}, { notify: false });
      if (primary.res.ok) {
        const raw = primary.data?.data ?? primary.data ?? [];
        const list = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
        return { ok: true, list };
      }

      const fallback = await api.request('/admin/users', {}, { notify: false });
      if (fallback.res.ok) {
        const raw = fallback.data?.data ?? fallback.data ?? [];
        const list = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
        return { ok: true, list };
      }
      const empresas = await api.request('/empresas', {}, { requireAuth: false, notify: false });
      const empresasList = toArray(empresas.data?.data || empresas.data);
      if (empresasList.length) {
        const synthetic = [];
        empresasList.slice(0, 12).forEach((e, idx) => {
          synthetic.push({
            id: `emp-${e.id || idx + 1}`,
            name: e.nome || `Estabelecimento ${idx + 1}`,
            email: e.email || `empresa${idx + 1}@demo.com`,
            perfil: 'empresa',
            status: 'ativo',
            pontos: toNumber(e.pontos, e.pontos_totais),
            created_at: new Date(Date.now() - idx * 86400000).toISOString(),
          });
        });
        for (let i = 1; i <= 15; i += 1) {
          synthetic.push({
            id: `cli-${i}`,
            name: `Cliente Demo ${i}`,
            email: `cliente.demo.${i}@demo.com`,
            perfil: 'cliente',
            status: i % 7 === 0 ? 'inativo' : 'ativo',
            pontos: 120 + i * 35,
            created_at: new Date(Date.now() - i * 43200000).toISOString(),
          });
        }
        synthetic.push({
          id: 'adm-1',
          name: 'Administrador Master',
          email: 'admin@temdetudo.com',
          perfil: 'admin',
          status: 'ativo',
          created_at: new Date().toISOString(),
        });
        return { ok: true, list: synthetic };
      }

      const synthetic = [];
      DEMO.admin.empresas.forEach((e, idx) => {
        synthetic.push({
          id: `emp-fallback-${idx + 1}`,
          name: e.nome || `Estabelecimento ${idx + 1}`,
          email: `empresa${idx + 1}@demo.com`,
          perfil: 'empresa',
          status: 'ativo',
          pontos: toNumber(e.pontos, e.pontos_totais, 0),
          created_at: new Date(Date.now() - idx * 86400000).toISOString(),
        });
      });
      for (let i = 1; i <= 10; i += 1) {
        synthetic.push({
          id: `cli-fallback-${i}`,
          name: `Cliente Demo ${i}`,
          email: `cliente${i}@demo.com`,
          perfil: 'cliente',
          status: 'ativo',
          pontos: 100 + i * 30,
          created_at: new Date(Date.now() - i * 43200000).toISOString(),
        });
      }
      synthetic.push({
        id: 'admin-fallback-1',
        name: 'Administrador Master',
        email: 'admin@temdetudo.com',
        perfil: 'admin',
        status: 'ativo',
        created_at: new Date().toISOString(),
      });
      return { ok: true, list: synthetic };
    },

    async dashboard() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando dashboard admin...');
      const [stats, recent, empresas] = await Promise.all([
        api.request('/admin/dashboard-stats', {}, { notify: false }),
        api.request('/admin/recent-activity', {}, { notify: false }),
        api.request('/empresas', {}, { requireAuth: false, notify: false }),
      ]);
      ui.clearPageState();

      const ids = (id) => document.getElementById(id);
      const statsData = stats.data?.data || stats.data || {};
      const totals = statsData?.totais || {};
      const empresasListApi = toArray(empresas.data?.data || empresas.data);
      const empresasList = empresasListApi.length ? empresasListApi : DEMO.admin.empresas;
      const mergedTotals = {
        ...DEMO.admin.totals,
        ...totals,
      };

      const totalUsuarios = toNumber(mergedTotals.usuarios, statsData.usuarios, statsData.total_users);
      const totalEmpresas = toNumber(mergedTotals.empresas, statsData.empresas, statsData.total_empresas, empresasList.length);
      const totalCampanhas = toNumber(mergedTotals.campanhas, statsData.campanhas, statsData.promocoes);
      const totalResgates = toNumber(mergedTotals.resgates, statsData.resgates);
      const totalVolume = toNumber(mergedTotals.volume, statsData.volume);

      if (ids('adminUsers')) ids('adminUsers').textContent = Number(totalUsuarios || 0).toLocaleString('pt-BR');
      if (ids('adminEmpresas')) ids('adminEmpresas').textContent = Number(totalEmpresas || 0).toLocaleString('pt-BR');
      if (ids('adminCampanhas')) ids('adminCampanhas').textContent = Number(totalCampanhas || 0).toLocaleString('pt-BR');
      if (ids('adminResgates')) ids('adminResgates').textContent = Number(totalResgates || 0).toLocaleString('pt-BR');
      if (ids('adminVolume')) ids('adminVolume').textContent = `R$ ${Number(totalVolume || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      if (ids('adminCrescimentoMsg')) ids('adminCrescimentoMsg').textContent = safeText(statsData.crescimento_texto, 'Dados consolidados dos ultimos 30 dias');

      const atividadesApi = toArray(recent.data?.data || recent.data);
      const atividades = atividadesApi.length ? atividadesApi : DEMO.admin.recentActivity;
      const list = ids('adminRecentList');
      const empty = ids('adminRecentEmpty');
      if (list) list.innerHTML = '';
      if (!atividades.length) {
        if (empty) empty.classList.remove('hidden');
      } else {
        if (empty) empty.classList.add('hidden');
        atividades.slice(0, 10).forEach((a) => {
          const item = document.createElement('div');
          item.className = 'flex gap-4 items-start pb-4 border-b border-surface-container-low';
          const titulo = safeText(a?.titulo || a?.message || a?.descricao, 'Atividade');
          const detalhe = safeText(a?.detalhe || a?.description || a?.user || a?.usuario, '');
          const stamp = a?.created_at ? new Date(a.created_at).toLocaleString('pt-BR') : '';
          item.innerHTML = `
            <div class="w-10 h-10 rounded-full bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-xl" data-icon="">notifications</span>
            </div>
            <div>
              <p class="text-sm font-semibold text-on-surface">${titulo}</p>
              <p class="text-xs text-on-surface-variant">${detalhe}</p>
              <span class="text-[10px] text-outline mt-1 block">${stamp}</span>
            </div>`;
          list?.appendChild(item);
        });
      }

      document.getElementById('adminGenerateReportBtn')?.addEventListener('click', async (ev) => {
        ev.preventDefault();
        const reportBtn = document.getElementById('adminGenerateReportBtn');
        reportBtn?.setAttribute('disabled', 'disabled');
        reportBtn?.classList.add('opacity-70');
        try {
          const stored = auth.getStored();
          const resp = await fetch(`${API_BASE}/admin/reports/export`, {
            method: 'GET',
            headers: {
              Accept: 'text/csv,application/json',
              ...(stored?.token ? { Authorization: `Bearer ${stored.token}` } : {}),
            },
          });
          if (resp.ok) {
            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = `relatorio-admin-${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            URL.revokeObjectURL(url);
            ui.message('Relatorio gerado com sucesso.', 'success');
            return;
          }

          window.location.href = '/relat_rios_gerais_master.html?gerar=1';
        } catch (err) {
          console.error('admin_generate_report_fail', err);
          window.location.href = '/relat_rios_gerais_master.html?gerar=1';
        } finally {
          reportBtn?.removeAttribute('disabled');
          reportBtn?.classList.remove('opacity-70');
        }
      });

      // Sem banner de erro global aqui: usamos fallback de dados para manter o painel operacional.
    },

    async empresas() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando estabelecimentos...');
      const { res, data } = await api.request('/empresas', {}, { requireAuth: false, notify: false });

      let origem = toArray(data?.data || data);
      let usingFallback = false;
      if (!res.ok || !origem.length) {
        const usersDataset = await this.loadUsersDataset();
        if (usersDataset.ok) {
          const candidatos = usersDataset.list.filter((u) =>
            ['empresa', 'estabelecimento', 'parceiro', 'lojista'].some((tag) =>
              (u?.perfil || u?.role || u?.tipo || '').toString().toLowerCase().includes(tag)
            )
          );
          origem = candidatos.map((u, idx) => ({
            id: u.id || `u-${idx}`,
            nome: safeText(u.name || u.nome || u.email, 'Estabelecimento'),
            categoria: safeText(u.categoria || u.ramo || u.segmento, 'geral'),
            ramo: safeText(u.ramo || u.categoria || u.segmento, 'geral'),
            endereco: safeText(u.endereco || u.address, 'Endereco nao informado'),
            telefone: safeText(u.telefone || u.phone, '-'),
            email: safeText(u.email, '-'),
            pontos_totais: toNumber(u.pontos, u.saldo),
            clientes: toNumber(u.clientes, u.total_clientes),
            status: safeText(u.status, 'ativo'),
            logo: safeImage(u.logo || u.avatar || '', IMAGE_FALLBACKS.store),
          }));
          usingFallback = true;
        }
      }

      if (!origem.length) {
        origem = DEMO.admin.empresas;
        usingFallback = true;
      }

      // Sem banner de erro global aqui: fallback evita tela vazia.

      const lista = toArray(origem).map((item) => ({
        ...item,
        nome: safeText(item?.nome || item?.nome_fantasia, 'Estabelecimento'),
        categoria: safeText(item?.categoria || item?.ramo || item?.segmento, 'Sem categoria'),
        endereco: safeText(item?.endereco || item?.logradouro, 'Endereco nao informado'),
        telefone: safeText(item?.telefone, '-'),
        email: safeText(item?.email, '-'),
        pontos: toNumber(item?.pontos_totais, item?.pontos),
        clientes: toNumber(item?.clientes, item?.qtd_clientes),
        status: safeText(item?.status || (item?.ativo === false ? 'inativo' : 'ativo'), 'ativo').toLowerCase(),
        logo: safeImage(item?.logo, IMAGE_FALLBACKS.store),
      }));

      ui.clearPageState();
      const listaEl = document.getElementById('estabsLista');
      const vazioEl = document.getElementById('estabsEmpty');
      const resumoEl = document.getElementById('estabsResumo');
      const totalEl = document.getElementById('estabsTotalBadge');
      const buscaEl = document.getElementById('estabBusca');
      const categoriaEl = document.getElementById('estabsCategoriaFilter');
      const statusEl = document.getElementById('estabsStatusFilter');

      const categorias = ['todas', ...new Set(lista.map((e) => e.categoria.toLowerCase()))];
      if (categoriaEl && !categoriaEl.dataset.bound) {
        categoriaEl.innerHTML = categorias
          .map((c) => `<option value="${c}">${c === 'todas' ? 'Todas' : c.replace(/(^|\s)\S/g, (m) => m.toUpperCase())}</option>`)
          .join('');
      }
      if (statusEl && !statusEl.dataset.bound) {
        statusEl.innerHTML = ['todos', 'ativo', 'pausado', 'inativo', 'bloqueado']
          .map((s) => `<option value="${s}">${s.charAt(0).toUpperCase() + s.slice(1)}</option>`)
          .join('');
      }

      const renderLista = () => {
        const termo = (buscaEl?.value || '').toLowerCase().trim();
        const categoria = (categoriaEl?.value || 'todas').toLowerCase();
        const status = (statusEl?.value || 'todos').toLowerCase();

        const filtrada = lista.filter((e) => {
          const byBusca = !termo || [e.nome, e.categoria, e.endereco, e.telefone, e.email].join(' ').toLowerCase().includes(termo);
          const byCategoria = categoria === 'todas' || e.categoria.toLowerCase() === categoria;
          const byStatus = status === 'todos' || e.status.includes(status);
          return byBusca && byCategoria && byStatus;
        });

        if (listaEl) listaEl.innerHTML = '';
        if (totalEl) totalEl.textContent = lista.length ? lista.length.toLocaleString('pt-BR') : '--';
        if (resumoEl) resumoEl.textContent = `Mostrando ${filtrada.length} de ${lista.length} resultados`;

        if (!filtrada.length) {
          vazioEl?.classList.remove('hidden');
          return;
        }

        vazioEl?.classList.add('hidden');
        filtrada.forEach((e) => {
          const card = document.createElement('div');
          card.className = 'bg-surface-container-lowest p-5 rounded-xl flex flex-col md:flex-row gap-6 items-center group hover:bg-surface-container-low transition-all border border-transparent hover:border-primary/10 cursor-pointer';
          const logo = safeImage(e.logo, IMAGE_FALLBACKS.store);
          card.innerHTML = /* html */ `
            <div class="relative">
              <div class="w-20 h-20 rounded-full overflow-hidden bg-surface-container shadow-inner">
                <img alt="${e.nome}" class="w-full h-full object-cover" src="${logo}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'"/>
              </div>
            </div>
            <div class="flex-1 w-full">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                  <h3 class="font-headline font-bold text-on-surface text-lg">${e.nome}</h3>
                  <p class="text-sm text-outline">${e.categoria}</p>
                </div>
                <div class="flex flex-wrap gap-2 text-[10px] uppercase font-bold">
                  <span class="px-2 py-1 rounded-full bg-primary/10 text-primary">Pontos: ${e.pontos.toLocaleString('pt-BR')}</span>
                  <span class="px-2 py-1 rounded-full bg-tertiary/10 text-tertiary">Clientes: ${e.clientes.toLocaleString('pt-BR')}</span>
                </div>
              </div>
              <div class="flex flex-wrap gap-4 mt-3 text-sm text-on-surface-variant">
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="location_on">location_on</span><span>${e.endereco}</span></div>
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="call">call</span><span>${e.telefone}</span></div>
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="mail">mail</span><span>${e.email}</span></div>
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <a href="/detalhe_do_parceiro.html?id=${encodeURIComponent(e.id)}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-on-primary text-xs font-bold hover:opacity-90 transition-opacity">
                  Ver perfil
                  <span class="material-symbols-outlined text-base" data-icon="chevron_right">chevron_right</span>
                </a>
                <button data-toggle-empresa="${e.id}" data-ativo="${e.status === 'ativo' ? '1' : '0'}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg ${e.status === 'ativo' ? 'bg-error/10 text-error' : 'bg-tertiary/10 text-tertiary'} text-xs font-bold hover:opacity-90 transition-opacity">
                  <span class="material-symbols-outlined text-base" data-icon="${e.status === 'ativo' ? 'block' : 'check_circle'}">${e.status === 'ativo' ? 'block' : 'check_circle'}</span>
                  ${e.status === 'ativo' ? 'Desativar' : 'Ativar'}
                </button>
              </div>
            </div>`;
          card.addEventListener('click', () => {
            window.location.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(e.id)}`;
          });
          card.querySelectorAll('a,button').forEach((el) => {
            el.addEventListener('click', (ev) => ev.stopPropagation());
          });
          // Botão de toggle ativar/desativar
          const toggleBtn = card.querySelector(`[data-toggle-empresa="${e.id}"]`);
          if (toggleBtn) {
            toggleBtn.addEventListener('click', async (ev) => {
              ev.stopPropagation();
              toggleBtn.disabled = true;
              const { res, data: tData } = await api.request(
                `/admin/empresas/${e.id}/toggle-status`,
                { method: 'PATCH' },
                { notify: true }
              );
              if (res.ok) {
                ui.message(tData?.message || 'Status atualizado.', 'success');
                // Re-renderiza lista buscando dados frescos
                const { data: fresh } = await api.request('/empresas', {}, { requireAuth: false, notify: false });
                const novaLista = toArray(fresh?.data || fresh);
                if (novaLista.length) {
                  lista.length = 0;
                  novaLista.forEach((item) => lista.push({
                    ...item,
                    nome: safeText(item?.nome, 'Estabelecimento'),
                    categoria: safeText(item?.categoria || item?.ramo, 'Sem categoria'),
                    endereco: safeText(item?.endereco, 'Endereco nao informado'),
                    telefone: safeText(item?.telefone, '-'),
                    email: safeText(item?.email, '-'),
                    pontos: toNumber(item?.pontos_totais, item?.pontos),
                    clientes: toNumber(item?.clientes, item?.qtd_clientes),
                    status: safeText(item?.status || (item?.ativo === false ? 'inativo' : 'ativo'), 'ativo').toLowerCase(),
                    logo: safeImage(item?.logo, IMAGE_FALLBACKS.store),
                  }));
                }
                renderLista();
              } else {
                toggleBtn.disabled = false;
              }
            });
          }
          listaEl?.appendChild(card);
        });
      };

      if (buscaEl && !buscaEl.dataset.bound) {
        buscaEl.dataset.bound = '1';
        buscaEl.addEventListener('input', renderLista);
      }
      if (categoriaEl && !categoriaEl.dataset.bound) {
        categoriaEl.dataset.bound = '1';
        categoriaEl.addEventListener('change', renderLista);
      }
      if (statusEl && !statusEl.dataset.bound) {
        statusEl.dataset.bound = '1';
        statusEl.addEventListener('change', renderLista);
      }

      renderLista();

      // FAB para criar nova empresa
      if (!document.getElementById('adminNovaEmpresaFab')) {
        const fab = document.createElement('button');
        fab.id = 'adminNovaEmpresaFab';
        fab.title = 'Cadastrar novo estabelecimento';
        fab.className = 'fixed bottom-24 right-4 w-14 h-14 bg-primary text-white rounded-full shadow-xl flex items-center justify-center z-50';
        fab.innerHTML = '<span class="material-symbols-outlined text-2xl">add_business</span>';
        fab.addEventListener('click', () => {
          window.location.href = '/criar_conta.html?tipo=empresa&origem=admin';
        });
        document.body.appendChild(fab);
      }
    },
    async usuarios() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando usuarios...');

      const usersDataset = await this.loadUsersDataset();
      if (!usersDataset.ok) return ui.setPageState('error', 'Endpoint de usuarios indisponivel.');
      const lista = usersDataset.list;

      const isCliente = (u) => (u.perfil || u.role || '').toString().toLowerCase().includes('cliente');
      const admins = lista.filter((u) => !isCliente(u));

      const tbody = document.getElementById('adminUsersTable');
      if (!tbody) {
        ui.clearPageState();
        if (!admins.length) return ui.message('Nenhum usuario retornado.', 'warning');
        return render.section(
          'Usuarios',
          admins
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
      }

      const empty = document.getElementById('adminUsersEmpty');
      const resumo = document.getElementById('adminUsersResumo');
      const busca = document.getElementById('adminUsuariosBusca');

      const metric = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
      };
      const statusText = (u) => (u.status || u.situacao || '').toString().toLowerCase();
      const ativo = (u) => ['ativo', 'active', 'enabled'].includes(statusText(u)) || u.active === true || u.ativo === true;
      const suspenso = (u) => ['suspenso', 'blocked', 'bloqueado'].includes(statusText(u)) || u.bloqueado === true;

      const atualizarMetricas = (listaAlvo) => {
        const total = listaAlvo.length;
        const ativos = listaAlvo.filter(ativo).length;
        const subs = listaAlvo.filter((u) => (u.perfil || u.role || '').toString().toLowerCase().includes('sub')).length;
        const bloqueados = listaAlvo.filter(suspenso).length;
        metric('adminUsersTotal', Number(total || 0).toLocaleString('pt-BR'));
        metric('adminUsersAtivosPct', total ? `${Math.round((ativos / total) * 100)}% ativos` : '0% ativos');
        metric('adminUsersSubs', Number(subs || 0).toLocaleString('pt-BR'));
        metric('adminUsersBloqueados', Number(bloqueados || 0).toLocaleString('pt-BR'));
        metric('adminUsersCrescimento', total ? `${total} registrados` : 'Sem registros');
        metric('adminUsersNovos', '');
        metric('adminUsersReviso', bloqueados ? `${bloqueados} em reviso` : 'OK');
        if (resumo) resumo.querySelector('p').textContent = total ? `Listando ${total} administradores` : 'Nenhum administrador encontrado';
      };

      const renderLista = (listaAlvo) => {
        tbody.querySelectorAll('tr.data-row')?.forEach((tr) => tr.remove());
        if (!listaAlvo.length) {
          if (empty) empty.classList.remove('hidden');
          atualizarMetricas(listaAlvo);
          ui.clearPageState();
          return;
        }
        if (empty) empty.classList.add('hidden');
        listaAlvo.forEach((u) => {
          const tr = document.createElement('tr');
          tr.className = 'data-row hover:bg-surface-container-low transition-colors group';
          const nome = u.name || u.nome || u.email || 'Usuario';
          const email = u.email || '';
          const perfil = u.perfil || u.role || 'admin';
          const status = suspenso(u) ? 'Suspenso' : ativo(u) ? 'Ativo' : 'Inativo';
          const ultimo = u.last_login || u.updated_at || u.created_at || '';
          tr.innerHTML = `
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary font-bold uppercase">${nome.substring(0, 1)}</div>
                <div>
                  <p class="font-bold text-on-surface">${nome}</p>
                  <p class="text-xs text-on-surface-variant">${email}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span class="px-3 py-1 bg-secondary-container text-on-secondary-container text-[11px] font-bold rounded-full">${perfil}</span>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-1.5 ${status === 'Ativo' ? 'text-tertiary' : status === 'Suspenso' ? 'text-error' : 'text-on-surface-variant'}">
                <span class="w-2 h-2 rounded-full ${status === 'Ativo' ? 'bg-tertiary' : status === 'Suspenso' ? 'bg-error' : 'bg-outline'}"></span>
                <span class="text-xs font-bold uppercase">${status}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-on-surface-variant">${ultimo || '-'}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary-container/20 rounded-lg transition-all" title="Editar"><span class="material-symbols-outlined text-xl">edit</span></button>
                <button class="p-2 text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-lg transition-all" title="Bloquear"><span class="material-symbols-outlined text-xl">block</span></button>
              </div>
            </td>`;
          tbody.appendChild(tr);
        });
        atualizarMetricas(listaAlvo);
        ui.clearPageState();
      };

      const bindBusca = (listaOrig) => {
        if (!busca || busca.dataset.bound) return;
        busca.dataset.bound = '1';
        busca.addEventListener('input', () => {
          const term = busca.value.toLowerCase();
          const filtrada = listaOrig.filter(
            (u) =>
              (u.name || '').toLowerCase().includes(term) ||
              (u.email || '').toLowerCase().includes(term) ||
              (u.perfil || u.role || '').toLowerCase().includes(term)
          );
          renderLista(filtrada);
        });
      };

      const pendenciasEl = document.getElementById('adminPendenciasList');
      const pendenciasEmpty = document.getElementById('adminPendenciasEmpty');
      if (pendenciasEl) {
        pendenciasEl.innerHTML = '';
        const pendentes = admins.filter(suspenso).slice(0, 3);
        if (pendentes.length) {
          pendenciasEl.classList.remove('hidden');
          pendenciasEmpty?.classList.add('hidden');
          pendentes.forEach((p) => {
            const card = document.createElement('div');
            card.className = 'p-3 bg-white rounded-xl flex items-center gap-3 shadow-sm border border-slate-100';
            card.innerHTML = `
              <div class="w-10 h-10 bg-error-container text-error flex items-center justify-center rounded-full">
                <span class="material-symbols-outlined">report_problem</span>
              </div>
              <div class="flex-1">
                <p class="text-sm font-bold text-on-surface leading-none">${p.name || p.email}</p>
                <p class="text-xs text-on-surface-variant">${p.email || 'Conta bloqueada'}</p>
              </div>
              <button class="text-tertiary hover:scale-110 transition-transform" title="Revisar"><span class="material-symbols-outlined text-xl">check_circle</span></button>
            `;
            pendenciasEl.appendChild(card);
          });
        } else {
          pendenciasEl.classList.add('hidden');
          pendenciasEmpty?.classList.remove('hidden');
        }
      }

      document.getElementById('btnNovoAdmin')?.addEventListener('click', () =>
        ui.message('Criacao de admin via painel: use /admin/create-user com permissoes adequadas.', 'info')
      );
      document.getElementById('btnAudit')?.addEventListener('click', () => (window.location.href = '/relat_rios_gerais_master.html'));

      bindBusca(admins);
      renderLista(admins);
    },

    async clientesMaster() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando clientes...');
      const usersDataset = await this.loadUsersDataset();
      
      let clientes = [];
      if (usersDataset.ok && usersDataset.list.length) {
        const lista = usersDataset.list;
        clientes = lista.filter((u) => (u.perfil || u.role || '').toString().toLowerCase().includes('cliente'));
      }
      
      // Fallback: dados de demonstração se não houver clientes
      if (!clientes.length) {
        clientes = [
          { id: 1, name: 'João Silva', email: 'joao.silva@email.com', cpf: '123.456.789-00', pontos: 1250, saldo: 1250, status: 'ativo', created_at: new Date(Date.now() - 30 * 24 * 3600000).toISOString(), last_login: new Date(Date.now() - 2 * 3600000).toISOString() },
          { id: 2, name: 'Maria Santos', email: 'maria.santos@email.com', cpf: '987.654.321-00', pontos: 850, saldo: 850, status: 'ativo', created_at: new Date(Date.now() - 45 * 24 * 3600000).toISOString(), last_login: new Date(Date.now() - 5 * 3600000).toISOString() },
          { id: 3, name: 'Pedro Oliveira', email: 'pedro.oli@email.com', cpf: '456.789.123-00', pontos: 2100, saldo: 2100, status: 'ativo', created_at: new Date(Date.now() - 60 * 24 * 3600000).toISOString(), last_login: new Date(Date.now() - 24 * 3600000).toISOString() },
          { id: 4, name: 'Ana Costa', email: 'ana.costa@email.com', cpf: '321.654.987-00', pontos: 450, saldo: 450, status: 'ativo', created_at: new Date(Date.now() - 15 * 24 * 3600000).toISOString(), last_login: new Date(Date.now() - 48 * 3600000).toISOString() },
          { id: 5, name: 'Carlos Lima', email: 'carlos.lima@email.com', cpf: '789.123.456-00', pontos: 3200, saldo: 3200, status: 'ativo', created_at: new Date(Date.now() - 90 * 24 * 3600000).toISOString(), last_login: new Date(Date.now() - 1 * 3600000).toISOString() },
          { id: 6, name: 'Juliana Pereira', email: 'ju.pereira@email.com', cpf: '654.321.987-00', pontos: 180, saldo: 180, status: 'inativo', created_at: new Date(Date.now() - 120 * 24 * 3600000).toISOString(), last_login: new Date(Date.now() - 15 * 24 * 3600000).toISOString() },
        ];
      }

      const tbody = document.getElementById('adminClientesTable');
      if (!tbody) {
        ui.clearPageState();
        if (!clientes.length) return ui.message('Nenhum cliente encontrado.', 'warning');
        return render.section('Clientes', clientes.map((c) => `<div class=\"px-4 py-3\">${c.name || c.email}</div>`).join(''));
      }

      const empty = document.getElementById('adminClientesEmpty');
      const resumo = document.getElementById('adminClientesResumo');
      const busca = document.getElementById('adminClientesBusca') || document.getElementById('adminClientesBusca2');

      // Event delegation para bloquear/reativar usuário
      if (!tbody.dataset.delegated) {
        tbody.dataset.delegated = '1';
        tbody.addEventListener('click', async (e) => {
          const btn = e.target.closest('.btn-suspender');
          if (!btn) return;
          const tr = btn.closest('tr.data-row');
          if (!tr) return;
          const userId = tr.dataset.userId;
          const userName = tr.dataset.userName;
          const isSuspender = btn.title.includes('Suspender');
          const novoStatus = isSuspender ? 'bloqueado' : 'ativo';
          const acao = isSuspender ? 'Suspender' : 'Reativar';
          if (!confirm(`${acao} conta de "${userName}"?`)) return;
          const resp = await api.request(`/admin/users/${userId}/status`, { method: 'PUT', body: JSON.stringify({ status: novoStatus }) }, { notify: false });
          if (resp.res?.ok) {
            ui.message(`Conta ${isSuspender ? 'suspensa' : 'reativada'} com sucesso.`, 'success');
            await admin.clientesMaster();
          } else {
            ui.message(resp.data?.message || `Erro ao ${acao.toLowerCase()} conta.`, 'error');
          }
        });
      }

      const statusText = (u) => (u.status || u.situacao || '').toString().toLowerCase();
      const ativo = (u) => ['ativo', 'active', 'enabled'].includes(statusText(u)) || u.active === true || u.ativo === true;

      const atualizarMetricas = (lst) => {
        const total = lst.length;
        const ativos = lst.filter(ativo).length;
        const pts = lst.map((c) => Number(c.pontos || c.saldo || 0)).filter((n) => !Number.isNaN(n));
        const media = pts.length ? Math.round(pts.reduce((a, b) => a + b, 0) / pts.length) : 0;
        const novos = lst.filter((c) => c.created_at && new Date(c.created_at) > new Date(Date.now() - 24 * 3600 * 1000)).length;
        const set = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.textContent = val;
        };
        set('adminClientesTotal', Number(total || 0).toLocaleString('pt-BR'));
        set('adminClientesCrescimento', total ? `${ativos} ativos` : '');
        set('adminClientesMediaPontos', pts.length ? `${media} pts` : '--');
        const bar = document.getElementById('adminClientesMediaBar');
        if (bar) bar.style.width = pts.length ? `${Math.min(100, Math.round((media / 5000) * 100))}%` : '0%';
        set('adminClientesNovos', novos || '0');
        if (resumo) resumo.querySelector('p').textContent = total ? `Exibindo ${total} clientes` : 'Nenhum cliente encontrado';
      };

      const renderLista = (lst) => {
        tbody.querySelectorAll('tr.data-row')?.forEach((tr) => tr.remove());
        if (!lst.length) {
          empty?.classList.remove('hidden');
          atualizarMetricas(lst);
          ui.clearPageState();
          return;
        }
        empty?.classList.add('hidden');
        lst.forEach((c) => {
          const nome = c.name || c.nome || c.email || 'Cliente';
          const email = c.email || '';
          const cpf = c.cpf || c.documento || '---';
          const pontos = Number(c.pontos || c.saldo || 0);
          const status = ativo(c) ? 'Ativo' : 'Inativo';
          const ultima = c.last_login || c.updated_at || c.created_at || '-';
          const tr = document.createElement('tr');
          tr.className = 'data-row hover:bg-surface transition-colors group';
          tr.dataset.userId = c.id;
          tr.dataset.userName = nome;
          const statusAtual = ativo(c) ? 'ativo' : 'inativo';
          tr.innerHTML = `\n<td class=\"px-6 py-4\">\n  <div class=\"flex items-center gap-3\">\n    <div class=\"w-10 h-10 rounded-full overflow-hidden bg-surface-container flex items-center justify-center text-primary font-bold uppercase\">${nome.substring(0,1)}</div>\n    <div>\n      <p class=\"font-bold text-sm text-on-surface\">${nome}</p>\n      <p class=\"text-xs text-on-surface-variant\">${email}</p>\n    </div>\n  </div>\n</td>\n<td class=\"px-6 py-4 text-sm text-on-surface-variant\">${cpf}</td>\n<td class=\"px-6 py-4\"><span class=\"text-sm font-bold text-primary\">${pontos.toLocaleString('pt-BR')} pts</span></td>\n<td class=\"px-6 py-4\"><span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${status === 'Ativo' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'}\">${status}</span></td>\n<td class=\"px-6 py-4 text-sm text-on-surface-variant\">${ultima}</td>\n<td class=\"px-6 py-4 text-right space-x-1\">\n  <button class=\"p-2 text-on-surface-variant hover:text-primary hover:bg-primary/10 rounded-lg transition-all btn-suspender\" title=\"${statusAtual === 'ativo' ? 'Suspender conta' : 'Reativar conta'}\"><span class=\"material-symbols-outlined text-[20px]\">${statusAtual === 'ativo' ? 'block' : 'check_circle'}</span></button>\n</td>\n`;
          tbody.appendChild(tr);
        });
        atualizarMetricas(lst);
        ui.clearPageState();
      };

      const bindBusca = (lst) => {
        const input = busca;
        if (!input || input.dataset.bound) return;
        input.dataset.bound = '1';
        input.addEventListener('input', () => {
          const term = input.value.toLowerCase();
          const filtrada = lst.filter(
            (c) =>
              (c.name || '').toLowerCase().includes(term) ||
              (c.email || '').toLowerCase().includes(term) ||
              (c.cpf || c.documento || '').toLowerCase().includes(term)
          );
          renderLista(filtrada);
        });
      };

      bindBusca(clientes);
      renderLista(clientes);
    },

    async relatorios() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando relatorios...');

      const usersDataset = await this.loadUsersDataset();
      const [stats, checkins, empresasResp] = await Promise.all([
        api.request('/admin/dashboard-stats', {}, { notify: false }),
        api.request('/admin/pontos/estatisticas', {}, { notify: false }),
        api.request('/empresas', {}, { requireAuth: false, notify: false }),
      ]);
      ui.clearPageState();

      const statsData = stats.data?.data || stats.data || {};
      const totals = statsData?.totais || {};
      const usersList = usersDataset.ok ? usersDataset.list : [];
      const usersPayload = { total: usersList.length };
      const empresasListApi = toArray(empresasResp.data?.data || empresasResp.data);
      const empresasList = empresasListApi.length ? empresasListApi : DEMO.admin.empresas;
      const checkData = checkins.data?.data || checkins.data || {};
      const fallbackUsers = usersList.length ? usersList.length : DEMO.admin.totals.usuarios;

      const totalEmpresas = toNumber(totals.empresas, statsData.empresas, statsData.total_empresas, empresasList.length);
      const totalUsuarios = toNumber(totals.usuarios, statsData.usuarios, statsData.total_users, usersPayload.total, usersList.length, fallbackUsers);
      const totalClientes = toNumber(
        statsData.clientes,
        usersList.filter((u) => (u?.perfil || u?.role || '').toString().toLowerCase().includes('cliente')).length,
        Math.round(totalUsuarios * 0.76)
      );
      const totalPromocoes = toNumber(totals.campanhas, statsData.promocoes, statsData.campanhas, DEMO.admin.totals.campanhas);
      const totalResgates = toNumber(totals.resgates, statsData.resgates, DEMO.admin.totals.resgates);
      const totalVolume = toNumber(totals.volume, statsData.volume, DEMO.admin.totals.volume);

      const setText = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
      };

      setText('relEmpresas', Number(totalEmpresas || 0).toLocaleString('pt-BR'));
      setText('relClientes', Number((totalClientes || totalUsuarios || 0)).toLocaleString('pt-BR'));
      setText('relPromocoes', Number(totalPromocoes || 0).toLocaleString('pt-BR'));
      setText('relResgates', Number(totalResgates || 0).toLocaleString('pt-BR'));
      setText('relVolume', `R$ ${Number(totalVolume || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
      setText('relCrescimento', safeText(statsData.crescimento_texto, 'Dados consolidados dos ultimos 30 dias'));

      const relStatsList = document.getElementById('relStatsList');
      if (relStatsList) {
        const resumo = {
          usuarios: totalUsuarios,
          clientes: totalClientes,
          empresas: totalEmpresas,
          promocoes: totalPromocoes,
          resgates: totalResgates,
          volume: totalVolume,
        };
        relStatsList.innerHTML = '';
        Object.entries(resumo).forEach(([k, v]) => {
          const li = document.createElement('div');
          li.className = 'flex items-center justify-between px-4 py-2 rounded-lg bg-surface-container-low';
          const value = k === 'volume'
            ? `R$ ${Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
            : (v ? Number(v).toLocaleString('pt-BR') : '--');
          li.innerHTML = `<span class="text-sm font-semibold capitalize">${k.replace(/_/g, ' ')}</span><span class="text-sm font-bold text-primary">${value}</span>`;
          relStatsList.appendChild(li);
        });
      }

      const relCheckinsList = document.getElementById('relCheckinsList');
      if (relCheckinsList) {
        relCheckinsList.innerHTML = '';
        const entries = Object.entries(checkData || {});
        if (!entries.length) {
          relCheckinsList.innerHTML = '<p class="text-sm text-on-surface-variant">Sem estatisticas de pontos disponiveis.</p>';
        } else {
          entries.forEach(([k, v]) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between px-4 py-2 rounded-lg bg-surface-container-low';
            row.innerHTML = `<span class="text-sm font-semibold capitalize">${k.replace(/_/g, ' ')}</span><span class="text-sm font-bold text-tertiary">${Number(v || 0).toLocaleString('pt-BR')}</span>`;
            relCheckinsList.appendChild(row);
          });
        }
      }

      const shouldExport = new URLSearchParams(window.location.search).get('gerar') === '1';
      if (shouldExport) {
        const stored = auth.getStored();
        try {
          const resp = await fetch(`${API_BASE}/admin/reports/export`, {
            method: 'GET',
            headers: {
              Accept: 'text/csv,application/json',
              ...(stored?.token ? { Authorization: `Bearer ${stored.token}` } : {}),
            },
          });
          if (resp.ok) {
            const blob = await resp.blob();
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = `relatorio-admin-${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            URL.revokeObjectURL(url);
            ui.message('Relatorio gerado com sucesso.', 'success');
          } else {
            ui.message('Nao foi possivel gerar o relatorio agora.', 'error');
          }
        } finally {
          const params = new URLSearchParams(window.location.search);
          params.delete('gerar');
          const clean = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}`;
          window.history.replaceState({}, '', clean);
        }
      }

      // Check-ins Pendentes de aprovacao
      const checkinsContainer = document.getElementById('checkinsPendentesContainer');
      if (checkinsContainer) {
        const { data: cpData } = await api.request('/admin/pontos/checkins-pendentes', {}, { notify: false });
        const pendentes = toArray(cpData?.data || cpData);
        if (!pendentes.length) {
          checkinsContainer.innerHTML = '<p class="text-sm text-on-surface-variant text-center py-4">Nenhum check-in pendente de aprovacao.</p>';
        } else {
          checkinsContainer.innerHTML = pendentes.slice(0, 10).map((c) => {
            const nome = c.user?.name || c.usuario_nome || 'Cliente';
            const empresa = c.empresa?.nome || c.empresa_nome || 'Empresa';
            const pts = Number(c.pontos || c.points || 0).toLocaleString('pt-BR');
            const data = c.created_at ? new Date(c.created_at).toLocaleString('pt-BR') : '--';
            return `<div class="flex items-center justify-between bg-surface-container-low p-3 rounded-xl gap-3" data-checkin-id="${c.id}">
              <div>
                <p class="font-semibold text-sm text-on-surface">${nome}</p>
                <p class="text-xs text-on-surface-variant">${empresa} &bull; ${pts} pts &bull; ${data}</p>
              </div>
              <div class="flex gap-2">
                <button class="btn-aprovar-checkin px-3 py-1 text-xs rounded-lg bg-tertiary text-on-tertiary font-semibold hover:opacity-90" data-id="${c.id}">Aprovar</button>
                <button class="btn-rejeitar-checkin px-3 py-1 text-xs rounded-lg bg-error-container text-on-error-container font-semibold hover:opacity-90" data-id="${c.id}">Rejeitar</button>
              </div>
            </div>`;
          }).join('');
          checkinsContainer.querySelectorAll('.btn-aprovar-checkin').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.dataset.id;
              const { res } = await api.request(`/admin/pontos/checkin/${id}/aprovar`, { method: 'POST' }, { notify: true });
              if (res.ok) {
                btn.closest('[data-checkin-id]')?.remove();
                ui.message('Check-in aprovado com sucesso.', 'success');
              }
            });
          });
          checkinsContainer.querySelectorAll('.btn-rejeitar-checkin').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.dataset.id;
              const { res } = await api.request(`/admin/pontos/checkin/${id}/rejeitar`, { method: 'POST' }, { notify: true });
              if (res.ok) btn.closest('[data-checkin-id]')?.remove();
            });
          });
        }
      }

      // Sem banner de erro global aqui: fallback evita ruido visual.
    },

    async configuracoes() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando configuracoes...');
      const { res, data } = await api.request('/admin/settings', {}, { notify: false });
      ui.clearPageState();

      if (!res.ok || data?.success === false || !data?.data) {
        ui.message(data?.message || 'Nao foi possivel carregar configuracoes.', 'error');
        return;
      }

      const cfg = data.data;
      const bind = (id) => document.getElementById(id);

      if (bind('cfgPlatformName')) bind('cfgPlatformName').value = cfg.platform_name || 'Tem de Tudo';
      if (bind('cfgSupportEmail')) bind('cfgSupportEmail').value = cfg.support_email || '';
      if (bind('cfgSupportWhatsapp')) bind('cfgSupportWhatsapp').value = cfg.support_whatsapp || '';
      if (bind('cfgPointsBase')) bind('cfgPointsBase').value = Number(cfg.points_base_per_real ?? 1);
      if (bind('cfgPointsExpiration')) bind('cfgPointsExpiration').value = Number(cfg.points_expiration_days ?? 365);
      if (bind('cfgAllowCliente')) bind('cfgAllowCliente').checked = Boolean(cfg.allow_register_cliente);
      if (bind('cfgAllowEmpresa')) bind('cfgAllowEmpresa').checked = Boolean(cfg.allow_register_empresa);
      if (bind('cfgPushEnabled')) bind('cfgPushEnabled').checked = Boolean(cfg.push_enabled);
      if (bind('cfgMaintenanceMode')) bind('cfgMaintenanceMode').checked = Boolean(cfg.maintenance_mode);

      bind('cfgReloadBtn')?.addEventListener('click', () => window.location.reload());

      bind('cfgSaveBtn')?.addEventListener('click', async () => {
        const payload = {
          platform_name: bind('cfgPlatformName')?.value || 'Tem de Tudo',
          support_email: bind('cfgSupportEmail')?.value || '',
          support_whatsapp: bind('cfgSupportWhatsapp')?.value || '',
          points_base_per_real: Number(bind('cfgPointsBase')?.value || 1),
          points_expiration_days: Number(bind('cfgPointsExpiration')?.value || 365),
          allow_register_cliente: Boolean(bind('cfgAllowCliente')?.checked),
          allow_register_empresa: Boolean(bind('cfgAllowEmpresa')?.checked),
          push_enabled: Boolean(bind('cfgPushEnabled')?.checked),
          maintenance_mode: Boolean(bind('cfgMaintenanceMode')?.checked),
        };

        const saveBtn = bind('cfgSaveBtn');
        saveBtn?.setAttribute('disabled', 'disabled');
        saveBtn?.classList.add('opacity-70');
        const resp = await api.request('/admin/settings', { method: 'PUT', body: JSON.stringify(payload) });
        saveBtn?.removeAttribute('disabled');
        saveBtn?.classList.remove('opacity-70');

        if (resp.res.ok && resp.data?.success !== false) {
          ui.message(resp.data?.message || 'Configuracoes salvas com sucesso.', 'success');
        } else {
          const errors = resp.data?.errors ? Object.values(resp.data.errors).flat().join(' ') : '';
          ui.message(resp.data?.message || errors || 'Erro ao salvar configuracoes.', 'error');
        }
      });
    },
  };

  // ---------------------- Login (publico) ---------------------- //
  async function handleLogin() {
    if (page === 'acessar_conta' || window.__inlineLoginManaged) return;
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
      const payload = data || {};
      const token = payload?.token || payload?.access_token || payload?.data?.token || null;
      const user = auth.normalizeUser(payload?.user || payload?.data?.user || payload?.usuario || null);
      const perfil = user?.perfil || user?.role || user?.tipo || null;
      const target = redirectMap[perfil] || '/';
      console.log('LOGIN_SUBMIT_OK', JSON.stringify({ status: res.status, payload, perfil, redirect: target }, null, 2));
      if (res.ok && token && user) {
        auth.save(token, user);
        ui.clearPageState();
        ui.message('Login realizado, redirecionando...', 'success');
        setTimeout(() => (window.location.href = target), 300);
      } else {
        ui.clearPageState();
        console.error('LOGIN_SUBMIT_FAIL', JSON.stringify({ status: res.status, payload: data }, null, 2));
        ui.message(data?.message || payload?.message || 'Nao foi possivel entrar.', 'error');
      }
    });
  }

  // ---------------------- Dispatcher ---------------------- //
  const handlers = {
    // Publico / shared
    acessar_conta: async () => {
      // Se usuario ja estiver logado, redireciona ao painel correto
      const stored = auth.getStored();
      if (stored?.token && stored?.user?.perfil) {
        const perfil = auth.normalizePerfil(stored.user.perfil);
        const destinos = { admin: '/dashboard_admin_master.html', empresa: '/dashboard_parceiro.html', cliente: '/meus_pontos.html' };
        window.location.href = destinos[perfil] || '/entrar.html';
        return;
      }
      // Redireciona para pagina de login padrao
      if (!window.location.pathname.includes('entrar')) {
        window.location.href = '/entrar.html';
        return;
      }
      // Ativa listener no formulario de login desta pagina (se houver)
      const form = document.querySelector('form[data-login-form], form#loginForm, form');
      if (!form) return;
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const emailEl = form.querySelector('[name="email"], [type="email"]');
        const senhaEl = form.querySelector('[name="password"], [name="senha"], [type="password"]');
        if (!emailEl || !senhaEl) return;
        const { res, data } = await api.request('/login', { method: 'POST', body: JSON.stringify({ email: emailEl.value.trim(), password: senhaEl.value }) });
        if (!res.ok) { ui.message(data?.message || 'Credenciais invalidas.', 'error'); return; }
        auth.save(data.token, data.user);
        const perfil = auth.normalizePerfil(data.user?.perfil);
        const destinos = { admin: '/dashboard_admin_master.html', empresa: '/dashboard_parceiro.html', cliente: '/meus_pontos.html' };
        window.location.href = destinos[perfil] || '/meus_pontos.html';
      });
    },
    home_tem_de_tudo: async () => {
      const cards = document.querySelectorAll('main section.space-y-4 .grid > div');
      if (!cards.length) return;
      const { data } = await api.request('/empresas', {}, { requireAuth: false });
      const empresas = data?.data || data || [];
      if (!empresas.length) return;
      cards.forEach((card, idx) => {
        const empresa = empresas[idx];
        if (!empresa) return;
        const img = card.querySelector('img');
        const title = card.querySelector('h4');
        const badge = card.querySelector('span.text-xs');
        const desc = card.querySelector('p.text-xs');
        if (img) {
          img.src = safeImage(empresa.logo, IMAGE_FALLBACKS.store);
          img.onerror = () => {
            img.onerror = null;
            img.src = IMAGE_FALLBACKS.store;
          };
        }
        if (title) title.textContent = empresa.nome || 'Parceiro';
        if (badge) badge.textContent = (empresa.ramo || 'Parceiro').toString().toUpperCase();
        if (desc) desc.textContent = empresa.endereco || 'Parceiro ativo no programa.';
      });
    },
    oferta_especial: cliente.detalheParceiro,
    tudo_vibrante: async () => {
      // Carrega lista de empresas parceiras e exibe na pagina
      const { data } = await api.request('/empresas', {}, { requireAuth: false, notify: false });
      const empresas = toArray(data?.data || data);
      if (!empresas.length) return;
      // Preenche cards de parceiros na pagina (qualquer grid de cards)
      const cards = document.querySelectorAll('.partner-card, [data-empresa-card], main .grid > div, main section .grid > div');
      if (cards.length) {
        cards.forEach((card, idx) => {
          const emp = empresas[idx];
          if (!emp) return;
          const img = card.querySelector('img');
          const title = card.querySelector('h3, h4, .nome-empresa');
          const badge = card.querySelector('.ramo, span.text-xs, .categoria');
          const desc = card.querySelector('p.text-xs, p.text-sm, .descricao');
          if (img) { img.src = safeImage(emp.logo, IMAGE_FALLBACKS.store); img.alt = emp.nome || 'Parceiro'; }
          if (title) title.textContent = emp.nome || 'Parceiro';
          if (badge) badge.textContent = (emp.ramo || 'Parceiro').toString().toUpperCase();
          if (desc) desc.textContent = emp.descricao || emp.endereco || 'Parceiro ativo no programa.';
          const link = card.querySelector('a') || card.closest('a');
          if (link) link.href = `/detalhe_do_parceiro.html?id=${emp.id}`;
        });
      } else {
        // Sem grid estatico: renderiza cards dinamicamente no main
        const main = document.querySelector('main') || document.body;
        const grid = document.createElement('div');
        grid.className = 'max-w-6xl mx-auto px-4 py-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4';
        grid.innerHTML = empresas.map((emp) => `
          <a href="/detalhe_do_parceiro.html?id=${emp.id}" class="flex flex-col items-center gap-2 bg-white rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow">
            <img src="${safeImage(emp.logo, IMAGE_FALLBACKS.store)}" alt="${emp.nome || 'Parceiro'}" class="w-14 h-14 rounded-xl object-cover" onerror="this.src='${IMAGE_FALLBACKS.store}'">
            <p class="font-semibold text-sm text-center text-on-surface leading-tight">${emp.nome || 'Parceiro'}</p>
            <span class="text-[10px] text-on-surface-variant uppercase tracking-wide">${(emp.ramo || 'Parceiro').toString()}</span>
          </a>`).join('');
        main.appendChild(grid);
      }
    },
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
        else ui.message(data?.message || 'Erro ao solicitar recuperacao.', 'error');
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
          ui.message('Senha redefinida. Faca login.', 'success');
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
    configuracoes_cliente: cliente.configuracoes,
    criar_conta: async () => {
      const form = document.getElementById('signupForm');
      if (!form) return;
      const perfilSel = document.getElementById('sgPerfil');
      const cnpj = document.getElementById('sgCnpj');
      const end = document.getElementById('sgEndereco');
      const blocoEmpresa = document.getElementById('empresaFields');

      const tipoParam = new URLSearchParams(window.location.search).get('tipo');
      if (tipoParam && ['cliente', 'empresa'].includes(tipoParam)) {
        perfilSel.value = tipoParam;
      }

      if (perfilSel.value === 'empresa') blocoEmpresa.classList.remove('hidden');
      else blocoEmpresa.classList.add('hidden');

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
        // Código de indicação: via input ou URL ?ref=
        const refInput = document.getElementById('sgReferralCode');
        const refFromUrl = new URLSearchParams(window.location.search).get('ref');
        const refCode = (refInput?.value || refFromUrl || '').trim().toUpperCase();
        if (perfil === 'cliente' && refCode) payload.referral_code = refCode;
        
        // Fluxo admin: criação de empresa deve usar endpoint administrativo protegido.
        const urlParams = new URLSearchParams(window.location.search);
        const origem = urlParams.get('origem');
        const isAdminCompanyFlow = (perfil === 'empresa' && origem === 'admin');

        let endpoint = '/auth/register';
        let requestPayload = payload;
        let requestConfig = { requireAuth: false, notify: false };

        if (isAdminCompanyFlow) {
          const me = await api.request('/auth/me', {}, { requireAuth: true, notify: false });
          const mePerfil = auth.normalizePerfil(
            me.data?.data?.user?.perfil ||
            me.data?.user?.perfil ||
            me.data?.perfil ||
            null
          );

          if (!me.res?.ok || mePerfil !== 'admin') {
            ui.message('Para cadastrar estabelecimento, faca login como administrador.', 'warning');
            setTimeout(() => (window.location.href = '/entrar.html'), 800);
            return;
          }

          endpoint = '/admin/create-user';
          requestPayload = {
            name: payload.name,
            email: payload.email,
            password: payload.password,
            perfil: 'empresa',
            telefone: payload.telefone || null,
            cnpj: payload.cnpj || null,
            endereco: payload.endereco || null,
            status: 'ativo',
          };
          requestConfig = { requireAuth: true, notify: true };
        }
        
        ui.setPageState('loading', 'Criando conta...');
        const { res, data } = await api.request(endpoint, {
          method: 'POST',
          body: JSON.stringify(requestPayload),
        }, requestConfig);
        ui.clearPageState();
        if (res.ok && data?.success !== false) {
          if (perfil === 'empresa' && origem === 'admin') {
            ui.message('Estabelecimento criado com sucesso!', 'success');
            setTimeout(() => (window.location.href = '/gest_o_de_estabelecimentos.html'), 800);
          } else {
            ui.message('Conta criada. Faca login.', 'success');
            setTimeout(() => (window.location.href = '/entrar.html'), 800);
          }
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
    minhas_campanhas_loja: empresa.campanhas,

    // Admin
    dashboard_admin_master: admin.dashboard,
    gest_o_de_estabelecimentos: admin.empresas,
    gest_o_de_usu_rios_master: admin.usuarios,
    gest_o_de_clientes_master: admin.clientesMaster,
    relat_rios_gerais_master: admin.relatorios,
    configuracoes_admin: admin.configuracoes,
    banners_e_categorias_master: async () => {
      if (!(await auth.guard(['admin']))) return;
      const status = document.getElementById('conteudoStatus');
      const sections = document.querySelectorAll('main > section');
      const bannersSection = sections[1];
      const categoriasSection = sections[2];

      const escapeHtml = (value) =>
        String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/\"/g, '&quot;')
          .replace(/'/g, '&#39;');

      const fallbackContent = () => ({
        partial: true,
        banners: [
          {
            id: 'fallback-1',
            title: 'Semana de Pontos em Dobro',
            link: '/recompensas.html',
            active: true,
          },
          {
            id: 'fallback-2',
            title: 'Novos Parceiros na Plataforma',
            link: '/parceiros_tem_de_tudo.html',
            active: true,
          },
        ],
        categorias: [
          { id: 'fallback-cat-1', name: 'Restaurantes', slug: 'restaurantes', active: true },
          { id: 'fallback-cat-2', name: 'Beleza', slug: 'beleza', active: true },
          { id: 'fallback-cat-3', name: 'Saude', slug: 'saude', active: true },
        ],
      });

      const fetchContent = async () => {
        const { res, data } = await api.request('/admin/content', {}, { notify: false });
        if (res.ok && data?.success !== false) return data?.data || { banners: [], categorias: [] };
        return fallbackContent();
      };

      const renderContent = async () => {
        const payload = await fetchContent();
        const { banners = [], categorias = [] } = payload;
        const isPartial = Boolean(payload?.partial);
        if (status) {
          status.textContent = `Conteudo sincronizado: ${banners.length} banner(s), ${categorias.length} categoria(s).`;
        }

        if (bannersSection) {
          bannersSection.innerHTML = `
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-headline font-bold text-on-surface">Banners</h3>
              <button id="novoBannerBtn" class="px-3 py-1.5 rounded-lg ${isPartial ? 'bg-outline text-white/80 cursor-not-allowed' : 'bg-primary text-white'} text-sm font-bold" ${isPartial ? 'disabled' : ''}>${isPartial ? 'Indisponivel' : 'Novo banner'}</button>
            </div>
            <div id="bannersList" class="space-y-3"></div>
          `;

          const list = bannersSection.querySelector('#bannersList');
          if (!banners.length) {
            list.innerHTML = '<p class="text-sm text-on-surface-variant">Nenhum banner cadastrado.</p>';
          } else {
            list.innerHTML = banners.map((b) => `
              <div class="p-3 rounded-xl bg-surface-container-low flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-sm text-on-surface truncate">${escapeHtml(b.title)}</p>
                  <p class="text-xs text-on-surface-variant truncate">${escapeHtml(b.link || '-')}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-bold uppercase ${b.active ? 'text-tertiary' : 'text-outline'}">${b.active ? 'Ativo' : 'Inativo'}</span>
                  <button data-b-action="toggle" data-id="${b.id}" class="px-2 py-1 rounded bg-amber-500 text-white text-xs">Ativar/Pausar</button>
                  <button data-b-action="edit" data-id="${b.id}" class="px-2 py-1 rounded bg-slate-700 text-white text-xs">Editar</button>
                  <button data-b-action="delete" data-id="${b.id}" class="px-2 py-1 rounded bg-rose-600 text-white text-xs">Excluir</button>
                </div>
              </div>
            `).join('');
          }

          bannersSection.querySelector('#novoBannerBtn')?.addEventListener('click', async () => {
            const title = window.prompt('Titulo do banner:');
            if (!title) return;
            const image_url = window.prompt('URL da imagem:') || '';
            const link = window.prompt('Link do banner:') || '';
            const { res, data } = await api.request('/admin/content/banners', { method: 'POST', body: JSON.stringify({ title, image_url, link, active: true }) });
            if (res.ok && data?.success !== false) {
              ui.message('Banner criado com sucesso.', 'success');
              await renderContent();
            } else {
              ui.message(data?.message || 'Erro ao criar banner.', 'error');
            }
          });

          list?.querySelectorAll('[data-b-action="delete"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              if (!window.confirm('Excluir banner?')) return;
              const { res, data } = await api.request(`/admin/content/banners/${id}`, { method: 'DELETE' });
              if (res.ok && data?.success !== false) {
                ui.message('Banner removido.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao remover banner.', 'error');
              }
            });
          });

          list?.querySelectorAll('[data-b-action="edit"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              const item = banners.find((b) => String(b.id) === String(id));
              if (!item) return;
              const title = window.prompt('Titulo do banner:', item.title || '');
              if (!title) return;
              const image_url = window.prompt('URL da imagem:', item.image_url || '') || '';
              const link = window.prompt('Link:', item.link || '') || '';
              const { res, data } = await api.request(`/admin/content/banners/${id}`, { method: 'PUT', body: JSON.stringify({ title, image_url, link }) });
              if (res.ok && data?.success !== false) {
                ui.message('Banner atualizado.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao atualizar banner.', 'error');
              }
            });
          });

          list?.querySelectorAll('[data-b-action="toggle"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              const item = banners.find((b) => String(b.id) === String(id));
              if (!item) return;
              const { res, data } = await api.request(`/admin/content/banners/${id}`, { method: 'PUT', body: JSON.stringify({ active: !item.active }) });
              if (res.ok && data?.success !== false) {
                ui.message('Status do banner atualizado.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao atualizar status.', 'error');
              }
            });
          });
        }

        if (categoriasSection) {
          categoriasSection.innerHTML = `
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-headline font-bold text-on-surface">Categorias</h3>
              <button id="novaCategoriaBtn" class="px-3 py-1.5 rounded-lg ${isPartial ? 'bg-outline text-white/80 cursor-not-allowed' : 'bg-primary text-white'} text-sm font-bold" ${isPartial ? 'disabled' : ''}>${isPartial ? 'Indisponivel' : 'Nova categoria'}</button>
            </div>
            <div id="categoriasList" class="space-y-3"></div>
          `;

          const list = categoriasSection.querySelector('#categoriasList');
          if (!categorias.length) {
            list.innerHTML = '<p class="text-sm text-on-surface-variant">Nenhuma categoria cadastrada.</p>';
          } else {
            list.innerHTML = categorias.map((c) => `
              <div class="p-3 rounded-xl bg-surface-container-low flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-sm text-on-surface truncate">${escapeHtml(c.name)}</p>
                  <p class="text-xs text-on-surface-variant truncate">${escapeHtml(c.slug || '-')}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-bold uppercase ${c.active ? 'text-tertiary' : 'text-outline'}">${c.active ? 'Ativo' : 'Inativo'}</span>
                  <button data-c-action="toggle" data-id="${c.id}" class="px-2 py-1 rounded bg-amber-500 text-white text-xs">Ativar/Pausar</button>
                  <button data-c-action="edit" data-id="${c.id}" class="px-2 py-1 rounded bg-slate-700 text-white text-xs">Editar</button>
                  <button data-c-action="delete" data-id="${c.id}" class="px-2 py-1 rounded bg-rose-600 text-white text-xs">Excluir</button>
                </div>
              </div>
            `).join('');
          }

          categoriasSection.querySelector('#novaCategoriaBtn')?.addEventListener('click', async () => {
            const name = window.prompt('Nome da categoria:');
            if (!name) return;
            const slug = (window.prompt('Slug (opcional):') || '').trim();
            const { res, data } = await api.request('/admin/content/categorias', { method: 'POST', body: JSON.stringify({ name, slug: slug || undefined, active: true }) });
            if (res.ok && data?.success !== false) {
              ui.message('Categoria criada.', 'success');
              await renderContent();
            } else {
              ui.message(data?.message || 'Erro ao criar categoria.', 'error');
            }
          });

          list?.querySelectorAll('[data-c-action="delete"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              if (!window.confirm('Excluir categoria?')) return;
              const { res, data } = await api.request(`/admin/content/categorias/${id}`, { method: 'DELETE' });
              if (res.ok && data?.success !== false) {
                ui.message('Categoria removida.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao remover categoria.', 'error');
              }
            });
          });

          list?.querySelectorAll('[data-c-action="edit"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              const item = categorias.find((c) => String(c.id) === String(id));
              if (!item) return;
              const name = window.prompt('Nome da categoria:', item.name || '');
              if (!name) return;
              const slug = window.prompt('Slug:', item.slug || '') || '';
              const { res, data } = await api.request(`/admin/content/categorias/${id}`, { method: 'PUT', body: JSON.stringify({ name, slug }) });
              if (res.ok && data?.success !== false) {
                ui.message('Categoria atualizada.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao atualizar categoria.', 'error');
              }
            });
          });

          list?.querySelectorAll('[data-c-action="toggle"]').forEach((btn) => {
            btn.addEventListener('click', async () => {
              const id = btn.getAttribute('data-id');
              const item = categorias.find((c) => String(c.id) === String(id));
              if (!item) return;
              const { res, data } = await api.request(`/admin/content/categorias/${id}`, { method: 'PUT', body: JSON.stringify({ active: !item.active }) });
              if (res.ok && data?.success !== false) {
                ui.message('Status da categoria atualizado.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao atualizar status.', 'error');
              }
            });
          });
        }
      };

      try {
        await renderContent();
      } catch (err) {
        console.error('admin_content_render_fail', err);
        const payload = fallbackContent();
        const { banners = [], categorias = [] } = payload;
        if (status) status.textContent = `Conteudo sincronizado: ${banners.length} banner(s), ${categorias.length} categoria(s).`;
        if (bannersSection) {
          bannersSection.innerHTML = `
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-headline font-bold text-on-surface">Banners</h3>
            </div>
            <div class="space-y-3">${banners.map((b) => `
              <div class="p-3 rounded-xl bg-surface-container-low flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-sm text-on-surface truncate">${escapeHtml(b.title)}</p>
                  <p class="text-xs text-on-surface-variant truncate">${escapeHtml(b.link || '-')}</p>
                </div>
                <span class="text-[10px] font-bold uppercase ${b.active ? 'text-tertiary' : 'text-outline'}">${b.active ? 'Ativo' : 'Inativo'}</span>
              </div>`).join('')}</div>`;
        }
        if (categoriasSection) {
          categoriasSection.innerHTML = `
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-headline font-bold text-on-surface">Categorias</h3>
            </div>
            <div class="space-y-3">${categorias.map((c) => `
              <div class="p-3 rounded-xl bg-surface-container-low flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-sm text-on-surface truncate">${escapeHtml(c.name)}</p>
                  <p class="text-xs text-on-surface-variant truncate">${escapeHtml(c.slug || '-')}</p>
                </div>
                <span class="text-[10px] font-bold uppercase ${c.active ? 'text-tertiary' : 'text-outline'}">${c.active ? 'Ativo' : 'Inativo'}</span>
              </div>`).join('')}</div>`;
        }
      }
    },
  };

  // ---- NPS Modal helper (Gap 6) ----
  function _showNpsModal(promocaoId) {
    const overlay = document.createElement('div');
    overlay.id = 'npsModalOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:1rem';
    overlay.innerHTML = `
      <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
        <h3 class="font-bold text-lg text-on-surface mb-1">Avalie sua experiência</h3>
        <p class="text-sm text-on-surface-variant mb-4">De 0 a 10, o quanto você recomendaria esta promoção a um amigo?</p>
        <div class="flex flex-wrap gap-2 justify-center mb-4" id="npsButtons">
          ${[0,1,2,3,4,5,6,7,8,9,10].map((n) => `<button data-nota="${n}" class="nps-btn w-9 h-9 rounded-full border border-surface-variant text-sm font-bold hover:bg-primary hover:text-white transition-colors">${n}</button>`).join('')}
        </div>
        <textarea id="npsComentario" class="w-full border border-surface-variant rounded-xl p-2 text-sm resize-none" rows="2" placeholder="Comentário opcional..."></textarea>
        <div class="flex gap-2 mt-4">
          <button id="npsEnviar" class="flex-1 bg-primary text-white rounded-xl py-2 font-semibold text-sm hover:bg-primary/90 transition-colors" disabled>Enviar</button>
          <button id="npsFechar" class="px-4 py-2 rounded-xl border border-surface-variant text-sm text-on-surface-variant hover:bg-surface-container transition-colors">Pular</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    let notaSelecionada = null;
    overlay.querySelector('#npsButtons').addEventListener('click', (e) => {
      const btn = e.target.closest('.nps-btn');
      if (!btn) return;
      notaSelecionada = Number(btn.dataset.nota);
      overlay.querySelectorAll('.nps-btn').forEach((b) => b.classList.toggle('bg-primary', Number(b.dataset.nota) === notaSelecionada));
      overlay.querySelectorAll('.nps-btn').forEach((b) => b.classList.toggle('text-white', Number(b.dataset.nota) === notaSelecionada));
      overlay.querySelector('#npsEnviar').disabled = false;
    });
    overlay.querySelector('#npsFechar').addEventListener('click', () => {
      overlay.remove();
      setTimeout(() => cliente.recompensas(), 300);
    });
    overlay.querySelector('#npsEnviar').addEventListener('click', async () => {
      if (notaSelecionada === null) return;
      const comentario = overlay.querySelector('#npsComentario')?.value || '';
      await api.request('/nps/responder', { method: 'POST', body: JSON.stringify({ nota: notaSelecionada, comentario, contexto: 'resgate', empresa_id: null }) }, { notify: false });
      overlay.remove();
      ui.message('Obrigado pelo seu feedback!', 'success');
      setTimeout(() => cliente.recompensas(), 800);
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    normalizeBrandingVisuals();
    remapNavigationForPerfil();
    harmonizeLinksByStoredPerfil();
    wireFallbackLinks();
    wireFallbackButtons();
    wireSettingsShortcuts();
    wirePushButtons();
    const handler = handlers[page];
    if (handler) {
      try {
        await handler();
      } catch (err) {
        console.error(err);
        ui.message('Erro ao carregar pagina.', 'error');
      }
    }
  });
})();
