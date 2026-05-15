/**
 * Stitch Integration Layer (Tem de Tudo)
 * Objetivo: manter comportamento atual, com codigo mais organizado e claro.
 * Modulos internos: api, auth, ui, render, pages (cliente/empresa/admin/shared).
 */
(function () {
  // ---------------------- Constantes ---------------------- //
  const API_BASE = `${window.location.origin}/api`;
  const STORAGE = { token: 'tem_de_tudo_token', user: 'tem_de_tudo_user', pendingCompanyQr: 'tem_de_tudo_pending_company_qr' };
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
      reportStats: {
        checkins_hoje: 184,
        checkins_ontem: 167,
        checkins_pendentes: 9,
        pontos_distribuidos_mes: 42180,
        cupons_resgatados_mes: 612,
        usuarios_ativos_mes: 928,
      },
      ticketStats: {
        total: 21,
        pendentes: 12,
        resolvidos: 9,
        urgentes: 3,
      },
      tickets: [
        {
          id: 'demo-ticket-1',
          title: 'Falha de resgate no caixa',
          message: 'Cliente nao conseguiu aplicar cupom no POS.',
          status: 'pendente',
          priority: 'alta',
          category: 'resgate',
          created_at: new Date().toISOString(),
          user: { name: 'Loja Centro', email: 'centro@parceiro.demo' },
        },
        {
          id: 'demo-ticket-2',
          title: 'Divergencia de saldo de pontos',
          message: 'Usuario reportou saldo diferente do extrato.',
          status: 'pendente',
          priority: 'media',
          category: 'pontos',
          created_at: new Date(Date.now() - 3600e3).toISOString(),
          user: { name: 'Suporte Interno', email: 'suporte@temdetudo.com' },
        },
        {
          id: 'demo-ticket-3',
          title: 'Push nao recebido em Android',
          message: 'Cliente autorizou notificacoes, mas nao recebeu transacao.',
          status: 'resolvido',
          priority: 'baixa',
          category: 'push',
          created_at: new Date(Date.now() - 7200e3).toISOString(),
          read_at: new Date(Date.now() - 5400e3).toISOString(),
          user: { name: 'Cliente Demo', email: 'cliente.demo@exemplo.com' },
        },
      ],
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

  const DEMO_PARTNERS = [
    {
      id: 1,
      nome: 'Malagueta Galpao',
      categoria: 'Restaurante',
      ramo: 'restaurante',
      descricao: 'Galpao gastronomico com almoco executivo, happy hour e fidelizacao por QR Code.',
      endereco: 'Rua do Mercado, 128 - Centro, Sao Paulo - SP',
      telefone: '(11) 4002-1101',
      whatsapp: '(11) 98888-2101',
      instagram: '@malaguetagalpao',
      facebook: 'malaguetagalpao',
      email: 'malagueta@demo.local',
      logo: '/assets/images/company1.jpg',
      avaliacao_media: 4.7,
      total_avaliacoes: 3,
      points_multiplier: 1,
      public_page_url: '/detalhe_do_parceiro.html?id=1',
      publicamente_visivel: true,
      status: 'active',
      cartao_fidelidade: {
        titulo: 'Cartao Fidelidade',
        regra_ganho: 'Ganhe 1 ponto a cada visita',
        pontos_por_visita: 1,
        pontos_necessarios: 15,
        recompensa_descricao: 'Ganhe uma porcao de fritas ou um lanche',
        status: 'available',
      },
      bonus_aniversario: {
        titulo: 'Parabens! Seu beneficio do mes esta liberado',
        descricao: 'Cliente aniversariante valida o beneficio presencialmente mostrando o QR Code.',
        imagem_url: '/assets/images/company1.jpg',
        status: 'public',
      },
    },
    {
      id: 2,
      nome: 'Texano Burger',
      categoria: 'Hamburgueria',
      ramo: 'hamburgueria',
      descricao: 'Hamburguer artesanal, combos semanais e recompensas presenciais no balcao.',
      endereco: 'Av. Paulista, 940 - Bela Vista, Sao Paulo - SP',
      telefone: '(11) 4002-1102',
      whatsapp: '(11) 98888-2102',
      instagram: '@texanoburger',
      facebook: 'texanoburger',
      email: 'texano@demo.local',
      logo: '/assets/images/company2.jpg',
      avaliacao_media: 4.5,
      total_avaliacoes: 2,
      points_multiplier: 1.25,
      public_page_url: '/detalhe_do_parceiro.html?id=2',
      publicamente_visivel: true,
      status: 'active',
      cartao_fidelidade: {
        titulo: 'Cartao Fidelidade',
        regra_ganho: 'Ganhe 1 ponto a cada visita',
        pontos_por_visita: 1,
        pontos_necessarios: 15,
        recompensa_descricao: 'Ganhe uma porcao de fritas ou um lanche',
        status: 'available',
      },
      bonus_aniversario: {
        titulo: 'Aniversariante ganha cortesia',
        descricao: 'Apresente seu QR Code no caixa para validar presencialmente.',
        imagem_url: '/assets/images/company2.jpg',
        status: 'public',
      },
    },
    {
      id: 3,
      nome: 'Makoto Sushi',
      categoria: 'Japonesa',
      ramo: 'japonesa',
      descricao: 'Sushi bar com promocoes ativas, fidelidade e bonus de aniversario do mes.',
      endereco: 'Rua Harmonia, 55 - Vila Madalena, Sao Paulo - SP',
      telefone: '(11) 4002-1103',
      whatsapp: '(11) 98888-2103',
      instagram: '@makotosushi',
      facebook: 'makotosushi',
      email: 'makoto@demo.local',
      logo: '/assets/images/company3.jpg',
      avaliacao_media: 4,
      total_avaliacoes: 1,
      points_multiplier: 1.5,
      public_page_url: '/detalhe_do_parceiro.html?id=3',
      publicamente_visivel: true,
      status: 'active',
      cartao_fidelidade: {
        titulo: 'Cartao Fidelidade',
        regra_ganho: 'Ganhe 1 ponto a cada visita',
        pontos_por_visita: 1,
        pontos_necessarios: 15,
        recompensa_descricao: 'Ganhe uma porcao de fritas ou um lanche',
        status: 'available',
      },
      bonus_aniversario: {
        titulo: 'Presente do aniversariante',
        descricao: 'Liberado para clientes elegiveis no mes do aniversario.',
        imagem_url: '/assets/images/company3.jpg',
        status: 'public',
      },
    },
    {
      id: 4,
      nome: 'Florenza Boutique',
      categoria: 'Moda/Beleza',
      ramo: 'moda',
      descricao: 'Boutique com beneficios recorrentes, mimo de aniversario e campanhas sazonais.',
      endereco: 'Alameda das Flores, 210 - Jardins, Sao Paulo - SP',
      telefone: '(11) 4002-1104',
      whatsapp: '(11) 98888-2104',
      instagram: '@florenzaboutique',
      facebook: 'florenzaboutique',
      email: 'florenza@demo.local',
      logo: '/assets/images/company4.jpg',
      avaliacao_media: 5,
      total_avaliacoes: 3,
      points_multiplier: 1,
      public_page_url: '/detalhe_do_parceiro.html?id=4',
      publicamente_visivel: true,
      status: 'active',
      cartao_fidelidade: {
        titulo: 'Cartao Fidelidade',
        regra_ganho: 'Ganhe 1 ponto a cada visita',
        pontos_por_visita: 1,
        pontos_necessarios: 15,
        recompensa_descricao: 'Ganhe uma porcao de fritas ou um lanche',
        status: 'available',
      },
      bonus_aniversario: {
        titulo: 'Mimo do mes de aniversario',
        descricao: 'Valide seu presente diretamente com a equipe da loja.',
        imagem_url: '/assets/images/company4.jpg',
        status: 'public',
      },
    },
  ];

  const DEMO_ADMIN_COMPANIES = [
    ...DEMO_PARTNERS.map((company, idx) => ({
      ...company,
      responsavel: ['Marina Rocha', 'Caio Torres', 'Hiro Tanaka', 'Livia Salles'][idx] || 'Equipe Demo',
      status: 'active',
      ativo: true,
      publicamente_visivel: true,
      qr_code_ready: true,
    })),
    {
      id: 101,
      nome: 'Empresa Pendente Demo',
      categoria: 'Servicos',
      ramo: 'servicos',
      descricao: 'Cadastro aguardando aprovacao para entrar na vitrine publica.',
      endereco: 'Rua do Cadastro, 88 - Campinas, SP',
      telefone: '(19) 4002-8801',
      whatsapp: '(19) 98888-8801',
      email: 'pendente@demo.local',
      responsavel: 'Felipe Cadastro',
      logo: '/assets/images/company2.jpg',
      status: 'pending',
      ativo: false,
      publicamente_visivel: false,
      qr_code_ready: false,
    },
    {
      id: 102,
      nome: 'Empresa Suspensa Demo',
      categoria: 'Varejo',
      ramo: 'varejo',
      descricao: 'Cadastro suspenso para demonstrar governanca e reativacao.',
      endereco: 'Av. de Testes, 450 - Osasco, SP',
      telefone: '(11) 4002-8802',
      whatsapp: '(11) 98888-8802',
      email: 'suspensa@demo.local',
      responsavel: 'Carla Compliance',
      logo: '/assets/images/company3.jpg',
      status: 'suspended',
      ativo: false,
      publicamente_visivel: false,
      qr_code_ready: true,
    },
  ];

  function safeImage(url, fallback = IMAGE_FALLBACKS.store) {
    if (!url || typeof url !== 'string') return fallback;
    const trimmed = url.trim();
    return trimmed || fallback;
  }

  function decodeMojibake(value) {
    if (value == null) return '';
    let text = String(value);
    const markerRegex = /(?:Ã.|Â.|â€™|â€œ|â€|�)/g;
    if (!markerRegex.test(text)) return text;
    markerRegex.lastIndex = 0;

    for (let i = 0; i < 2; i += 1) {
      const currentMarkers = (text.match(markerRegex) || []).length;
      if (!currentMarkers) break;
      try {
        const decoded = decodeURIComponent(escape(text));
        const newMarkers = (decoded.match(markerRegex) || []).length;
        if (newMarkers > currentMarkers) break;
        text = decoded;
      } catch {
        break;
      }
    }
    return text;
  }


  function safeText(value, fallback = '') {
    const parsed = decodeMojibake(value).trim();
    return parsed || fallback;
  }

  function normalizeCategoryKey(value) {
    return safeText(value)
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
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

  function setPendingCompanyQr(code) {
    if (!code) return;
    localStorage.setItem(STORAGE.pendingCompanyQr, String(code));
  }

  function getPendingCompanyQr() {
    return (localStorage.getItem(STORAGE.pendingCompanyQr) || '').trim();
  }

  function clearPendingCompanyQr() {
    localStorage.removeItem(STORAGE.pendingCompanyQr);
  }

  function buildCompanyLinkPageUrl(code) {
    return `/vincular_empresa.html?code=${encodeURIComponent(String(code || ''))}`;
  }

  function buildLoginRedirectForCompanyQr(code) {
    const next = buildCompanyLinkPageUrl(code);
    return `/entrar.html?next=${encodeURIComponent(next)}`;
  }

  function resolveCompanyQrRedirect(perfil) {
    const pendingCode = getPendingCompanyQr();
    if (!pendingCode || perfil !== 'cliente') return null;
    return buildCompanyLinkPageUrl(pendingCode);
  }

  function renderStars(rating = 0) {
    const total = 5;
    const normalized = Math.max(0, Math.min(5, Number(rating || 0)));
    return Array.from({ length: total }, (_, index) => (index < Math.round(normalized) ? '★' : '☆')).join('');
  }

  function formatDatePtBr(value, fallback = 'Nao informada') {
    if (!value) return fallback;
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return fallback;
    return parsed.toLocaleDateString('pt-BR');
  }

  function formatDateRangePtBr(start, end, fallback = 'Nao informada') {
    if (!start && !end) return fallback;
    const startLabel = formatDatePtBr(start, '');
    const endLabel = formatDatePtBr(end, '');
    if (startLabel && endLabel) return `${startLabel} ate ${endLabel}`;
    return startLabel || endLabel || fallback;
  }

  function bonusStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'available':
        return {
          label: 'Disponivel',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Apresente seu QR Code no estabelecimento para resgatar.',
        };
      case 'redeemed':
        return {
          label: 'Ja utilizado',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Este bonus de adesao ja foi utilizado.',
        };
      case 'expired':
        return {
          label: 'Expirado',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'O bonus de adesao desta empresa expirou.',
        };
      case 'not_linked':
        return {
          label: 'Exige vinculo',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Leia o QR Code da empresa para liberar o bonus de adesao.',
        };
      default:
        return {
          label: 'Indisponivel',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhum bonus de adesao ativo no momento.',
        };
    }
  }

  function loyaltyStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'reward_available':
        return {
          label: 'Recompensa liberada',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Cliente ja pode resgatar a recompensa no estabelecimento.',
        };
      case 'available':
        return {
          label: 'Acumulando',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Apresente seu QR Code no estabelecimento para acumular pontos ou resgatar.',
        };
      case 'not_linked':
        return {
          label: 'Exige vinculo',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Leia o QR Code da empresa para liberar o progresso individual.',
        };
      case 'expired':
        return {
          label: 'Expirado',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'O cartao fidelidade desta empresa expirou.',
        };
      case 'inactive':
        return {
          label: 'Inativo',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'A empresa ainda nao esta operando este cartao no momento.',
        };
      default:
        return {
          label: 'Indisponivel',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhum cartao fidelidade ativo no momento.',
        };
    }
  }

  function promotionStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'available':
        return {
          label: 'Disponivel',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Apresente seu QR Code no estabelecimento para validar.',
        };
      case 'redeemed':
        return {
          label: 'Ja utilizada',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Esta promocao ja foi validada para este cliente.',
        };
      case 'not_linked':
        return {
          label: 'Exige vinculo',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Vincule-se a empresa para ficar elegivel a esta promocao.',
        };
      case 'expired':
        return {
          label: 'Expirada',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'A promocao expirou e nao pode mais ser validada.',
        };
      case 'inactive':
        return {
          label: 'Inativa',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'A empresa pausou esta promocao no momento.',
        };
      case 'public':
        return {
          label: 'Publica',
          badgeClass: 'bg-fuchsia-50 text-fuchsia-700',
          message: 'Entre como cliente e apresente seu QR Code no estabelecimento para validar.',
        };
      default:
        return {
          label: 'Indisponivel',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhuma promocao ativa no momento.',
        };
    }
  }

  function birthdayBonusStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'available':
        return {
          label: 'Elegivel',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Apresente seu QR Code no estabelecimento para resgatar o bonus aniversario.',
        };
      case 'redeemed':
        return {
          label: 'Ja utilizado',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Este bonus aniversario ja foi utilizado neste ano.',
        };
      case 'not_linked':
        return {
          label: 'Exige vinculo',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Vincule-se a empresa para liberar o bonus aniversario.',
        };
      case 'missing_birth_date':
        return {
          label: 'Atualize seu cadastro',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'Informe sua data de nascimento para a empresa liberar o bonus aniversario.',
        };
      case 'out_of_window':
        return {
          label: 'Fora da janela',
          badgeClass: 'bg-fuchsia-50 text-fuchsia-700',
          message: 'Este bonus aparece apenas no mes do aniversario ou na janela configurada pela empresa.',
        };
      case 'public':
        return {
          label: 'Consulte no app',
          badgeClass: 'bg-sky-50 text-sky-700',
          message: 'Entre como cliente vinculado para verificar sua elegibilidade ao bonus aniversario.',
        };
      case 'inactive':
        return {
          label: 'Inativo',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'A empresa nao esta operando bonus aniversario no momento.',
        };
      default:
        return {
          label: 'Indisponivel',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhum bonus aniversario ativo no momento.',
        };
    }
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
    let validatedToken = null;

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
        validatedToken = token || validatedToken;
      } else {
        userCache = null;
        validatedToken = null;
      }
    };

    const clear = () => {
      localStorage.removeItem(STORAGE.token);
      localStorage.removeItem(STORAGE.user);
      userCache = null;
      validatedToken = null;
    };

    const logout = () => {
      clear();
      window.location.href = '/entrar.html';
    };

    const ensure = async () => {
      const stored = getStored();
      const token = (stored?.token || '').trim();
      const storedUser = normalizeUser(stored.user);
      if (!token) {
        clear();
        window.location.href = '/entrar.html';
        return null;
      }

      if (userCache && validatedToken === token) return normalizeUser(userCache);

      const { res, data } = await api.request('/auth/me', {}, { notify: false });
      const apiUser = normalizeUser(data?.user || data?.data?.user || data?.data || data);
      if (res.ok && apiUser && apiUser.perfil) {
        save(token, apiUser);
        validatedToken = token;
        return apiUser;
      }

      if (res.status === 401 || res.status === 403) {
        clear();
        window.location.href = '/entrar.html';
        return null;
      }
      if (storedUser && storedUser.perfil) {
        userCache = storedUser;
        validatedToken = null;
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
      admin: ['dashboard_admin_master', 'gest_o_de_estabelecimentos', 'gest_o_de_usu_rios_master', 'gest_o_de_clientes_master', 'relat_rios_gerais_master', 'tickets_admin_master', 'banners_e_categorias_master', 'configuracoes_admin'],
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
        support_agent: '/tickets_admin_master.html',
        confirmation_number: '/tickets_admin_master.html',
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
    if (text.includes('ticket')) return scope === 'admin' ? '/tickets_admin_master.html' : null;
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
          '/recompensas.html': '/relat_rios_gerais_master.html',
          '/hist_rico_de_uso.html': '/relat_rios_gerais_master.html',
        }
      : perfil === 'empresa'
        ? {
          '/meus_pontos.html': '/dashboard_parceiro.html',
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

  function wireAvatarFallbacks() {
    document.querySelectorAll('img[src="/img/avatar-admin.png"]').forEach((img) => {
      if (img.dataset.avatarFallbackBound === '1') return;
      img.dataset.avatarFallbackBound = '1';
      img.addEventListener('error', () => {
        img.onerror = null;
        img.src = '/img/logo.png';
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
      {
        if (!(await auth.guard(['cliente']))) return;
        const pendingCompanyQr = resolveCompanyQrRedirect('cliente');
        if (pendingCompanyQr && !new URLSearchParams(window.location.search).has('ignore_pending_qr')) {
          window.location.href = pendingCompanyQr;
          return;
        }

        ui.setPageState('loading', 'Carregando sua home...');
        const [{ data: dashboardResp }, { data: myQrResp }] = await Promise.all([
          api.request('/cliente/dashboard', {}, { notify: false }),
          api.request('/cliente/meu-qrcode', {}, { notify: false }),
        ]);
        ui.clearPageState();

        const payload = dashboardResp?.data || {};
        const currentUser = await auth.ensure();
        const linkedCompanies = toArray(payload.empresas_vinculadas);
        const featuredCompanies = toArray(payload.empresas_destaque);
        const quickActions = payload.acoes_rapidas || {};
        const myQr = myQrResp?.data || {};
        const params = new URLSearchParams(window.location.search);

        const welcomeEl = document.getElementById('header-welcome');
        if (welcomeEl) {
          const firstName = safeText(currentUser?.name || currentUser?.nome || 'Cliente').split(' ')[0];
          welcomeEl.textContent = `Ola, ${firstName}`;
        }

        const renderCompanyCard = (company) => {
          const rating = Number(company.avaliacao_media || 0);
          const reviews = Number(company.total_avaliacoes || 0);
          const linkedBadge = company.vinculada
            ? '<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-700">Vinculada</span>'
            : '';

          return `
            <article class="rounded-[24px] bg-white p-4 shadow-[0_12px_32px_rgba(8,10,18,0.08)] ring-1 ring-black/5">
              <div class="flex items-start gap-4">
                <img class="h-16 w-16 rounded-2xl bg-slate-50 object-cover" src="${safeImage(company.logo, IMAGE_FALLBACKS.store)}" alt="${safeText(company.nome, 'Empresa')}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#B01774]">${safeText(company.categoria || company.ramo, 'Empresa')}</p>
                      <h3 class="mt-1 text-lg font-extrabold leading-tight text-[#111B3F]">${safeText(company.nome, 'Empresa')}</h3>
                    </div>
                    ${linkedBadge}
                  </div>
                  <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-bold text-amber-400">${renderStars(rating)}</span>
                    <span>${rating > 0 ? rating.toFixed(1).replace('.', ',') : 'Novo'}${reviews ? ` • ${reviews} avaliações` : ''}</span>
                  </div>
                  <p class="mt-2 line-clamp-2 text-sm text-slate-500">${safeText(company.endereco, 'Endereco nao informado')}</p>
                </div>
              </div>
              <div class="mt-4 flex gap-3">
                <a class="inline-flex h-11 flex-1 items-center justify-center rounded-full bg-[linear-gradient(90deg,#00AFA8_0%,#133F8C_45%,#B01774_100%)] px-4 text-sm font-extrabold text-white" href="/detalhe_do_parceiro.html?id=${encodeURIComponent(company.id)}">Abrir empresa</a>
                <button class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 px-4 text-sm font-bold text-slate-600" type="button" data-company-open="${encodeURIComponent(company.id)}">Ver</button>
              </div>
            </article>
          `;
        };

        const linkedList = document.getElementById('linkedCompaniesList');
        const linkedEmpty = document.getElementById('linkedCompaniesEmpty');
        const linkedCount = document.getElementById('linkedCompaniesCount');
        if (linkedList) {
          linkedList.innerHTML = linkedCompanies.map(renderCompanyCard).join('');
        }
        if (linkedEmpty) linkedEmpty.classList.toggle('hidden', linkedCompanies.length > 0);
        if (linkedCount) linkedCount.textContent = linkedCompanies.length ? `${linkedCompanies.length} empresa(s)` : 'Nenhuma empresa vinculada ainda';

        const featuredList = document.getElementById('featuredCompaniesList');
        const featuredSection = document.getElementById('featuredCompaniesSection');
        if (featuredList) {
          featuredList.innerHTML = featuredCompanies.map(renderCompanyCard).join('');
        }
        if (featuredSection) {
          featuredSection.classList.toggle('hidden', featuredCompanies.length === 0);
        }

        document.querySelectorAll('[data-company-open]').forEach((button) => {
          button.addEventListener('click', () => {
            const id = button.getAttribute('data-company-open');
            if (id) window.location.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(id)}`;
          });
        });

        const readQrUrl = quickActions.ler_qr_empresa_url || '/validar_resgate.html?modo=vinculo-empresa';
        document.getElementById('btnReadCompanyQr')?.addEventListener('click', () => {
          window.location.href = readQrUrl;
        });
        document.getElementById('btnBottomScan')?.addEventListener('click', () => {
          window.location.href = readQrUrl;
        });

        const qrCard = document.getElementById('homeMyQrCard');
        const qrContainer = document.getElementById('homeMyQrContainer');
        const toggleQrBtn = document.getElementById('btnShowMyQr');
        const revealMyQr = () => {
          qrCard?.classList.remove('hidden');
          qrCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
          if (toggleQrBtn) {
            toggleQrBtn.textContent = 'Ocultar Meu QR Code';
          }
        };
        const hideMyQr = () => {
          qrCard?.classList.add('hidden');
          if (toggleQrBtn) {
            toggleQrBtn.textContent = 'Meu QR Code';
          }
        };

        if (qrContainer) {
          if (myQr.codigo && myQr.qrcode_svg) {
            const expiresAt = myQr.expira_em ? new Date(myQr.expira_em).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '--';
            qrContainer.innerHTML = `
              <div class="mx-auto flex h-64 w-64 items-center justify-center rounded-[28px] bg-white p-4 shadow-[0_18px_45px_rgba(8,10,18,0.12)] ring-1 ring-black/5">${myQr.qrcode_svg}</div>
              <div class="mt-4 rounded-[20px] bg-slate-50 px-4 py-3 text-center">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Codigo seguro</p>
                <p class="mt-2 break-all font-mono text-sm font-bold text-[#111B3F]">${safeText(myQr.codigo)}</p>
              </div>
              <div class="mt-3 flex flex-wrap items-center justify-center gap-3">
                <button id="copyCustomerQrCode" class="inline-flex h-11 items-center justify-center rounded-full bg-[#111B3F] px-5 text-sm font-bold text-white" type="button">Copiar codigo</button>
                <p class="text-xs text-slate-500">Expira às ${expiresAt}</p>
              </div>
            `;
            document.getElementById('copyCustomerQrCode')?.addEventListener('click', () => {
              navigator.clipboard.writeText(myQr.codigo).then(() => ui.message('Codigo do seu QR copiado.', 'success'));
            });
          } else {
            qrContainer.innerHTML = '<p class="text-sm text-slate-500">Nao foi possivel carregar seu QR Code agora.</p>';
          }
        }

        toggleQrBtn?.addEventListener('click', () => {
          if (qrCard?.classList.contains('hidden')) {
            revealMyQr();
          } else {
            hideMyQr();
          }
        });

        if (params.get('mostrar') === 'meu-qrcode') {
          revealMyQr();
        } else {
          hideMyQr();
        }

        return;
      }

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

      // ---- Politica do programa de fidelidade ----
      const { data: programaResp } = await api.request('/fidelidade/programa', {}, { notify: false });
      const programa = programaResp?.data || null;
      if (programa && !document.getElementById('programaFidelidadeSection')) {
        const host = document.querySelector('main') || document.body;
        const sec = document.createElement('section');
        sec.id = 'programaFidelidadeSection';
        sec.className = 'max-w-6xl mx-auto px-4 pt-4';
        const pontosPorReal = Number(programa?.acumulo?.pontos_por_real || 1).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        const scanBase = Number(programa?.acumulo?.pontos_base_scan || 100).toLocaleString('pt-BR');
        sec.innerHTML = `
          <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4">
            <h3 class="text-base font-semibold text-on-surface mb-2">Como funciona sua fidelidade</h3>
            <div class="grid gap-2 text-sm text-on-surface-variant">
              <p><span class="font-semibold text-on-surface">Acumulo por compra:</span> ${pontosPorReal} ponto(s) por R$ 1,00, com multiplicador da empresa.</p>
              <p><span class="font-semibold text-on-surface">Acumulo por QR:</span> base de ${scanBase} ponto(s), ajustada por campanha/multiplicador.</p>
              <p><span class="font-semibold text-on-surface">Resgate:</span> custo prioriza pontos_necessarios e limite por usuario/estoque da promocao.</p>
            </div>
          </div>`;
        host.appendChild(sec);
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
      const stored = auth.getStored();
      const viewer = auth.normalizeUser(stored?.user);
      const perfilViewer = auth.normalizePerfil(viewer?.perfil || viewer?.role || viewer?.tipo);
      const grid = document.getElementById('partners-grid');
      const searchInput = document.getElementById('parceiroBusca');
      const emptyMsg = document.getElementById('partners-empty');
      const loading = document.getElementById('partners-loading');
      const filterButtons = Array.from(document.querySelectorAll('.parceiro-filtro-btn'));

      const matchesCategory = (item, categoryKey) => {
        if (!categoryKey || categoryKey === 'todos') return true;
        const labels = [
          normalizeCategoryKey(item?.categoria),
          normalizeCategoryKey(item?.ramo),
          normalizeCategoryKey(item?.nome),
        ].filter(Boolean).join(' ');

        switch (categoryKey) {
          case 'restaurantes':
            return /(restaurante|hamburgueria|pizzaria|alimentacao|comida|burger)/.test(labels);
          case 'sorveterias':
            return /(sorvete|sorveteria|gelato|acai)/.test(labels);
          case 'bares':
            return /(bar|boteco|pub|chopp)/.test(labels);
          case 'japonesa':
            return /(japonesa|sushi|ramen|oriental)/.test(labels);
          case 'petshops':
            return /(pet|petshop|veterinaria)/.test(labels);
          case 'beleza':
            return /(beleza|moda|boutique|salao|barbearia|estetica)/.test(labels);
          default:
            return true;
        }
      };

      const renderCards = (lista = []) => {
        if (!grid) return;
        grid.innerHTML = '';
        if (!lista.length) {
          emptyMsg?.classList.remove('hidden');
          return;
        }
        emptyMsg?.classList.add('hidden');
        const tpl = (e) => {
          const rating = toNumber(e?.avaliacao_media, e?.rating, 0);
          const reviews = toNumber(e?.total_avaliacoes, e?.reviews_count, 0);
          const ratingLabel = rating > 0
            ? `${rating.toFixed(1).replace('.', ',')} • ${reviews || 0} avaliacao(oes)`
            : 'Novo parceiro';

          return `
            <article class="bg-surface-container-lowest rounded-[28px] p-4 flex flex-col gap-4 shadow-[0_12px_32px_rgba(11,31,58,0.06)] hover:bg-surface-container-high transition-colors cursor-pointer" data-parceiro-id="${e.id}">
              <div class="flex gap-4">
                <div class="w-20 h-20 rounded-[22px] overflow-hidden flex-shrink-0 bg-surface-container">
                  <img class="w-full h-full object-cover" src="${safeImage(e.logo, IMAGE_FALLBACKS.store)}" alt="${safeText(e.nome, 'Parceiro')}" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex justify-between items-start gap-2">
                    <div>
                      <p class="font-label text-[11px] text-tertiary font-bold tracking-[0.18em] mb-1 uppercase">${safeText(e.categoria || e.ramo, 'Parceiro')}</p>
                      <h3 class="font-headline font-bold text-lg text-on-surface leading-tight">${safeText(e.nome, 'Parceiro')}</h3>
                    </div>
                  </div>
                  <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-bold text-amber-400">${renderStars(rating)}</span>
                    <span>${ratingLabel}</span>
                  </div>
                  <p class="mt-2 text-sm text-on-surface-variant line-clamp-2">${safeText(e.endereco, 'Endereco nao informado')}</p>
                </div>
              </div>
              <div class="flex items-center justify-between gap-3 pt-2 border-t border-surface-container">
                <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-3 py-1 text-[11px] font-semibold text-on-surface-variant">
                  <span class="material-symbols-outlined text-sm text-[#E10098]" style="font-variation-settings: 'FILL' 1;">storefront</span>
                  Empresa ativa
                </span>
                <a class="inline-flex h-11 items-center justify-center rounded-full bg-primary px-4 text-sm font-semibold text-on-primary hover:opacity-90 transition-opacity" href="/detalhe_do_parceiro.html?id=${e.id}">Abrir empresa</a>
              </div>
            </article>`;
        };
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

      let activeCategory = 'todos';

      const setActiveFilter = (categoryKey) => {
        activeCategory = categoryKey || 'todos';
        filterButtons.forEach((button) => {
          const isActive = (button.dataset.categoryKey || 'todos') === activeCategory;
          button.classList.toggle('is-active', isActive);
          if (button.classList.contains('parceiro-category-card')) {
            button.classList.toggle('bg-primary', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-primary/10', !isActive);
          } else {
            button.classList.toggle('bg-primary', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('bg-surface-container', !isActive);
            button.classList.toggle('text-on-surface-variant', !isActive);
          }
        });
      };

      const load = async (busca = '') => {
        loading?.classList.remove('hidden');
        const params = new URLSearchParams();
        if (busca) params.set('busca', busca);
        const qs = params.toString() ? `?${params.toString()}` : '';
        const prefersClientEndpoint = perfilViewer === 'cliente' && stored?.token;
        const primaryPath = `${prefersClientEndpoint ? '/cliente/empresas' : '/empresas'}${qs}`;
        const primaryResponse = await api.request(primaryPath, {}, { requireAuth: false, notify: false });
        let lista = toArray(primaryResponse.data?.data || primaryResponse.data);

        if ((!primaryResponse.res?.ok || !lista.length) && prefersClientEndpoint) {
          const publicResponse = await api.request(`/empresas${qs}`, {}, { requireAuth: false, notify: false });
          lista = toArray(publicResponse.data?.data || publicResponse.data);
        }
        loading?.classList.add('hidden');

        if (!Array.isArray(lista) || lista.length === 0) {
          lista = DEMO_PARTNERS;
        }

        const filtered = lista.filter((item) => matchesCategory(item, activeCategory));
        renderCards(filtered);
      };

      const triggerLoad = () => load(searchInput?.value || '');
      searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          triggerLoad();
        }
      });

      filterButtons.forEach((button) => {
        if (button.dataset.boundFilter === '1') return;
        button.dataset.boundFilter = '1';
        button.addEventListener('click', () => {
          setActiveFilter(button.dataset.categoryKey || 'todos');
          triggerLoad();
        });
      });

      setActiveFilter('todos');
      await load();
      return;
    },

    async detalheParceiro() {
      {
        const stored = auth.getStored();
        const viewer = auth.normalizeUser(stored?.user);
        const perfilViewer = auth.normalizePerfil(viewer?.perfil || viewer?.role || viewer?.tipo);
        const selectedCompanyId = new URLSearchParams(window.location.search).get('id');
        if (!selectedCompanyId) return ui.setPageState('empty', 'Nenhuma empresa selecionada.');

        ui.setPageState('loading', 'Carregando empresa...');
        const { res, data } = await api.request(`/empresas/${selectedCompanyId}`, {}, { requireAuth: false, notify: false });
        ui.clearPageState();

        let companyInfo = data?.data || data || {};
        if (!res.ok || data?.success === false) {
          const fallbackListResponse = await api.request('/empresas', {}, { requireAuth: false, notify: false });
          const fallbackList = toArray(fallbackListResponse.data?.data || fallbackListResponse.data);
          const fallbackCompany = fallbackList.find((item) => String(item?.id || '') === String(selectedCompanyId))
            || DEMO_PARTNERS.find((item) => String(item?.id || '') === String(selectedCompanyId));

          if (!fallbackCompany) {
            ui.setPageState('empty', data?.message || 'Empresa indisponivel no momento.');
            return;
          }

          companyInfo = {
            ...fallbackCompany,
            public_page_url: fallbackCompany.public_page_url || `/detalhe_do_parceiro.html?id=${encodeURIComponent(selectedCompanyId)}`,
            publicamente_visivel: fallbackCompany.publicamente_visivel !== false,
            status: fallbackCompany.status || 'active',
            cartao_fidelidade: fallbackCompany.cartao_fidelidade || null,
            bonus_aniversario: fallbackCompany.bonus_aniversario || null,
          };
          ui.message('Exibindo fallback demo desta empresa enquanto o detalhamento completo nao responde.', 'warning');
        }
        const publicPromotionsResponse = await api.request(`/empresas/${selectedCompanyId}/promocoes`, {}, { requireAuth: false, notify: false });
        const publicReviewsResponse = await api.request(`/empresas/${selectedCompanyId}/avaliacoes`, {}, { requireAuth: false, notify: false });
        const publicPromotions = toArray(publicPromotionsResponse.data?.data || publicPromotionsResponse.data);
        const publicReviewsPayload = publicReviewsResponse.res.ok && publicReviewsResponse.data?.success !== false
          ? (publicReviewsResponse.data?.data || {})
          : {
              summary: {
                average: Number(companyInfo.avaliacao_media || 0),
                total: Number(companyInfo.total_avaliacoes || 0),
                distribution: [],
              },
              items: [],
            };
        const params = new URLSearchParams(window.location.search);
        const setText = (id, value, fallback = 'Nao informado') => {
          const el = document.getElementById(id);
          if (el) el.textContent = safeText(value, fallback);
        };
        const setLink = (id, value, formatter) => {
          const el = document.getElementById(id);
          if (!el) return;
          if (!value) {
            el.classList.add('hidden');
            return;
          }
          el.classList.remove('hidden');
          el.href = formatter ? formatter(value) : value;
        };
        const renderBonusCard = (payload) => {
          const bonus = payload?.bonus || null;
          const meta = bonusStatusMeta(payload?.status);
          const titleEl = document.getElementById('partnerBonusTitle');
          const statusEl = document.getElementById('partnerBonusStatus');
          const descriptionEl = document.getElementById('partnerBonusDescription');
          const expiryEl = document.getElementById('partnerBonusExpiry');
          const hintEl = document.getElementById('partnerBonusHint');
          const imageEl = document.getElementById('partnerBonusImage');
          const actionEl = document.getElementById('partnerBonusAction');

          if (titleEl) titleEl.textContent = bonus?.titulo || 'Bonus de adesao';
          if (statusEl) {
            statusEl.textContent = meta.label;
            statusEl.className = `inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${meta.badgeClass}`;
          }
          if (descriptionEl) {
            descriptionEl.textContent = bonus?.descricao || meta.message;
          }
          if (expiryEl) {
            expiryEl.textContent = formatDatePtBr(bonus?.data_expiracao, 'Nao informada');
          }
          if (hintEl) {
            hintEl.textContent = meta.message;
          }
          if (imageEl) {
            imageEl.src = safeImage(bonus?.imagem_url || bonus?.imagem, IMAGE_FALLBACKS.promo);
            imageEl.onerror = () => {
              imageEl.onerror = null;
              imageEl.src = IMAGE_FALLBACKS.promo;
            };
          }
          if (!actionEl) return;

          if (!perfilViewer) {
            actionEl.textContent = 'Entrar como cliente';
            actionEl.onclick = () => {
              window.location.href = '/entrar.html';
            };
            return;
          }

          if (perfilViewer !== 'cliente') {
            actionEl.textContent = 'Voltar ao painel';
            actionEl.onclick = () => {
              window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html';
            };
            return;
          }

          if (payload?.status === 'available' || payload?.status === 'redeemed') {
            actionEl.textContent = 'Mostrar meu QR Code';
            actionEl.onclick = () => {
              window.location.href = '/meus_pontos.html?mostrar=meu-qrcode';
            };
            return;
          }

          actionEl.textContent = 'Ler QR da empresa';
          actionEl.onclick = () => {
            window.location.href = '/validar_resgate.html?modo=vinculo-empresa';
          };
        };
        const renderBirthdayCard = (payload) => {
          const bonus = payload?.bonus || payload || null;
          const status = payload?.status || (bonus?.status === 'available' ? 'public' : bonus?.status);
          const meta = birthdayBonusStatusMeta(status);
          const titleEl = document.getElementById('partnerBirthdayTitle');
          const statusEl = document.getElementById('partnerBirthdayStatus');
          const descriptionEl = document.getElementById('partnerBirthdayDescription');
          const validityEl = document.getElementById('partnerBirthdayValidity');
          const hintEl = document.getElementById('partnerBirthdayHint');
          const imageEl = document.getElementById('partnerBirthdayImage');
          const actionEl = document.getElementById('partnerBirthdayAction');

          if (titleEl) titleEl.textContent = bonus?.titulo || 'Bonus aniversario';
          if (statusEl) {
            statusEl.textContent = meta.label;
            statusEl.className = `inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${meta.badgeClass}`;
          }
          if (descriptionEl) {
            descriptionEl.textContent = bonus?.descricao || meta.message;
          }
          if (validityEl) {
            validityEl.textContent = bonus?.validade_descricao
              || formatDateRangePtBr(payload?.valid_from, payload?.valid_until, 'Consulte a regra da empresa');
          }
          if (hintEl) {
            hintEl.textContent = payload?.message || meta.message;
          }
          if (imageEl) {
            imageEl.src = safeImage(bonus?.imagem_url || bonus?.imagem, IMAGE_FALLBACKS.promo);
            imageEl.onerror = () => {
              imageEl.onerror = null;
              imageEl.src = IMAGE_FALLBACKS.promo;
            };
          }
          if (!actionEl) return;

          actionEl.disabled = false;
          actionEl.classList.remove('opacity-60', 'cursor-not-allowed');

          if (!perfilViewer) {
            actionEl.textContent = 'Entrar como cliente';
            actionEl.onclick = () => {
              window.location.href = '/entrar.html';
            };
            return;
          }

          if (perfilViewer !== 'cliente') {
            actionEl.textContent = 'Voltar ao painel';
            actionEl.onclick = () => {
              window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html';
            };
            return;
          }

          if (status === 'missing_birth_date') {
            actionEl.textContent = 'Atualizar meu perfil';
            actionEl.onclick = () => {
              window.location.href = '/meu_perfil.html';
            };
            return;
          }

          if (status === 'not_linked') {
            actionEl.textContent = 'Ler QR da empresa';
            actionEl.onclick = () => {
              window.location.href = '/validar_resgate.html?modo=vinculo-empresa';
            };
            return;
          }

          if (status === 'available' || status === 'redeemed') {
            actionEl.textContent = 'Mostrar meu QR Code';
            actionEl.onclick = () => {
              window.location.href = '/meus_pontos.html?mostrar=meu-qrcode';
            };
            return;
          }

          actionEl.textContent = 'Aguardar periodo valido';
          actionEl.onclick = null;
          actionEl.disabled = true;
          actionEl.classList.add('opacity-60', 'cursor-not-allowed');
        };
        const renderLoyaltyCard = (payload) => {
          const loyalty = payload?.card || companyInfo.cartao_fidelidade || null;
          const progress = payload?.progress || null;
          const meta = loyaltyStatusMeta(payload?.status || loyalty?.status);
          const titleEl = document.getElementById('partnerLoyaltyTitle');
          const statusEl = document.getElementById('partnerLoyaltyStatus');
          const descriptionEl = document.getElementById('partnerLoyaltyDescription');
          const ruleEl = document.getElementById('partnerLoyaltyRule');
          const rewardEl = document.getElementById('partnerLoyaltyReward');
          const progressLabelEl = document.getElementById('partnerLoyaltyProgressLabel');
          const progressStatusEl = document.getElementById('partnerLoyaltyProgressStatus');
          const progressBarEl = document.getElementById('partnerLoyaltyProgressBar');
          const hintEl = document.getElementById('partnerLoyaltyHint');
          const actionEl = document.getElementById('partnerLoyaltyAction');
          const pointsPerVisitEl = document.getElementById('partnerLoyaltyPointsPerVisit');
          const targetEl = document.getElementById('partnerLoyaltyTarget');
          const expiryEl = document.getElementById('partnerLoyaltyExpiry');

          const requiredPoints = Math.max(0, Number(loyalty?.pontos_necessarios || progress?.required_points || 0));
          const currentPoints = Math.max(0, Number(progress?.current_points || 0));
          const progressPercent = Math.max(0, Math.min(100, Number(progress?.percentage || 0)));
          const rewardAvailable = Boolean(progress?.reward_available);

          if (titleEl) titleEl.textContent = loyalty?.titulo || 'Cartao fidelidade';
          if (statusEl) {
            statusEl.textContent = meta.label;
            statusEl.className = `inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${meta.badgeClass}`;
          }
          if (descriptionEl) {
            descriptionEl.textContent = loyalty?.descricao || meta.message;
          }
          if (ruleEl) {
            ruleEl.textContent = loyalty?.regra_ganho || 'Ganhe pontos por visita.';
          }
          if (rewardEl) {
            rewardEl.textContent = loyalty?.recompensa_descricao || 'Ainda nao informada';
          }
          if (progressLabelEl) {
            progressLabelEl.textContent = progress?.progress_label || `${currentPoints} de ${requiredPoints} pontos`;
          }
          if (progressStatusEl) {
            progressStatusEl.textContent = rewardAvailable
              ? 'Recompensa pronta'
              : (payload?.status === 'not_linked' ? 'Exige vinculo' : 'Em andamento');
          }
          if (progressBarEl) {
            progressBarEl.style.width = `${progressPercent}%`;
          }
          if (hintEl) {
            hintEl.textContent = meta.message;
          }
          if (pointsPerVisitEl) {
            pointsPerVisitEl.textContent = String(loyalty?.pontos_por_visita || progress?.points_per_visit || 0);
          }
          if (targetEl) {
            targetEl.textContent = `${requiredPoints} pontos`;
          }
          if (expiryEl) {
            expiryEl.textContent = formatDatePtBr(loyalty?.data_expiracao, 'Nao informada');
          }

          if (!actionEl) return;

          if (!perfilViewer) {
            actionEl.textContent = 'Entrar como cliente';
            actionEl.onclick = () => {
              window.location.href = '/entrar.html';
            };
            return;
          }

          if (perfilViewer !== 'cliente') {
            actionEl.textContent = 'Voltar ao painel';
            actionEl.onclick = () => {
              window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html';
            };
            return;
          }

          if (payload?.status === 'not_linked') {
            actionEl.textContent = 'Ler QR da empresa';
            actionEl.onclick = () => {
              window.location.href = '/validar_resgate.html?modo=vinculo-empresa';
            };
            return;
          }

          actionEl.textContent = rewardAvailable ? 'Mostrar meu QR para resgatar' : 'Mostrar meu QR Code';
          actionEl.onclick = () => {
            window.location.href = '/meus_pontos.html?mostrar=meu-qrcode';
          };
        };
        const renderPromotionCards = (publicItems, viewerItems = []) => {
          const section = document.getElementById('partnerPromotionsCard');
          const listEl = document.getElementById('partnerPromotionsList');
          const emptyEl = document.getElementById('partnerPromotionsEmpty');
          const statusEl = document.getElementById('partnerPromotionsStatus');
          const messageEl = document.getElementById('partnerPromotionsMessage');
          if (!section || !listEl || !emptyEl || !statusEl || !messageEl) return;

          const viewerMap = new Map(toArray(viewerItems).map((item) => [Number(item?.id || 0), item]));
          const merged = toArray(publicItems).map((item) => {
            const viewerItem = viewerMap.get(Number(item?.id || 0));
            if (viewerItem) {
              return { ...item, ...viewerItem };
            }

            return {
              ...item,
              viewer_status: perfilViewer === 'cliente'
                ? 'not_linked'
                : (!perfilViewer ? 'public' : 'inactive'),
              can_self_redeem: false,
              can_present_qr: false,
            };
          });
          const items = merged.length ? merged : toArray(viewerItems);
          const normalizedItems = items.map((item) => {
            const normalized = { ...item };
            if (!normalized.viewer_status) {
              if (perfilViewer === 'cliente') {
                normalized.viewer_status = 'not_linked';
              } else if (!perfilViewer) {
                normalized.viewer_status = 'public';
              } else {
                normalized.viewer_status = 'inactive';
              }
            }
            return normalized;
          });

          listEl.innerHTML = '';
          emptyEl.classList.toggle('hidden', normalizedItems.length > 0);

          const summaryStatus = normalizedItems.some((item) => item.viewer_status === 'available')
            ? 'available'
            : normalizedItems.some((item) => item.viewer_status === 'redeemed')
              ? 'redeemed'
              : normalizedItems.some((item) => item.viewer_status === 'not_linked')
                ? 'not_linked'
                : normalizedItems.some((item) => item.viewer_status === 'public')
                  ? 'public'
                  : (normalizedItems[0]?.status || 'inactive');
          const summaryMeta = promotionStatusMeta(summaryStatus);
          statusEl.textContent = summaryMeta.label;
          statusEl.className = `inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${summaryMeta.badgeClass}`;
          messageEl.textContent = normalizedItems.length
            ? summaryMeta.message
            : 'Nenhuma promocao ativa no momento.';

          normalizedItems.forEach((promo) => {
            const meta = promotionStatusMeta(promo.viewer_status || promo.status);
            const card = document.createElement('article');
            card.className = 'overflow-hidden rounded-[26px] bg-slate-50 shadow-sm ring-1 ring-black/5';
            card.innerHTML = `
              <div class="grid gap-0 md:grid-cols-[200px_minmax(0,1fr)]">
                <img class="h-full min-h-[180px] w-full object-cover" src="${safeImage(promo.imagem_url || promo.imagem, IMAGE_FALLBACKS.promo)}" alt="${safeText(promo.titulo || 'Promocao')}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" />
                <div class="space-y-4 p-5">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h3 class="text-xl font-extrabold text-[#111B3F]">${safeText(promo.titulo, 'Promocao instantanea')}</h3>
                      <p class="mt-2 text-sm leading-7 text-slate-600">${safeText(promo.descricao, meta.message)}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}">${meta.label}</span>
                  </div>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[20px] bg-white p-4">
                      <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Validade</p>
                      <p class="mt-2 text-sm font-semibold text-[#111B3F]">${formatDatePtBr(promo.data_expiracao || promo.validade, 'Nao informada')}</p>
                    </div>
                    <div class="rounded-[20px] bg-white p-4">
                      <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Como validar</p>
                      <p class="mt-2 text-sm font-semibold text-[#111B3F]">Apresente seu QR Code no estabelecimento para validar.</p>
                    </div>
                  </div>
                  <div class="flex flex-wrap items-center gap-3">
                    <button type="button" class="partner-promo-action inline-flex h-11 items-center justify-center rounded-full bg-[linear-gradient(90deg,#00AFA8_0%,#133F8C_45%,#B01774_100%)] px-5 text-sm font-extrabold text-white shadow-[0_6px_14px_rgba(0,0,0,0.12)]">Continuar</button>
                    <span class="text-sm text-slate-500">${safeText(promo.message, meta.message)}</span>
                  </div>
                </div>
              </div>
            `;

            const actionBtn = card.querySelector('.partner-promo-action');
            if (actionBtn) {
              if (!perfilViewer) {
                actionBtn.textContent = 'Entrar como cliente';
                actionBtn.onclick = () => { window.location.href = '/entrar.html'; };
              } else if (perfilViewer !== 'cliente') {
                actionBtn.textContent = 'Voltar ao painel';
                actionBtn.onclick = () => { window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html'; };
              } else if (promo.viewer_status === 'available') {
                actionBtn.textContent = 'Mostrar meu QR Code';
                actionBtn.onclick = () => { window.location.href = '/meus_pontos.html?mostrar=meu-qrcode'; };
              } else if (promo.viewer_status === 'redeemed') {
                actionBtn.textContent = 'Ja utilizada';
                actionBtn.disabled = true;
                actionBtn.classList.add('opacity-60', 'cursor-not-allowed');
              } else {
                actionBtn.textContent = 'Ler QR da empresa';
                actionBtn.onclick = () => { window.location.href = '/validar_resgate.html?modo=vinculo-empresa'; };
              }
            }

            listEl.appendChild(card);
          });
        };
        const reviewForm = document.getElementById('partnerReviewForm');
        const reviewCommentEl = document.getElementById('partnerReviewComment');
        const reviewStatusEl = document.getElementById('partnerReviewStatus');
        const reviewSubmitEl = document.getElementById('partnerReviewSubmit');
        const reviewShowQrEl = document.getElementById('partnerReviewShowQr');
        const reviewHintEl = document.getElementById('partnerMyReviewHint');
        const reviewStars = Array.from(document.querySelectorAll('.partner-review-star'));
        let selectedReviewRating = 0;
        let myReview = null;

        const setReviewRating = (rating = 0) => {
          selectedReviewRating = Number(rating || 0);
          reviewStars.forEach((button) => {
            const buttonRating = Number(button.dataset.rating || 0);
            const isActive = buttonRating <= selectedReviewRating;
            button.className = `partner-review-star inline-flex h-11 w-11 items-center justify-center rounded-full text-lg font-extrabold shadow-sm transition-all ${
              isActive
                ? 'bg-[#111B3F] text-white'
                : 'bg-white text-slate-400'
            }`;
          });
        };

        const setReviewMode = (mode) => {
          const editable = mode === 'editable';
          reviewStars.forEach((button) => {
            button.disabled = !editable;
            button.classList.toggle('cursor-not-allowed', !editable);
            button.classList.toggle('opacity-60', !editable);
          });
          if (reviewCommentEl) reviewCommentEl.disabled = !editable;
          if (reviewSubmitEl) {
            reviewSubmitEl.disabled = !editable;
            reviewSubmitEl.classList.toggle('opacity-60', !editable);
            reviewSubmitEl.classList.toggle('cursor-not-allowed', !editable);
          }
        };

        reviewStars.forEach((button) => {
          if (button.dataset.bound === '1') return;
          button.dataset.bound = '1';
          button.addEventListener('click', () => {
            setReviewRating(Number(button.dataset.rating || 0));
          });
        });

        if (reviewShowQrEl && reviewShowQrEl.dataset.bound !== '1') {
          reviewShowQrEl.dataset.bound = '1';
          reviewShowQrEl.addEventListener('click', () => {
            if (perfilViewer === 'cliente') {
              window.location.href = '/meus_pontos.html?mostrar=meu-qrcode';
            } else if (!perfilViewer) {
              window.location.href = '/entrar.html';
            } else {
              window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html';
            }
          });
        }

        const renderReviews = (payload, ownReview = null) => {
          const summary = payload?.summary || {};
          const items = toArray(payload?.items);
          const average = Number(summary.average || companyInfo.avaliacao_media || 0);
          const total = Number(summary.total || companyInfo.total_avaliacoes || 0);
          const distribution = toArray(summary.distribution);
          const averageLabel = average > 0 ? average.toFixed(1).replace('.', ',') : '0,0';

          setText('partnerReviewsAverage', averageLabel, '0,0');
          setText('partnerReviewsStarsLarge', renderStars(average), renderStars(0));
          setText('partnerReviewsTotal', total ? `${total} avaliações` : 'Sem avaliações', 'Sem avaliações');
          setText('partnerReviewsSubtitle', total ? 'Comentários recentes de clientes vinculados.' : 'Ainda não há avaliações públicas desta empresa.');

          const distributionEl = document.getElementById('partnerReviewsDistribution');
          if (distributionEl) {
            distributionEl.innerHTML = '';
            distribution.forEach((entry) => {
              const entryTotal = Number(entry?.total || 0);
              const percent = total > 0 ? Math.round((entryTotal / total) * 100) : 0;
              const row = document.createElement('div');
              row.className = 'flex items-center gap-3';
              row.innerHTML = `
                <span class="w-10 text-xs font-bold uppercase tracking-[0.12em] text-slate-400">${entry.star}★</span>
                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                  <div class="h-full rounded-full bg-[linear-gradient(90deg,#00AFA8_0%,#133F8C_45%,#B01774_100%)]" style="width:${percent}%"></div>
                </div>
                <span class="w-14 text-right text-xs font-semibold text-slate-500">${entryTotal}</span>
              `;
              distributionEl.appendChild(row);
            });
          }

          const listEl = document.getElementById('partnerReviewsList');
          const emptyEl = document.getElementById('partnerReviewsEmpty');
          if (listEl) listEl.innerHTML = '';
          if (emptyEl) emptyEl.classList.toggle('hidden', items.length > 0);

          items.forEach((review) => {
            const card = document.createElement('article');
            card.className = 'rounded-[22px] bg-slate-50 p-4';
            card.innerHTML = `
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-extrabold text-[#111B3F]">${safeText(review?.cliente?.nome, 'Cliente')}</p>
                  <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-[#B01774]">${renderStars(Number(review?.nota || review?.estrelas || 0))}</p>
                </div>
                <span class="text-xs text-slate-400">${formatDatePtBr(review?.updated_at || review?.created_at, 'Agora')}</span>
              </div>
              <p class="mt-3 text-sm leading-6 text-slate-600">${safeText(review?.comentario, 'Cliente avaliou sem comentário adicional.')}</p>
            `;
            listEl?.appendChild(card);
          });

          myReview = ownReview || null;
          if (myReview) {
            setReviewRating(Number(myReview?.nota || myReview?.estrelas || 0));
            if (reviewCommentEl) reviewCommentEl.value = safeText(myReview?.comentario, '');
            if (reviewStatusEl) reviewStatusEl.textContent = 'Sua avaliação atual já foi carregada. Você pode editar a nota e o comentário.';
            if (reviewSubmitEl) reviewSubmitEl.textContent = 'Atualizar avaliação';
          } else {
            setReviewRating(0);
            if (reviewCommentEl) reviewCommentEl.value = '';
            if (reviewStatusEl) reviewStatusEl.textContent = 'Escolha uma nota de 1 a 5 e adicione um comentário opcional.';
            if (reviewSubmitEl) reviewSubmitEl.textContent = 'Salvar avaliação';
          }

          if (!perfilViewer) {
            setReviewMode('readonly');
            if (reviewHintEl) reviewHintEl.textContent = 'Entre com uma conta de cliente para registrar sua avaliação.';
            if (reviewStatusEl) reviewStatusEl.textContent = 'A avaliação é permitida apenas para clientes autenticados e vinculados.';
            if (reviewSubmitEl) reviewSubmitEl.textContent = 'Entrar para avaliar';
            return;
          }

          if (perfilViewer !== 'cliente') {
            setReviewMode('readonly');
            if (reviewHintEl) reviewHintEl.textContent = 'Somente clientes vinculados podem avaliar. Perfis de empresa e admin apenas consultam este painel.';
            if (reviewStatusEl) reviewStatusEl.textContent = 'Use uma conta de cliente vinculada para avaliar.';
            if (reviewSubmitEl) reviewSubmitEl.textContent = 'Voltar ao painel';
            return;
          }

          setReviewMode('editable');
          if (reviewHintEl) reviewHintEl.textContent = 'Avalie de 1 a 5 apenas se você já estiver vinculado a esta empresa.';
        };

        if (reviewForm && reviewForm.dataset.bound !== '1') {
          reviewForm.dataset.bound = '1';
          reviewForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (perfilViewer !== 'cliente') {
              window.location.href = !perfilViewer ? '/entrar.html' : (redirectMap[perfilViewer] || '/meus_pontos.html');
              return;
            }

            if (selectedReviewRating < 1 || selectedReviewRating > 5) {
              ui.message('Escolha uma nota de 1 a 5 antes de enviar sua avaliação.', 'warning');
              return;
            }

            ui.setPageState('loading', 'Salvando avaliação...');
            const endpoint = myReview
              ? `/empresas/${encodeURIComponent(selectedCompanyId)}/avaliacoes/minha`
              : `/empresas/${encodeURIComponent(selectedCompanyId)}/avaliacoes`;
            const method = myReview ? 'PUT' : 'POST';
            const { res: saveRes, data: saveData } = await api.request(endpoint, {
              method,
              body: JSON.stringify({
                estrelas: selectedReviewRating,
                comentario: reviewCommentEl?.value?.trim() || '',
              }),
            }, { notify: false });
            ui.clearPageState();

            if (!saveRes.ok || saveData?.success === false) {
              ui.message(saveData?.message || 'Não foi possível salvar sua avaliação agora.', 'error');
              return;
            }

            myReview = saveData?.data?.avaliacao || myReview;
            ui.message(saveData?.message || 'Avaliação salva com sucesso.', 'success');

            const refreshedReviews = await api.request(`/empresas/${selectedCompanyId}/avaliacoes`, {}, { requireAuth: false, notify: false });
            const refreshedPayload = refreshedReviews.res.ok && refreshedReviews.data?.success !== false
              ? (refreshedReviews.data?.data || {})
              : publicReviewsPayload;
            renderReviews(refreshedPayload, myReview);
          });
        }

        const heroLogo = document.getElementById('partner-logo');
        if (heroLogo) {
          heroLogo.src = safeImage(companyInfo.logo, IMAGE_FALLBACKS.store);
          heroLogo.onerror = () => {
            heroLogo.onerror = null;
            heroLogo.src = IMAGE_FALLBACKS.store;
          };
        }

        setText('partner-category', companyInfo.categoria || companyInfo.ramo, 'Empresa');
        setText('partner-name', companyInfo.nome, 'Empresa');
        setText('partner-address', companyInfo.endereco, 'Endereco nao informado');
        setText('partner-about', companyInfo.descricao, 'Esta empresa ja esta pronta para receber clientes via QR Code e operar bonus ou fidelidade conforme configuracao ativa.');
        setText('partner-phone', companyInfo.telefone, 'Nao informado');
        setText('partner-whatsapp', companyInfo.whatsapp, 'Nao informado');
        setText('partner-instagram', companyInfo.instagram, 'Nao informado');
        setText('partner-facebook', companyInfo.facebook, 'Nao informado');

        const rating = Number(companyInfo.avaliacao_media || 0);
        const totalReviews = Number(companyInfo.total_avaliacoes || 0);
        setText('partner-rating', rating > 0 ? rating.toFixed(1).replace('.', ',') : 'Novo', 'Novo');
        setText('partner-review-count', totalReviews ? `${totalReviews} avaliações` : 'Sem avaliações ainda', 'Sem avaliações ainda');
        setText('partner-rating-stars', renderStars(rating), renderStars(0));

        setLink('partnerWhatsappLink', companyInfo.whatsapp, (value) => `https://wa.me/${String(value).replace(/\D/g, '')}`);
        setLink('partnerInstagramLink', companyInfo.instagram, (value) => String(value).startsWith('http') ? value : `https://instagram.com/${String(value).replace(/^@/, '')}`);
        setLink('partnerFacebookLink', companyInfo.facebook, (value) => String(value).startsWith('http') ? value : `https://facebook.com/${String(value).replace(/^@/, '')}`);

        const statusBadge = document.getElementById('partner-status-badge');
        if (statusBadge) statusBadge.textContent = companyInfo.publicamente_visivel ? 'Empresa ativa no app' : 'Empresa indisponivel';

        const ctaBtn = document.getElementById('partnerPrimaryAction');
        if (ctaBtn) {
          if (perfilViewer === 'cliente') {
            ctaBtn.textContent = 'Ler QR Code da Empresa';
            ctaBtn.addEventListener('click', () => {
              window.location.href = '/validar_resgate.html?modo=vinculo-empresa';
            });
          } else if (!perfilViewer) {
            ctaBtn.textContent = 'Entrar para continuar';
            ctaBtn.addEventListener('click', () => {
              window.location.href = '/entrar.html';
            });
          } else {
            ctaBtn.textContent = 'Voltar ao painel';
            ctaBtn.addEventListener('click', () => {
              window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html';
            });
          }
        }

        renderBonusCard(null);
        renderBirthdayCard(companyInfo?.bonus_aniversario
          ? {
              status: companyInfo.bonus_aniversario?.status === 'available'
                ? 'public'
                : companyInfo.bonus_aniversario?.status,
              message: companyInfo.bonus_aniversario?.status === 'available'
                ? 'Entre como cliente vinculado para consultar sua elegibilidade ao bonus aniversario.'
                : 'A empresa nao esta operando bonus aniversario no momento.',
              bonus: companyInfo.bonus_aniversario,
            }
          : null);
        renderLoyaltyCard(null);
        let viewerPromotions = [];

        if (perfilViewer === 'cliente' && stored?.token) {
          const [bonusResponse, birthdayResponse, loyaltyResponse, promotionsResponse, myReviewResponse] = await Promise.all([
            api.request(`/cliente/bonus-adesao/disponivel/${encodeURIComponent(selectedCompanyId)}`, {}, { notify: false }),
            api.request(`/cliente/bonus-aniversario/disponiveis?empresa_id=${encodeURIComponent(selectedCompanyId)}`, {}, { notify: false }),
            api.request(`/cliente/cartao-fidelidade/progresso/${encodeURIComponent(selectedCompanyId)}`, {}, { notify: false }),
            api.request(`/cliente/promocoes?empresa_id=${encodeURIComponent(selectedCompanyId)}`, {}, { notify: false }),
            api.request(`/cliente/avaliacoes?empresa_id=${encodeURIComponent(selectedCompanyId)}`, {}, { notify: false }),
          ]);
          if (bonusResponse.res.ok && bonusResponse.data?.success !== false) {
            const bonusPayload = bonusResponse.data?.data || {};
            renderBonusCard(bonusPayload);
            if (params.get('linked') === '1') {
              ui.message(
                bonusPayload.status === 'available'
                  ? 'Empresa vinculada com sucesso. Bonus de adesao disponivel para apresentar no estabelecimento.'
                  : 'Empresa vinculada com sucesso.',
                'success'
              );
            }
          } else if (params.get('linked') === '1') {
            ui.message('Empresa vinculada com sucesso.', 'success');
          }

          if (loyaltyResponse.res.ok && loyaltyResponse.data?.success !== false) {
            renderLoyaltyCard(loyaltyResponse.data?.data || {});
          }

          if (birthdayResponse.res.ok && birthdayResponse.data?.success !== false) {
            const birthdayItems = toArray(birthdayResponse.data?.data?.items || birthdayResponse.data?.data);
            renderBirthdayCard(birthdayItems[0] || null);
          }

          if (promotionsResponse.res.ok && promotionsResponse.data?.success !== false) {
            viewerPromotions = toArray(promotionsResponse.data?.data || promotionsResponse.data);
          }

          if (myReviewResponse.res.ok && myReviewResponse.data?.success !== false) {
            myReview = toArray(myReviewResponse.data?.data?.items || myReviewResponse.data?.data)[0] || null;
          }
        } else if (params.get('linked') === '1') {
          ui.message('Empresa vinculada com sucesso.', 'success');
        }

        renderReviews(publicReviewsPayload, myReview);
        renderPromotionCards(publicPromotions, viewerPromotions);

        return;
      }

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

      const listaProdutos = toArray(produtos.data?.data || produtos.data?.produtos || produtos.data);
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

      if (!produtos.res?.ok && viewer?.perfil === 'cliente') {
        ui.message('Produtos deste parceiro indisponiveis no momento.', 'warning');
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

      const promos = toArray(promosResp?.data || promosResp);
      const host = document.querySelector('main') || document.body;

      // ---- Promocoes instantaneas canônicas ----
      if (promos.length) {
        const promosWrap = document.createElement('section');
        promosWrap.className = 'max-w-6xl mx-auto px-4 pt-4';
        promosWrap.innerHTML = `<h3 class="text-lg font-semibold text-on-surface mb-3">Promocoes instantaneas</h3>
          <div id="promosGrid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>`;
        host.appendChild(promosWrap);

        const grid = promosWrap.querySelector('#promosGrid');
        promos.forEach((p) => {
          const meta = promotionStatusMeta(p.viewer_status || p.status);
          const empresaUrl = p?.empresa?.public_page_url || (p?.empresa?.id ? `/detalhe_do_parceiro.html?id=${p.empresa.id}` : '#');
          const ctaLabel = p.viewer_status === 'available'
            ? 'Apresentar QR no estabelecimento'
            : (p.viewer_status === 'redeemed' ? 'Ja utilizada' : 'Ver empresa');
          const card = document.createElement('div');
          card.className = 'rounded-2xl bg-white/80 border border-surface-variant/30 shadow-sm p-4 flex flex-col gap-2';
          card.innerHTML = `
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-bold text-on-surface">${p.titulo || p.nome || 'Promocao'}</p>
                <p class="text-xs text-on-surface-variant">${p?.empresa?.nome || p.empresa_nome || ''}</p>
              </div>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap ${meta.badgeClass}">${meta.label}</span>
            </div>
            <p class="text-sm text-on-surface-variant line-clamp-2">${p.descricao || ''}</p>
            <div class="flex items-center justify-between mt-auto pt-2 border-t border-surface-variant/20">
              <span class="text-xs text-on-surface-variant">${formatDatePtBr(p.data_expiracao || p.validade, 'Sem prazo')}</span>
              <button data-promo-id="${p.id}" data-promo-status="${p.viewer_status || p.status || 'public'}" data-promo-url="${empresaUrl}"
                class="btn-promo-info px-3 py-1.5 rounded-lg text-sm font-semibold transition-all ${p.viewer_status === 'redeemed' ? 'bg-surface-container text-on-surface-variant cursor-not-allowed' : 'bg-primary text-white hover:bg-primary/90'}"
                ${p.viewer_status === 'redeemed' ? 'disabled' : ''}>
                ${ctaLabel}
              </button>
            </div>`;
          grid.appendChild(card);
        });

        // Event delegation para orientar a validacao presencial
        grid.addEventListener('click', async (e) => {
          const btn = e.target.closest('.btn-promo-info');
          if (!btn || btn.disabled) return;
          const status = btn.dataset.promoStatus || 'public';
          const targetUrl = btn.dataset.promoUrl || '/parceiros_tem_de_tudo.html';
          if (status === 'available') {
            ui.message('Apresente seu QR Code no estabelecimento para validar esta promocao.', 'success');
            setTimeout(() => {
              window.location.href = '/meus_pontos.html?mostrar=meu-qrcode';
            }, 300);
          } else {
            window.location.href = targetUrl;
          }
        });
      } else {
        const empty = document.createElement('p');
        empty.className = 'max-w-6xl mx-auto px-4 pt-4 text-center text-on-surface-variant';
        empty.textContent = 'Nenhuma promocao disponivel no momento.';
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

      // ---- ISSUE #11: Botões "Trocar pontos" (Gift Cards/Vouchers) ----
      // Adiciona event listeners aos botões de trocar pontos presentes no HTML
      setTimeout(() => {
        const trocarButtons = Array.from(document.querySelectorAll('button')).filter((btn) =>
          btn.textContent.trim().toLowerCase() === 'trocar'
        );

        trocarButtons.forEach((btn) => {
          btn.addEventListener('click', async () => {
            // Extrair informações do card pai
            const card = btn.closest('.bg-surface-container-lowest');
            if (!card) return;

            const titulo = card.querySelector('h4')?.textContent || 'Voucher';
            const pontosText = card.querySelector('.text-primary')?.textContent;
            const pontos = pontosText ? parseInt(pontosText.replace(/\D/g, '')) : 0;

            // Verificar se tem pontos suficientes
            if (pontos > Number(saldoAtual)) {
              ui.message(`Pontos insuficientes! Você tem ${Number(saldoAtual).toLocaleString('pt-BR')} pts, precisa de ${pontos} pts.`, 'error');
              return;
            }

            // Confirmar troca
            if (!confirm(`Trocar ${pontos} pontos por "${titulo}"?\n\nSeu saldo atual: ${Number(saldoAtual).toLocaleString('pt-BR')} pts\nNovo saldo: ${(Number(saldoAtual) - pontos).toLocaleString('pt-BR')} pts`)) return;

            btn.disabled = true;
            const textoOriginal = btn.textContent;
            btn.textContent = 'Processando...';

            // API de troca de pontos por voucher
            const { res, data } = await api.request('/pontos/trocar-voucher', {
              method: 'POST',
              body: JSON.stringify({
                descricao: titulo,
                pontos_necessarios: pontos,
                tipo: 'voucher'
              })
            });

            if (res.ok && data?.success) {
              const codigo = data?.data?.codigo || data?.codigo || 'VCH' + Math.random().toString(36).substr(2, 9).toUpperCase();
              ui.message(`✅ Voucher resgatado com sucesso!\n\nCódigo: ${codigo}\n\nGuarde este código para utilizar.`, 'success');
              setTimeout(() => {
                window.location.href = '/meus_pontos.html';
              }, 3000);
            } else {
              ui.message(data?.message || 'Erro ao trocar pontos. Tente novamente.', 'error');
              btn.disabled = false;
              btn.textContent = textoOriginal;
            }
          });
        });
      }, 500); // Timeout para garantir que o DOM está pronto
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
      let dashboardData = {};
      let linkedCompanies = [];
      const perfil = auth.normalizePerfil(user?.perfil || user?.role || user?.tipo);

      if (perfil === 'cliente') {
        try {
          const [resp, dashboardResp] = await Promise.all([
            api.request('/pontos/meus-dados', {}, { notify: false }),
            api.request('/cliente/dashboard', {}, { notify: false }),
          ]);
          dados = resp.data?.data || {};
          dashboardData = dashboardResp.data?.data || {};
          linkedCompanies = toArray(
            dashboardData?.empresas_vinculadas
            || dashboardData?.empresas
            || dashboardData?.linked_companies
          );
        } catch (_) {}
      } else if (perfil === 'empresa') {
        try {
          const resp = await api.request('/empresa/perfil', {}, { notify: false });
          empresaData = resp.data?.data || null;
        } catch (_) {}

        // Fallback: dados fictícios se API não retornar dados da empresa
        if (!empresaData || !empresaData.empresa) {
          empresaData = {
            empresa: {
              id: 1,
              nome: 'Empresa Demonstração',
              ramo: 'varejo',
              categoria: 'Comércio',
              cnpj: '00.000.000/0001-00',
              endereco: 'Rua Exemplo, 123 - Centro',
              telefone: '(11) 98765-4321',
              logo: '',
              points_multiplier: 1.5,
              ativo: true
            },
            total_clientes: 245,
            pontos_distribuidos: 15800,
            promocoes_ativas: 3
          };
        }
      }
      ui.clearPageState();

      const headerGreeting = document.getElementById('profileHeaderGreeting');
      const heroName = document.getElementById('hero-name');
      const heroLevel = document.getElementById('hero-level');
      const heroStatus = document.getElementById('hero-status');
      const heroPoints = document.getElementById('hero-points');
      const heroMetricLabel = document.getElementById('heroMetricLabel');
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

      const safeProfileName = safeText(user?.name || user?.nome, 'Usuario');
      if (headerGreeting) headerGreeting.textContent = `Ola, ${safeProfileName}`;
      if (heroName) heroName.textContent = safeProfileName;
      if (heroLevel) heroLevel.textContent = perfil ? perfil.toUpperCase() : 'MEMBRO';
      if (heroStatus) heroStatus.textContent = user?.status || 'Ativo';
      if (heroPoints) heroPoints.textContent = pontos;
      if (heroMetricLabel) heroMetricLabel.textContent = 'pontos';
      if (heroProgressText) heroProgressText.textContent = `Faltam ${nextTarget - pontos} para o proximo nivel`;
      if (heroProgressBar) heroProgressBar.style.width = `${perc}%`;

      if (perfil === 'empresa') {
        const totalClientes = Number(empresaData?.total_clientes || 0);
        const promocoesAtivas = Number(empresaData?.promocoes_ativas || 0);
        if (heroPoints) heroPoints.textContent = totalClientes;
        if (heroMetricLabel) heroMetricLabel.textContent = 'clientes';
        if (heroProgressText) heroProgressText.textContent = `${promocoesAtivas} promocao(oes) ativa(s)`;
        if (heroProgressBar) heroProgressBar.style.width = `${Math.max(8, Math.min(100, promocoesAtivas * 25))}%`;
      } else if (perfil === 'admin') {
        if (heroMetricLabel) heroMetricLabel.textContent = 'acessos';
        if (heroProgressText) heroProgressText.textContent = 'Painel administrativo e governanca';
        if (heroProgressBar) heroProgressBar.style.width = '72%';
      }

      const pf = (id) => document.getElementById(id);
      Array.from(document.querySelectorAll('[data-profile-client-only="true"]')).forEach((block) => {
        block.classList.toggle('hidden', perfil !== 'cliente');
      });

      const linkedSection = document.getElementById('profileLinkedCompaniesSection');
      const linkedCount = document.getElementById('profileCompanyCount');
      const linkedList = document.getElementById('profileLinkedCompaniesList');
      const linkedEmpty = document.getElementById('profileLinkedCompaniesEmpty');
      if (perfil === 'cliente' && linkedSection && linkedList && linkedEmpty) {
        const renderLinkedCompanyCard = (company) => {
          const companyId = Number(company?.id || company?.empresa_id || 0);
          const logo = safeImage(company?.logo, IMAGE_FALLBACKS.store);
          const category = safeText(company?.categoria || company?.ramo, 'Empresa');
          const rating = Number(company?.avaliacao_media || 0);
          const ratingLabel = rating > 0 ? `${rating.toFixed(1).replace('.', ',')} / 5` : 'Novo parceiro';
          const linkedAt = formatDatePtBr(company?.data_vinculo || company?.data_inscricao, 'Vinculo recente');

          return `
            <a href="/detalhe_do_parceiro.html?id=${encodeURIComponent(companyId)}" class="profile-linked-company-card">
              <img src="${logo}" alt="${safeText(company?.nome, 'Empresa')}" class="h-16 w-16 rounded-[22px] bg-slate-100 object-cover" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-[#B01774]">${category}</p>
                    <h4 class="mt-1 text-base font-extrabold leading-tight text-[#111B3F]">${safeText(company?.nome, 'Empresa')}</h4>
                  </div>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">${ratingLabel}</span>
                </div>
                <p class="mt-3 text-sm text-slate-500">Vinculada em ${linkedAt}</p>
              </div>
            </a>
          `;
        };

        if (linkedCount) {
          linkedCount.textContent = linkedCompanies.length
            ? `${linkedCompanies.length} empresa(s)`
            : 'Nenhum vinculo';
        }
        linkedList.innerHTML = linkedCompanies.map(renderLinkedCompanyCard).join('');
        linkedEmpty.classList.toggle('hidden', linkedCompanies.length > 0);
      } else if (linkedSection) {
        linkedSection.classList.add('hidden');
      }

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
            const container = document.getElementById('profileReferralHost') || document.querySelector('main') || document.body;
            const refSection = document.createElement('div');
            refSection.id = 'referralSection';
            refSection.className = 'profile-surface-card profile-referral-card';
            refSection.innerHTML = `
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Indicacao</p>
                  <h3 class="mt-2 font-headline text-2xl font-extrabold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">group_add</span>
                    Indique e ganhe
                  </h3>
                  <p class="mt-2 text-sm leading-6 text-on-surface-variant">Compartilhe seu codigo e ganhe <strong>50 pontos</strong> por cada amigo que se cadastrar.</p>
                </div>
                <span class="rounded-full bg-surface-container px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Demo</span>
              </div>
              <div class="mt-4 flex items-center gap-2">
                <span id="refCodeDisplay" class="flex-1 rounded-[18px] bg-surface-container p-3 text-center text-lg font-bold tracking-widest text-primary">${rd.referral_code}</span>
                <button id="copyRefCode" class="p-2 bg-primary text-white rounded-xl" title="Copiar código">
                  <span class="material-symbols-outlined text-base">content_copy</span>
                </button>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-[18px] bg-surface-container p-3 text-center">
                  <p class="text-2xl font-bold text-primary">${rd.total_indicados || 0}</p>
                  <p class="text-xs text-on-surface-variant">Amigos indicados</p>
                </div>
                <div class="rounded-[18px] bg-surface-container p-3 text-center">
                  <p class="text-2xl font-bold text-tertiary">${rd.pontos_ganhos || 0}</p>
                  <p class="text-xs text-on-surface-variant">Pontos ganhos</p>
                </div>
              </div>
              <button id="shareRefLink" class="mt-4 flex h-12 w-full items-center justify-center gap-2 rounded-full bg-primary text-sm font-semibold text-white">
                <span class="material-symbols-outlined text-base">share</span>
                Compartilhar meu link
              </button>`;
            if (container.id === 'profileReferralHost') {
              container.innerHTML = '';
              container.appendChild(refSection);
            } else {
              const lastCard = container.querySelector('section:last-child') || container.lastElementChild;
              container.insertBefore(refSection, lastCard);
            }

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
      const mode = new URLSearchParams(window.location.search).get('modo') || (perfil === 'empresa' ? 'validacao-cliente' : 'vinculo-empresa');
      const customerCompanyLinkMode = perfil === 'cliente' && mode === 'vinculo-empresa';
      const companyBenefitMode = perfil === 'empresa' && ['bonus-adesao', 'fidelidade', 'aniversario', 'beneficios'].includes(mode);
      const companyBonusMode = perfil === 'empresa' && mode === 'bonus-adesao';

      const titleEl = document.querySelector('main header h1');
      const copyEl = document.getElementById('scanInstructions');
      const buttonLabel = document.getElementById('usarCupomBtn');
      const manualInput = document.getElementById('cupomId');
      const input = document.getElementById('cupomId');
      const btn = document.getElementById('usarCupomBtn');
      const list = document.getElementById('validacoesRecentes');
      const empty = document.getElementById('validacoesEmpty');
      const bonusPanel = document.getElementById('bonusValidationPanel');
      const bonusActionBtn = document.getElementById('bonusValidationAction');
      const birthdayValidationSection = document.getElementById('birthdayValidationSection');
      const birthdayValidationTitle = document.getElementById('birthdayValidationTitle');
      const birthdayValidationStatus = document.getElementById('birthdayValidationStatus');
      const birthdayValidationDescription = document.getElementById('birthdayValidationDescription');
      const birthdayValidationWindow = document.getElementById('birthdayValidationWindow');
      const birthdayValidationAction = document.getElementById('birthdayValidationAction');
      const loyaltyAddPointBtn = document.getElementById('loyaltyValidationAddPoint');
      const loyaltyRedeemBtn = document.getElementById('loyaltyValidationRedeem');
      const promotionValidationSection = document.getElementById('promotionValidationSection');
      const promotionValidationTitle = document.getElementById('promotionValidationTitle');
      const promotionValidationStatus = document.getElementById('promotionValidationStatus');
      const promotionValidationDescription = document.getElementById('promotionValidationDescription');
      const promotionValidationList = document.getElementById('promotionValidationList');

      if (!input || !btn) return;

      if (titleEl) {
        titleEl.textContent = perfil === 'empresa'
          ? (companyBenefitMode ? 'Consultar cliente e validar beneficios' : 'Ler QR do Cliente')
          : 'Ler QR da Empresa';
      }
      if (copyEl) {
        copyEl.textContent = perfil === 'empresa'
          ? (companyBenefitMode
              ? 'Empresa: consulte o cliente pelo QR Code e valide bonus de adesao, bonus aniversario, pontos e resgates somente no estabelecimento.'
              : 'Empresa: escaneie o QR do cliente para validar acoes futuras e registrar atendimento.')
          : 'Cliente: escaneie o QR do adesivo da empresa para se vincular no app.';
      }
      if (buttonLabel) {
        buttonLabel.innerHTML = perfil === 'empresa'
          ? (companyBenefitMode
              ? '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">qr_code_scanner</span> Consultar Cliente'
              : '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">verified</span> Validar Agora')
          : '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">link</span> Vincular Agora';
      }
      if (manualInput && customerCompanyLinkMode) {
        manualInput.placeholder = 'Cole o codigo do QR da empresa...';
      }
      if (manualInput && companyBenefitMode) {
        manualInput.placeholder = 'Cole o QR dinamico do cliente...';
      }
      if (bonusPanel && !companyBenefitMode) {
        bonusPanel.classList.add('hidden');
      }

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
          if (res.ok && qrList.length && qrList[0].code) {
            const qr = qrList[0];
            container.innerHTML = `
              <p class="text-[11px] text-outline mb-1">Use este QR no adesivo fisico para abrir o fluxo publico de vinculo da empresa.</p>
              <img src="${qr.qr_image || qr.qr_url}" alt="QR Code da loja" class="w-44 h-44 rounded-xl border border-outline-variant/40 bg-white p-2" loading="lazy" />
              <div class="bg-surface-container px-4 py-2 rounded-xl text-center">
                <span class="text-xs font-mono text-on-surface break-all">${qr.code}</span>
              </div>
              <div class="flex flex-wrap items-center justify-center gap-2">
                <button id="copiarQrLoja" class="px-3 py-1.5 rounded-lg bg-surface-container text-xs font-semibold text-on-surface">Copiar codigo</button>
                <button id="copiarLinkQrLoja" class="px-3 py-1.5 rounded-lg bg-surface-container text-xs font-semibold text-on-surface">Copiar link</button>
              </div>
              <p class="text-[10px] text-outline mt-1">Scans: ${qr.usage_count || 0} | Ativo: ${qr.active ? 'Sim' : 'Nao'}</p>`;
            document.getElementById('copiarQrLoja')?.addEventListener('click', () => {
              navigator.clipboard.writeText(qr.code).then(() => ui.message('Codigo da loja copiado.', 'success'));
            });
            document.getElementById('copiarLinkQrLoja')?.addEventListener('click', () => {
              navigator.clipboard.writeText(qr.scan_url || '').then(() => ui.message('Link publico do QR copiado.', 'success'));
            });
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

      if (perfil === 'cliente') {
        const main = document.querySelector('main') || document.querySelector('.main-content') || document.body;
        const qrHost = document.getElementById('clienteQRSectionHost');
        const myQrSection = document.createElement('div');
        myQrSection.id = 'clienteQRSection';
        myQrSection.className = 'profile-surface-card profile-qr-card';
        myQrSection.innerHTML = `
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Meu QR Code</p>
              <h3 class="mt-2 font-headline text-2xl font-extrabold text-on-surface">Apresente no estabelecimento</h3>
              <p class="mt-2 text-sm leading-6 text-on-surface-variant">Mostre este QR para o parceiro escanear e validar bonus, fidelidade, promocao ou aniversario.</p>
            </div>
            <a href="/validar_resgate.html?modo=vinculo-empresa" class="inline-flex h-11 items-center justify-center rounded-full bg-surface-container px-4 text-xs font-bold uppercase tracking-[0.14em] text-on-surface">Ler QR da empresa</a>
          </div>
          <div id="clienteQRContainer" class="flex flex-col items-center gap-2 min-h-[80px] justify-center">
            <p class="text-sm text-outline">Carregando...</p>
          </div>
        `;
        if (qrHost) {
          qrHost.innerHTML = '';
          qrHost.appendChild(myQrSection);
        } else {
          main.prepend(myQrSection);
        }

        const qrContainer = document.getElementById('clienteQRContainer');
        const { res, data } = await api.request('/cliente/meu-qrcode', {}, { notify: false });
        const payload = data?.data || {};
        if (res.ok && payload?.codigo && payload?.qrcode_svg) {
          const expiraEm = payload.expira_em ? new Date(payload.expira_em).toLocaleTimeString('pt-BR') : '--';
          qrContainer.innerHTML = `
            <div class="w-44 h-44 bg-white rounded-xl border border-outline-variant/40 p-2 flex items-center justify-center overflow-hidden">
              ${payload.qrcode_svg}
            </div>
            <div class="bg-surface-container px-4 py-2 rounded-xl text-center w-full">
              <span class="text-xs font-mono text-on-surface break-all">${payload.codigo}</span>
            </div>
            <button id="copiarMeuQr" class="px-3 py-1.5 rounded-lg bg-surface-container text-xs font-semibold text-on-surface">Copiar codigo</button>
            <p class="text-[10px] text-outline mt-1">Expira as ${expiraEm}</p>
          `;
          document.getElementById('copiarMeuQr')?.addEventListener('click', () => {
            navigator.clipboard.writeText(payload.codigo).then(() => ui.message('Codigo do seu QR copiado.', 'success'));
          });
        } else {
          qrContainer.innerHTML = '<p class="text-sm text-outline">Nao foi possivel carregar seu QR agora.</p>';
        }
      }

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
      let latestCompanyLookup = null;

      const renderBonusLookup = (lookup) => {
        if (!bonusPanel || !companyBenefitMode) return;
        latestCompanyLookup = {
          ...(latestCompanyLookup || {}),
          ...(lookup || {}),
          cliente: lookup?.cliente || latestCompanyLookup?.cliente || {},
          empresa: lookup?.empresa || latestCompanyLookup?.empresa || {},
          vinculo: lookup?.vinculo || latestCompanyLookup?.vinculo || {},
          bonus_adesao: lookup?.bonus_adesao || latestCompanyLookup?.bonus_adesao || {},
          bonus_aniversario: lookup?.bonus_aniversario || latestCompanyLookup?.bonus_aniversario || {},
          cartao_fidelidade: lookup?.cartao_fidelidade || latestCompanyLookup?.cartao_fidelidade || {},
          promocoes: lookup?.promocoes || latestCompanyLookup?.promocoes || {},
        };

        const cliente = latestCompanyLookup?.cliente || {};
        const vinculo = latestCompanyLookup?.vinculo || {};
        const bonus = latestCompanyLookup?.bonus_adesao || {};
        const bonusInfo = bonus?.bonus || {};
        const meta = bonusStatusMeta(bonus.status);
        const birthday = latestCompanyLookup?.bonus_aniversario || {};
        const birthdayInfo = birthday?.bonus || {};
        const birthdayMeta = birthdayBonusStatusMeta(birthday?.status);
        const loyalty = latestCompanyLookup?.cartao_fidelidade || {};
        const loyaltyCard = loyalty?.card || {};
        const loyaltyProgress = loyalty?.progress || {};
        const loyaltyMeta = loyaltyStatusMeta(loyalty?.status);
        const loyaltyHistory = Array.isArray(loyalty?.history_summary) ? loyalty.history_summary : [];
        const promotions = latestCompanyLookup?.promocoes || {};
        const promotionItems = Array.isArray(promotions?.items) ? promotions.items : [];

        bonusPanel.classList.remove('hidden');
        document.getElementById('bonusValidationClientName').textContent = cliente.nome || 'Cliente';
        document.getElementById('bonusValidationClientPhone').textContent = cliente.telefone || 'Nao informado';
        document.getElementById('bonusValidationClientBirthdate').textContent = formatDatePtBr(cliente.data_nascimento, 'Nao informada');
        document.getElementById('bonusValidationCompany').textContent = latestCompanyLookup?.empresa?.nome || 'Nao identificada';
        document.getElementById('bonusValidationLinkStatus').textContent = vinculo.existe
          ? `Cliente vinculado desde ${formatDatePtBr(vinculo.data_inscricao, 'data nao informada')}`
          : 'Cliente ainda nao vinculado a esta empresa';
        document.getElementById('bonusValidationBonusStatus').textContent = meta.label;
        document.getElementById('bonusValidationBonusStatus').className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}`;
        document.getElementById('bonusValidationBonusTitle').textContent = bonusInfo.titulo || 'Bonus de adesao';
        document.getElementById('bonusValidationBonusDescription').textContent = bonusInfo.descricao || meta.message;
        document.getElementById('bonusValidationBonusExpiry').textContent = formatDatePtBr(bonusInfo.data_expiracao, 'Nao informada');
        document.getElementById('bonusValidationMessage').textContent = bonus.message || loyalty.message || meta.message;

        if (birthdayValidationSection && birthdayValidationStatus && birthdayValidationDescription) {
          birthdayValidationSection.classList.remove('hidden');
          if (birthdayValidationTitle) {
            birthdayValidationTitle.textContent = birthdayInfo.titulo || 'Nenhum bonus aniversario configurado';
          }
          birthdayValidationStatus.textContent = birthdayMeta.label;
          birthdayValidationStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${birthdayMeta.badgeClass}`;
          birthdayValidationDescription.textContent = birthdayInfo.descricao || birthday.message || birthdayMeta.message;
          if (birthdayValidationWindow) {
            birthdayValidationWindow.textContent = birthdayInfo.validade_descricao
              || formatDateRangePtBr(birthday?.valid_from, birthday?.valid_until, 'Validade nao informada');
          }
        }

        document.getElementById('loyaltyValidationTitle').textContent = loyaltyCard.titulo || 'Nenhum cartao configurado';
        document.getElementById('loyaltyValidationStatus').textContent = loyaltyMeta.label;
        document.getElementById('loyaltyValidationStatus').className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${loyaltyMeta.badgeClass}`;
        document.getElementById('loyaltyValidationDescription').textContent = loyaltyCard.descricao || loyaltyMeta.message;
        document.getElementById('loyaltyValidationRule').textContent = loyaltyCard.regra_ganho || 'Ganhe pontos por visita.';
        document.getElementById('loyaltyValidationReward').textContent = loyaltyCard.recompensa_descricao || 'Ainda nao informada';
        document.getElementById('loyaltyValidationPoints').textContent = String(loyaltyProgress.current_points || 0);
        document.getElementById('loyaltyValidationTarget').textContent = `${Number(loyaltyProgress.required_points || loyaltyCard.pontos_necessarios || 0)} pontos`;
        document.getElementById('loyaltyValidationProgressLabel').textContent = loyaltyProgress.progress_label || `0 de ${Number(loyaltyCard.pontos_necessarios || 0)} pontos`;
        document.getElementById('loyaltyValidationProgressState').textContent = loyaltyProgress.reward_available
          ? 'Recompensa liberada'
          : (loyalty.status === 'not_linked' ? 'Exige vinculo' : 'Em andamento');
        document.getElementById('loyaltyValidationProgressBar').style.width = `${Math.max(0, Math.min(100, Number(loyaltyProgress.percentage || 0)))}%`;

        const historyHost = document.getElementById('loyaltyValidationHistory');
        if (historyHost) {
          if (!loyaltyHistory.length) {
            historyHost.innerHTML = '<p>Nenhuma movimentacao registrada ainda.</p>';
          } else {
            historyHost.innerHTML = loyaltyHistory.map((item) => `
              <div class="rounded-lg bg-white p-3 ring-1 ring-black/5">
                <div class="flex items-center justify-between gap-3">
                  <span class="text-xs font-bold uppercase tracking-[0.12em] ${item.tipo === 'redeemed' ? 'text-rose-600' : 'text-[#111B3F]'}">${item.tipo === 'redeemed' ? 'Resgate' : 'Ponto'}</span>
                  <span class="text-[11px] font-semibold text-on-surface-variant">${formatDatePtBr(item.created_at, 'Agora')}</span>
                </div>
                <p class="mt-1 text-sm font-semibold text-on-surface">${item.descricao || 'Movimentacao registrada.'}</p>
              </div>
            `).join('');
          }
        }

        if (promotionValidationSection && promotionValidationStatus && promotionValidationDescription && promotionValidationList) {
          promotionValidationSection.classList.remove('hidden');
          const promotionSummaryStatus = promotionItems.some((item) => item.viewer_status === 'available')
            ? 'available'
            : promotionItems.some((item) => item.viewer_status === 'redeemed')
              ? 'redeemed'
              : promotionItems.some((item) => item.viewer_status === 'not_linked')
                ? 'not_linked'
                : (promotions?.status || 'inactive');
          const promotionMeta = promotionStatusMeta(promotionSummaryStatus);

          if (promotionValidationTitle) {
            promotionValidationTitle.textContent = promotionItems.length
              ? `${promotionItems.length} promocao(oes) consultada(s)`
              : 'Nenhuma promocao elegivel';
          }
          promotionValidationStatus.textContent = promotionMeta.label;
          promotionValidationStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${promotionMeta.badgeClass}`;
          promotionValidationDescription.textContent = promotions?.message || promotionMeta.message;

          if (!promotionItems.length) {
            promotionValidationList.innerHTML = '<p>Nenhuma promocao elegivel para este cliente no momento.</p>';
          } else {
            promotionValidationList.innerHTML = promotionItems.map((promo) => {
              const metaPromo = promotionStatusMeta(promo.viewer_status || promo.status);
              const canValidatePromo = Boolean(promo.viewer_status === 'available' && promo.id && cliente.id);

              return `
                <div class="rounded-xl bg-white p-4 ring-1 ring-black/5">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="text-sm font-bold text-on-surface">${safeText(promo.titulo, 'Promocao instantanea')}</p>
                      <p class="mt-1 text-xs leading-5 text-on-surface-variant">${safeText(promo.descricao, metaPromo.message)}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${metaPromo.badgeClass}">${metaPromo.label}</span>
                  </div>
                  <div class="mt-3 flex items-center justify-between gap-3">
                    <p class="text-[11px] font-semibold text-on-surface-variant">Validade: ${formatDatePtBr(promo.data_expiracao || promo.validade, 'Nao informada')}</p>
                    <button type="button" data-action="validar-promocao" data-promocao-id="${promo.id}"
                      class="rounded-[1rem] px-3 py-2 text-xs font-bold ${canValidatePromo ? 'bg-gradient-to-r from-[#00BCD4] via-[#7A2C8F] to-[#E10098] text-white shadow-sm' : 'bg-surface-container text-on-surface-variant'}"
                      ${canValidatePromo ? '' : 'disabled'}>
                      ${canValidatePromo ? 'Validar promocao' : 'Sem acao'}
                    </button>
                  </div>
                </div>
              `;
            }).join('');

            promotionValidationList.querySelectorAll('[data-action="validar-promocao"]').forEach((button) => {
              button.addEventListener('click', async () => {
                const promotionId = Number(button.getAttribute('data-promocao-id') || 0);
                if (!promotionId || !cliente.id) return;

                button.disabled = true;
                button.classList.add('opacity-60');
                const { res, data } = await api.request(`/empresa/promocoes/${promotionId}/validar`, {
                  method: 'POST',
                  body: JSON.stringify({ cliente_id: cliente.id }),
                });
                button.disabled = false;
                button.classList.remove('opacity-60');

                if (res.ok && data?.success) {
                  ui.message(data?.message || 'Promocao validada com sucesso.', 'success');
                  renderBonusLookup({
                    ...(latestCompanyLookup || {}),
                    ...(data.data || {}),
                    promocoes: data?.data?.promocoes || latestCompanyLookup?.promocoes,
                  });
                  const validatedPromotion = promotionItems.find((item) => Number(item?.id || 0) === promotionId);
                  pushItem({
                    cliente: cliente.nome || 'Cliente',
                    beneficio: validatedPromotion?.titulo || 'Promocao instantanea',
                    hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                  });
                } else {
                  ui.message(data?.message || 'Nao foi possivel validar a promocao.', 'error');
                }
              });
            });
          }
        }

        if (bonusActionBtn) {
          const canValidate = Boolean(bonus.can_validate && bonusInfo.id && cliente.id);
          if (!canValidate) {
            bonusActionBtn.classList.add('hidden');
            bonusActionBtn.onclick = null;
          } else {
            bonusActionBtn.classList.remove('hidden');
            bonusActionBtn.textContent = 'Validar bonus';
            bonusActionBtn.onclick = async () => {
              bonusActionBtn.disabled = true;
              bonusActionBtn.classList.add('opacity-60');
              const { res, data } = await api.request(`/empresa/bonus-adesao/${bonusInfo.id}/validar`, {
                method: 'POST',
                body: JSON.stringify({ cliente_id: cliente.id }),
              });
              bonusActionBtn.disabled = false;
              bonusActionBtn.classList.remove('opacity-60');

              if (res.ok && data?.success) {
                ui.message(data?.message || 'Bonus validado com sucesso.', 'success');
                renderBonusLookup(data.data || {});
                pushItem({
                  cliente: cliente.nome || 'Cliente',
                  beneficio: bonusInfo.titulo || 'Bonus de adesao',
                  hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                });
              } else {
                ui.message(data?.message || 'Nao foi possivel validar o bonus.', 'error');
              }
            };
          }
        }

        if (birthdayValidationAction) {
          const canValidateBirthday = Boolean(birthday.can_validate && birthdayInfo.id && cliente.id);
          if (!canValidateBirthday) {
            birthdayValidationAction.classList.add('hidden');
            birthdayValidationAction.onclick = null;
          } else {
            birthdayValidationAction.classList.remove('hidden');
            birthdayValidationAction.textContent = 'Validar bonus aniversario';
            birthdayValidationAction.onclick = async () => {
              birthdayValidationAction.disabled = true;
              birthdayValidationAction.classList.add('opacity-60');
              const { res, data } = await api.request(`/empresa/bonus-aniversario/${birthdayInfo.id}/validar`, {
                method: 'POST',
                body: JSON.stringify({ cliente_id: cliente.id }),
              });
              birthdayValidationAction.disabled = false;
              birthdayValidationAction.classList.remove('opacity-60');

              if (res.ok && data?.success) {
                ui.message(data?.message || 'Bonus aniversario validado com sucesso.', 'success');
                renderBonusLookup({
                  ...(latestCompanyLookup || {}),
                  bonus_aniversario: data?.data?.bonus_aniversario || latestCompanyLookup?.bonus_aniversario,
                });
                pushItem({
                  cliente: cliente.nome || 'Cliente',
                  beneficio: birthdayInfo.titulo || 'Bonus aniversario',
                  hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                });
              } else {
                ui.message(data?.message || 'Nao foi possivel validar o bonus aniversario.', 'error');
              }
            };
          }
        }

        if (loyaltyAddPointBtn) {
          const canAddPoint = Boolean(loyalty.can_add_point && loyaltyCard.id && cliente.id);
          if (!canAddPoint) {
            loyaltyAddPointBtn.classList.add('hidden');
            loyaltyAddPointBtn.onclick = null;
          } else {
            loyaltyAddPointBtn.classList.remove('hidden');
            loyaltyAddPointBtn.onclick = async () => {
              loyaltyAddPointBtn.disabled = true;
              loyaltyAddPointBtn.classList.add('opacity-60');
              const { res, data } = await api.request(`/empresa/cartao-fidelidade/${loyaltyCard.id}/clientes/${cliente.id}/adicionar-ponto`, {
                method: 'POST',
                body: JSON.stringify({}),
              });
              loyaltyAddPointBtn.disabled = false;
              loyaltyAddPointBtn.classList.remove('opacity-60');

              if (res.ok && data?.success) {
                ui.message(data?.message || 'Ponto registrado com sucesso.', 'success');
                renderBonusLookup({
                  ...(latestCompanyLookup || {}),
                  ...(data.data || {}),
                  cartao_fidelidade: data?.data?.cartao_fidelidade || latestCompanyLookup?.cartao_fidelidade,
                });
                pushItem({
                  cliente: cliente.nome || 'Cliente',
                  beneficio: `+${loyaltyCard.pontos_por_visita || loyaltyProgress.points_per_visit || 1} ponto(s)`,
                  hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                });
              } else {
                ui.message(data?.message || 'Nao foi possivel adicionar ponto.', 'error');
              }
            };
          }
        }

        if (loyaltyRedeemBtn) {
          const canRedeem = Boolean(loyalty.can_redeem && loyaltyCard.id && cliente.id);
          if (!canRedeem) {
            loyaltyRedeemBtn.classList.add('hidden');
            loyaltyRedeemBtn.onclick = null;
          } else {
            loyaltyRedeemBtn.classList.remove('hidden');
            loyaltyRedeemBtn.onclick = async () => {
              loyaltyRedeemBtn.disabled = true;
              loyaltyRedeemBtn.classList.add('opacity-60');
              const { res, data } = await api.request(`/empresa/cartao-fidelidade/${loyaltyCard.id}/clientes/${cliente.id}/resgatar`, {
                method: 'POST',
                body: JSON.stringify({}),
              });
              loyaltyRedeemBtn.disabled = false;
              loyaltyRedeemBtn.classList.remove('opacity-60');

              if (res.ok && data?.success) {
                ui.message(data?.message || 'Recompensa validada com sucesso.', 'success');
                renderBonusLookup({
                  ...(latestCompanyLookup || {}),
                  ...(data.data || {}),
                  cartao_fidelidade: data?.data?.cartao_fidelidade || latestCompanyLookup?.cartao_fidelidade,
                });
                pushItem({
                  cliente: cliente.nome || 'Cliente',
                  beneficio: loyaltyCard.recompensa_descricao || 'Recompensa fidelidade',
                  hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                });
              } else {
                ui.message(data?.message || 'Nao foi possivel resgatar a recompensa.', 'error');
              }
            };
          }
        }
      };

      if (companyBenefitMode) {
        window.addEventListener('empresa-bonus-lookup', (event) => {
          renderBonusLookup(event.detail || {});
        });
      }

      btn.addEventListener('click', async () => {
        const codigo = input.value.trim();
        if (!codigo) {
          return ui.message(
            perfil === 'empresa'
              ? (companyBenefitMode ? 'Informe o QR do cliente.' : 'Informe o codigo do cupom.')
              : 'Informe o codigo do QR da empresa.',
            'warning'
          );
        }

        btn.disabled = true;
        btn.classList.add('opacity-60');

        let response;
        if (perfil === 'empresa' && companyBenefitMode) {
          response = await api.request('/empresa/clientes/qrcode/consultar', {
            method: 'POST',
            body: JSON.stringify({ qrcode: codigo }),
          });
        } else if (perfil === 'empresa') {
          response = await api.request(`/pontos/usar-cupom/${encodeURIComponent(codigo)}`, { method: 'POST' });
        } else {
          response = await api.request('/cliente/vincular-empresa-qrcode', {
            method: 'POST',
            body: JSON.stringify({ code: codigo }),
          });
        }

        const { res, data } = response;
        btn.disabled = false;
        btn.classList.remove('opacity-60');

        if (res.ok && data?.success) {
          if (perfil === 'empresa' && companyBenefitMode) {
            renderBonusLookup(data.data || {});
            ui.message(data?.message || 'Cliente consultado com sucesso.', 'success');
            return;
          }

          if (perfil === 'empresa') {
            ui.message('Cupom validado/uso registrado.', 'success');
            const info = data.data || {};
            pushItem({
              cliente: info.cliente_nome || info.cliente || 'Cliente',
              beneficio: info.promocao || info.recompensa || info.cupom || 'Cupom',
              codigo,
              hora: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
            });
            return;
          }

          clearPendingCompanyQr();
          ui.message(data?.message || 'Empresa vinculada com sucesso.', 'success');
          const target = `${data?.data?.public_page_url || `/detalhe_do_parceiro.html?id=${encodeURIComponent(data?.data?.empresa?.id || '')}`}&linked=1`.replace('?&', '?');
          setTimeout(() => {
            window.location.href = target.includes('?') ? target : `${target}?linked=1`;
          }, 500);
          return;
        }

        ui.message(
          data?.message || (
            perfil === 'empresa'
              ? (companyBenefitMode ? 'Nao foi possivel consultar este cliente.' : 'Nao foi possivel usar o cupom.')
              : 'Nao foi possivel vincular esta empresa.'
          ),
          'error'
        );
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
      const currentUser = await auth.ensure();
      const [promos, resumo, qrcodes] = await Promise.all([
        api.request('/empresa/promocoes'),
        api.request('/empresa/relatorios/resumo', {}, { notify: false }),
        api.request('/empresa/qrcodes', {}, { notify: false }),
      ]);

      const kpiVolume = document.getElementById('kpiVolume');
      const kpiClientes = document.getElementById('kpiClientes');
      const kpiResgates = document.getElementById('kpiResgates');
      const kpiVolumeDesc = document.getElementById('kpiVolumeDesc');
      const kpiVolumeTrend = document.getElementById('kpiVolumeTrend');
      const campanhasBox = document.getElementById('campanhasAtivas');
      const campanhasEmpty = document.getElementById('campanhasEmpty');
      const movDistribuido = document.getElementById('movDistribuido');
      const movResgatado = document.getElementById('movResgatado');
      const movClientes = document.getElementById('movClientes');
      const movMsg = document.getElementById('movMsg');
      const recentClientsBox = document.getElementById('empresaRecentClients');
      const recentClientsEmpty = document.getElementById('empresaRecentClientsEmpty');
      const latestRedemptionsBox = document.getElementById('empresaLatestRedemptions');
      const latestRedemptionsEmpty = document.getElementById('empresaLatestRedemptionsEmpty');
      const heroName = document.getElementById('empresaHeroName');
      const heroSubtitle = document.getElementById('empresaHeroSubtitle');
      const heroMeta = document.getElementById('empresaHeroMeta');
      const heroLogo = document.getElementById('empresaHeroLogo');
      const qrContainer = document.getElementById('empresaDashboardQrContainer');
      const publicLinkBtn = document.getElementById('empresaDashboardPublicLinkBtn');
      ui.clearPageState();

      const resumoData = resumo.data?.data || {};
      const cards = resumoData.cards || {};
      const qrList = toArray(qrcodes.data?.data || qrcodes.data);
      const qrPayload = qrList[0] || {};
      const empresaInfo = qrPayload.empresa || {};
      const totalClientes = Number(cards.total_clientes_vinculados || 0);
      const aniversariantes = Number(cards.clientes_aniversariantes_mes || 0);
      const totalAvaliacoes = Number(cards.total_avaliacoes || 0);
      const mediaAvaliacao = Number(cards.media_avaliacao || 0);
      const notificacoes = Number(cards.total_notificacoes_enviadas || 0);
      const listaPromos = promos.data?.data || promos.data || [];

      if (heroName) heroName.textContent = safeText(empresaInfo?.nome, safeText(currentUser?.name, 'Sua empresa'));
      if (heroSubtitle) {
        heroSubtitle.textContent = qrPayload?.public_page_url
          ? 'Use o QR da empresa para divulgar a pagina publica e validar clientes presencialmente.'
          : 'Gerencie clientes, campanhas e resgates sem sair deste painel.';
      }
      if (heroMeta) {
        heroMeta.textContent = safeText(currentUser?.name)
          ? `Responsavel: ${safeText(currentUser.name)}`
          : 'Fluxo operacional completo para a equipe da loja';
      }
      if (heroLogo) {
        const heroImage = safeImage(listaPromos[0]?.imagem_url || listaPromos[0]?.imagem || empresaInfo?.logo, IMAGE_FALLBACKS.store);
        heroLogo.src = heroImage;
        heroLogo.onerror = () => {
          heroLogo.onerror = null;
          heroLogo.src = IMAGE_FALLBACKS.store;
        };
      }
      if (publicLinkBtn && qrPayload?.public_page_url) {
        publicLinkBtn.href = qrPayload.public_page_url;
      }
      if (qrContainer) {
        if (qrPayload?.code) {
          qrContainer.innerHTML = `
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
              <div class="mx-auto flex h-40 w-40 items-center justify-center rounded-[22px] bg-white p-3 shadow-[0_10px_24px_rgba(0,0,0,0.14)] ring-1 ring-white/10">
                ${qrPayload.qr_image?.startsWith('data:')
                  ? `<img src="${qrPayload.qr_image}" alt="QR Code da empresa" class="h-full w-full object-contain" />`
                  : `<img src="${safeImage(qrPayload.qr_url, IMAGE_FALLBACKS.store)}" alt="QR Code da empresa" class="h-full w-full object-contain" />`}
              </div>
              <div class="space-y-3 text-sm text-white/80">
                <p>Apresente este QR no adesivo da loja para abrir o fluxo publico correto no app do cliente.</p>
                <div class="rounded-[18px] bg-white/10 px-4 py-3 ring-1 ring-white/10">
                  <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/60">Codigo da empresa</p>
                  <p class="mt-2 break-all font-mono text-xs text-white">${safeText(qrPayload.code)}</p>
                </div>
              </div>
            </div>
          `;
        } else {
          qrContainer.textContent = 'Nenhum QR da empresa gerado ainda. Gere o QR e volte a esta tela.';
        }
      }

      if (kpiVolume) kpiVolume.textContent = Number(totalClientes).toLocaleString('pt-BR');
      if (kpiClientes) kpiClientes.textContent = Number(aniversariantes).toLocaleString('pt-BR');
      if (kpiResgates) kpiResgates.textContent = Number(totalAvaliacoes).toLocaleString('pt-BR');
      if (kpiVolumeDesc) kpiVolumeDesc.textContent = `${Number(cards.novos_clientes_mes || 0).toLocaleString('pt-BR')} novo(s) cliente(s) no mes atual`;
      if (kpiVolumeTrend) kpiVolumeTrend.textContent = notificacoes > 0
        ? `${notificacoes.toLocaleString('pt-BR')} notificacao(oes) ja enviadas para clientes vinculados`
        : 'Nenhuma notificacao enviada ainda';

      if (movDistribuido) movDistribuido.textContent = Number(cards.total_pontos_distribuidos || 0).toLocaleString('pt-BR');
      if (movResgatado) movResgatado.textContent = Number(cards.total_promocoes_resgatadas || 0).toLocaleString('pt-BR');
      if (movClientes) movClientes.textContent = Number(cards.clientes_inativos || 0).toLocaleString('pt-BR');
      if (movMsg) movMsg.textContent = totalAvaliacoes > 0
        ? `Media de avaliacao atual: ${mediaAvaliacao.toFixed(1).replace('.', ',')}`
        : 'Sem avaliacoes registradas ate o momento.';

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

      const recentClients = toArray(resumoData.clientes_recentes);
      if (recentClientsBox) {
        recentClientsBox.innerHTML = '';
        recentClientsEmpty?.classList.toggle('hidden', recentClients.length > 0);
        recentClients.slice(0, 5).forEach((cliente) => {
          const item = document.createElement('div');
          item.className = 'rounded-xl bg-surface-container-low p-3';
          item.innerHTML = `
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-bold text-on-surface">${safeText(cliente?.nome, 'Cliente')}</p>
                <p class="mt-1 text-xs text-on-surface-variant">${safeText(cliente?.email, 'Sem email')} • ${formatDatePtBr(cliente?.data_vinculo, 'Vinculo recente')}</p>
              </div>
              <span class="rounded-full bg-white px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-tertiary">${Number(cliente?.dias_inatividade || 0).toLocaleString('pt-BR')}d</span>
            </div>
          `;
          recentClientsBox.appendChild(item);
        });
      }

      const latestRedemptions = toArray(resumoData.ultimos_resgates);
      if (latestRedemptionsBox) {
        latestRedemptionsBox.innerHTML = '';
        latestRedemptionsEmpty?.classList.toggle('hidden', latestRedemptions.length > 0);
        latestRedemptions.slice(0, 5).forEach((evento) => {
          const item = document.createElement('div');
          item.className = 'rounded-xl bg-surface-container-low p-3';
          item.innerHTML = `
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-bold text-on-surface">${safeText(evento?.cliente_nome, 'Cliente')}</p>
                <p class="mt-1 text-xs text-on-surface-variant">${safeText(evento?.titulo, 'Beneficio validado')} • ${safeText(evento?.tipo, 'resgate')}</p>
              </div>
              <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-primary">${formatDatePtBr(evento?.data, 'Agora')}</span>
            </div>
          `;
          latestRedemptionsBox.appendChild(item);
        });
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
        const payload = data?.data || {};
        const lista = payload?.data || data?.data || data || [];
        const ativos = lista.filter((item) => item?.status_inatividade !== 'inactive').length;
        const inativos = lista.filter((item) => item?.status_inatividade === 'inactive').length;
        if (statTotal) statTotal.textContent = Number(payload?.total || lista.length || 0).toLocaleString('pt-BR');
        if (statAtivos) statAtivos.textContent = Number(ativos || 0).toLocaleString('pt-BR');
        if (statNovos) statNovos.textContent = Number(inativos || 0).toLocaleString('pt-BR');
        if (resumoEl) resumoEl.textContent = `Exibindo ${lista.length} cliente(s) • ${inativos} inativo(s) no filtro atual`;
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
          card.className = 'bg-surface-container-lowest rounded-xl p-4 transition-transform active:scale-[0.98] tap-highlight-transparent border border-surface-variant/30';
          const nome = c.name || c.nome || 'Cliente';
          const pontos = Number(c.pontos_atuais || c.total_ganho || c.pontos || 0);
          const ultima = c.ultima_visita || c.updated_at;
          const inativo = c.status_inatividade === 'inactive';
          const nascimento = formatDatePtBr(c.data_nascimento, 'Nao informado');
          const vinculo = formatDatePtBr(c.data_vinculo, 'Nao informado');
          card.innerHTML = `
            <div class="flex items-start gap-4">
              <div class="relative">
                <div class="w-14 h-14 rounded-full overflow-hidden bg-surface-container">
                  <img alt="${nome}" class="w-full h-full object-cover" src="${c.avatar || '/img/placeholder-user.png'}"/>
                </div>
              </div>
              <div class="flex-1">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <h3 class="font-headline font-bold text-on-surface">${nome}</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">${safeText(c.email, 'Sem email')} • ${safeText(c.telefone, 'Sem telefone')}</p>
                  </div>
                  <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${inativo ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}">${inativo ? 'Inativo' : 'Ativo'}</span>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Pontos atuais</p>
                    <p class="mt-1 text-sm font-bold text-primary">${pontos.toLocaleString('pt-BR')} pts</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Ultima visita</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${formatDatePtBr(ultima, 'Nao informada')}</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Nascimento</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${nascimento}</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Vinculo</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${vinculo}</p>
                  </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-on-surface-variant">
                  <span>${Number(c.total_promocoes_resgatadas || 0).toLocaleString('pt-BR')} promocao(oes)</span>
                  <span>${Number(c.total_recompensas_resgatadas || 0).toLocaleString('pt-BR')} recompensa(s)</span>
                  <span>${Number(c.dias_inatividade || 0).toLocaleString('pt-BR')} dia(s) sem visita</span>
                </div>
              </div>
            </div>
          `;
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
      const weeklyStatus = data?.meta?.weekly_limit || { limit: 2, used: 0, remaining: 2 };
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
        validade: document.getElementById('ofertaValidade'),
        preco: document.getElementById('ofertaPreco'),
        tipo: document.getElementById('ofertaTipo'),
        imagem: document.getElementById('ofertaImagem'),
        notificationTitle: document.getElementById('ofertaNotificationTitle'),
        notificationBody: document.getElementById('ofertaNotificationBody'),
        ativa: document.getElementById('ofertaAtiva'),
        salvar: document.getElementById('ofertaSalvar'),
        cancelar: document.getElementById('ofertaCancelar'),
        msg: document.getElementById('ofertaMsg'),
        weeklyInfo: document.getElementById('promoWeeklyInfo'),
      };
      let editingId = null;
      let filtroAtual = 'todas';

      if (form.weeklyInfo) {
        form.weeklyInfo.textContent = `Limite semanal: ${weeklyStatus.used}/${weeklyStatus.limit} envios utilizados | Restantes: ${weeklyStatus.remaining}`;
      }

      const setCounts = (arr) => {
        const stats = { todas: arr.length, ativas: 0, programadas: 0, inativas: 0 };
        arr.forEach((p) => {
          const st = String(p.status || (p.ativo ? 'available' : 'inactive')).toLowerCase();
          if (st === 'available') stats.ativas += 1;
          else if (p.enviada_em || p.data_envio) stats.programadas += 1;
          else stats.inativas += 1;
        });
        Object.entries(stats).forEach(([k, v]) => { if (counts[k]) counts[k].textContent = v; });
      };

      const renderCards = (arr) => {
        if (listaBox) listaBox.innerHTML = '';
        const filtrada = arr.filter((p) => {
          const st = String(p.status || (p.ativo ? 'available' : 'inactive')).toLowerCase();
          if (filtroAtual === 'todas') return true;
          if (filtroAtual === 'ativas') return st === 'available';
          if (filtroAtual === 'programadas') return Boolean(p.enviada_em || p.data_envio);
          return st !== 'available';
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
          const meta = promotionStatusMeta(p.status);
          const canSend = Boolean(p.ativo && p.status === 'available' && !p.enviada_em && weeklyStatus.remaining > 0);
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
                <p class="mt-1 text-[11px] text-on-surface-variant">Validade: ${formatDatePtBr(p.data_expiracao || p.validade, 'Nao informada')}</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Push: ${p.notification_title || p.titulo || 'Nao informado'}</p>
              </div>
              <div class="flex items-center justify-between mt-2">
                <div class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full ${p.status === 'available' ? 'bg-[#00C2D1]' : 'bg-outline'}"></span>
                  <span class="text-[10px] font-label font-bold uppercase ${meta.badgeClass} px-2 py-1 rounded-full">${meta.label}</span>
                </div>
                <div class="flex items-center gap-2 text-[10px] text-outline">
                  <button class="px-3 py-1 rounded-lg ${p.ativo ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'} text-xs" data-action="toggle">${p.ativo ? 'Pausar' : 'Ativar'}</button>
                  <button class="px-3 py-1 rounded-lg ${canSend ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant'} text-xs" data-action="enviar" ${canSend ? '' : 'disabled'}>${p.enviada_em ? 'Push enviado' : 'Enviar push'}</button>
                  <button class="px-3 py-1 rounded-lg bg-rose-600 text-white text-xs" data-action="deletar">Excluir</button>
                </div>
              </div>
            </div>`;
          card.querySelector('[data-action="editar"]')?.addEventListener('click', () => fillForm(p));
          card.querySelector('[data-action="toggle"]')?.addEventListener('click', async () => {
            const { res, data: resp } = await api.request(`/empresa/promocoes/${p.id}/toggle`, {
              method: 'PATCH',
              body: JSON.stringify({ ativo: !p.ativo }),
            });
            if (res.ok && resp?.success !== false) {
              ui.message(resp?.message || 'Promocao atualizada.', 'success');
              location.reload();
            } else {
              ui.message(resp?.message || 'Erro ao atualizar promocao.', 'error');
            }
          });
          card.querySelector('[data-action="enviar"]')?.addEventListener('click', async () => {
            const { res, data: resp } = await api.request(`/empresa/promocoes/${p.id}/enviar`, { method: 'POST' });
            if (res.ok && resp?.success) {
              ui.message(resp?.message || 'Push enviado com sucesso.', 'success');
              location.reload();
            } else {
              ui.message(resp?.message || 'Nao foi possivel enviar a promocao.', 'error');
            }
          });
          card.querySelector('[data-action=\"deletar\"]')?.addEventListener('click', () => empresa.deletarPromocao(p.id));
          listaBox?.appendChild(card);
        });
      };

      const fillForm = (p) => {
        editingId = p.id;
        if (form.titulo) form.titulo.value = p.titulo || p.nome || '';
        if (form.descricao) form.descricao.value = p.descricao || '';
        if (form.validade) form.validade.value = (p.data_expiracao || p.validade || '').slice(0, 10);
        if (form.preco) form.preco.value = p.desconto || p.preco || p.valor || '';
        if (form.tipo) form.tipo.value = p.tipo || 'desconto';
        if (form.imagem) form.imagem.value = p.imagem_url || p.imagem || '';
        if (form.notificationTitle) form.notificationTitle.value = p.notification_title || p.titulo || '';
        if (form.notificationBody) form.notificationBody.value = p.notification_body || p.descricao || '';
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
        if (form.validade) form.validade.value = '';
        if (form.preco) form.preco.value = '';
        if (form.imagem) form.imagem.value = '';
        if (form.notificationTitle) form.notificationTitle.value = '';
        if (form.notificationBody) form.notificationBody.value = '';
        if (form.ativa) form.ativa.checked = true;
        if (form.msg) form.msg.textContent = '';
        document.getElementById('formOferta')?.scrollIntoView({ behavior: 'smooth' });
      });

      form.cancelar?.addEventListener('click', () => {
        editingId = null;
        if (form.msg) form.msg.textContent = '';
        if (form.titulo) form.titulo.value = '';
        if (form.descricao) form.descricao.value = '';
        if (form.validade) form.validade.value = '';
        if (form.preco) form.preco.value = '';
        if (form.imagem) form.imagem.value = '';
        if (form.notificationTitle) form.notificationTitle.value = '';
        if (form.notificationBody) form.notificationBody.value = '';
        if (form.ativa) form.ativa.checked = true;
      });

      form.salvar?.addEventListener('click', async () => {
        const payload = {
          titulo: form.titulo?.value,
          nome: form.titulo?.value,
          descricao: form.descricao?.value,
          validade: form.validade?.value || null,
          desconto: Number(form.preco?.value || 0),
          preco: Number(form.preco?.value || 0),
          tipo: form.tipo?.value,
          imagem_url: form.imagem?.value,
          notification_title: form.notificationTitle?.value || null,
          notification_body: form.notificationBody?.value || null,
          ativo: form.ativa?.checked ?? true,
        };
        if (!payload.titulo) return ui.message('Informe o titulo.', 'warning');
        if (!payload.imagem_url) return ui.message('Informe a imagem obrigatoria da promocao.', 'warning');
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

      const bonusUi = {
        id: document.getElementById('bonusId'),
        titulo: document.getElementById('bonusTitulo'),
        descricao: document.getElementById('bonusDescricao'),
        validade: document.getElementById('bonusValidade'),
        imagem: document.getElementById('bonusImagem'),
        termos: document.getElementById('bonusTermos'),
        ativo: document.getElementById('bonusAtivo'),
        salvar: document.getElementById('bonusSalvar'),
        cancelar: document.getElementById('bonusCancelar'),
        mensagem: document.getElementById('bonusMensagem'),
        list: document.getElementById('bonusAdesaoList'),
        empty: document.getElementById('bonusAdesaoEmpty'),
        total: document.getElementById('bonusTotal'),
        previewTitle: document.getElementById('bonusPreviewTitle'),
        previewDescription: document.getElementById('bonusPreviewDescription'),
        previewStatus: document.getElementById('bonusPreviewStatus'),
        previewValidity: document.getElementById('bonusPreviewValidity'),
        previewImage: document.getElementById('bonusPreviewImage'),
      };

      if (bonusUi.salvar && bonusUi.list) {
        let bonusItems = [];
        let bonusEditingId = null;

        const updateBonusPreview = () => {
          const payload = {
            titulo: bonusUi.titulo?.value?.trim() || 'Bonus de adesao',
            descricao: bonusUi.descricao?.value?.trim() || 'Configure o beneficio exibido ao cliente vinculado.',
            data_expiracao: bonusUi.validade?.value || null,
            imagem_url: bonusUi.imagem?.value?.trim() || '',
            ativo: bonusUi.ativo?.checked ?? false,
          };
          const meta = bonusStatusMeta(payload.ativo ? 'available' : 'unavailable');
          if (bonusUi.previewTitle) bonusUi.previewTitle.textContent = payload.titulo;
          if (bonusUi.previewDescription) bonusUi.previewDescription.textContent = payload.descricao;
          if (bonusUi.previewStatus) {
            bonusUi.previewStatus.textContent = payload.ativo ? 'Ativo' : 'Inativo';
            bonusUi.previewStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}`;
          }
          if (bonusUi.previewValidity) {
            bonusUi.previewValidity.textContent = payload.data_expiracao
              ? `Validade: ${formatDatePtBr(payload.data_expiracao)}`
              : 'Validade nao informada';
          }
          if (bonusUi.previewImage) {
            bonusUi.previewImage.src = safeImage(payload.imagem_url, IMAGE_FALLBACKS.promo);
            bonusUi.previewImage.onerror = () => {
              bonusUi.previewImage.onerror = null;
              bonusUi.previewImage.src = IMAGE_FALLBACKS.promo;
            };
          }
        };

        const resetBonusForm = () => {
          bonusEditingId = null;
          if (bonusUi.id) bonusUi.id.value = '';
          if (bonusUi.titulo) bonusUi.titulo.value = '';
          if (bonusUi.descricao) bonusUi.descricao.value = '';
          if (bonusUi.validade) bonusUi.validade.value = '';
          if (bonusUi.imagem) bonusUi.imagem.value = '';
          if (bonusUi.termos) bonusUi.termos.value = '';
          if (bonusUi.ativo) bonusUi.ativo.checked = true;
          if (bonusUi.mensagem) bonusUi.mensagem.textContent = '';
          updateBonusPreview();
        };

        const fillBonusForm = (bonus) => {
          bonusEditingId = bonus.id;
          if (bonusUi.id) bonusUi.id.value = bonus.id;
          if (bonusUi.titulo) bonusUi.titulo.value = bonus.titulo || '';
          if (bonusUi.descricao) bonusUi.descricao.value = bonus.descricao || '';
          if (bonusUi.validade) bonusUi.validade.value = bonus.data_expiracao ? new Date(bonus.data_expiracao).toISOString().slice(0, 10) : '';
          if (bonusUi.imagem) bonusUi.imagem.value = bonus.imagem_url || bonus.imagem || '';
          if (bonusUi.termos) bonusUi.termos.value = bonus.termos || '';
          if (bonusUi.ativo) bonusUi.ativo.checked = Boolean(bonus.ativo);
          if (bonusUi.mensagem) bonusUi.mensagem.textContent = 'Editando bonus selecionado.';
          updateBonusPreview();
        };

        const renderBonusList = () => {
          if (bonusUi.list) bonusUi.list.innerHTML = '';
          if (bonusUi.total) bonusUi.total.textContent = `${bonusItems.length} item${bonusItems.length === 1 ? '' : 's'}`;
          if (bonusUi.empty) bonusUi.empty.classList.toggle('hidden', bonusItems.length > 0);

          bonusItems.forEach((bonus) => {
            const meta = bonusStatusMeta(bonus.status);
            const card = document.createElement('div');
            card.className = 'rounded-[18px] bg-white p-3 shadow-sm ring-1 ring-black/5';
            card.innerHTML = `
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-bold text-on-surface">${bonus.titulo || 'Bonus de adesao'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${bonus.descricao || 'Sem descricao.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">Validade: ${formatDatePtBr(bonus.data_expiracao, 'Nao informada')}</p>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}">${meta.label}</span>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button data-action="edit" class="rounded-lg bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface">Editar</button>
                <button data-action="toggle" class="rounded-lg ${bonus.ativo ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'} px-3 py-2 text-xs font-semibold">${bonus.ativo ? 'Desativar' : 'Ativar'}</button>
              </div>`;
            card.querySelector('[data-action="edit"]')?.addEventListener('click', () => fillBonusForm(bonus));
            card.querySelector('[data-action="toggle"]')?.addEventListener('click', async () => {
              const { res, data } = await api.request(`/empresa/bonus-adesao/${bonus.id}/toggle`, {
                method: 'PATCH',
                body: JSON.stringify({ ativo: !bonus.ativo }),
              });
              if (res.ok && data?.success) {
                ui.message(data?.message || 'Status do bonus atualizado.', 'success');
                await loadBonusList();
              } else {
                ui.message(data?.message || 'Nao foi possivel atualizar o bonus.', 'error');
              }
            });
            bonusUi.list?.appendChild(card);
          });
        };

        const loadBonusList = async () => {
          const { res, data } = await api.request('/empresa/bonus-adesao');
          bonusItems = res.ok && data?.success !== false ? toArray(data?.data ?? data) : [];
          renderBonusList();
        };

        [bonusUi.titulo, bonusUi.descricao, bonusUi.validade, bonusUi.imagem, bonusUi.termos, bonusUi.ativo]
          .filter(Boolean)
          .forEach((field) => {
            field.addEventListener('input', updateBonusPreview);
            field.addEventListener('change', updateBonusPreview);
          });

        bonusUi.cancelar?.addEventListener('click', () => {
          resetBonusForm();
        });

        bonusUi.salvar?.addEventListener('click', async () => {
          const payload = {
            titulo: bonusUi.titulo?.value?.trim() || '',
            descricao: bonusUi.descricao?.value?.trim() || '',
            data_expiracao: bonusUi.validade?.value || null,
            imagem_url: bonusUi.imagem?.value?.trim() || null,
            termos: bonusUi.termos?.value?.trim() || null,
            ativo: bonusUi.ativo?.checked ?? true,
          };

          if (!payload.titulo) {
            return ui.message('Informe o titulo do bonus de adesao.', 'warning');
          }

          const path = bonusEditingId ? `/empresa/bonus-adesao/${bonusEditingId}` : '/empresa/bonus-adesao';
          const method = bonusEditingId ? 'PUT' : 'POST';
          const { res, data } = await api.request(path, {
            method,
            body: JSON.stringify(payload),
          });

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Bonus de adesao salvo.', 'success');
            resetBonusForm();
            await loadBonusList();
          } else {
            ui.message(data?.message || 'Nao foi possivel salvar o bonus de adesao.', 'error');
          }
        });

        resetBonusForm();
        await loadBonusList();
      }

      const loyaltyUi = {
        id: document.getElementById('cartaoFidelidadeId'),
        titulo: document.getElementById('cartaoTitulo'),
        descricao: document.getElementById('cartaoDescricao'),
        regraGanho: document.getElementById('cartaoRegraGanho'),
        pontosPorVisita: document.getElementById('cartaoPontosPorVisita'),
        pontosNecessarios: document.getElementById('cartaoPontosNecessarios'),
        validade: document.getElementById('cartaoValidade'),
        recompensa: document.getElementById('cartaoRecompensa'),
        ativo: document.getElementById('cartaoAtivo'),
        salvar: document.getElementById('cartaoSalvar'),
        cancelar: document.getElementById('cartaoCancelar'),
        mensagem: document.getElementById('cartaoMensagem'),
        list: document.getElementById('cartaoFidelidadeList'),
        empty: document.getElementById('cartaoFidelidadeEmpty'),
        total: document.getElementById('cartaoTotal'),
        previewTitle: document.getElementById('cartaoPreviewTitle'),
        previewDescription: document.getElementById('cartaoPreviewDescription'),
        previewStatus: document.getElementById('cartaoPreviewStatus'),
        previewRule: document.getElementById('cartaoPreviewRule'),
        previewReward: document.getElementById('cartaoPreviewReward'),
        previewMeta: document.getElementById('cartaoPreviewMeta'),
      };

      if (loyaltyUi.salvar && loyaltyUi.list) {
        let loyaltyItems = [];
        let loyaltyEditingId = null;

        const updateLoyaltyPreview = () => {
          const payload = {
            titulo: loyaltyUi.titulo?.value?.trim() || 'Cartao fidelidade',
            descricao: loyaltyUi.descricao?.value?.trim() || 'Configure a regra de pontos e a recompensa que o cliente vera na pagina publica.',
            regra_ganho: loyaltyUi.regraGanho?.value?.trim() || 'Ganhe 1 ponto a cada visita.',
            pontos_por_visita: Number(loyaltyUi.pontosPorVisita?.value || 1),
            pontos_necessarios: Number(loyaltyUi.pontosNecessarios?.value || 0),
            recompensa_descricao: loyaltyUi.recompensa?.value?.trim() || 'Ainda nao informada',
            data_expiracao: loyaltyUi.validade?.value || null,
            ativo: loyaltyUi.ativo?.checked ?? false,
          };
          const meta = loyaltyStatusMeta(payload.ativo ? 'available' : 'inactive');
          if (loyaltyUi.previewTitle) loyaltyUi.previewTitle.textContent = payload.titulo;
          if (loyaltyUi.previewDescription) loyaltyUi.previewDescription.textContent = payload.descricao;
          if (loyaltyUi.previewRule) loyaltyUi.previewRule.textContent = payload.regra_ganho;
          if (loyaltyUi.previewReward) loyaltyUi.previewReward.textContent = payload.recompensa_descricao;
          if (loyaltyUi.previewStatus) {
            loyaltyUi.previewStatus.textContent = payload.ativo ? 'Ativo' : 'Inativo';
            loyaltyUi.previewStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}`;
          }
          if (loyaltyUi.previewMeta) {
            loyaltyUi.previewMeta.textContent = `Meta: ${payload.pontos_necessarios} pontos | ${payload.data_expiracao ? `Validade ${formatDatePtBr(payload.data_expiracao)}` : 'Validade nao informada'}`;
          }
        };

        const resetLoyaltyForm = () => {
          loyaltyEditingId = null;
          if (loyaltyUi.id) loyaltyUi.id.value = '';
          if (loyaltyUi.titulo) loyaltyUi.titulo.value = '';
          if (loyaltyUi.descricao) loyaltyUi.descricao.value = '';
          if (loyaltyUi.regraGanho) loyaltyUi.regraGanho.value = 'Ganhe 1 ponto a cada visita.';
          if (loyaltyUi.pontosPorVisita) loyaltyUi.pontosPorVisita.value = 1;
          if (loyaltyUi.pontosNecessarios) loyaltyUi.pontosNecessarios.value = 10;
          if (loyaltyUi.validade) loyaltyUi.validade.value = '';
          if (loyaltyUi.recompensa) loyaltyUi.recompensa.value = '';
          if (loyaltyUi.ativo) loyaltyUi.ativo.checked = true;
          if (loyaltyUi.mensagem) loyaltyUi.mensagem.textContent = '';
          updateLoyaltyPreview();
        };

        const fillLoyaltyForm = (card) => {
          loyaltyEditingId = card.id;
          if (loyaltyUi.id) loyaltyUi.id.value = card.id;
          if (loyaltyUi.titulo) loyaltyUi.titulo.value = card.titulo || '';
          if (loyaltyUi.descricao) loyaltyUi.descricao.value = card.descricao || '';
          if (loyaltyUi.regraGanho) loyaltyUi.regraGanho.value = card.regra_ganho || 'Ganhe 1 ponto a cada visita.';
          if (loyaltyUi.pontosPorVisita) loyaltyUi.pontosPorVisita.value = card.pontos_por_visita || 1;
          if (loyaltyUi.pontosNecessarios) loyaltyUi.pontosNecessarios.value = card.pontos_necessarios || 10;
          if (loyaltyUi.validade) loyaltyUi.validade.value = card.data_expiracao || '';
          if (loyaltyUi.recompensa) loyaltyUi.recompensa.value = card.recompensa_descricao || '';
          if (loyaltyUi.ativo) loyaltyUi.ativo.checked = Boolean(card.ativo);
          if (loyaltyUi.mensagem) loyaltyUi.mensagem.textContent = 'Editando cartao selecionado.';
          updateLoyaltyPreview();
        };

        const renderLoyaltyList = () => {
          if (loyaltyUi.list) loyaltyUi.list.innerHTML = '';
          if (loyaltyUi.total) loyaltyUi.total.textContent = `${loyaltyItems.length} item${loyaltyItems.length === 1 ? '' : 's'}`;
          if (loyaltyUi.empty) loyaltyUi.empty.classList.toggle('hidden', loyaltyItems.length > 0);

          loyaltyItems.forEach((card) => {
            const meta = loyaltyStatusMeta(card.status);
            const item = document.createElement('div');
            item.className = 'rounded-[18px] bg-white p-3 shadow-sm ring-1 ring-black/5';
            item.innerHTML = `
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-bold text-on-surface">${card.titulo || 'Cartao fidelidade'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${card.regra_ganho || 'Ganhe pontos por visita.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">Meta: ${card.pontos_necessarios || 0} pontos | Recompensa: ${card.recompensa_descricao || 'Nao informada'}</p>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}">${meta.label}</span>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button data-action="edit" class="rounded-lg bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface">Editar</button>
                <button data-action="toggle" class="rounded-lg ${card.ativo ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'} px-3 py-2 text-xs font-semibold">${card.ativo ? 'Desativar' : 'Ativar'}</button>
              </div>`;
            item.querySelector('[data-action="edit"]')?.addEventListener('click', () => fillLoyaltyForm(card));
            item.querySelector('[data-action="toggle"]')?.addEventListener('click', async () => {
              const { res, data } = await api.request(`/empresa/cartao-fidelidade/${card.id}/toggle`, {
                method: 'PATCH',
                body: JSON.stringify({ ativo: !card.ativo }),
              });
              if (res.ok && data?.success) {
                ui.message(data?.message || 'Status do cartao atualizado.', 'success');
                await loadLoyaltyList();
              } else {
                ui.message(data?.message || 'Nao foi possivel atualizar o cartao.', 'error');
              }
            });
            loyaltyUi.list?.appendChild(item);
          });
        };

        const loadLoyaltyList = async () => {
          const { res, data } = await api.request('/empresa/cartao-fidelidade');
          loyaltyItems = res.ok && data?.success !== false ? toArray(data?.data ?? data) : [];
          renderLoyaltyList();
        };

        [loyaltyUi.titulo, loyaltyUi.descricao, loyaltyUi.regraGanho, loyaltyUi.pontosPorVisita, loyaltyUi.pontosNecessarios, loyaltyUi.validade, loyaltyUi.recompensa, loyaltyUi.ativo]
          .filter(Boolean)
          .forEach((field) => {
            field.addEventListener('input', updateLoyaltyPreview);
            field.addEventListener('change', updateLoyaltyPreview);
          });

        loyaltyUi.cancelar?.addEventListener('click', () => {
          resetLoyaltyForm();
        });

        loyaltyUi.salvar?.addEventListener('click', async () => {
          const payload = {
            titulo: loyaltyUi.titulo?.value?.trim() || '',
            descricao: loyaltyUi.descricao?.value?.trim() || '',
            regra_ganho: loyaltyUi.regraGanho?.value?.trim() || 'Ganhe 1 ponto a cada visita.',
            pontos_por_visita: Number(loyaltyUi.pontosPorVisita?.value || 1),
            pontos_necessarios: Number(loyaltyUi.pontosNecessarios?.value || 0),
            recompensa_descricao: loyaltyUi.recompensa?.value?.trim() || '',
            data_expiracao: loyaltyUi.validade?.value || null,
            ativo: loyaltyUi.ativo?.checked ?? true,
          };

          if (!payload.titulo) return ui.message('Informe o titulo do cartao fidelidade.', 'warning');
          if (!payload.pontos_necessarios || payload.pontos_necessarios < 1) return ui.message('Informe a meta de pontos.', 'warning');
          if (!payload.recompensa_descricao) return ui.message('Informe a recompensa do cartao fidelidade.', 'warning');

          const path = loyaltyEditingId ? `/empresa/cartao-fidelidade/${loyaltyEditingId}` : '/empresa/cartao-fidelidade';
          const method = loyaltyEditingId ? 'PUT' : 'POST';
          const { res, data } = await api.request(path, {
            method,
            body: JSON.stringify(payload),
          });

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Cartao fidelidade salvo.', 'success');
            resetLoyaltyForm();
            await loadLoyaltyList();
          } else {
            ui.message(data?.message || 'Nao foi possivel salvar o cartao fidelidade.', 'error');
          }
        });

        resetLoyaltyForm();
        await loadLoyaltyList();
      }

      const birthdayUi = {
        id: document.getElementById('birthdayBonusId'),
        titulo: document.getElementById('birthdayBonusTitle'),
        descricao: document.getElementById('birthdayBonusDescription'),
        diasValidade: document.getElementById('birthdayBonusDaysValidity'),
        imagem: document.getElementById('birthdayBonusImage'),
        notificationTitle: document.getElementById('birthdayBonusNotificationTitle'),
        notificationBody: document.getElementById('birthdayBonusNotificationBody'),
        ativo: document.getElementById('birthdayBonusActive'),
        salvar: document.getElementById('birthdayBonusSave'),
        enviar: document.getElementById('birthdayBonusSend'),
        cancelar: document.getElementById('birthdayBonusCancel'),
        mensagem: document.getElementById('birthdayBonusMessage'),
        list: document.getElementById('birthdayBonusList'),
        empty: document.getElementById('birthdayBonusEmpty'),
        total: document.getElementById('birthdayBonusTotal'),
        previewTitle: document.getElementById('birthdayBonusPreviewTitle'),
        previewDescription: document.getElementById('birthdayBonusPreviewDescription'),
        previewStatus: document.getElementById('birthdayBonusPreviewStatus'),
        previewValidity: document.getElementById('birthdayBonusPreviewValidity'),
        previewNotification: document.getElementById('birthdayBonusPreviewNotification'),
        previewImage: document.getElementById('birthdayBonusPreviewImage'),
      };

      if (birthdayUi.salvar && birthdayUi.list) {
        let birthdayItems = [];
        let birthdayEditingId = null;

        const updateBirthdayPreview = () => {
          const payload = {
            titulo: birthdayUi.titulo?.value?.trim() || 'Bonus aniversario',
            descricao: birthdayUi.descricao?.value?.trim() || 'Configure o beneficio anual exibido para o cliente elegivel.',
            dias_validade: Number(birthdayUi.diasValidade?.value || 0),
            imagem_url: birthdayUi.imagem?.value?.trim() || '',
            notification_title: birthdayUi.notificationTitle?.value?.trim() || '',
            notification_body: birthdayUi.notificationBody?.value?.trim() || '',
            ativo: birthdayUi.ativo?.checked ?? false,
          };
          const meta = birthdayBonusStatusMeta(payload.ativo ? 'available' : 'inactive');
          if (birthdayUi.previewTitle) birthdayUi.previewTitle.textContent = payload.titulo;
          if (birthdayUi.previewDescription) birthdayUi.previewDescription.textContent = payload.descricao;
          if (birthdayUi.previewStatus) {
            birthdayUi.previewStatus.textContent = payload.ativo ? 'Ativo' : 'Inativo';
            birthdayUi.previewStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}`;
          }
          if (birthdayUi.previewValidity) {
            birthdayUi.previewValidity.textContent = payload.dias_validade > 0
              ? `Valido por ${payload.dias_validade} dia(s) a partir do aniversario`
              : 'Valido durante todo o mes do aniversario';
          }
          if (birthdayUi.previewNotification) {
            const title = payload.notification_title || 'Nao configurado';
            const body = payload.notification_body || 'Sem mensagem';
            birthdayUi.previewNotification.textContent = `Push: ${title} | ${body}`;
          }
          if (birthdayUi.previewImage) {
            birthdayUi.previewImage.src = safeImage(payload.imagem_url, IMAGE_FALLBACKS.promo);
            birthdayUi.previewImage.onerror = () => {
              birthdayUi.previewImage.onerror = null;
              birthdayUi.previewImage.src = IMAGE_FALLBACKS.promo;
            };
          }
        };

        const resetBirthdayForm = () => {
          birthdayEditingId = null;
          if (birthdayUi.id) birthdayUi.id.value = '';
          if (birthdayUi.titulo) birthdayUi.titulo.value = '';
          if (birthdayUi.descricao) birthdayUi.descricao.value = '';
          if (birthdayUi.diasValidade) birthdayUi.diasValidade.value = '';
          if (birthdayUi.imagem) birthdayUi.imagem.value = '';
          if (birthdayUi.notificationTitle) birthdayUi.notificationTitle.value = '';
          if (birthdayUi.notificationBody) birthdayUi.notificationBody.value = '';
          if (birthdayUi.ativo) birthdayUi.ativo.checked = true;
          if (birthdayUi.mensagem) birthdayUi.mensagem.textContent = '';
          updateBirthdayPreview();
        };

        const fillBirthdayForm = (bonus) => {
          birthdayEditingId = bonus.id;
          if (birthdayUi.id) birthdayUi.id.value = bonus.id;
          if (birthdayUi.titulo) birthdayUi.titulo.value = bonus.titulo || '';
          if (birthdayUi.descricao) birthdayUi.descricao.value = bonus.descricao || '';
          if (birthdayUi.diasValidade) birthdayUi.diasValidade.value = bonus.dias_validade || '';
          if (birthdayUi.imagem) birthdayUi.imagem.value = bonus.imagem_url || bonus.imagem || '';
          if (birthdayUi.notificationTitle) birthdayUi.notificationTitle.value = bonus.notification_title || bonus.titulo || '';
          if (birthdayUi.notificationBody) birthdayUi.notificationBody.value = bonus.notification_body || bonus.descricao || '';
          if (birthdayUi.ativo) birthdayUi.ativo.checked = Boolean(bonus.ativo);
          if (birthdayUi.mensagem) birthdayUi.mensagem.textContent = 'Editando bonus aniversario selecionado.';
          updateBirthdayPreview();
        };

        const renderBirthdayList = () => {
          if (birthdayUi.list) birthdayUi.list.innerHTML = '';
          if (birthdayUi.total) birthdayUi.total.textContent = `${birthdayItems.length} item${birthdayItems.length === 1 ? '' : 's'}`;
          if (birthdayUi.empty) birthdayUi.empty.classList.toggle('hidden', birthdayItems.length > 0);

          birthdayItems.forEach((bonus) => {
            const meta = birthdayBonusStatusMeta(bonus.status);
            const canSend = Boolean(bonus.ativo && bonus.id);
            const card = document.createElement('div');
            card.className = 'rounded-[18px] bg-white p-3 shadow-sm ring-1 ring-black/5';
            card.innerHTML = `
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-bold text-on-surface">${bonus.titulo || 'Bonus aniversario'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${bonus.descricao || 'Sem descricao.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">${safeText(bonus.validade_descricao, 'Validade nao informada')}</p>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${meta.badgeClass}">${meta.label}</span>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button data-action="edit" class="rounded-lg bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface">Editar</button>
                <button data-action="toggle" class="rounded-lg ${bonus.ativo ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'} px-3 py-2 text-xs font-semibold">${bonus.ativo ? 'Desativar' : 'Ativar'}</button>
                <button data-action="send" class="rounded-lg ${canSend ? 'bg-[#111B3F] text-white' : 'bg-surface-container text-on-surface-variant'} px-3 py-2 text-xs font-semibold" ${canSend ? '' : 'disabled'}>Enviar push</button>
              </div>`;
            card.querySelector('[data-action="edit"]')?.addEventListener('click', () => fillBirthdayForm(bonus));
            card.querySelector('[data-action="toggle"]')?.addEventListener('click', async () => {
              const { res, data } = await api.request(`/empresa/bonus-aniversario/${bonus.id}/toggle`, {
                method: 'PATCH',
                body: JSON.stringify({ ativo: !bonus.ativo }),
              });
              if (res.ok && data?.success) {
                ui.message(data?.message || 'Status do bonus aniversario atualizado.', 'success');
                await loadBirthdayList();
              } else {
                ui.message(data?.message || 'Nao foi possivel atualizar o bonus aniversario.', 'error');
              }
            });
            card.querySelector('[data-action="send"]')?.addEventListener('click', async () => {
              const { res, data } = await api.request(`/empresa/bonus-aniversario/${bonus.id}/enviar-elegiveis`, {
                method: 'POST',
              });
              if (res.ok && data?.success) {
                const sent = Number(data?.meta?.delivery?.total_sent || 0);
                ui.message(data?.message || `Envio concluido para ${sent} cliente(s).`, 'success');
              } else {
                ui.message(data?.message || 'Nao foi possivel enviar o bonus aniversario.', 'error');
              }
            });
            birthdayUi.list?.appendChild(card);
          });
        };

        const loadBirthdayList = async () => {
          const { res, data } = await api.request('/empresa/bonus-aniversario');
          birthdayItems = res.ok && data?.success !== false ? toArray(data?.data ?? data) : [];
          renderBirthdayList();
        };

        [birthdayUi.titulo, birthdayUi.descricao, birthdayUi.diasValidade, birthdayUi.imagem, birthdayUi.notificationTitle, birthdayUi.notificationBody, birthdayUi.ativo]
          .filter(Boolean)
          .forEach((field) => {
            field.addEventListener('input', updateBirthdayPreview);
            field.addEventListener('change', updateBirthdayPreview);
          });

        birthdayUi.cancelar?.addEventListener('click', () => {
          resetBirthdayForm();
        });

        birthdayUi.salvar?.addEventListener('click', async () => {
          const payload = {
            titulo: birthdayUi.titulo?.value?.trim() || '',
            descricao: birthdayUi.descricao?.value?.trim() || '',
            dias_validade: birthdayUi.diasValidade?.value ? Number(birthdayUi.diasValidade.value) : null,
            imagem_url: birthdayUi.imagem?.value?.trim() || null,
            notification_title: birthdayUi.notificationTitle?.value?.trim() || null,
            notification_body: birthdayUi.notificationBody?.value?.trim() || null,
            ativo: birthdayUi.ativo?.checked ?? true,
          };

          if (!payload.titulo) return ui.message('Informe o titulo do bonus aniversario.', 'warning');
          if (!payload.descricao) return ui.message('Informe a descricao do bonus aniversario.', 'warning');

          const path = birthdayEditingId ? `/empresa/bonus-aniversario/${birthdayEditingId}` : '/empresa/bonus-aniversario';
          const method = birthdayEditingId ? 'PUT' : 'POST';
          const { res, data } = await api.request(path, {
            method,
            body: JSON.stringify(payload),
          });

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Bonus aniversario salvo.', 'success');
            resetBirthdayForm();
            await loadBirthdayList();
          } else {
            ui.message(data?.message || 'Nao foi possivel salvar o bonus aniversario.', 'error');
          }
        });

        birthdayUi.enviar?.addEventListener('click', async () => {
          const target = birthdayItems.find((item) => item.ativo) || birthdayItems[0];
          if (!target?.id) {
            ui.message('Cadastre um bonus aniversario antes de enviar.', 'warning');
            return;
          }

          const { res, data } = await api.request(`/empresa/bonus-aniversario/${target.id}/enviar-elegiveis`, {
            method: 'POST',
          });
          if (res.ok && data?.success) {
            const sent = Number(data?.meta?.delivery?.total_sent || 0);
            ui.message(data?.message || `Envio concluido para ${sent} cliente(s).`, 'success');
          } else {
            ui.message(data?.message || 'Nao foi possivel enviar o bonus aniversario.', 'error');
          }
        });

        resetBirthdayForm();
        await loadBirthdayList();
      }

      const reminderUi = {
        id: document.getElementById('returnReminderId'),
        dias: document.getElementById('returnReminderDays'),
        titulo: document.getElementById('returnReminderTitle'),
        mensagem: document.getElementById('returnReminderMessageInput'),
        ativo: document.getElementById('returnReminderActive'),
        salvar: document.getElementById('returnReminderSave'),
        enviar: document.getElementById('returnReminderSend'),
        cancelar: document.getElementById('returnReminderCancel'),
        feedback: document.getElementById('returnReminderFeedback'),
        list: document.getElementById('returnReminderList'),
        empty: document.getElementById('returnReminderEmpty'),
        total: document.getElementById('returnReminderTotal'),
        previewTitle: document.getElementById('returnReminderPreviewTitle'),
        previewMessage: document.getElementById('returnReminderPreviewMessage'),
        previewStatus: document.getElementById('returnReminderPreviewStatus'),
        previewMeta: document.getElementById('returnReminderPreviewMeta'),
      };

      if (reminderUi.salvar && reminderUi.list) {
        let reminderItems = [];
        let reminderEditingId = null;

        const updateReminderPreview = () => {
          const payload = {
            dias_sem_visita: Number(reminderUi.dias?.value || 30),
            titulo: reminderUi.titulo?.value?.trim() || 'Lembrete de retorno',
            mensagem: reminderUi.mensagem?.value?.trim() || 'Sentimos sua falta. Volte para aproveitar as novidades da loja.',
            ativo: reminderUi.ativo?.checked ?? false,
          };
          if (reminderUi.previewTitle) reminderUi.previewTitle.textContent = payload.titulo;
          if (reminderUi.previewMessage) reminderUi.previewMessage.textContent = payload.mensagem;
          if (reminderUi.previewStatus) {
            reminderUi.previewStatus.textContent = payload.ativo ? 'Ativo' : 'Inativo';
            reminderUi.previewStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${payload.ativo ? 'bg-emerald-500/20 text-white' : 'bg-white/10 text-white/80'}`;
          }
          if (reminderUi.previewMeta) {
            reminderUi.previewMeta.textContent = `Disparo apos ${payload.dias_sem_visita} dia(s) sem visita`;
          }
        };

        const resetReminderForm = () => {
          reminderEditingId = null;
          if (reminderUi.id) reminderUi.id.value = '';
          if (reminderUi.dias) reminderUi.dias.value = 30;
          if (reminderUi.titulo) reminderUi.titulo.value = '';
          if (reminderUi.mensagem) reminderUi.mensagem.value = '';
          if (reminderUi.ativo) reminderUi.ativo.checked = true;
          if (reminderUi.feedback) reminderUi.feedback.textContent = '';
          updateReminderPreview();
        };

        const fillReminderForm = (reminder) => {
          reminderEditingId = reminder.id;
          if (reminderUi.id) reminderUi.id.value = reminder.id;
          if (reminderUi.dias) reminderUi.dias.value = reminder.dias_sem_visita || reminder.dias_ausencia || 30;
          if (reminderUi.titulo) reminderUi.titulo.value = reminder.titulo || '';
          if (reminderUi.mensagem) reminderUi.mensagem.value = reminder.mensagem || '';
          if (reminderUi.ativo) reminderUi.ativo.checked = Boolean(reminder.ativo);
          if (reminderUi.feedback) reminderUi.feedback.textContent = 'Editando lembrete selecionado.';
          updateReminderPreview();
        };

        const renderReminderList = () => {
          if (reminderUi.list) reminderUi.list.innerHTML = '';
          if (reminderUi.total) reminderUi.total.textContent = `${reminderItems.length} item${reminderItems.length === 1 ? '' : 's'}`;
          if (reminderUi.empty) reminderUi.empty.classList.toggle('hidden', reminderItems.length > 0);

          reminderItems.forEach((reminder) => {
            const active = Boolean(reminder.ativo);
            const item = document.createElement('div');
            item.className = 'rounded-[18px] bg-white p-3 shadow-sm ring-1 ring-black/5';
            item.innerHTML = `
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-bold text-on-surface">${reminder.titulo || 'Lembrete de retorno'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${reminder.mensagem || 'Sem mensagem.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">Disparo apos ${reminder.dias_sem_visita || reminder.dias_ausencia || 0} dia(s) sem visita</p>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}">${active ? 'Ativo' : 'Inativo'}</span>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <button data-action="edit" class="rounded-lg bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface">Editar</button>
                <button data-action="toggle" class="rounded-lg ${active ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'} px-3 py-2 text-xs font-semibold">${active ? 'Desativar' : 'Ativar'}</button>
              </div>`;
            item.querySelector('[data-action="edit"]')?.addEventListener('click', () => fillReminderForm(reminder));
            item.querySelector('[data-action="toggle"]')?.addEventListener('click', async () => {
              const { res, data } = await api.request(`/empresa/lembrete-retorno/${reminder.id}/toggle`, {
                method: 'PATCH',
                body: JSON.stringify({ ativo: !active }),
              });
              if (res.ok && data?.success) {
                ui.message(data?.message || 'Status do lembrete atualizado.', 'success');
                await loadReminderList();
              } else {
                ui.message(data?.message || 'Nao foi possivel atualizar o lembrete.', 'error');
              }
            });
            reminderUi.list?.appendChild(item);
          });
        };

        const loadReminderList = async () => {
          const { res, data } = await api.request('/empresa/lembrete-retorno');
          reminderItems = res.ok && data?.success !== false ? toArray(data?.data ?? data) : [];
          renderReminderList();
        };

        [reminderUi.dias, reminderUi.titulo, reminderUi.mensagem, reminderUi.ativo]
          .filter(Boolean)
          .forEach((field) => {
            field.addEventListener('input', updateReminderPreview);
            field.addEventListener('change', updateReminderPreview);
          });

        reminderUi.cancelar?.addEventListener('click', () => {
          resetReminderForm();
        });

        reminderUi.salvar?.addEventListener('click', async () => {
          const payload = {
            dias_sem_visita: Number(reminderUi.dias?.value || 0),
            titulo: reminderUi.titulo?.value?.trim() || '',
            mensagem: reminderUi.mensagem?.value?.trim() || '',
            ativo: reminderUi.ativo?.checked ?? true,
          };

          if (!payload.dias_sem_visita || payload.dias_sem_visita < 1) return ui.message('Informe os dias sem visita.', 'warning');
          if (!payload.titulo) return ui.message('Informe o titulo do lembrete.', 'warning');
          if (!payload.mensagem) return ui.message('Informe a mensagem do lembrete.', 'warning');

          const path = reminderEditingId ? `/empresa/lembrete-retorno/${reminderEditingId}` : '/empresa/lembrete-retorno';
          const method = reminderEditingId ? 'PUT' : 'POST';
          const { res, data } = await api.request(path, {
            method,
            body: JSON.stringify(payload),
          });

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Lembrete de retorno salvo.', 'success');
            resetReminderForm();
            await loadReminderList();
          } else {
            ui.message(data?.message || 'Nao foi possivel salvar o lembrete.', 'error');
          }
        });

        reminderUi.enviar?.addEventListener('click', async () => {
          const target = reminderItems.find((item) => item.ativo) || reminderItems[0];
          if (!target?.id) {
            ui.message('Cadastre um lembrete antes de enviar.', 'warning');
            return;
          }

          const { res, data } = await api.request('/empresa/lembrete-retorno/enviar-elegiveis', {
            method: 'POST',
            body: JSON.stringify({ lembrete_id: target.id }),
          });
          if (res.ok && data?.success) {
            const sent = Number(data?.meta?.delivery?.total_sent || 0);
            ui.message(data?.message || `Lembretes enviados para ${sent} cliente(s).`, 'success');
          } else {
            ui.message(data?.message || 'Nao foi possivel enviar os lembretes.', 'error');
          }
        });

        resetReminderForm();
        await loadReminderList();
      }

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
    enrichUsersDataset(baseList = []) {
      const list = Array.isArray(baseList) ? [...baseList] : [];
      if (list.length >= 12) return list;

      const seenEmails = new Set(
        list
          .map((item) => String(item?.email || '').trim().toLowerCase())
          .filter(Boolean)
      );

      [
        { id: 'admin-demo-main', name: 'Admin Demo', email: 'admin@demo.local', perfil: 'admin', status: 'ativo' },
        { id: 'admin-demo-sub', name: 'Operacao Demo', email: 'operacao@demo.local', perfil: 'sub-admin', status: 'ativo' },
        { id: 'empresa-demo-1', name: 'Malagueta Galpao', email: 'malagueta@demo.local', perfil: 'empresa', status: 'ativo' },
        { id: 'empresa-demo-2', name: 'Texano Burger', email: 'texano@demo.local', perfil: 'empresa', status: 'ativo' },
        { id: 'empresa-demo-3', name: 'Makoto Sushi', email: 'makoto@demo.local', perfil: 'empresa', status: 'ativo' },
        { id: 'empresa-demo-4', name: 'Florenza Boutique', email: 'florenza@demo.local', perfil: 'empresa', status: 'ativo' },
        { id: 'cliente-demo-1', name: 'Joao Cliente Demo', email: 'joao@demo.local', perfil: 'cliente', status: 'ativo', pontos: 5 },
        { id: 'cliente-demo-2', name: 'Maria Aniversariante', email: 'maria@demo.local', perfil: 'cliente', status: 'ativo', pontos: 14 },
        { id: 'cliente-demo-3', name: 'Pedro Inativo', email: 'pedro@demo.local', perfil: 'cliente', status: 'bloqueado', pontos: 0 },
        { id: 'cliente-demo-4', name: 'Ana Fidelidade', email: 'ana@demo.local', perfil: 'cliente', status: 'ativo', pontos: 16 },
      ].forEach((candidate, idx) => {
        const email = String(candidate.email || '').trim().toLowerCase();
        if (!email || seenEmails.has(email)) return;
        seenEmails.add(email);
        list.push({
          ...candidate,
          created_at: candidate.created_at || new Date(Date.now() - (idx + 1) * 43200000).toISOString(),
          updated_at: candidate.updated_at || new Date(Date.now() - idx * 21600000).toISOString(),
        });
      });

      return list;
    },

    enrichCompaniesDataset(baseList = []) {
      const list = Array.isArray(baseList) ? [...baseList] : [];
      const seenIds = new Set(list.map((item) => String(item?.id || '')).filter(Boolean));
      const seenNames = new Set(
        list
          .map((item) => safeText(item?.nome || item?.nome_fantasia || item?.name, '').toLowerCase())
          .filter(Boolean)
      );

      DEMO_ADMIN_COMPANIES.forEach((candidate, idx) => {
        const idKey = String(candidate?.id || '');
        const nameKey = safeText(candidate?.nome || candidate?.name, '').toLowerCase();
        if ((idKey && seenIds.has(idKey)) || (nameKey && seenNames.has(nameKey))) return;

        if (idKey) seenIds.add(idKey);
        if (nameKey) seenNames.add(nameKey);

        list.push({
          ...candidate,
          created_at: candidate.created_at || new Date(Date.now() - (idx + 1) * 86400000).toISOString(),
          updated_at: candidate.updated_at || new Date(Date.now() - idx * 43200000).toISOString(),
        });
      });

      return list;
    },

    summarizeCompanies(list = []) {
      const summary = { total: list.length, pending: 0, active: 0, suspended: 0, rejected: 0 };
      list.forEach((item) => {
        const status = safeText(item?.status, '').toLowerCase();
        if (status === 'pending') summary.pending += 1;
        else if (status === 'active' || status === 'ativo') summary.active += 1;
        else if (status === 'suspended' || status === 'suspenso') summary.suspended += 1;
        else if (status === 'rejected' || status === 'rejeitado') summary.rejected += 1;
      });
      return summary;
    },

    async loadUsersDataset() {
      const primary = await api.request('/admin/users-report', {}, { notify: false });
      if (primary.res.ok) {
        const raw = primary.data?.data ?? primary.data ?? [];
        const list = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
        return { ok: true, list: admin.enrichUsersDataset(list) };
      }

      const fallback = await api.request('/admin/users', {}, { notify: false });
      if (fallback.res.ok) {
        const raw = fallback.data?.data ?? fallback.data ?? [];
        const list = Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : [];
        return { ok: true, list: admin.enrichUsersDataset(list) };
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
        return { ok: true, list: admin.enrichUsersDataset(synthetic) };
      }

      const synthetic = [];
      DEMO_ADMIN_COMPANIES.forEach((e, idx) => {
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
      return { ok: true, list: admin.enrichUsersDataset(synthetic) };
    },

    async dashboard() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando dashboard admin...');
      const [stats, recent, empresas, ticketsStatsResp, adminSummaryResp] = await Promise.all([
        api.request('/admin/dashboard-stats', {}, { notify: false }),
        api.request('/admin/recent-activity', {}, { notify: false }),
        api.request('/empresas', {}, { requireAuth: false, notify: false }),
        api.request('/admin/tickets/stats', {}, { notify: false }),
        api.request('/admin/relatorios/resumo', {}, { notify: false }),
      ]);
      ui.clearPageState();

      const ids = (id) => document.getElementById(id);
      const statsData = stats.data?.data || stats.data || {};
      const adminSummary = adminSummaryResp.data?.data || {};
      const summaryCards = adminSummary?.cards || {};
      const totals = statsData?.totais || {};
      const empresasListApi = toArray(empresas.data?.data || empresas.data);
      const empresasList = empresasListApi.length ? admin.enrichCompaniesDataset(empresasListApi) : DEMO_ADMIN_COMPANIES;
      const mergedTotals = {
        ...DEMO.admin.totals,
        ...totals,
      };

      const totalUsuarios = toNumber(mergedTotals.usuarios, statsData.usuarios, statsData.total_users);
      const totalEmpresas = toNumber(summaryCards.total_empresas, mergedTotals.empresas, statsData.empresas, statsData.total_empresas, empresasList.length);
      const totalCampanhas = toNumber(summaryCards.total_promocoes, mergedTotals.campanhas, statsData.campanhas, statsData.promocoes);
      const totalResgates = toNumber(summaryCards.total_resgates, mergedTotals.resgates, statsData.resgates);
      const totalVolume = toNumber(mergedTotals.volume, statsData.volume);
      const ticketStatsData = ticketsStatsResp.data?.data || {};
      const hasTicketData = toNumber(ticketStatsData.total, ticketStatsData.pendentes, ticketStatsData.resolvidos) > 0;
      const ticketStats = hasTicketData ? ticketStatsData : DEMO.admin.ticketStats;
      const totalTicketsPendentes = toNumber(ticketStats.pendentes, DEMO.admin.ticketStats.pendentes);
      const totalTicketsUrgentes = toNumber(ticketStats.urgentes, Math.min(totalTicketsPendentes, DEMO.admin.ticketStats.urgentes));

      if (ids('adminUsers')) ids('adminUsers').textContent = Number(totalUsuarios || 0).toLocaleString('pt-BR');
      if (ids('adminEmpresas')) ids('adminEmpresas').textContent = Number(totalEmpresas || 0).toLocaleString('pt-BR');
      if (ids('adminCampanhas')) ids('adminCampanhas').textContent = Number(totalCampanhas || 0).toLocaleString('pt-BR');
      if (ids('adminResgates')) ids('adminResgates').textContent = Number(totalResgates || 0).toLocaleString('pt-BR');
      if (ids('adminVolume')) ids('adminVolume').textContent = `R$ ${Number(totalVolume || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      if (ids('adminCrescimentoMsg')) ids('adminCrescimentoMsg').textContent = safeText(
        statsData.crescimento_texto,
        summaryCards.total_notificacoes !== undefined
          ? `Resumo consolidado: ${Number(summaryCards.total_notificacoes || 0).toLocaleString('pt-BR')} notificacao(oes) enviadas na base.`
          : 'Dados consolidados dos ultimos 30 dias'
      );
      if (ids('adminTicketsPendentes')) ids('adminTicketsPendentes').textContent = `${Number(totalTicketsPendentes || 0).toLocaleString('pt-BR')} ticket(s) pendente(s)`;
      if (ids('adminTicketsUrgentes')) ids('adminTicketsUrgentes').textContent = totalTicketsUrgentes > 0
        ? `${Number(totalTicketsUrgentes).toLocaleString('pt-BR')} ticket(s) urgente(s)`
        : 'Sem urgencias no momento';

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

    async tickets() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando tickets...');

      const statusFilter = document.getElementById('ticketStatusFilter');
      const list = document.getElementById('adminTicketsList');
      const empty = document.getElementById('adminTicketsEmpty');
      const searchInput = document.getElementById('ticketBusca');
      const badge = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
      };

      const loadTickets = async () => {
        const status = (statusFilter?.value || 'pendente').trim();
        const busca = (searchInput?.value || '').trim();
        const query = new URLSearchParams();
        if (status) query.set('status', status);
        if (busca) query.set('q', busca);
        query.set('per_page', '30');

        const [ticketsResp, statsResp] = await Promise.all([
          api.request('/admin/tickets' + (query.toString() ? `?${query.toString()}` : ''), {}, { notify: false }),
          api.request('/admin/tickets/stats', {}, { notify: false }),
        ]);

        ui.clearPageState();

        const apiTickets = toArray(ticketsResp.data?.data?.data || ticketsResp.data?.data || ticketsResp.data);
        const statsData = statsResp.data?.data || {};
        const hasApiTickets = apiTickets.length > 0;
        const hasStatsData = toNumber(statsData.total, statsData.pendentes, statsData.resolvidos) > 0;
        const tickets = hasApiTickets ? apiTickets : DEMO.admin.tickets;
        const stats = hasStatsData || hasApiTickets ? statsData : DEMO.admin.ticketStats;

        badge('ticketTotal', Number(toNumber(stats.total, tickets.length)).toLocaleString('pt-BR'));
        badge('ticketPendentes', Number(toNumber(stats.pendentes, tickets.filter((t) => (t.status || '').toLowerCase() === 'pendente').length)).toLocaleString('pt-BR'));
        badge('ticketResolvidos', Number(toNumber(stats.resolvidos, tickets.filter((t) => (t.status || '').toLowerCase() === 'resolvido').length)).toLocaleString('pt-BR'));
        badge('ticketUrgentes', Number(toNumber(stats.urgentes, tickets.filter((t) => (t.priority || '').toLowerCase() === 'alta').length)).toLocaleString('pt-BR'));

        if (!list) return;
        list.innerHTML = '';

        if (!tickets.length) {
          empty?.classList.remove('hidden');
          return;
        }
        empty?.classList.add('hidden');

        tickets.forEach((ticket) => {
          const createdAt = ticket?.created_at ? new Date(ticket.created_at).toLocaleString('pt-BR') : '--';
          const status = (ticket?.status || (ticket?.read_at ? 'resolvido' : 'pendente')).toLowerCase();
          const prioridade = (ticket?.priority || 'media').toLowerCase();
          const chipStatus = status === 'resolvido'
            ? 'bg-emerald-100 text-emerald-700'
            : 'bg-amber-100 text-amber-700';
          const chipPrioridade = prioridade === 'alta'
            ? 'bg-rose-100 text-rose-700'
            : prioridade === 'baixa'
              ? 'bg-slate-100 text-slate-600'
              : 'bg-indigo-100 text-indigo-700';

          const row = document.createElement('div');
          row.className = 'p-4 rounded-xl bg-surface-container-low flex flex-col md:flex-row md:items-center md:justify-between gap-3';
          row.dataset.ticketId = ticket.id;
          row.innerHTML = `
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="text-sm font-bold text-on-surface">${safeText(ticket.title, 'Ticket')}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${chipStatus}">${status}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${chipPrioridade}">${prioridade}</span>
              </div>
              <p class="text-xs text-on-surface-variant">${safeText(ticket.message, '')}</p>
              <p class="text-[10px] text-outline mt-1">
                ${safeText(ticket.user?.name || ticket.user_name, 'Sistema')} · ${safeText(ticket.user?.email || '', '')} · ${createdAt}
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button class="ticket-resolver px-3 py-1.5 rounded-lg bg-tertiary text-on-tertiary text-xs font-semibold hover:opacity-90 ${status === 'resolvido' ? 'hidden' : ''}" data-id="${ticket.id}">Resolver</button>
              <button class="ticket-reabrir px-3 py-1.5 rounded-lg bg-secondary text-on-secondary text-xs font-semibold hover:opacity-90 ${status === 'pendente' ? 'hidden' : ''}" data-id="${ticket.id}">Reabrir</button>
              <button class="ticket-fechar px-3 py-1.5 rounded-lg bg-error-container text-on-error-container text-xs font-semibold hover:opacity-90" data-id="${ticket.id}">Fechar</button>
            </div>`;
          list.appendChild(row);
        });

        list.querySelectorAll('.ticket-resolver').forEach((btn) => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            if (`${id}`.startsWith('demo-ticket-')) {
              btn.closest('[data-ticket-id]')?.remove();
              ui.message('Ticket demo marcado como resolvido.', 'success');
              return;
            }
            const { res, data } = await api.request(`/admin/tickets/${id}/resolve`, { method: 'POST' }, { notify: true });
            if (res.ok && data?.success !== false) {
              ui.message('Ticket resolvido com sucesso.', 'success');
              await loadTickets();
            }
          });
        });

        list.querySelectorAll('.ticket-reabrir').forEach((btn) => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            if (`${id}`.startsWith('demo-ticket-')) {
              ui.message('Ticket demo reaberto.', 'info');
              await loadTickets();
              return;
            }
            const { res, data } = await api.request(`/admin/tickets/${id}/reopen`, { method: 'POST' }, { notify: true });
            if (res.ok && data?.success !== false) {
              ui.message('Ticket reaberto com sucesso.', 'success');
              await loadTickets();
            }
          });
        });

        list.querySelectorAll('.ticket-fechar').forEach((btn) => {
          btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            if (`${id}`.startsWith('demo-ticket-')) {
              btn.closest('[data-ticket-id]')?.remove();
              ui.message('Ticket demo removido.', 'info');
              return;
            }
            const { res, data } = await api.request(`/admin/tickets/${id}`, { method: 'DELETE' }, { notify: true });
            if (res.ok && data?.success !== false) {
              btn.closest('[data-ticket-id]')?.remove();
              ui.message('Ticket encerrado com sucesso.', 'success');
            }
          });
        });
      };

      statusFilter?.addEventListener('change', loadTickets);
      searchInput?.addEventListener('input', () => {
        clearTimeout(searchInput._ticketDebounce);
        searchInput._ticketDebounce = setTimeout(loadTickets, 250);
      });

      await loadTickets();
    },

    async empresas() {
      if (!(await auth.guard(['admin']))) return;
      const listaEl = document.getElementById('estabsLista');
      const vazioEl = document.getElementById('estabsEmpty');
      const resumoEl = document.getElementById('estabsResumo');
      const totalEl = document.getElementById('estabsTotalBadge');
      const buscaEl = document.getElementById('estabBusca');
      const categoriaEl = document.getElementById('estabsCategoriaFilter');
      const statusEl = document.getElementById('estabsStatusFilter');
      const statusMeta = {
        pending: { label: 'Pendente', badge: 'bg-amber-100 text-amber-700', action: 'approve', actionLabel: 'Aprovar', actionClass: 'bg-tertiary/10 text-tertiary', secondaryAction: 'reject', secondaryLabel: 'Rejeitar', secondaryClass: 'bg-error/10 text-error' },
        active: { label: 'Ativa', badge: 'bg-emerald-100 text-emerald-700', action: 'suspend', actionLabel: 'Suspender', actionClass: 'bg-error/10 text-error' },
        suspended: { label: 'Suspensa', badge: 'bg-slate-200 text-slate-700', action: 'approve', actionLabel: 'Reativar', actionClass: 'bg-tertiary/10 text-tertiary' },
        rejected: { label: 'Rejeitada', badge: 'bg-rose-100 text-rose-700', action: 'approve', actionLabel: 'Ativar', actionClass: 'bg-tertiary/10 text-tertiary' },
      };

      const actionEndpoint = (id, action) => {
        if (action === 'suspend') return `/admin/empresas/${id}/suspend`;
        if (action === 'reject') return `/admin/empresas/${id}/reject`;
        return `/admin/empresas/${id}/approve`;
      };

      const statusLabel = (value) => statusMeta[value]?.label || safeText(value, 'Desconhecido');

      const fetchCompanies = async () => {
        const params = new URLSearchParams();
        const termo = (buscaEl?.value || '').trim();
        const categoria = (categoriaEl?.value || 'todas').toLowerCase();
        const status = (statusEl?.value || 'todos').toLowerCase();
        if (termo) params.set('search', termo);
        if (categoria && categoria !== 'todas') params.set('categoria', categoria);
        if (status && status !== 'todos') params.set('status', status);

        ui.setPageState('loading', 'Carregando estabelecimentos...');
        const { res, data } = await api.request(`/admin/empresas${params.toString() ? `?${params.toString()}` : ''}`, {}, { notify: false });
        if (!res.ok || data?.success === false) {
          const fallbackCompanies = admin.enrichCompaniesDataset(DEMO_ADMIN_COMPANIES);
          ui.clearPageState();
          ui.message(data?.message || 'Exibindo estabelecimentos demo enquanto o endpoint admin nao responde.', 'warning');
          return {
            empresas: fallbackCompanies,
            summary: admin.summarizeCompanies(fallbackCompanies),
          };
        }

        ui.clearPageState();
        const payload = data?.data || { empresas: [], summary: null };
        const enrichedCompanies = admin.enrichCompaniesDataset(toArray(payload?.empresas));
        return {
          ...payload,
          empresas: enrichedCompanies,
          summary: payload?.summary || admin.summarizeCompanies(enrichedCompanies),
        };
      };

      const renderLista = async () => {
        const payload = await fetchCompanies();
        if (!payload) return;

        const lista = toArray(payload?.empresas).map((item) => ({
          ...item,
          nome: safeText(item?.nome || item?.nome_fantasia, 'Estabelecimento'),
          categoria: safeText(item?.categoria || item?.ramo || 'Sem categoria', 'Sem categoria'),
          endereco: safeText(item?.endereco, 'Endereco nao informado'),
          telefone: safeText(item?.telefone, '-'),
          whatsapp: safeText(item?.whatsapp, ''),
          email: safeText(item?.email, '-'),
          responsavel: safeText(item?.responsavel, '-'),
          status: safeText(item?.status, 'pending').toLowerCase(),
          ativo: Boolean(item?.ativo),
          publicamente_visivel: Boolean(item?.publicamente_visivel),
          qr_code_ready: Boolean(item?.qr_code_ready),
          logo: safeImage(item?.logo, IMAGE_FALLBACKS.store),
        }));

        if (categoriaEl && !categoriaEl.dataset.bound) {
          const categorias = ['todas', ...new Set(lista.map((e) => (e.categoria || '').toLowerCase()).filter(Boolean))];
          categoriaEl.innerHTML = categorias
            .map((c) => `<option value="${c}">${c === 'todas' ? 'Todas' : c.replace(/(^|\s)\S/g, (m) => m.toUpperCase())}</option>`)
            .join('');
        }
        if (statusEl && !statusEl.dataset.bound) {
          statusEl.innerHTML = ['todos', 'pending', 'active', 'suspended', 'rejected']
            .map((s) => `<option value="${s}">${s === 'todos' ? 'Todos' : statusLabel(s)}</option>`)
            .join('');
        }

        if (listaEl) listaEl.innerHTML = '';
        if (totalEl) totalEl.textContent = `${payload?.summary?.total ?? lista.length}`;
        if (resumoEl) {
          const summary = payload?.summary || {};
          resumoEl.textContent = `Pendentes ${summary.pending ?? 0} | Ativas ${summary.active ?? 0} | Suspensas ${summary.suspended ?? 0} | Rejeitadas ${summary.rejected ?? 0}`;
        }

        if (!lista.length) {
          vazioEl?.classList.remove('hidden');
          return;
        }

        vazioEl?.classList.add('hidden');
        lista.forEach((e) => {
          const meta = statusMeta[e.status] || statusMeta.pending;
          const detailsHref = e.publicamente_visivel
            ? `/detalhe_do_parceiro.html?id=${encodeURIComponent(e.id)}`
            : `/gest_o_de_estabelecimentos.html?empresa=${encodeURIComponent(e.id)}`;
          const detailsLabel = e.publicamente_visivel ? 'Ver perfil' : 'Ver cadastro';
          const card = document.createElement('div');
          card.className = 'bg-surface-container-lowest p-5 rounded-xl flex flex-col md:flex-row gap-6 items-start group hover:bg-surface-container-low transition-all border border-transparent hover:border-primary/10';
          card.innerHTML = /* html */ `
            <div class="relative">
              <div class="w-20 h-20 rounded-full overflow-hidden bg-surface-container shadow-inner">
                <img alt="${e.nome}" class="w-full h-full object-cover" src="${e.logo}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'"/>
              </div>
            </div>
            <div class="flex-1 w-full">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-headline font-bold text-on-surface text-lg">${e.nome}</h3>
                    <span class="px-2 py-1 rounded-full text-[10px] uppercase font-black ${meta.badge}">${meta.label}</span>
                    <span class="px-2 py-1 rounded-full text-[10px] uppercase font-black ${e.publicamente_visivel ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'}">${e.publicamente_visivel ? 'Publica' : 'Oculta'}</span>
                  </div>
                  <p class="text-sm text-outline mt-1">${e.categoria}</p>
                  <p class="text-xs text-on-surface-variant mt-2">Responsavel: <span class="font-bold">${e.responsavel}</span> · ${e.email}</p>
                </div>
                <div class="flex flex-wrap gap-2 text-[10px] uppercase font-bold">
                  <span class="px-2 py-1 rounded-full bg-primary/10 text-primary">QR ${e.qr_code_ready ? 'Pronto' : 'Pendente'}</span>
                  <span class="px-2 py-1 rounded-full bg-surface-container-high text-on-surface-variant">${e.ativo ? 'Ativo' : 'Inativo'}</span>
                </div>
              </div>
              <div class="flex flex-wrap gap-4 mt-3 text-sm text-on-surface-variant">
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="location_on">location_on</span><span>${e.endereco}</span></div>
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="call">call</span><span>${e.telefone}</span></div>
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="chat">chat</span><span>${e.whatsapp || '-'}</span></div>
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <a href="${detailsHref}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-on-primary text-xs font-bold hover:opacity-90 transition-opacity">
                  ${detailsLabel}
                  <span class="material-symbols-outlined text-base" data-icon="chevron_right">chevron_right</span>
                </a>
                <button data-company-action="${meta.action}" data-company-id="${e.id}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg ${meta.actionClass} text-xs font-bold hover:opacity-90 transition-opacity">
                  ${meta.actionLabel}
                </button>
                ${meta.secondaryAction ? `<button data-company-action="${meta.secondaryAction}" data-company-id="${e.id}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg ${meta.secondaryClass} text-xs font-bold hover:opacity-90 transition-opacity">${meta.secondaryLabel}</button>` : ''}
              </div>
            </div>`;

          card.querySelectorAll('a,button').forEach((el) => {
            el.addEventListener('click', async (ev) => {
              ev.stopPropagation();
              if (el.tagName.toLowerCase() !== 'button') return;
              el.disabled = true;
              const action = el.dataset.companyAction;
              const endpoint = actionEndpoint(e.id, action);
              const { res, data } = await api.request(endpoint, { method: 'POST' }, { notify: true });
              if (res.ok && data?.success !== false) {
                ui.message(data?.message || 'Status atualizado.', 'success');
                await renderLista();
              } else {
                el.disabled = false;
              }
            });
          });

          listaEl?.appendChild(card);
        });
      };

      if (buscaEl && !buscaEl.dataset.bound) {
        buscaEl.dataset.bound = '1';
        buscaEl.addEventListener('input', () => {
          clearTimeout(buscaEl._companyDebounce);
          buscaEl._companyDebounce = setTimeout(renderLista, 250);
        });
      }
      if (categoriaEl && !categoriaEl.dataset.bound) {
        categoriaEl.dataset.bound = '1';
        categoriaEl.addEventListener('change', renderLista);
      }
      if (statusEl && !statusEl.dataset.bound) {
        statusEl.dataset.bound = '1';
        statusEl.addEventListener('change', renderLista);
      }

      await renderLista();

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

      const usersDataset = await admin.loadUsersDataset();
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

      if (!tbody.dataset.delegated) {
        tbody.dataset.delegated = '1';
        tbody.addEventListener('click', async (event) => {
          const btn = event.target.closest('[data-user-action]');
          if (!btn) return;

          const tr = btn.closest('tr.data-row');
          if (!tr) return;

          const userId = tr.dataset.userId;
          const userName = tr.dataset.userName || 'Usuario';
          const action = btn.dataset.userAction;
          if (!userId || !action) return;

          if (action === 'toggle-status') {
            const currentStatus = (tr.dataset.userStatus || '').toLowerCase();
            const nextStatus = currentStatus === 'ativo' ? 'bloqueado' : 'ativo';
            const actionLabel = currentStatus === 'ativo' ? 'bloquear' : 'reativar';
            if (!confirm(`Deseja ${actionLabel} a conta de "${userName}"?`)) return;

            const resp = await api.request(`/admin/users/${userId}/status`, {
              method: 'PUT',
              body: JSON.stringify({ status: nextStatus }),
            }, { notify: false });

            if (resp.res?.ok && resp.data?.success !== false) {
              ui.message(resp.data?.message || 'Status atualizado com sucesso.', 'success');
              await admin.usuarios();
            } else {
              ui.message(resp.data?.message || `Nao foi possivel ${actionLabel} a conta.`, 'error');
            }
            return;
          }

          if (action === 'edit-sensitive') {
            const currentCpf = tr.dataset.userCpf || '';
            const currentBirth = tr.dataset.userBirth || '';
            const cpf = prompt(`CPF de ${userName} (opcional):`, currentCpf);
            if (cpf === null) return;
            const birth = prompt(`Data de nascimento de ${userName} (AAAA-MM-DD, opcional):`, currentBirth);
            if (birth === null) return;

            const payload = { motivo: 'Ajuste manual via painel admin' };
            if (cpf.trim()) payload.cpf = cpf.trim();
            if (birth.trim()) payload.data_nascimento = birth.trim();

            if (!payload.cpf && !payload.data_nascimento) {
              ui.message('Nenhuma alteracao informada.', 'info');
              return;
            }

            const resp = await api.request(`/admin/users/${userId}/dados-sensiveis`, {
              method: 'PUT',
              body: JSON.stringify(payload),
            }, { notify: false });

            if (resp.res?.ok && resp.data?.success !== false) {
              ui.message(resp.data?.message || 'Dados atualizados com sucesso.', 'success');
              await admin.usuarios();
            } else {
              ui.message(resp.data?.message || 'Nao foi possivel atualizar os dados do usuario.', 'error');
            }
          }
        });
      }

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
        const resumoText = resumo?.querySelector('p');
        if (resumoText) resumoText.textContent = total ? `Listando ${total} administradores` : 'Nenhum administrador encontrado';
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
          const statusAtual = status === 'Ativo' ? 'ativo' : (status === 'Suspenso' ? 'bloqueado' : 'inativo');
          tr.dataset.userId = u.id;
          tr.dataset.userName = nome;
          tr.dataset.userStatus = statusAtual;
          tr.dataset.userCpf = u.cpf || u.documento || '';
          tr.dataset.userBirth = u.data_nascimento || '';
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
                <button data-user-action="edit-sensitive" class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary-container/20 rounded-lg transition-all" title="Editar dados sensiveis"><span class="material-symbols-outlined text-xl">edit</span></button>
                <button data-user-action="toggle-status" class="p-2 text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-lg transition-all" title="${statusAtual === 'ativo' ? 'Bloquear conta' : 'Reativar conta'}"><span class="material-symbols-outlined text-xl">${statusAtual === 'ativo' ? 'block' : 'check_circle'}</span></button>
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
      document.getElementById('btnCreateCompany')?.addEventListener('click', () => {
        window.location.href = '/criar_conta.html?tipo=empresa&origem=admin';
      });

      bindBusca(admins);
      renderLista(admins);
    },

    async clientesMaster() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando clientes...');
      const usersDataset = await admin.loadUsersDataset();
      
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
        const resumoText = resumo?.querySelector('p');
        if (resumoText) resumoText.textContent = total ? `Exibindo ${total} clientes` : 'Nenhum cliente encontrado';
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

      const usersDataset = await admin.loadUsersDataset();
      const [stats, checkins, empresasResp, adminSummaryResp] = await Promise.all([
        api.request('/admin/dashboard-stats', {}, { notify: false }),
        api.request('/admin/pontos/estatisticas', {}, { notify: false }),
        api.request('/empresas', {}, { requireAuth: false, notify: false }),
        api.request('/admin/relatorios/resumo', {}, { notify: false }),
      ]);
      ui.clearPageState();

      const statsData = stats.data?.data || stats.data || {};
      const adminSummary = adminSummaryResp.data?.data || {};
      const summaryCards = adminSummary?.cards || {};
      const totals = statsData?.totais || {};
      const usersList = usersDataset.ok ? usersDataset.list : [];
      const usersPayload = { total: usersList.length };
      const empresasListApi = toArray(empresasResp.data?.data || empresasResp.data);
      const empresasList = empresasListApi.length ? admin.enrichCompaniesDataset(empresasListApi) : DEMO_ADMIN_COMPANIES;
      const checkData = checkins.data?.data || checkins.data || {};
      const fallbackUsers = usersList.length ? usersList.length : DEMO.admin.totals.usuarios;

      const totalEmpresas = toNumber(summaryCards.total_empresas, totals.empresas, statsData.empresas, statsData.total_empresas, empresasList.length);
      const totalUsuarios = toNumber(totals.usuarios, statsData.usuarios, statsData.total_users, usersPayload.total, usersList.length, fallbackUsers);
      const totalClientes = toNumber(
        summaryCards.total_clientes,
        statsData.clientes,
        usersList.filter((u) => (u?.perfil || u?.role || '').toString().toLowerCase().includes('cliente')).length,
        Math.round(totalUsuarios * 0.76)
      );
      const totalPromocoes = toNumber(summaryCards.total_promocoes, totals.campanhas, statsData.promocoes, statsData.campanhas, DEMO.admin.totals.campanhas);
      const totalResgates = toNumber(summaryCards.total_resgates, totals.resgates, statsData.resgates, DEMO.admin.totals.resgates);
      const totalVinculos = toNumber(summaryCards.total_vinculos_cliente_empresa);
      const totalNotificacoes = toNumber(summaryCards.total_notificacoes);
      const mediaGeralAvaliacoes = Number(toNumber(summaryCards.media_geral_avaliacoes, 0)).toFixed(1);
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
      setText(
        'relCrescimento',
        safeText(
          statsData.crescimento_texto,
          summaryCards.media_geral_avaliacoes !== undefined
            ? `Media geral das avaliacoes: ${mediaGeralAvaliacoes}`
            : 'Dados consolidados dos ultimos 30 dias'
        )
      );

      const renderMetricRows = (containerId, rows, accentClass, emptyText) => {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        if (!rows.length) {
          const empty = document.createElement('p');
          empty.className = 'text-sm text-on-surface-variant';
          empty.textContent = emptyText;
          container.appendChild(empty);
          return;
        }

        rows.forEach(({ label, value }) => {
          const row = document.createElement('div');
          row.className = 'flex items-center justify-between px-4 py-2 rounded-lg bg-surface-container-low';

          const labelEl = document.createElement('span');
          labelEl.className = 'text-sm font-semibold text-on-surface';
          labelEl.textContent = label;

          const valueEl = document.createElement('span');
          valueEl.className = `text-sm font-bold ${accentClass}`;
          valueEl.textContent = value;

          row.appendChild(labelEl);
          row.appendChild(valueEl);
          container.appendChild(row);
        });
      };

      const relStatsList = document.getElementById('relStatsList');
      if (relStatsList) {
        renderMetricRows(
          'relStatsList',
          [
            { label: 'Usuarios', value: Number(totalUsuarios || 0).toLocaleString('pt-BR') },
            { label: 'Clientes', value: Number(totalClientes || 0).toLocaleString('pt-BR') },
            { label: 'Empresas', value: Number(totalEmpresas || 0).toLocaleString('pt-BR') },
            { label: 'Vinculos cliente x empresa', value: Number(totalVinculos || 0).toLocaleString('pt-BR') },
            { label: 'Promocoes', value: Number(totalPromocoes || 0).toLocaleString('pt-BR') },
            { label: 'Resgates', value: Number(totalResgates || 0).toLocaleString('pt-BR') },
            { label: 'Notificacoes', value: Number(totalNotificacoes || 0).toLocaleString('pt-BR') },
            { label: 'Media geral de avaliacoes', value: mediaGeralAvaliacoes },
            { label: 'Volume estimado', value: `R$ ${Number(totalVolume || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },
          ],
          'text-primary',
          'Sem metricas administrativas consolidadas.'
        );
      }

      renderMetricRows(
        'relCompaniesStatus',
        [
          { label: 'Pendentes', value: Number(summaryCards.empresas_pending || 0).toLocaleString('pt-BR') },
          { label: 'Ativas', value: Number(summaryCards.empresas_active || 0).toLocaleString('pt-BR') },
          { label: 'Suspensas', value: Number(summaryCards.empresas_suspended || 0).toLocaleString('pt-BR') },
          { label: 'Rejeitadas', value: Number(summaryCards.empresas_rejected || 0).toLocaleString('pt-BR') },
        ],
        'text-secondary',
        'Resumo de status indisponivel.'
      );

      renderMetricRows(
        'relTopCompaniesClients',
        toArray(adminSummary.empresas_com_mais_clientes).map((item) => ({
          label: safeText(item?.nome, 'Empresa'),
          value: `${Number(item?.total_clientes || 0).toLocaleString('pt-BR')} cliente(s)`,
        })),
        'text-secondary',
        'Nenhum ranking de clientes disponivel.'
      );

      renderMetricRows(
        'relTopCompaniesRedemptions',
        toArray(adminSummary.empresas_com_mais_resgates).map((item) => ({
          label: safeText(item?.nome, 'Empresa'),
          value: `${Number(item?.total_resgates || 0).toLocaleString('pt-BR')} resgate(s)`,
        })),
        'text-tertiary',
        'Nenhum ranking de resgates disponivel.'
      );

      const relCheckinsList = document.getElementById('relCheckinsList');
      if (relCheckinsList) {
        relCheckinsList.innerHTML = '';
        const entries = Object.entries(checkData || {}).filter(([, value]) => Number.isFinite(Number(value)));
        const usingFallbackStats = !entries.length;
        const statsEntries = usingFallbackStats
          ? Object.entries(DEMO.admin.reportStats)
          : entries;
        if (usingFallbackStats) {
          const note = document.createElement('p');
          note.className = 'text-xs text-on-surface-variant mb-2';
          note.textContent = 'Exibindo dados consolidados ficticios para demonstracao.';
          relCheckinsList.appendChild(note);
        }
        if (!statsEntries.length) {
          relCheckinsList.innerHTML = '<p class="text-sm text-on-surface-variant">Sem estatisticas de pontos disponiveis.</p>';
        } else {
          statsEntries.forEach(([k, v]) => {
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
        const fallbackInputs = form.querySelectorAll('input');
        const emailEl = form.querySelector('[name="email"], [type="email"], #loginEmail, input[type="text"]');
        const senhaEl = form.querySelector('[name="password"], [name="senha"], [type="password"], #loginPassword');
        const email = (emailEl?.value || fallbackInputs[0]?.value || '').trim();
        const password = senhaEl?.value || fallbackInputs[1]?.value || '';
        if (!email || !password) {
          ui.message('Informe email/CPF e senha.', 'warning');
          return;
        }
        const { res, data } = await api.request(
          '/auth/login',
          { method: 'POST', body: JSON.stringify({ email, password }) },
          { requireAuth: false }
        );
        const payload = data || {};
        const token = payload?.token || payload?.access_token || payload?.data?.token || null;
        const user = auth.normalizeUser(payload?.user || payload?.data?.user || payload?.usuario || null);
        if (!res.ok || !token || !user) {
          ui.message(data?.message || payload?.message || 'Credenciais invalidas.', 'error');
          return;
        }
        auth.save(token, user);
        const perfil = auth.normalizePerfil(user?.perfil || user?.role || user?.tipo);
        const destinos = { admin: '/dashboard_admin_master.html', empresa: '/dashboard_parceiro.html', cliente: '/meus_pontos.html' };
        window.location.href = destinos[perfil] || '/meus_pontos.html';
      });
    },
    vincular_empresa: async () => {
      const params = new URLSearchParams(window.location.search);
      const code = (params.get('code') || '').trim();
      const titleEl = document.getElementById('companyLinkName');
      const statusEl = document.getElementById('companyLinkStatus');
      const messageEl = document.getElementById('companyLinkMessage');
      const primaryBtn = document.getElementById('companyLinkPrimary');
      const secondaryBtn = document.getElementById('companyLinkSecondary');

      if (!code) {
        if (messageEl) messageEl.textContent = 'QR Code da empresa nao informado.';
        return;
      }

      ui.setPageState('loading', 'Validando QR da empresa...');
      const { res, data } = await api.request(`/qrcode/empresa/${encodeURIComponent(code)}`, {}, { requireAuth: false, notify: false });
      ui.clearPageState();

      if (!res.ok || data?.success === false) {
        if (statusEl) statusEl.textContent = 'QR indisponivel';
        if (messageEl) messageEl.textContent = data?.message || 'Nao foi possivel identificar esta empresa.';
        primaryBtn?.addEventListener('click', () => { window.location.href = '/entrar.html'; });
        return;
      }

      const payload = data?.data || {};
      const company = payload.empresa || {};
      if (titleEl) titleEl.textContent = safeText(company.nome, 'Empresa');
      if (statusEl) statusEl.textContent = safeText(company.categoria || company.ramo, 'Empresa');

      const stored = auth.getStored();
      const viewer = auth.normalizeUser(stored?.user);
      const perfil = auth.normalizePerfil(viewer?.perfil || viewer?.role || viewer?.tipo);

      if (!stored?.token || !perfil) {
        setPendingCompanyQr(code);
        if (messageEl) messageEl.textContent = 'Redirecionando para entrar ou criar conta e concluir o vínculo com a empresa.';
        primaryBtn?.addEventListener('click', () => { window.location.href = buildLoginRedirectForCompanyQr(code); });
        secondaryBtn?.addEventListener('click', () => {
          setPendingCompanyQr(code);
          window.location.href = '/criar_conta.html?tipo=cliente';
        });
        setTimeout(() => {
          window.location.href = buildLoginRedirectForCompanyQr(code);
        }, 350);
        return;
      }

      if (perfil !== 'cliente') {
        if (messageEl) messageEl.textContent = 'Apenas clientes podem se vincular a empresas por este fluxo.';
        primaryBtn?.addEventListener('click', () => {
          window.location.href = redirectMap[perfil] || '/meus_pontos.html';
        });
        return;
      }

      if (messageEl) messageEl.textContent = `Vinculando sua conta a ${safeText(company.nome, 'esta empresa')}...`;
      setPendingCompanyQr(code);
      const linkResponse = await api.request('/cliente/vincular-empresa-qrcode', {
        method: 'POST',
        body: JSON.stringify({ code }),
      });
      if (!linkResponse.res.ok || linkResponse.data?.success === false) {
        if (messageEl) messageEl.textContent = linkResponse.data?.message || 'Nao foi possivel concluir o vínculo.';
        primaryBtn?.addEventListener('click', () => { window.location.href = '/meus_pontos.html?ignore_pending_qr=1'; });
        return;
      }

      clearPendingCompanyQr();
      const target = linkResponse.data?.data?.public_page_url || `/detalhe_do_parceiro.html?id=${encodeURIComponent(company.id || '')}`;
      if (messageEl) messageEl.textContent = 'Vínculo concluído. Abrindo a página da empresa...';
      setTimeout(() => {
        window.location.href = target.includes('?') ? `${target}&linked=1` : `${target}?linked=1`;
      }, 350);
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
    'escolher-tipo': async () => {
      // Fluxo publico: manter acesso normal sem redireciono automatico.
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
      const nomeInput = document.getElementById('sgNome');
      const nomeLabel = document.getElementById('sgNomeLabel');
      const cnpj = document.getElementById('sgCnpj');
      const end = document.getElementById('sgEndereco');
      const whatsapp = document.getElementById('sgWhatsApp');
      const categoria = document.getElementById('sgCategoria');
      const nomeFantasia = document.getElementById('sgNomeFantasia');
      const blocoEmpresa = document.getElementById('empresaFields');
      const nascimentoGroup = document.getElementById('sgNascimentoGroup');
      const nascimentoInput = document.getElementById('sgDataNascimento');
      const referralField = document.getElementById('referralField');
      const empresaRestricao = document.getElementById('empresaRestricao');

      const urlParams = new URLSearchParams(window.location.search);
      const tipoParam = urlParams.get('tipo');
      const origem = urlParams.get('origem');
      const isAdminCompanyFlow = origem === 'admin' && tipoParam === 'empresa';

      const loadCategorias = async () => {
        if (!categoria || categoria.dataset.loaded) return;
        categoria.dataset.loaded = '1';
        categoria.innerHTML = '<option value="">Selecione uma categoria</option>';

        const { res, data } = await api.request('/categorias', {}, { requireAuth: false, notify: false });
        const categorias = toArray(data?.data || data?.categorias || [])
          .map((item) => ({
            label: safeText(item?.name || item?.nome, ''),
            value: safeText(item?.slug || item?.name || item?.nome, ''),
          }))
          .filter((item) => item.label && item.value);

        categorias.forEach((item) => {
          const option = document.createElement('option');
          option.value = item.value;
          option.textContent = item.label;
          categoria.appendChild(option);
        });
      };

      const syncPerfilState = async () => {
        const isEmpresa = perfilSel.value === 'empresa';
        blocoEmpresa?.classList.toggle('hidden', !isEmpresa);
        nascimentoGroup?.classList.toggle('hidden', isEmpresa);
        if (nascimentoInput) nascimentoInput.required = !isEmpresa;
        referralField?.classList.toggle('hidden', isEmpresa);
        empresaRestricao?.classList.toggle('hidden', !isEmpresa || isAdminCompanyFlow);
        if (nomeLabel) nomeLabel.textContent = isEmpresa ? 'Responsavel' : 'Nome';
        if (nomeInput) nomeInput.placeholder = isEmpresa ? 'Nome do responsavel' : 'Seu nome completo';
        if (isEmpresa) await loadCategorias();
      };

      if (tipoParam && ['cliente', 'empresa'].includes(tipoParam)) {
        perfilSel.value = tipoParam;
      }

      await syncPerfilState();
      perfilSel?.addEventListener('change', async () => {
        await syncPerfilState();
      });

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const perfil = perfilSel.value;
        const responsavel = nomeInput?.value?.trim() || '';
        const payload = {
          perfil,
          name: responsavel,
          email: document.getElementById('sgEmail').value,
          telefone: document.getElementById('sgTelefone').value,
          data_nascimento: nascimentoInput?.value || '',
          password: document.getElementById('sgSenha').value,
          password_confirmation: document.getElementById('sgSenhaConf').value,
          terms: document.getElementById('sgTerms').checked,
        };

        if (perfil === 'empresa') {
          delete payload.data_nascimento;
          payload.responsavel = responsavel;
          payload.nome_fantasia = nomeFantasia?.value?.trim() || '';
          payload.cnpj = cnpj?.value || '';
          payload.endereco = end?.value || '';
          payload.whatsapp = whatsapp?.value || '';
          payload.categoria = categoria?.value || '';
        }

        const refInput = document.getElementById('sgReferralCode');
        const refFromUrl = urlParams.get('ref');
        const refCode = (refInput?.value || refFromUrl || '').trim().toUpperCase();
        if (perfil === 'cliente' && refCode) payload.referral_code = refCode;

        let endpoint = '/auth/register';
        let requestPayload = payload;
        let requestConfig = { requireAuth: false, notify: false };

        if (isAdminCompanyFlow && perfil === 'empresa') {
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
            responsavel: payload.responsavel || payload.name,
            nome_fantasia: payload.nome_fantasia || '',
            email: payload.email,
            password: payload.password,
            perfil: 'empresa',
            telefone: payload.telefone || null,
            whatsapp: payload.whatsapp || null,
            categoria: payload.categoria || null,
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
          if (perfil === 'empresa' && isAdminCompanyFlow) {
            ui.message('Estabelecimento criado com sucesso!', 'success');
            setTimeout(() => (window.location.href = '/gest_o_de_estabelecimentos.html'), 800);
          } else if (perfil === 'empresa') {
            ui.message(data?.message || 'Solicitacao enviada. Aguarde aprovacao do administrador.', 'success');
            setTimeout(() => (window.location.href = '/entrar.html'), 1200);
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
    tickets_admin_master: admin.tickets,
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
    wireAvatarFallbacks();
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
