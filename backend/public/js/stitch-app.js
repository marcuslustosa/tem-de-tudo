/**
 * Stitch Integration Layer (Tem de Tudo)
 * Objetivo: manter comportamento atual, com codigo mais organizado e claro.
 * Modulos internos: api, auth, ui, render, pages (cliente/empresa/admin/shared).
 */
(function () {
  // ---------------------- Constantes ---------------------- //
  const API_BASE = `${window.location.origin}/api`;
  const STORAGE = {
    token: 'tem_de_tudo_token',
    user: 'tem_de_tudo_user',
    pendingCompanyQr: 'tem_de_tudo_pending_company_qr',
    accessNotice: 'tem_de_tudo_access_notice',
    pushPrompt: 'tem_de_tudo_push_prompt',
  };
  const redirectMap = {
    cliente: '/meus_pontos.html',
    empresa: '/dashboard_parceiro.html',
    admin: '/dashboard_admin_master.html',
    administrador: '/dashboard_admin_master.html',
    revenda: '/revenda_painel.html',
  };
  const page = document.body?.dataset?.page || location.pathname.replace(/\//g, '').replace('.html', '');
  const VAPID_CACHE_KEY = 'vapid_public_key';
  // Placeholders neutros da marca — nunca foto de banco de imagem.
  const IMAGE_FALLBACKS = {
    store: '/img/placeholder-store.svg',
    promo: '/img/placeholder-promo.svg',
    hero: '/img/placeholder-store.svg',
  };

  function safeImage(url, fallback = IMAGE_FALLBACKS.store) {
    if (!url || typeof url !== 'string') return fallback;
    const trimmed = url.trim();
    return trimmed || fallback;
  }

  function decodeMojibake(value) {
    if (value == null) return '';
    let text = String(value);
    const markerRegex = /(?:\u00C3.|\u00C2.|\u00E2[\u0080-\u00BF].|\uFFFD)/g;
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

  // ---------------------------------------------------------------------------
  // Cropper de imagem pr\u00f3prio (canvas) \u2014 sem depend\u00eancia externa/CDN.
  // Abre um modal com reposicionamento (arrastar) + zoom e devolve um Blob
  // j\u00e1 cortado e comprimido, pronto para upload. (Prompt 02 \u2014 padr\u00e3o de imagens)
  // ---------------------------------------------------------------------------
  const tdtImageCropper = (() => {
    function open(opts = {}) {
      const {
        file,
        aspect = 1,
        round = false,
        title = 'Ajustar imagem',
        outputWidth = 800,
        mime = 'image/jpeg',
        quality = 0.85,
      } = opts;

      return new Promise((resolve) => {
        if (!file) { resolve(null); return; }

        const reader = new FileReader();
        reader.onerror = () => resolve(null);
        reader.onload = () => {
          const img = new Image();
          img.onerror = () => resolve(null);
          img.onload = () => build(img);
          img.src = reader.result;
        };
        reader.readAsDataURL(file);

        function build(img) {
          const stageW = Math.min(400, Math.max(240, window.innerWidth - 80));
          const stageH = Math.round(stageW / aspect);

          const overlay = document.createElement('div');
          overlay.className = 'tdt-crop-overlay';
          overlay.innerHTML = `
            <div class="tdt-crop-dialog" role="dialog" aria-modal="true" aria-label="${title}">
              <p class="tdt-crop-title">${title}</p>
              <div class="tdt-crop-stage" style="height:${stageH}px">
                <canvas width="${stageW}" height="${stageH}"></canvas>
                <div class="tdt-crop-mask ${round ? 'tdt-crop-mask--round' : ''}"></div>
              </div>
              <div class="tdt-crop-controls">
                <span class="material-symbols-outlined" style="color:var(--i9-text-muted)">zoom_out</span>
                <input type="range" min="1" max="3" step="0.01" value="1" aria-label="Zoom" />
                <span class="material-symbols-outlined" style="color:var(--i9-text-muted)">zoom_in</span>
              </div>
              <div class="tdt-crop-footer">
                <button type="button" class="tdt-uploader__btn tdt-uploader__btn--ghost" data-crop-cancel>Cancelar</button>
                <button type="button" class="tdt-uploader__btn tdt-uploader__btn--primary" data-crop-save>Salvar</button>
              </div>
            </div>`;
          document.body.appendChild(overlay);
          const prevOverflow = document.body.style.overflow;
          document.body.style.overflow = 'hidden';

          const canvas = overlay.querySelector('canvas');
          const ctx = canvas.getContext('2d');
          const range = overlay.querySelector('input[type=range]');
          const stage = overlay.querySelector('.tdt-crop-stage');

          const baseScale = Math.max(stageW / img.width, stageH / img.height);
          let zoom = 1;
          const offset = { x: 0, y: 0 };

          const clamp = () => {
            const scale = baseScale * zoom;
            const maxX = Math.max(0, (img.width * scale - stageW) / 2);
            const maxY = Math.max(0, (img.height * scale - stageH) / 2);
            offset.x = Math.min(maxX, Math.max(-maxX, offset.x));
            offset.y = Math.min(maxY, Math.max(-maxY, offset.y));
          };
          const geometry = () => {
            const scale = baseScale * zoom;
            const dispW = img.width * scale;
            const dispH = img.height * scale;
            return { dispW, dispH, dx: (stageW - dispW) / 2 + offset.x, dy: (stageH - dispH) / 2 + offset.y };
          };
          const draw = () => {
            clamp();
            const g = geometry();
            ctx.clearRect(0, 0, stageW, stageH);
            ctx.drawImage(img, g.dx, g.dy, g.dispW, g.dispH);
          };
          draw();

          range.addEventListener('input', () => { zoom = Number(range.value) || 1; draw(); });

          let dragging = false;
          let last = null;
          const onStart = (x, y) => { dragging = true; last = { x, y }; };
          const onMove = (x, y) => {
            if (!dragging) return;
            offset.x += x - last.x;
            offset.y += y - last.y;
            last = { x, y };
            draw();
          };
          const onEnd = () => { dragging = false; };
          const mDown = (e) => onStart(e.clientX, e.clientY);
          const mMove = (e) => onMove(e.clientX, e.clientY);
          const tStart = (e) => { const t = e.touches[0]; if (t) onStart(t.clientX, t.clientY); };
          const tMove = (e) => { const t = e.touches[0]; if (t) onMove(t.clientX, t.clientY); };
          stage.addEventListener('mousedown', mDown);
          window.addEventListener('mousemove', mMove);
          window.addEventListener('mouseup', onEnd);
          stage.addEventListener('touchstart', tStart, { passive: true });
          stage.addEventListener('touchmove', tMove, { passive: true });
          stage.addEventListener('touchend', onEnd);

          let settled = false;
          const cleanup = (result) => {
            if (settled) return;
            settled = true;
            window.removeEventListener('mousemove', mMove);
            window.removeEventListener('mouseup', onEnd);
            overlay.remove();
            document.body.style.overflow = prevOverflow;
            resolve(result);
          };

          overlay.querySelector('[data-crop-cancel]').addEventListener('click', () => cleanup(null));
          overlay.addEventListener('mousedown', (e) => { if (e.target === overlay) cleanup(null); });
          overlay.querySelector('[data-crop-save]').addEventListener('click', () => {
            const outW = Math.round(outputWidth);
            const outH = Math.round(outputWidth / aspect);
            const out = document.createElement('canvas');
            out.width = outW;
            out.height = outH;
            const octx = out.getContext('2d');
            octx.fillStyle = '#ffffff';
            octx.fillRect(0, 0, outW, outH);
            const g = geometry();
            const k = outW / stageW;
            octx.drawImage(img, g.dx * k, g.dy * k, g.dispW * k, g.dispH * k);
            out.toBlob((blob) => cleanup(blob), mime, quality);
          });
        }
      });
    }

    return { open };
  })();

  // Liga um widget de upload de imagem da empresa (logo/banner) aos endpoints.
  // Reutiliza o cropper acima; envia o arquivo j\u00e1 otimizado via multipart.
  function bindEmpresaImageUploader(kind, currentUrl) {
    const root = document.querySelector(`[data-uploader="${kind}"]`);
    if (!root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';

    const input = root.querySelector('[data-uploader-input]');
    const pickBtn = root.querySelector('[data-uploader-pick]');
    const pickLabel = root.querySelector('[data-uploader-picklabel]');
    const removeBtn = root.querySelector('[data-uploader-remove]');
    const imgEl = root.querySelector('[data-uploader-image]');
    const placeholder = root.querySelector('[data-uploader-placeholder]');

    const cfg = kind === 'logo'
      ? { aspect: 1, round: true, outputWidth: 512, title: 'Ajustar logo (1:1)', endpoint: '/empresa/perfil/logo' }
      : { aspect: 16 / 6, round: false, outputWidth: 1280, title: 'Ajustar banner (capa)', endpoint: '/empresa/perfil/banner' };

    const showImage = (url) => {
      if (url && imgEl) {
        imgEl.src = url;
        imgEl.classList.remove('hidden');
        placeholder?.classList.add('hidden');
        removeBtn?.classList.remove('hidden');
        if (pickLabel) pickLabel.textContent = 'Alterar';
      } else {
        imgEl?.removeAttribute('src');
        imgEl?.classList.add('hidden');
        placeholder?.classList.remove('hidden');
        removeBtn?.classList.add('hidden');
        if (pickLabel) pickLabel.textContent = kind === 'logo' ? 'Enviar logo' : 'Enviar banner';
      }
    };
    const setBusy = (busy) => {
      if (pickBtn) pickBtn.disabled = busy;
      if (removeBtn) removeBtn.disabled = busy;
    };

    showImage(currentUrl);

    pickBtn?.addEventListener('click', () => input?.click());
    input?.addEventListener('change', async () => {
      const file = input.files && input.files[0];
      input.value = '';
      if (!file) return;
      if (!/^image\//.test(file.type)) {
        ui.message('Selecione um arquivo de imagem v\u00e1lido.', 'warning');
        return;
      }
      const blob = await tdtImageCropper.open({
        file,
        aspect: cfg.aspect,
        round: cfg.round,
        outputWidth: cfg.outputWidth,
        title: cfg.title,
      });
      if (!blob) return;
      const fd = new FormData();
      fd.append('image', blob, `${kind}.jpg`);
      setBusy(true);
      const { res, data } = await api.request(cfg.endpoint, { method: 'POST', body: fd });
      setBusy(false);
      if (res.ok && data?.success) {
        const url = data.data?.url ? `${data.data.url}?t=${Date.now()}` : currentUrl;
        showImage(url);
        ui.message('Imagem atualizada com sucesso.', 'success');
      } else {
        ui.message(data?.message || 'N\u00e3o foi poss\u00edvel enviar a imagem.', 'error');
      }
    });

    removeBtn?.addEventListener('click', async () => {
      setBusy(true);
      const { res, data } = await api.request(cfg.endpoint, { method: 'DELETE' });
      setBusy(false);
      if (res.ok && data?.success) {
        showImage(null);
        ui.message('Imagem removida.', 'info');
      } else {
        ui.message(data?.message || 'N\u00e3o foi poss\u00edvel remover a imagem.', 'error');
      }
    });
  }

  // Picker de imagem com crop 1:1 e Blob LOCAL (sem upload imediato).
  // Usado nos formul\u00e1rios de b\u00f4nus, que enviam a imagem junto no submit (multipart).
  function createLocalImagePicker(selector, { aspect = 1, outputWidth = 800, title = 'Ajustar imagem' } = {}) {
    const root = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!root) return null;
    const imgEl = root.querySelector('[data-uploader-image]');
    const placeholder = root.querySelector('[data-uploader-placeholder]');
    const pickBtn = root.querySelector('[data-uploader-pick]');
    const pickLabel = root.querySelector('[data-uploader-picklabel]');
    const removeBtn = root.querySelector('[data-uploader-remove]');
    const input = root.querySelector('[data-uploader-input]');

    let blob = null;
    let removed = false;
    let existingUrl = '';

    const paint = (url) => {
      if (url && imgEl) {
        imgEl.src = url;
        imgEl.classList.remove('hidden');
        placeholder?.classList.add('hidden');
        removeBtn?.classList.remove('hidden');
        if (pickLabel) pickLabel.textContent = 'Alterar';
      } else {
        imgEl?.classList.add('hidden');
        imgEl?.removeAttribute('src');
        placeholder?.classList.remove('hidden');
        removeBtn?.classList.add('hidden');
        if (pickLabel) pickLabel.textContent = 'Enviar imagem';
      }
    };

    pickBtn?.addEventListener('click', () => input?.click());
    input?.addEventListener('change', async () => {
      const file = input.files && input.files[0];
      input.value = '';
      if (!file) return;
      if (!/^image\//.test(file.type)) { ui.message('Selecione uma imagem v\u00e1lida.', 'warning'); return; }
      const out = await tdtImageCropper.open({ file, aspect, round: false, outputWidth, title });
      if (!out) return;
      blob = out;
      removed = false;
      paint(URL.createObjectURL(out));
    });
    removeBtn?.addEventListener('click', () => {
      blob = null;
      removed = true;
      existingUrl = '';
      paint('');
    });

    return {
      get blob() { return blob; },
      get removed() { return removed; },
      get existingUrl() { return existingUrl; },
      hasImage() { return Boolean(blob || (existingUrl && !removed)); },
      setExisting(url) {
        existingUrl = url || '';
        blob = null;
        removed = false;
        paint(existingUrl ? safeImage(existingUrl, IMAGE_FALLBACKS.promo) : '');
      },
      reset() {
        existingUrl = '';
        blob = null;
        removed = false;
        paint('');
      },
      previewUrl() {
        return blob ? URL.createObjectURL(blob) : (existingUrl && !removed ? safeImage(existingUrl, IMAGE_FALLBACKS.promo) : '');
      },
      appendTo(fd) {
        if (blob) fd.append('imagem', blob, 'bonus.jpg');
        else if (existingUrl && !removed) fd.append('imagem_url', existingUrl);
        if (removed) fd.append('remover_imagem', '1');
      },
    };
  }

  // Modal automático do bônus de adesão (aparece ao entrar na empresa quando disponível).
  // Suporta "não mostrar novamente" (localStorage) e resgate presencial via QR.
  // `celebrar`: cadastro/vínculo recém-concluído — tom de "você acabou de ganhar".
  function tdtShowBonusModal({ empresaId, bonusId, titulo, descricao, imagem, validade, corMarca, celebrar } = {}) {
    const brand = corMarca || '#133F8C';
    const overlay = document.createElement('div');
    overlay.className = 'tdt-modal-overlay';
    overlay.innerHTML = `
      <div class="tdt-modal-dialog tdt-bonus-modal text-center">
        <button type="button" class="tdt-bonus-modal__close" data-close aria-label="Fechar"><span class="material-symbols-outlined">close</span></button>
        <div class="tdt-bonus-modal__burst" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="tdt-bonus-modal__badge">
          <img src="${imagem}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" alt="" />
        </div>
        <p class="tdt-bonus-modal__kicker">${celebrar ? 'Você acabou de ganhar!' : 'Bônus de adesão'}</p>
        <h3 class="tdt-bonus-modal__title">${safeText(titulo, 'Bônus de adesão')}</h3>
        <p class="tdt-bonus-modal__desc">${safeText(descricao, 'Você ganhou um benefício de boas-vindas!')}</p>
        <div class="mt-6 flex flex-col gap-2">
          <button type="button" class="loyalty-redeem-btn" data-redeem style="background:linear-gradient(135deg,${brand} 0%,#b01774 100%)"><span class="material-symbols-outlined">redeem</span> Resgatar agora</button>
          <button type="button" class="app-secondary-button justify-center" data-nevermore>${celebrar ? 'Deixar para depois' : 'Não ver mais'}</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const dismiss = () => {
      overlay.remove();
      document.body.style.overflow = prevOverflow;
    };
    // "Nao ver mais": nunca mais mostra o popup desta empresa.
    // Em celebracao de cadastro novo, apenas fecha (sem silenciar para sempre).
    const nevermore = () => {
      if (empresaId && !celebrar) {
        try { localStorage.setItem(`tdt_bonus_adesao_hide_${empresaId}`, '1'); } catch (_) { /* ignore */ }
      }
      dismiss();
    };
    overlay.querySelector('[data-close]')?.addEventListener('click', dismiss);
    overlay.querySelector('[data-nevermore]')?.addEventListener('click', nevermore);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) dismiss(); });
    // "OK, resgatar": resgata o bonus na hora (cliente escaneou/entrou na empresa).
    overlay.querySelector('[data-redeem]')?.addEventListener('click', async (ev) => {
      const btn = ev.currentTarget;
      if (!bonusId) { dismiss(); return; }
      if (btn.dataset.loading === '1') return;
      btn.dataset.loading = '1';
      btn.disabled = true;
      const prev = btn.innerHTML;
      btn.textContent = 'Resgatando...';
      try {
        const { res, data } = await api.request(`/cliente/bonus-adesao/${bonusId}/resgatar`, { method: 'POST' }, { notify: false });
        if (res.ok && data?.success !== false) {
          dismiss();
          // Sai da página da empresa: comprovante em tela cheia para mostrar no balcão.
          window.location.href = `/bonus_resgatado.html?empresa=${encodeURIComponent(empresaId || '')}&bonus=${encodeURIComponent(bonusId || '')}`;
          return;
        }
        ui.message(data?.message || 'Não foi possível resgatar o bônus agora.', 'error');
      } catch (_) {
        ui.message('Falha de conexão ao resgatar. Tente novamente.', 'error');
      }
      btn.disabled = false;
      btn.innerHTML = prev;
      btn.dataset.loading = '0';
    });
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

  // "2 empresas", "1 cartão" — nunca "2 empresa(s)".
  function plural(count, singular, pluralWord) {
    const n = Number(count) || 0;
    return `${n.toLocaleString('pt-BR')} ${n === 1 ? singular : (pluralWord || `${singular}s`)}`;
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

  function currentRelativeUrl() {
    const value = `${window.location.pathname || '/'}${window.location.search || ''}${window.location.hash || ''}`;

    return value.startsWith('/') ? value : `/${value}`;
  }

  function buildLoginRedirect(next = currentRelativeUrl()) {
    const safeNext = typeof next === 'string' && next.startsWith('/') && !next.startsWith('//')
      ? next
      : '/meus_pontos.html';

    return `/entrar.html?next=${encodeURIComponent(safeNext)}`;
  }

  function saveAccessNotice(message, tone = 'warning') {
    if (!message) return;
    try {
      sessionStorage.setItem(STORAGE.accessNotice, JSON.stringify({ message, tone }));
    } catch (err) {
      console.warn('Nao foi possivel salvar aviso temporario de acesso.', err?.message || err);
    }
  }

  function consumeAccessNotice() {
    try {
      const raw = sessionStorage.getItem(STORAGE.accessNotice);
      if (!raw) return;
      sessionStorage.removeItem(STORAGE.accessNotice);
      const payload = JSON.parse(raw);
      if (payload?.message) {
        ui.message(payload.message, payload.tone || 'warning');
      }
    } catch (err) {
      sessionStorage.removeItem(STORAGE.accessNotice);
      console.warn('Nao foi possivel consumir aviso temporario de acesso.', err?.message || err);
    }
  }

  function savePushPrompt(reason = 'login') {
    try {
      sessionStorage.setItem(STORAGE.pushPrompt, safeText(reason, 'login') || 'login');
    } catch (err) {
      console.warn('Nao foi possivel registrar o prompt de push.', err?.message || err);
    }
  }

  function getPushPrompt() {
    try {
      return safeText(sessionStorage.getItem(STORAGE.pushPrompt) || '', '');
    } catch (err) {
      console.warn('Nao foi possivel ler o prompt de push.', err?.message || err);
      return '';
    }
  }

  function clearPushPrompt() {
    try {
      sessionStorage.removeItem(STORAGE.pushPrompt);
    } catch (err) {
      console.warn('Nao foi possivel limpar o prompt de push.', err?.message || err);
    }
  }

  function redirectToLogin(message = 'Faça login para continuar.', next = currentRelativeUrl()) {
    saveAccessNotice(message, 'warning');
    window.location.href = buildLoginRedirect(next);
  }

  function resolvePostLoginTarget(perfil) {
    const next = new URLSearchParams(window.location.search).get('next');
    if (next && next.startsWith('/') && !next.startsWith('//')) {
      return next;
    }

    return redirectMap[perfil] || '/meus_pontos.html';
  }

  function restrictedAreaMessage(perfis = []) {
    if (perfis.includes('empresa')) return 'Acesso restrito a estabelecimentos.';
    if (perfis.includes('admin')) return 'Acesso restrito ao painel administrativo.';
    if (perfis.includes('cliente')) return 'Acesso restrito a clientes autenticados.';

    return 'Acesso restrito a este perfil.';
  }

  function resolveCompanyQrRedirect(perfil) {
    const pendingCode = getPendingCompanyQr();
    if (!pendingCode || perfil !== 'cliente') return null;
    return buildCompanyLinkPageUrl(pendingCode);
  }

  function isCompanyAccessError(res, data) {
    if (!res || ![403, 404].includes(Number(res.status || 0))) return false;
    const error = String(data?.error || '').trim();
    const message = String(data?.message || '').trim();

    return (
      ['company_not_linked', 'company_not_found', 'company_status_blocked', 'subscription_blocked'].includes(error)
      || (res.status === 404 && /empresa nao encontrada/i.test(message))
    );
  }

  function companyAccessMessage(data, fallback = 'Acesso operacional indisponível para esta empresa.') {
    const error = String(data?.error || '').trim();
    if (error === 'company_not_linked') {
      return 'Sua conta de empresa ainda nao esta vinculada a um estabelecimento ativo.';
    }
    if (error === 'company_not_found') {
      return 'Nao encontramos um estabelecimento ativo vinculado a este login.';
    }
    if (error === 'company_status_blocked') {
      return data?.message || 'Esta empresa ainda nao pode operar nesta etapa.';
    }
    if (error === 'subscription_blocked') {
      return data?.message || 'A operação desta empresa está temporariamente bloqueada.';
    }
    if (typeof data?.message === 'string' && data.message.trim()) {
      return data.message.trim();
    }

    return fallback;
  }

  function handleCompanyAccessFailure(res, data, fallbackMessage, feedbackEl = null) {
    if (!isCompanyAccessError(res, data)) return false;

    const message = companyAccessMessage(data, fallbackMessage);
    const stateType = String(data?.error || '').trim() === 'subscription_blocked' ? 'error' : 'empty';
    ui.setPageState(stateType, message);
    if (feedbackEl) {
      setInlineFeedback(feedbackEl, message, stateType === 'error' ? 'error' : 'warning');
    }

    return true;
  }

  function renderStars(rating = 0) {
    const total = 5;
    const normalized = Math.max(0, Math.min(5, Number(rating || 0)));
    return Array.from({ length: total }, (_, index) => (index < Math.round(normalized) ? '★' : '☆')).join('');
  }

  function formatDatePtBr(value, fallback = 'Não informada') {
    if (!value) return fallback;
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return fallback;
    return parsed.toLocaleDateString('pt-BR');
  }

  function toDateInputValue(value) {
    if (!value) return '';
    const raw = String(value).trim();
    const isoDate = raw.match(/^\d{4}-\d{2}-\d{2}/);
    if (isoDate) return isoDate[0];
    const parsed = new Date(raw);
    if (Number.isNaN(parsed.getTime())) return '';
    const year = parsed.getFullYear();
    const month = String(parsed.getMonth() + 1).padStart(2, '0');
    const day = String(parsed.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function formatDateRangePtBr(start, end, fallback = 'Não informada') {
    if (!start && !end) return fallback;
    const startLabel = formatDatePtBr(start, '');
    const endLabel = formatDatePtBr(end, '');
    if (startLabel && endLabel) return `${startLabel} até ${endLabel}`;
    return startLabel || endLabel || fallback;
  }

  function bonusStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'available':
        return {
          label: 'Disponível',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Toque em resgatar para usar o benefício.',
        };
      case 'redeemed':
        return {
          label: 'Já utilizado',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Este bônus de adesão já foi utilizado.',
        };
      case 'expired':
        return {
          label: 'Expirado',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'O bônus de adesão desta empresa expirou.',
        };
      case 'not_linked':
        return {
          label: 'Vincule-se',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Leia o QR Code da empresa para liberar o bônus de adesão.',
        };
      default:
        return {
          label: 'Indisponível',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhum bônus de adesão ativo no momento.',
        };
    }
  }

  function loyaltyStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'reward_available':
        return {
          label: 'Recompensa liberada',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Cliente já pode resgatar a recompensa no estabelecimento.',
        };
      case 'available':
        return {
          label: 'Acumulando',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Registre a visita para somar pontos de fidelidade.',
        };
      case 'not_linked':
        return {
          label: 'Vincule-se',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Leia o QR Code da empresa para liberar o progresso individual.',
        };
      case 'expired':
        return {
          label: 'Expirado',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'O cartão fidelidade desta empresa expirou.',
        };
      case 'inactive':
        return {
          label: 'Inativo',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'A empresa ainda não está operando este cartão no momento.',
        };
      default:
        return {
          label: 'Indisponível',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhum cartão fidelidade ativo no momento.',
        };
    }
  }

  function promotionStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'available':
        return {
          label: 'Disponível',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Toque em resgatar para usar.',
        };
      case 'redeemed':
        return {
          label: 'Já utilizada',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Esta promoção já foi validada para este cliente.',
        };
      case 'not_linked':
        return {
          label: 'Vincule-se',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Vincule-se à empresa para ficar elegível a esta promoção.',
        };
      case 'expired':
        return {
          label: 'Expirada',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'A promoção expirou e não pode mais ser validada.',
        };
      case 'inactive':
        return {
          label: 'Inativa',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'A empresa pausou esta promoção no momento.',
        };
      case 'public':
        return {
          label: 'Pública',
          badgeClass: 'bg-fuchsia-50 text-fuchsia-700',
          message: 'Entre como cliente e leia o QR da empresa para resgatar.',
        };
      default:
        return {
          label: 'Indisponível',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhuma promoção ativa no momento.',
        };
    }
  }

  function birthdayBonusStatusMeta(status) {
    switch (String(status || '').toLowerCase()) {
      case 'available':
        return {
          label: 'Elegível',
          badgeClass: 'bg-emerald-50 text-emerald-700',
          message: 'Toque em resgatar para usar o bônus de aniversário.',
        };
      case 'redeemed':
        return {
          label: 'Já utilizado',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Este bônus aniversário já foi utilizado neste ano.',
        };
      case 'not_linked':
        return {
          label: 'Vincule-se',
          badgeClass: 'bg-blue-50 text-blue-700',
          message: 'Vincule-se à empresa para liberar o bônus aniversário.',
        };
      case 'missing_birth_date':
        return {
          label: 'Atualize seu cadastro',
          badgeClass: 'bg-amber-50 text-amber-700',
          message: 'Informe sua data de nascimento para a empresa liberar o bônus aniversário.',
        };
      case 'out_of_window':
        return {
          label: 'Fora da janela',
          badgeClass: 'bg-fuchsia-50 text-fuchsia-700',
          message: 'Este bônus aparece apenas no mês do aniversário ou na janela configurada pela empresa.',
        };
      case 'public':
        return {
          label: 'Consulte no app',
          badgeClass: 'bg-sky-50 text-sky-700',
          message: 'Entre como cliente vinculado para verificar sua elegibilidade ao bônus aniversário.',
        };
      case 'inactive':
        return {
          label: 'Inativo',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'A empresa não está operando bônus aniversário no momento.',
        };
      default:
        return {
          label: 'Indisponível',
          badgeClass: 'bg-slate-100 text-slate-600',
          message: 'Nenhum bônus aniversário ativo no momento.',
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
      const spinner = type === 'loading'
        ? '<span class="ui-spinner" style="width:18px;height:18px;border-width:2px;"></span>'
        : '';
      pageStateEl.innerHTML = `<div class="border ${palette[type] || palette.info} rounded-xl px-4 py-3 shadow-sm text-sm flex items-center gap-2">${spinner}<span></span></div>`;
      const label = pageStateEl.querySelector('span:last-child');
      if (label) label.textContent = message;
    }

    function clearPageState() {
      if (pageStateEl) pageStateEl.remove();
      pageStateEl = null;
    }

    // Toast padronizado do design system: posicao fixa, icone por variante,
    // fade+slide. Uma unica fonte para todos os avisos do sistema.
    function message(text, variant = 'info') {
      const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
      const v = icons[variant] ? variant : 'info';
      let container = document.getElementById('ui-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'ui-toast-container';
        container.className = 'ui-toast-container';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
      }
      const toast = document.createElement('div');
      toast.className = `ui-toast ui-toast--${v}`;
      toast.setAttribute('role', v === 'error' ? 'alert' : 'status');
      const icon = document.createElement('span');
      icon.className = 'material-symbols-outlined ui-toast__icon';
      icon.textContent = icons[v];
      const span = document.createElement('span');
      span.className = 'ui-toast__text';
      span.textContent = text;
      toast.append(icon, span);
      container.appendChild(toast);
      const remove = () => {
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 200);
      };
      const timer = setTimeout(remove, 5000);
      toast.addEventListener('click', () => { clearTimeout(timer); remove(); });
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
      saveAccessNotice('Sessao encerrada.', 'info');
      window.location.href = '/entrar.html';
    };

    const ensure = async () => {
      const stored = getStored();
      const token = (stored?.token || '').trim();
      const storedUser = normalizeUser(stored.user);
      if (!token) {
        clear();
        redirectToLogin('Faça login para continuar.');
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

      if (res.status === 401) {
        clear();
        redirectToLogin('Sessao expirada. Faça login novamente.');
        return null;
      }
      if (storedUser && storedUser.perfil) {
        userCache = storedUser;
        validatedToken = null;
        return userCache;
      }
      console.warn('Sessao nao validada por /auth/me; sem dados de usuario no storage.');
      clear();
      redirectToLogin('Faça login para continuar.');
      return null;
    };

    const guard = async (perfis = []) => {
      const user = await ensure();
      if (!user) return false;
      const perfil = normalizePerfil(user.perfil || user.role || user.tipo);
      if (perfis.length && !perfis.includes(perfil)) {
        saveAccessNotice(restrictedAreaMessage(perfis), 'warning');
        window.location.href = redirectMap[perfil] || buildLoginRedirect();
        return false;
      }
      return true;
    };

    return { getStored, save, clear, logout, ensure, guard, normalizeUser, normalizePerfil };
  })();

  // ---------------------- Navegacao de fallback ---------------------- //
  function getStoredScope() {
    const storedUser = auth.normalizeUser(auth.getStored()?.user);
    const perfil = auth.normalizePerfil(storedUser?.perfil || storedUser?.role || storedUser?.tipo);
    if (perfil === 'admin') return 'admin';
    if (perfil === 'empresa') return 'empresa';
    return 'cliente';
  }

  function getScopeForCurrentPage() {
    const pageGroups = {
      admin: ['dashboard_admin_master', 'gest_o_de_estabelecimentos', 'gest_o_de_usu_rios_master', 'gest_o_de_clientes_master', 'relat_rios_gerais_master', 'tickets_admin_master', 'banners_e_categorias_master', 'configuracoes_admin'],
      empresa: ['dashboard_parceiro', 'gest_o_de_ofertas_parceiro', 'minhas_campanhas_loja', 'clientes_fidelizados_loja'],
      cliente: ['meus_pontos', 'parceiros_tem_de_tudo', 'detalhe_do_parceiro', 'recompensas', 'hist_rico_de_uso', 'meu_perfil', 'validar_resgate', 'configuracoes_cliente'],
    };

    if (page === 'meu_perfil' || page === 'validar_resgate') {
      return getStoredScope();
    }

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
        settings: commonProfile,
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
    if (text.includes('configur')) return scope === 'admin' ? '/configuracoes_admin.html' : '/meu_perfil.html';
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
    push.mountCards();
  }

  // ---- Instalar app (PWA): botao nativo no Android + guia no iPhone ----
  function mountInstallPrompt() {
    try {
      const isStandalone = window.matchMedia?.('(display-mode: standalone)')?.matches
        || window.navigator.standalone === true;

      let deferredPrompt = null;

      // Android/Chrome/Edge: captura a porta oficial de instalacao SEM mostrar
      // nada na tela (nada de botao flutuante que fica piscando). O usuario
      // aciona clicando em "Instalar app" no menu "Mais".
      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
      });

      window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        try { ui.message('App instalado! Abra pelo icone na tela de inicio.', 'success'); } catch (_) { /* silencioso */ }
      });

      const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

      // Passos ilustrados por plataforma. No iPhone o navegador não permite
      // instalar com 1 toque (limitação da Apple); no Android sem prompt
      // nativo, mostramos o caminho do menu do Chrome.
      const iosSteps = `
              <li>
                <span class="tdt-install-sheet__num">1</span>
                <div class="tdt-install-step">
                  <p>Toque em <b>Compartilhar</b> na barra do navegador.</p>
                  <div class="tdt-install-step__mock tdt-install-step__mock--bar" aria-hidden="true">
                    <span class="material-symbols-outlined">arrow_back_ios_new</span>
                    <span class="tdt-install-step__url">temdetudo.app</span>
                    <span class="tdt-install-step__target"><span class="material-symbols-outlined">ios_share</span></span>
                    <span class="material-symbols-outlined">content_copy</span>
                  </div>
                </div>
              </li>
              <li>
                <span class="tdt-install-sheet__num">2</span>
                <div class="tdt-install-step">
                  <p>Role o menu e toque em <b>Adicionar à Tela de Início</b>.</p>
                  <div class="tdt-install-step__mock tdt-install-step__mock--menu" aria-hidden="true">
                    <span class="tdt-install-step__menu-row"><span>Copiar</span><span class="material-symbols-outlined">content_copy</span></span>
                    <span class="tdt-install-step__menu-row tdt-install-step__menu-row--hot"><span>Adicionar à Tela de Início</span><span class="material-symbols-outlined">add_box</span></span>
                  </div>
                </div>
              </li>
              <li>
                <span class="tdt-install-sheet__num">3</span>
                <div class="tdt-install-step">
                  <p>Confirme tocando em <b>Adicionar</b> no canto superior.</p>
                  <div class="tdt-install-step__mock tdt-install-step__mock--confirm" aria-hidden="true">
                    <span>Cancelar</span>
                    <span class="tdt-install-step__confirm">Adicionar</span>
                  </div>
                </div>
              </li>`;
      const androidSteps = `
              <li>
                <span class="tdt-install-sheet__num">1</span>
                <div class="tdt-install-step">
                  <p>Toque no menu <b>⋮</b> no canto superior do Chrome.</p>
                  <div class="tdt-install-step__mock tdt-install-step__mock--bar" aria-hidden="true">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span class="tdt-install-step__url">temdetudo.app</span>
                    <span class="tdt-install-step__target"><span class="material-symbols-outlined">more_vert</span></span>
                  </div>
                </div>
              </li>
              <li>
                <span class="tdt-install-sheet__num">2</span>
                <div class="tdt-install-step">
                  <p>Toque em <b>Adicionar à tela inicial</b> (ou <b>Instalar app</b>).</p>
                  <div class="tdt-install-step__mock tdt-install-step__mock--menu" aria-hidden="true">
                    <span class="tdt-install-step__menu-row"><span>Nova guia</span><span class="material-symbols-outlined">add</span></span>
                    <span class="tdt-install-step__menu-row tdt-install-step__menu-row--hot"><span>Adicionar à tela inicial</span><span class="material-symbols-outlined">install_mobile</span></span>
                  </div>
                </div>
              </li>
              <li>
                <span class="tdt-install-sheet__num">3</span>
                <div class="tdt-install-step">
                  <p>Confirme em <b>Instalar</b>.</p>
                  <div class="tdt-install-step__mock tdt-install-step__mock--confirm" aria-hidden="true">
                    <span>Cancelar</span>
                    <span class="tdt-install-step__confirm">Instalar</span>
                  </div>
                </div>
              </li>`;

      let installSheet = null;
      const openInstallGuide = () => {
        if (installSheet) { installSheet.classList.add('is-open'); return; }
        installSheet = document.createElement('div');
        installSheet.className = 'tdt-install-sheet-overlay is-open';
        installSheet.innerHTML = `
          <div class="tdt-install-sheet" role="dialog" aria-label="Como instalar o app">
            <button type="button" class="tdt-install-sheet__close" data-close aria-label="Fechar">
              <span class="material-symbols-outlined">close</span>
            </button>
            <div class="tdt-install-sheet__head">
              <img src="/img/icon-192.png" alt="" class="tdt-install-sheet__icon" onerror="this.style.display='none'" />
              <div>
                <p class="tdt-install-sheet__title">Instalar o Tem de Tudo</p>
                <p class="tdt-install-sheet__sub">Fica igual a um app, com atalho na tela de início.</p>
              </div>
            </div>
            <ol class="tdt-install-sheet__steps tdt-install-sheet__steps--visual">${isIOS ? iosSteps : androidSteps}</ol>
            <p class="tdt-install-sheet__foot">Depois <b>abra pelo ícone</b> na tela de início para conseguir <b>ativar as notificações</b>.</p>
            <button type="button" class="tdt-install-sheet__ok" data-close>Entendi</button>
          </div>`;
        document.body.appendChild(installSheet);
        const close = () => installSheet.classList.remove('is-open');
        installSheet.querySelectorAll('[data-close]').forEach((el) => el.addEventListener('click', close));
        installSheet.addEventListener('click', (event) => { if (event.target === installSheet) close(); });
      };

      // Acionada quando a pessoa CLICA no botao "Instalar o aplicativo".
      window.tdtInstallApp = async () => {
        if (deferredPrompt) {
          // Android: 1 toque — abre a caixa nativa de instalacao.
          deferredPrompt.prompt();
          await deferredPrompt.userChoice.catch(() => null);
          deferredPrompt = null;
          return;
        }
        // Sem porta nativa: guia ilustrado da plataforma.
        openInstallGuide();
      };

      // Card VISIVEL e fixo no topo do dashboard de cada perfil (nao flutua,
      // nao pisca). So aparece se o app ainda NAO estiver instalado.
      const dashboards = ['meus_pontos', 'dashboard_parceiro', 'dashboard_admin_master', 'revenda_painel'];
      if (!isStandalone && dashboards.includes(page)) {
        const header = document.querySelector('header');
        if (header && !document.getElementById('tdtInstallBar')) {
          const bar = document.createElement('div');
          bar.id = 'tdtInstallBar';
          bar.className = 'tdt-install-bar';
          bar.innerHTML = `
            <button type="button" class="tdt-install-bar__btn">
              <span class="tdt-install-bar__icon"><span class="material-symbols-outlined">install_mobile</span></span>
              <span class="tdt-install-bar__text"><b>Instalar o aplicativo</b><small>Atalho na tela de início e notificações</small></span>
              <span class="material-symbols-outlined tdt-install-bar__chevron">chevron_right</span>
            </button>`;
          header.insertAdjacentElement('afterend', bar);
          bar.querySelector('button')?.addEventListener('click', () => window.tdtInstallApp());
        }
      }
    } catch (_) {
      /* instalacao e melhoria progressiva: nunca deve quebrar a pagina */
    }
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
    const target = scope === 'admin' ? '/configuracoes_admin.html' : (scope === 'empresa' ? '/meu_perfil.html' : '/configuracoes_cliente.html');

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

  function mountPageBackButton() {
    const scope = getScopeForCurrentPage();
    const fallbackByPage = {
      detalhe_do_parceiro: '/parceiros_tem_de_tudo.html',
      gest_o_de_ofertas_parceiro: '/dashboard_parceiro.html',
      minhas_campanhas_loja: '/dashboard_parceiro.html',
      clientes_fidelizados_loja: '/dashboard_parceiro.html',
      validar_resgate: scope === 'empresa' ? '/dashboard_parceiro.html' : '/meus_pontos.html',
      meu_perfil: scope === 'empresa'
        ? '/dashboard_parceiro.html'
        : (scope === 'admin' ? '/dashboard_admin_master.html' : '/meus_pontos.html'),
    };

    const fallback = fallbackByPage[page];
    if (!fallback) return;

    const header = document.querySelector('header');
    if (!header || header.querySelector('[data-page-back]')) return;

    const container = header.firstElementChild && header.firstElementChild.tagName === 'DIV'
      ? header.firstElementChild
      : header;
    const brandContainer = container.firstElementChild;
    const backButton = document.createElement('button');
    backButton.type = 'button';
    backButton.setAttribute('data-page-back', '1');
    backButton.className = 'inline-flex h-10 items-center gap-2 rounded-full bg-white/90 px-3 text-sm font-bold text-[#111B3F] shadow-sm';
    backButton.innerHTML = '<span class="material-symbols-outlined">arrow_back</span><span>Voltar</span>';
    backButton.addEventListener('click', () => {
      const sameOriginReferrer = document.referrer && (() => {
        try {
          return new URL(document.referrer).origin === window.location.origin;
        } catch {
          return false;
        }
      })();

      if (window.history.length > 1 && sameOriginReferrer) {
        window.history.back();
        return;
      }

      window.location.href = fallback;
    });

    if (brandContainer) {
      container.insertBefore(backButton, brandContainer);
    } else {
      container.prepend(backButton);
    }
  }

  function wireUtilityButtons() {
    const bindOnce = (btn, key, handler) => {
      if (btn.dataset[key] === '1') return false;
      btn.dataset[key] = '1';
      btn.addEventListener('click', handler);
      return true;
    };

    document.querySelectorAll('button').forEach((btn) => {
      if (btn.closest('form')) return;
      if ((btn.getAttribute('type') || '').toLowerCase() === 'submit') return;

      const iconEl = btn.querySelector('[data-icon], .material-symbols-outlined');
      const icon = (iconEl?.getAttribute('data-icon') || iconEl?.textContent || '').toString().toLowerCase().trim();
      const text = (btn.textContent || '').toLowerCase().trim();

      if (btn.id === 'logoutBtn' || btn.id === 'cfgLogoutBtn' || icon === 'logout' || text === 'sair') {
        bindOnce(btn, 'logoutBound', (ev) => {
          ev.preventDefault();
          auth.logout();
        });
        return;
      }

      if (btn.hasAttribute('data-push-toggle')) {
        bindOnce(btn, 'pushToggleBound', async (ev) => {
          ev.preventDefault();
          const previousDisabled = btn.disabled;
          btn.disabled = true;
          try {
            const result = await push.subscribe();
            ui.message(result.message || 'Notificacoes ativadas neste dispositivo.', 'success');
          } catch (err) {
            console.error('push_toggle_fail', err);
            ui.message(err?.message || 'Nao foi possivel ativar as notificacoes neste momento.', 'error');
          } finally {
            btn.disabled = previousDisabled;
          }
        });
        return;
      }

      if (btn.hasAttribute('data-email-toggle')) {
        bindOnce(btn, 'emailToggleBound', (ev) => {
          ev.preventDefault();
          btn.setAttribute('aria-pressed', btn.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
          ui.message('Preferencia de e-mail atualizada nesta sessao.', 'info');
        });
        return;
      }

      if ((icon === 'notifications' || btn.id === 'btnEstabNotif' || btn.id === 'adminClientesNotif') && btn.id !== 'empresaNotifBtn') {
        bindOnce(btn, 'notificationsBound', async (ev) => {
          ev.preventDefault();
          const previousDisabled = btn.disabled;
          btn.disabled = true;
          ui.message('Carregando notificacoes...', 'info');
          try {
            await notifications.load('Notificacoes');
            await notifications.markAllRead().catch(() => null);
          } catch (err) {
            console.error('notifications_button_fail', err);
            ui.message('Nao foi possivel carregar notificacoes agora.', 'error');
          } finally {
            btn.disabled = previousDisabled;
          }
        });
        return;
      }

      if (btn.hasAttribute('data-filters-help') || text.includes('filtros')) {
        bindOnce(btn, 'filtersHelpBound', (ev) => {
          ev.preventDefault();
          const target = document.querySelector(
            'main select, main input[type="search"], main input[id*="Busca"], main input[placeholder*="Buscar"]'
          );
          target?.focus?.();
          ui.message('Use os filtros visiveis para refinar a lista.', 'info');
        });
        return;
      }

      if (/Prev$|Next$/.test(btn.id || '')) {
        bindOnce(btn, 'paginationBound', (ev) => {
          ev.preventDefault();
          ui.message('Nenhuma outra página disponível no momento.', 'info');
        });
        return;
      }

      if (btn.getAttribute('aria-current') === 'page') {
        bindOnce(btn, 'currentPageBound', (ev) => {
          ev.preventDefault();
          ui.message('Você já está nesta página.', 'info');
        });
        return;
      }

      if (btn.hasAttribute('data-nav-fallback')) {
        bindOnce(btn, 'navFallbackInfoBound', (ev) => {
          ev.preventDefault();
          ui.message('Ação disponível pela navegação principal.', 'info');
        });
      }
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

  function normalizePageEncodingArtifacts(root = document.body) {
    if (!root) return;

    const hasMarkers = (value) => /Ã|Â|â|�|├/.test(String(value || ''));
    const decodeIfNeeded = (value) => {
      if (!value || !hasMarkers(value)) return value;
      const decoded = decodeMojibake(value);
      return decoded && decoded !== value ? decoded : value;
    };

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let currentNode = walker.nextNode();
    while (currentNode) {
      const raw = currentNode.nodeValue || '';
      const next = decodeIfNeeded(raw);
      if (next !== raw) {
        currentNode.nodeValue = next;
      }
      currentNode = walker.nextNode();
    }

    root.querySelectorAll('[placeholder],[title],[aria-label],[alt]').forEach((el) => {
      ['placeholder', 'title', 'aria-label', 'alt'].forEach((attr) => {
        const raw = el.getAttribute(attr);
        const next = decodeIfNeeded(raw);
        if (raw && next !== raw) {
          el.setAttribute(attr, next);
        }
      });
    });
  }

  function ensureAdjacentSection(sectionId, anchor, placement = 'afterend', className = '') {
    if (!anchor) return null;

    let section = document.getElementById(sectionId);
    if (!section) {
      section = document.createElement('section');
      section.id = sectionId;
      if (className) section.className = className;

      if (placement === 'beforebegin') {
        anchor.insertAdjacentElement('beforebegin', section);
      } else if (placement === 'afterbegin') {
        anchor.insertAdjacentElement('afterbegin', section);
      } else if (placement === 'beforeend') {
        anchor.insertAdjacentElement('beforeend', section);
      } else {
        anchor.insertAdjacentElement('afterend', section);
      }
    }

    if (className) section.className = className;
    return section;
  }

  function mountAdminPlatformBanner() {
    if (page !== 'dashboard_admin_master') return;
    const main = document.querySelector('main');
    const anchor = main?.firstElementChild || main;
    const section = ensureAdjacentSection(
      'adminPlatformRoleBanner',
      anchor,
      'beforebegin',
      'mb-6 rounded-2xl bg-surface-container-lowest p-5 shadow-sm'
    );
    if (!section) return;

    section.innerHTML = `
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant">Painel master</p>
          <h2 class="mt-2 font-headline text-xl font-extrabold text-on-surface">Controle da plataforma</h2>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-on-surface-variant">Empresas, vencimentos, cobranca, campanhas e crescimento ficam aqui. A operacao com clientes e os pontos continuam na area da empresa.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-3">
          <a class="empresa-shortcut-card" href="/gest_o_de_estabelecimentos.html">
            <span class="material-symbols-outlined">storefront</span>
            <span>Empresas</span>
          </a>
          <a class="empresa-shortcut-card" href="/gest_o_de_clientes_master.html">
            <span class="material-symbols-outlined">groups</span>
            <span>Clientes</span>
          </a>
          <a class="empresa-shortcut-card" href="/tickets_admin_master.html">
            <span class="material-symbols-outlined">support_agent</span>
            <span>Suporte</span>
          </a>
        </div>
      </div>
    `;
  }

  // Logout visivel no mobile para o admin: a saida antes so existia na sidebar
  // (escondida no celular). Injeta um botao "Sair" no cabecalho das telas admin.
  function mountAdminMobileLogout() {
    const adminPages = ['dashboard_admin_master', 'gest_o_de_estabelecimentos', 'gest_o_de_usu_rios_master', 'gest_o_de_clientes_master', 'relat_rios_gerais_master', 'tickets_admin_master', 'gestao_pontos', 'configuracoes_admin'];
    if (!adminPages.includes(page)) return;
    const header = document.querySelector('header.admin-header') || document.querySelector('header');
    if (!header) return;
    const hasLogout = header.querySelector('[data-admin-logout]')
      || Array.from(header.querySelectorAll('.material-symbols-outlined'))
        .some((s) => (s.getAttribute('data-icon') || s.textContent || '').trim().toLowerCase() === 'logout');
    if (hasLogout) return;
    const right = header.children.length > 1 ? header.lastElementChild : header;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('data-admin-logout', '1');
    btn.title = 'Sair da conta';
    btn.setAttribute('aria-label', 'Sair da conta');
    // Pilula rotulada (nao um icone solto) para leitura clara de "Sair".
    btn.className = 'inline-flex h-9 items-center gap-1.5 rounded-full bg-[#b41340]/10 pl-2.5 pr-3.5 text-[12px] font-bold text-[#b41340] hover:bg-[#b41340]/16 transition-colors whitespace-nowrap';
    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">logout</span>Sair';
    btn.addEventListener('click', (ev) => { ev.preventDefault(); auth.logout(); });
    right.appendChild(btn);
  }

  function mountClientHomeSummary({ linkedCompanies = [], featuredCompanies = [] } = {}) {
    if (page !== 'meus_pontos') return;
    // Prompt 01 (App do Cliente): home enxuta, sem bloco de estatísticas/"resumo".
    // As ações principais e o consentimento de push já cobrem essas informações.
    return;
    const anchor = document.getElementById('linkedCompaniesList')?.closest('section');
    const section = ensureAdjacentSection(
      'clientRelationshipOverview',
      anchor,
      'beforebegin',
      'mt-8 rounded-[28px] bg-white p-5 shadow-[0_18px_45px_rgba(8,10,18,0.08)] ring-1 ring-black/5 lg:p-6'
    );
    if (!section) return;

    section.innerHTML = `
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Resumo do app</p>
          <h2 class="mt-2 text-2xl font-extrabold text-[#111B3F]">Seus vínculos e benefícios</h2>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">Você recebe campanhas somente das empresas vinculadas à sua conta. Escaneie o QR da empresa para se vincular e resgatar os benefícios direto na página dela.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
          <a href="/parceiros_tem_de_tudo.html" class="app-secondary-button justify-center">Explorar empresas</a>
          <a href="/validar_resgate.html?modo=vinculo-empresa" class="app-primary-button justify-center">Ler QR da empresa</a>
        </div>
      </div>
      <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-[22px] bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Empresas vinculadas</p>
          <p class="mt-2 text-2xl font-extrabold text-[#133F8C]">${linkedCompanies.length.toLocaleString('pt-BR')}</p>
        </div>
        <div class="rounded-[22px] bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Empresas para explorar</p>
          <p class="mt-2 text-2xl font-extrabold text-[#00AFA8]">${featuredCompanies.length.toLocaleString('pt-BR')}</p>
        </div>
        <div class="rounded-[22px] bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Regra de push</p>
          <p class="mt-2 text-sm font-bold text-[#111B3F]">Somente empresas vinculadas</p>
        </div>
      </div>
      <div class="app-status-strip mt-5">
        <div class="app-status-chip">
          <span class="app-status-chip__label">Vinculo real</span>
          <span class="app-status-chip__value">Leia o QR da empresa para entrar na base certa.</span>
        </div>
        <div class="app-status-chip">
          <span class="app-status-chip__label">Push real</span>
          <span class="app-status-chip__value">Notificacoes chegam mesmo deslogado se este aparelho continuar com permissao ativa.</span>
        </div>
        <div class="app-status-chip">
          <span class="app-status-chip__label">Validacao presencial</span>
          <span class="app-status-chip__value">Leia o QR da empresa no balcao para resgatar bonus, fidelidade e promocoes.</span>
        </div>
      </div>
    `;
  }

  function mountCompanyClientsPushSummary({ total = 0, pushActive = 0, pushInactive = 0 } = {}) {
    if (page !== 'clientes_fidelizados_loja') return;
    const anchor = document.getElementById('clientesLista')?.closest('section');
    const section = ensureAdjacentSection(
      'clientesPushSummary',
      anchor,
      'beforebegin',
      'mb-6 rounded-2xl bg-surface-container-lowest p-4 shadow-sm'
    );
    if (!section) return;

    section.innerHTML = `
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant">Push dos clientes</p>
          <h2 class="mt-2 font-headline text-xl font-extrabold text-on-surface">Base pronta para campanha</h2>
          <p class="mt-2 text-sm leading-6 text-on-surface-variant">Promoções, aniversário e lembretes saem somente para clientes vinculados com notificações ativas no dispositivo.</p>
        </div>
        <a href="/gest_o_de_ofertas_parceiro.html#empresaOffersPushSummary" class="app-secondary-button justify-center">Abrir push e campanhas</a>
      </div>
      <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-surface-container-low p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Clientes vinculados</p>
          <p class="mt-2 text-2xl font-extrabold text-[#133f8c]">${Number(total || 0).toLocaleString('pt-BR')}</p>
        </div>
        <div class="rounded-xl bg-surface-container-low p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Com push ativo</p>
          <p class="mt-2 text-2xl font-extrabold text-[#00AFA8]">${Number(pushActive || 0).toLocaleString('pt-BR')}</p>
        </div>
        <div class="rounded-xl bg-surface-container-low p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Sem push ativo</p>
          <p class="mt-2 text-2xl font-extrabold text-[#B01774]">${Number(pushInactive || 0).toLocaleString('pt-BR')}</p>
        </div>
      </div>
    `;
  }

  const NAV_EXCLUDED_PAGES = ['revenda_painel', 'entrar', 'criar_conta', 'escolher-tipo', 'forgot_password', 'redirect_bridge', 'home_tem_de_tudo', 'bonus_resgatado'];

  function buildNavConfigs(pageKey) {
    return {
      admin: {
        title: 'Administrador',
        items: [
          { label: 'Dashboard', icon: 'home', href: '/dashboard_admin_master.html', active: ['dashboard_admin_master'] },
          { label: 'Empresas', icon: 'storefront', href: '/gest_o_de_estabelecimentos.html', active: ['gest_o_de_estabelecimentos'] },
          { label: 'Clientes', icon: 'groups', href: '/gest_o_de_clientes_master.html', active: ['gest_o_de_clientes_master', 'gest_o_de_usu_rios_master'] },
          { label: 'Relatorios', icon: 'analytics', href: '/relat_rios_gerais_master.html', active: ['relat_rios_gerais_master'] },
        ],
        more: [
          { label: 'Conteudo', icon: 'collections', href: '/banners_e_categorias_master.html' },
          { label: 'Usuarios', icon: 'group', href: '/gest_o_de_usu_rios_master.html' },
          { label: 'Configuracoes', icon: 'settings', href: '/configuracoes_admin.html' },
          { label: 'Suporte', icon: 'support_agent', href: '/tickets_admin_master.html' },
        ],
      },
      empresa: {
        title: 'Painel da empresa',
        items: [
          { label: 'Inicio', icon: 'home', href: '/dashboard_parceiro.html', active: ['dashboard_parceiro'] },
          { label: 'Clientes', icon: 'groups', href: '/clientes_fidelizados_loja.html', active: ['clientes_fidelizados_loja'] },
          { label: 'Ofertas', icon: 'campaign', href: '/gest_o_de_ofertas_parceiro.html', active: ['gest_o_de_ofertas_parceiro', 'minhas_campanhas_loja'] },
          { label: 'Meu QR', icon: 'qr_code_2', href: '/validar_resgate.html?modo=beneficios', active: pageKey === 'empresa:validar_resgate' ? ['validar_resgate'] : [] , accent: true },
        ],
        more: [
          { label: 'Operacao', icon: 'insights', href: '/minhas_campanhas_loja.html' },
          { label: 'Push', icon: 'send', href: '/gest_o_de_ofertas_parceiro.html#empresaOffersPushSummary' },
          { label: 'Perfil', icon: 'person', href: '/meu_perfil.html' },
          { label: 'Aniversario', icon: 'cake', href: '/gest_o_de_ofertas_parceiro.html#birthdayBonusSection' },
        ],
      },
      cliente: {
        title: 'Tem de Tudo',
        items: [
          { label: 'Inicio', icon: 'home', href: '/meus_pontos.html', active: ['meus_pontos'] },
          { label: 'Buscar', icon: 'storefront', href: '/parceiros_tem_de_tudo.html', active: ['parceiros_tem_de_tudo', 'detalhe_do_parceiro'] },
          { label: 'QR', icon: 'qr_code_scanner', href: '/validar_resgate.html?modo=vinculo-empresa', active: pageKey === 'cliente:validar_resgate' ? ['validar_resgate'] : [], accent: true },
          { label: 'Novidades', icon: 'notifications', href: '/recompensas.html', active: ['recompensas'] },
        ],
        more: [
          { label: 'Historico', icon: 'history', href: '/hist_rico_de_uso.html' },
          { label: 'Perfil', icon: 'person', href: '/meu_perfil.html' },
          { label: 'Notificacoes', icon: 'campaign', href: '/recompensas.html' },
        ],
      },
    };
  }

  // Sidebar de DESKTOP (>=1024px): dá cara de "sistema" a empresa/admin/cliente.
  // No mobile fica escondida (o dock inferior assume). Reaproveita a mesma nav.
  function mountDesktopSidebar() {
    if (NAV_EXCLUDED_PAGES.includes(page)) return;
    if (document.getElementById('tdtSidebar')) return;
    const scope = getScopeForCurrentPage();
    // Admin já possui shell/sidebar próprio de desktop — não duplicar.
    if (scope === 'admin') return;
    // Se a página já traz uma sidebar de desktop embutida, não injeta outra.
    if (document.querySelector('.admin-sidebar, aside.sidebar, [data-desktop-sidebar]')) return;
    const pageKey = `${scope}:${page}`;
    const config = buildNavConfigs(pageKey)[scope];
    if (!config) return;

    const allItems = [...config.items.filter((i) => !i.accent), ...config.more]
      .filter((item, idx, arr) => arr.findIndex((x) => x.href === item.href) === idx);
    const isActive = (item) => Array.isArray(item.active) && item.active.includes(page);

    const nav = document.createElement('aside');
    nav.id = 'tdtSidebar';
    nav.className = 'tdt-sidebar';
    nav.innerHTML = `
      <a href="${config.items[0]?.href || '#'}" class="tdt-sidebar__brand">
        <img src="/img/logo.png" alt="" onerror="this.style.display='none'" />
        <span>${config.title}</span>
      </a>
      <nav class="tdt-sidebar__nav">
        ${allItems.map((item) => `
          <a href="${item.href}" class="tdt-sidebar__link ${isActive(item) ? 'is-active' : ''}">
            <span class="material-symbols-outlined">${item.icon}</span>
            <span>${item.label}</span>
          </a>
        `).join('')}
      </nav>
      <a href="${scope === 'cliente' ? '/validar_resgate.html?modo=vinculo-empresa' : '/validar_resgate.html?modo=beneficios'}" class="tdt-sidebar__cta">
        <span class="material-symbols-outlined">qr_code_scanner</span>${scope === 'cliente' ? 'Ler QR' : 'Meu QR'}
      </a>`;
    document.body.appendChild(nav);
    document.body.classList.add('tdt-has-sidebar');
  }

  function mountUnifiedMobileDock() {
    // Paginas sem navegacao de app (login/cadastro/painel revenda/comprovante) nao usam o dock.
    if (NAV_EXCLUDED_PAGES.includes(page)) return;
    const scope = getScopeForCurrentPage();
    const oldDockList = Array.from(document.querySelectorAll('nav.fixed.bottom-0'));
    oldDockList.forEach((dock) => dock.remove());
    document.querySelectorAll('a.fixed.bottom-24, button.fixed.bottom-24').forEach((el) => el.remove());

    const pageKey = `${scope}:${page}`;
    const configs = buildNavConfigs(pageKey);

    const config = configs[scope];
    if (!config) return;

    const isActive = (item) => Array.isArray(item.active) && item.active.includes(page);
    const itemMarkup = (item) => {
      const active = isActive(item);
      const classes = [
        'app-mobile-dock__item',
        active ? 'app-mobile-dock__item--active' : '',
        item.accent ? 'app-mobile-dock__item--accent' : '',
      ].filter(Boolean).join(' ');
      return `
        <a href="${item.href}" class="${classes}" data-mobile-dock-link="1">
          <span class="app-mobile-dock__item-icon">
            <span class="material-symbols-outlined">${item.icon}</span>
          </span>
          <span class="app-mobile-dock__item-label">${item.label}</span>
        </a>
      `;
    };

    const dock = document.createElement('nav');
    dock.className = 'app-mobile-dock lg:hidden';
    dock.setAttribute('data-unified-mobile-dock', scope);
    dock.innerHTML = `
      <div class="app-mobile-dock__inner">
        ${config.items.map((item) => itemMarkup(item)).join('')}
        <button type="button" class="app-mobile-dock__item ${page === 'meu_perfil' ? 'app-mobile-dock__item--active' : ''}" data-mobile-dock-more>
          <span class="app-mobile-dock__item-icon">
            <span class="material-symbols-outlined">more_horiz</span>
          </span>
          <span class="app-mobile-dock__item-label">Mais</span>
        </button>
      </div>
    `;

    const sheet = document.createElement('div');
    sheet.className = 'app-mobile-sheet-backdrop lg:hidden';
    sheet.setAttribute('data-mobile-sheet', scope);
    sheet.innerHTML = `
      <div class="app-mobile-sheet" role="dialog" aria-modal="true" aria-label="Atalhos">
        <div class="app-mobile-sheet__grabber"></div>
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Atalhos</p>
            <h2 class="mt-2 font-headline text-xl font-extrabold text-on-surface">Mais opcoes deste perfil</h2>
          </div>
          <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant/30 text-on-surface" data-mobile-sheet-close>
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <div class="app-mobile-sheet__grid">
          ${config.more.map((item) => `
            <a href="${item.href}" class="app-mobile-sheet__link" data-mobile-sheet-link="1">
              <span class="material-symbols-outlined">${item.icon}</span>
              <span>${item.label}</span>
            </a>
          `).join('')}
        </div>
      </div>
    `;

    const openSheet = () => sheet.classList.add('is-open');
    const closeSheet = () => sheet.classList.remove('is-open');
    dock.querySelector('[data-mobile-dock-more]')?.addEventListener('click', openSheet);
    sheet.querySelector('[data-mobile-sheet-close]')?.addEventListener('click', closeSheet);
    sheet.addEventListener('click', (event) => {
      if (event.target === sheet) closeSheet();
    });
    sheet.querySelectorAll('[data-mobile-sheet-link]').forEach((link) => {
      link.addEventListener('click', closeSheet);
    });

    document.body.appendChild(dock);
    document.body.appendChild(sheet);
  }

  // ---------------------- Push ---------------------- //
  const push = (() => {
    let configCache = null;
    let promptModal = null;
    let promptState = null;
    const promptPages = new Set(['meus_pontos', 'meu_perfil', 'detalhe_do_parceiro', 'parceiros_tem_de_tudo']);

    function urlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);
      for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
      return outputArray;
    }

    function isIOS() {
      const ua = window.navigator.userAgent || '';
      const platform = window.navigator.platform || '';
      return /iPad|iPhone|iPod/i.test(ua)
        || (platform === 'MacIntel' && Number(window.navigator.maxTouchPoints || 0) > 1);
    }

    function isStandalone() {
      return window.matchMedia?.('(display-mode: standalone)')?.matches || window.navigator.standalone === true;
    }

    function isSupported() {
      return typeof window !== 'undefined'
        && 'Notification' in window
        && 'serviceWorker' in navigator
        && 'PushManager' in window;
    }

    async function getConfig(force = false) {
      if (configCache && !force) return configCache;

      const cachedKey = localStorage.getItem(VAPID_CACHE_KEY);
      const { res, data } = await api.request('/push/public-key', {}, {
        requireAuth: false,
        notify: false,
      });

      if (res.ok && data) {
        const publicKey = safeText(data?.vapidPublicKey || cachedKey || '', '');
        if (publicKey) localStorage.setItem(VAPID_CACHE_KEY, publicKey);

        configCache = {
          configured: Boolean(data?.configured && publicKey),
          publicKey: publicKey || null,
          serviceWorker: data?.serviceWorker || '/sw-push.js',
          message: data?.message || (publicKey
            ? 'Push configurado para este ambiente.'
            : 'Configuração de push pendente no servidor.'),
          loadFailed: false,
        };

        return configCache;
      }

      configCache = {
        configured: false,
        publicKey: cachedKey || null,
        serviceWorker: '/sw-push.js',
        message: 'Não foi possível verificar a configuração de push neste momento.',
        loadFailed: true,
      };

      return configCache;
    }

    async function getRegistration(serviceWorkerPath = '/sw-push.js') {
      await navigator.serviceWorker.register(serviceWorkerPath);

      return navigator.serviceWorker.ready;
    }

    async function getCurrentSubscription() {
      if (!isSupported()) return null;
      const registration = await navigator.serviceWorker.getRegistration();

      return registration?.pushManager?.getSubscription?.() || null;
    }

    async function getState() {
      if (isIOS() && !isStandalone()) {
        return {
          key: 'ios_install_required',
          tone: 'warning',
          badge: 'iPhone',
          status: 'No iPhone, adicione o Tem de Tudo à Tela de Início e abra pelo ícone para ativar notificações.',
          helper: 'Abra no Safari, toque em Compartilhar e escolha Adicionar à Tela de Início.',
        };
      }

      if (!isSupported()) {
        return {
          key: 'unsupported',
          tone: 'warning',
          badge: 'Indisponível',
          status: 'Seu navegador não suporta notificações push.',
          helper: 'Use Chrome, Edge ou Safari em um dispositivo compatível com PWA e notificações.',
        };
      }

      const config = await getConfig();
      if (!config.configured || !config.publicKey) {
        return {
          key: config.loadFailed ? 'unavailable' : 'config_missing',
          tone: 'warning',
          badge: 'Pendente',
          status: config.message || 'Configuração de push pendente no servidor.',
          helper: 'O servidor precisa expor VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY e VAPID_SUBJECT para habilitar o envio real.',
        };
      }

      if (Notification.permission === 'denied') {
        return {
          key: 'denied',
          tone: 'error',
          badge: 'Bloqueado',
          status: 'Permissão negada. Ative nas configurações do navegador para receber notificações.',
          helper: 'Depois de liberar a permissão, volte a este card e toque novamente em Ativar notificações.',
        };
      }

      const subscription = await getCurrentSubscription();
      if (Notification.permission === 'granted' && subscription) {
        return {
          key: 'enabled',
          tone: 'success',
          badge: 'Ativado',
          status: 'Notificações ativadas neste dispositivo.',
          helper: 'Você receberá promoções, bônus aniversário e lembretes apenas das empresas vinculadas à sua conta.',
          subscription,
        };
      }

      return {
        key: 'idle',
        tone: 'info',
        badge: 'Disponível',
        status: 'Receba promoções e benefícios',
        helper: 'Ative as notificações para receber novidades das empresas onde você se cadastrou: promoções, bônus de aniversário e lembretes de retorno.',
        subscription: null,
      };
    }

    function canAutoPromptOnCurrentPage() {
      return promptPages.has(page);
    }

    function canTriggerPermissionPrompt(state) {
      return ['idle', 'unavailable'].includes(state?.key);
    }

    function getPromptReasonCopy(reason) {
      if (reason === 'register') {
        return {
          kicker: 'Cadastro concluído',
          title: 'Ative as notificações neste dispositivo',
          body: 'Receba promoções, bônus e lembretes das empresas onde você se cadastrou sem depender de SMS ou e-mail.',
        };
      }

      if (reason === 'nudge') {
        return {
          kicker: 'Notificações',
          title: 'Ative as notificações',
          body: 'É por elas que promoções, bônus e lembretes das suas empresas chegam até você.',
        };
      }

      return {
        kicker: 'Bem-vindo de volta',
        title: 'Ative as notificações neste dispositivo',
        body: 'Receba campanhas e benefícios das empresas vinculadas à sua conta assim que fizer login no app.',
      };
    }

    function ensurePromptModal() {
      if (promptModal) return promptModal;

      const wrapper = document.createElement('div');
      wrapper.className = 'fixed inset-0 z-50 hidden items-end justify-center bg-slate-950/45 p-4 sm:items-center';
      wrapper.setAttribute('data-push-prompt-modal', 'true');
      wrapper.innerHTML = `
        <div class="w-full max-w-xl overflow-hidden rounded-[30px] bg-white shadow-[0_30px_90px_rgba(11,31,58,0.28)] ring-1 ring-black/5">
          <div class="bg-[linear-gradient(135deg,#133F8C_0%,#B01774_100%)] px-6 py-6 text-white">
            <div class="flex items-start justify-between gap-4">
              <div class="space-y-2">
                <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em]" data-push-prompt-kicker>Bem-vindo</span>
                <h2 class="text-2xl font-extrabold leading-tight" data-push-prompt-title>Ative as notificacoes neste dispositivo</h2>
                <p class="max-w-lg text-sm leading-6 text-white/82" data-push-prompt-body>Receba promocoes e beneficios das empresas vinculadas a sua conta.</p>
              </div>
              <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white transition hover:bg-white/15" data-push-prompt-close aria-label="Fechar">
                <span class="material-symbols-outlined">close</span>
              </button>
            </div>
          </div>
          <div class="space-y-4 px-6 py-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Status deste dispositivo</p>
                <p class="mt-2 text-base font-bold text-[#111B3F]" data-push-prompt-status>Verificando suporte e permissao...</p>
              </div>
              <span class="inline-flex rounded-full bg-slate-100 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500" data-push-prompt-badge>Verificando</span>
            </div>
            <p class="text-sm leading-6 text-slate-500" data-push-prompt-helper>Toque no botao abaixo para concluir a ativacao real das notificacoes neste aparelho.</p>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button type="button" class="app-secondary-button justify-center" data-push-prompt-secondary>Agora nao</button>
              <button type="button" class="app-primary-button justify-center" data-push-prompt-primary>Ativar notificacoes</button>
            </div>
          </div>
        </div>`;

      wrapper.addEventListener('click', (event) => {
        if (event.target === wrapper) {
          wrapper.classList.add('hidden');
        }
      });

      wrapper.querySelector('[data-push-prompt-close]')?.addEventListener('click', () => {
        wrapper.classList.add('hidden');
      });

      wrapper.querySelector('[data-push-prompt-secondary]')?.addEventListener('click', () => {
        wrapper.classList.add('hidden');
      });

      wrapper.querySelector('[data-push-prompt-primary]')?.addEventListener('click', async () => {
        const primary = wrapper.querySelector('[data-push-prompt-primary]');
        const reason = wrapper.dataset.pushPromptReason || 'login';

        if (!canTriggerPermissionPrompt(promptState)) {
          wrapper.classList.add('hidden');
          return;
        }

        primary.disabled = true;
        try {
          const result = await subscribe();
          const state = result?.state || await getState();
          promptState = state;
          updatePromptModal(state, reason);
          await refreshAllCards();

          if (result?.success) {
            ui.message(result.message || 'Notificacoes ativadas neste dispositivo.', 'success');
            wrapper.classList.add('hidden');
          } else {
            ui.message(state?.status || 'Nao foi possivel ativar as notificacoes neste momento.', state?.tone || 'warning');
          }
        } catch (err) {
          console.error('push_prompt_subscribe_fail', err);
          const state = await getState();
          promptState = state;
          updatePromptModal(state, reason);
          await refreshAllCards();
          ui.message(err?.message || 'Nao foi possivel ativar as notificacoes neste momento.', 'error');
        } finally {
          primary.disabled = false;
        }
      });

      document.body.appendChild(wrapper);
      promptModal = wrapper;
      return promptModal;
    }

    function updatePromptModal(state, reason = 'login') {
      const modal = ensurePromptModal();
      if (!modal) return;

      const copy = getPromptReasonCopy(reason);
      promptState = state;
      modal.dataset.pushPromptReason = reason;

      const kicker = modal.querySelector('[data-push-prompt-kicker]');
      const title = modal.querySelector('[data-push-prompt-title]');
      const body = modal.querySelector('[data-push-prompt-body]');
      const badge = modal.querySelector('[data-push-prompt-badge]');
      const status = modal.querySelector('[data-push-prompt-status]');
      const helper = modal.querySelector('[data-push-prompt-helper]');
      const primary = modal.querySelector('[data-push-prompt-primary]');
      const secondary = modal.querySelector('[data-push-prompt-secondary]');

      if (kicker) kicker.textContent = copy.kicker;
      if (title) title.textContent = copy.title;
      if (body) body.textContent = copy.body;
      if (badge) badge.textContent = state?.badge || 'Verificando';
      if (status) status.textContent = state?.status || 'Verificando suporte e permissao...';
      if (helper) helper.textContent = state?.helper || 'Toque no botao abaixo para concluir a ativacao real das notificacoes neste aparelho.';

      const actionable = canTriggerPermissionPrompt(state);
      if (primary) {
        primary.textContent = actionable ? 'Ativar notificacoes' : 'Entendi';
      }
      if (secondary) {
        secondary.classList.toggle('hidden', !actionable);
      }
    }

    async function openPrompt(reason = 'manual', presetState = null) {
      const state = presetState || await getState();
      promptState = state;
      updatePromptModal(state, reason);
      ensurePromptModal().classList.remove('hidden');
      return state;
    }

    async function refreshAllCards() {
      const cards = Array.from(document.querySelectorAll('[data-push-card]'));
      if (!cards.length) return;
      await Promise.all(cards.map((card) => refreshCard(card).catch((err) => {
        console.error('push_card_refresh_fail', err);
      })));
    }

    async function maybePromptAfterAuth() {
      if (!canAutoPromptOnCurrentPage()) return;

      // Cadastro/vínculo via QR recém-concluído: a celebração do bônus tem
      // prioridade nesta carga. Não consome o motivo — o convite de push
      // aparece na próxima página elegível.
      if (page === 'detalhe_do_parceiro'
        && new URLSearchParams(window.location.search).get('linked') === '1') return;

      const viewer = auth.normalizeUser(auth.getStored()?.user);
      const perfil = auth.normalizePerfil(viewer?.perfil || viewer?.role || viewer?.tipo);
      if (perfil !== 'cliente') {
        clearPushPrompt();
        return;
      }

      // Igual à localização: enquanto o push não estiver ativo, o convite
      // aparece 1x por sessão, mesmo sem login/cadastro recém-feito.
      const reason = getPushPrompt();
      let alreadyAskedThisSession = false;
      try { alreadyAskedThisSession = sessionStorage.getItem('tdt_push_nudged') === '1'; } catch (_) { /* ignore */ }
      if (!reason && alreadyAskedThisSession) return;

      const state = await getState();
      if (state.key === 'enabled') {
        clearPushPrompt();
        return;
      }

      clearPushPrompt();
      try { sessionStorage.setItem('tdt_push_nudged', '1'); } catch (_) { /* ignore */ }
      updatePromptModal(state, reason || 'nudge');
      ensurePromptModal().classList.remove('hidden');
    }

    function paintCard(card, state) {
      if (!card || !state) return;

      const badge = card.querySelector('[data-push-badge]');
      const status = card.querySelector('[data-push-status]');
      const helper = card.querySelector('[data-push-helper]');
      const enable = card.querySelector('[data-push-enable]');
      const disable = card.querySelector('[data-push-disable]');

      const badgeClasses = {
        success: 'bg-emerald-100 text-emerald-700',
        warning: 'bg-amber-100 text-amber-700',
        error: 'bg-rose-100 text-rose-700',
        info: 'bg-slate-100 text-slate-500',
      };
      const statusClasses = {
        success: 'text-emerald-700',
        warning: 'text-amber-700',
        error: 'text-rose-700',
        info: 'text-[#111B3F]',
      };

      if (badge) {
        badge.textContent = state.badge;
        badge.className = `inline-flex rounded-full px-4 py-2 text-[11px] font-bold uppercase tracking-[0.16em] ${badgeClasses[state.tone] || badgeClasses.info}`;
      }
      if (status) {
        status.textContent = state.status;
        status.className = `mt-4 text-sm font-semibold ${statusClasses[state.tone] || statusClasses.info}`;
      }
      if (helper) {
        helper.textContent = state.helper;
      }

      if (enable) {
        enable.disabled = false;
        enable.classList.toggle('hidden', state.key === 'enabled');
      }

      if (disable) {
        disable.disabled = state.key !== 'enabled';
        disable.classList.toggle('hidden', state.key !== 'enabled');
      }
    }

    async function refreshCard(card) {
      paintCard(card, await getState());
    }

    async function subscribe() {
      if (isIOS() && !isStandalone()) {
        return { success: false, state: await getState() };
      }

      if (!isSupported()) {
        return { success: false, state: await getState() };
      }

      // SEMPRE pergunta a permissão ao usuário primeiro (é o que ele espera ao
      // tocar em "Ativar notificações"). Só depois lidamos com o servidor push.
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        return { success: false, state: await getState() };
      }

      const config = await getConfig(true);
      if (!config.configured || !config.publicKey) {
        // Permissão concedida, mas o push do servidor ainda não está configurado.
        return {
          success: false,
          state: await getState(),
          message: 'Permissão concedida. O envio de notificações será ativado em breve.',
        };
      }

      const registration = await getRegistration(config.serviceWorker || '/sw-push.js');
      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(config.publicKey),
        });
      }

      const payload = subscription.toJSON ? subscription.toJSON() : JSON.parse(JSON.stringify(subscription));
      const { res, data } = await api.request('/push/subscribe', {
        method: 'POST',
        body: JSON.stringify(payload),
      }, {
        notify: false,
      });

      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || 'Não foi possível salvar a subscription push deste dispositivo.');
      }

      return {
        success: true,
        state: await getState(),
        message: data?.message || 'Notificacoes ativadas neste dispositivo.',
      };
    }

    async function unregister() {
      if (!('serviceWorker' in navigator)) {
        return {
          success: true,
          state: await getState(),
        };
      }

      const registration = await navigator.serviceWorker.getRegistration();
      const subscription = await registration?.pushManager?.getSubscription?.();

      if (!subscription?.endpoint) {
        return {
          success: true,
          state: await getState(),
          message: 'Nenhuma subscription ativa encontrada neste dispositivo.',
        };
      }

      const { res, data } = await api.request('/push/unsubscribe', {
        method: 'DELETE',
        body: JSON.stringify({ endpoint: subscription.endpoint }),
      }, {
        notify: false,
      });

      if (subscription) {
        await subscription.unsubscribe().catch(() => {});
      }

      if (!res.ok || data?.success === false) {
        throw new Error(data?.message || 'Não foi possível desativar as notificações neste dispositivo.');
      }

      return {
        success: true,
        state: await getState(),
        message: data?.message || 'Notificacoes desativadas neste dispositivo.',
      };
    }

    function bindCard(card) {
      if (!card || card.dataset.pushCardBound === '1') return;
      card.dataset.pushCardBound = '1';

      const enable = card.querySelector('[data-push-enable]');
      const disable = card.querySelector('[data-push-disable]');

      enable?.addEventListener('click', async () => {
        enable.disabled = true;
        try {
          const currentState = await getState();
          if (!canTriggerPermissionPrompt(currentState)) {
            await openPrompt('manual', currentState);
            ui.message(currentState?.status || 'Verifique o status das notificacoes neste dispositivo.', currentState?.tone || 'info');
            return;
          }

          const result = await subscribe();
          paintCard(card, result.state);
          if (result.success) {
            ui.message(result.message || 'Notificacoes ativadas neste dispositivo.', 'success');
          } else {
            await openPrompt('manual', result.state);
            ui.message(result.state?.status || 'Não foi possível ativar as notificações neste momento.', result.state?.tone || 'warning');
          }
        } catch (err) {
          console.error('push_subscribe_fail', err);
          await refreshCard(card);
          ui.message(err?.message || 'Não foi possível ativar as notificações neste momento.', 'error');
        } finally {
          enable.disabled = false;
        }
      });

      disable?.addEventListener('click', async () => {
        disable.disabled = true;
        try {
          const result = await unregister();
          paintCard(card, result.state);
          ui.message(result.message || 'Notificacoes desativadas neste dispositivo.', 'info');
        } catch (err) {
          console.error('push_unsubscribe_fail', err);
          await refreshCard(card);
          ui.message(err?.message || 'Não foi possível desativar as notificações neste momento.', 'error');
        } finally {
          disable.disabled = false;
        }
      });
    }

    function mountCards() {
      const cards = Array.from(document.querySelectorAll('[data-push-card]'));
      cards.forEach((card) => {
        bindCard(card);
        refreshCard(card).catch((err) => {
          console.error('push_card_refresh_fail', err);
        });
      });
      maybePromptAfterAuth().catch((err) => {
        console.error('push_prompt_mount_fail', err);
      });
    }

    return { mountCards, getState, subscribe, unregister, getConfig };
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
          saveAccessNotice('Sessao expirada. Faça login novamente.', 'warning');
          setTimeout(() => (window.location.href = buildLoginRedirect()), 300);
        } else {
          console.warn('401 em recurso protegido (sem logout forcado):', path);
        }
      }
      if (notify && res.status === 403) ui.message('Acesso negado para este perfil.', 'warning');
      if (notify && res.status === 404) ui.message('Recurso não encontrado.', 'warning');
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

  function normalizePushDeliveryMetrics(metrics = {}) {
    return {
      status: String(metrics?.status || ''),
      configMissing: Boolean(metrics?.config_missing),
      totalElegiveis: Number(metrics?.total_elegiveis ?? metrics?.total_targeted ?? 0),
      totalComSubscription: Number(metrics?.total_com_subscription ?? metrics?.total_with_subscription ?? 0),
      enviados: Number(metrics?.enviados ?? metrics?.total_sent ?? 0),
      falhas: Number(metrics?.falhas ?? metrics?.total_failed ?? 0),
      ignoradosSemSubscription: Number(metrics?.ignorados_sem_subscription ?? metrics?.total_without_subscription ?? 0),
      ignoradosSemVinculo: Number(metrics?.ignorados_sem_vinculo ?? 0),
      message: safeText(metrics?.message || ''),
    };
  }

  function formatPushDeliverySummary(metrics = {}, subject = 'clientes') {
    const normalized = normalizePushDeliveryMetrics(metrics);
    const detail = [
      `Elegiveis: ${normalized.totalElegiveis}`,
      `Com notificacoes ativas: ${normalized.totalComSubscription}`,
      `Enviados: ${normalized.enviados}`,
      `Falhas: ${normalized.falhas}`,
      `Sem subscription: ${normalized.ignoradosSemSubscription}`,
      `Sem vínculo: ${normalized.ignoradosSemVinculo}`,
    ].join(' | ');

    let short = `Resumo do envio para ${subject}: ${detail}`;
    if (normalized.status === 'config_missing') {
      short = normalized.message || 'Configuração de push pendente no servidor.';
    } else if (normalized.status === 'no_subscription') {
      short = normalized.message || `Nenhum ${subject} elegivel ativou notificacoes neste dispositivo ainda.`;
    } else if (normalized.status === 'failed') {
      short = normalized.message || `Nao foi possivel entregar a notificacao para ${subject}.`;
    }

    return {
      normalized,
      short,
      detail,
    };
  }

  function normalizeWeeklyLimitStatus(status = {}) {
    const rawLimit = Number(status?.limit ?? 5);
    const unlimited = Boolean(status?.unlimited) || rawLimit <= 0;
    const used = Math.max(0, Number(status?.used ?? 0) || 0);
    const windowDays = Math.max(1, Number(status?.window_days ?? 7) || 7);

    if (unlimited) {
      return {
        limit: 0,
        used,
        remaining: Number.MAX_SAFE_INTEGER,
        windowDays,
        unlimited: true,
        tone: 'success',
        title: 'Envios de push',
        detail: `${plural(used, 'envio feito', 'envios feitos')} — sem limite`,
        helper: 'Você pode enviar push ilimitado.',
      };
    }

    const limit = Math.max(1, rawLimit || 5);
    const remaining = Math.max(0, Number(status?.remaining ?? Math.max(0, limit - used)) || 0);
    const tone = remaining <= 0 ? 'error' : (remaining <= Math.ceil(limit / 3) ? 'warning' : 'success');

    return {
      limit,
      used,
      remaining,
      windowDays,
      unlimited: false,
      tone,
      title: `Janela de ${windowDays} dias`,
      detail: `${used}/${limit} envios usados`,
      helper: `Restam ${plural(remaining, 'envio', 'envios')} nesta janela.`,
    };
  }

  function setInlineFeedback(el, text, tone = 'info') {
    if (!el) return;
    const toneMap = {
      success: 'text-emerald-600',
      warning: 'text-amber-600',
      error: 'text-rose-600',
      info: 'text-slate-500',
    };
    el.textContent = text;
    el.className = `text-xs leading-5 ${toneMap[tone] || toneMap.info}`;
  }

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
        const { data: dashboardResp } = await api.request('/cliente/dashboard', {}, { notify: false });
        ui.clearPageState();

        const payload = dashboardResp?.data || {};
        const currentUser = await auth.ensure();
        const linkedCompanies = toArray(payload.empresas_vinculadas);
        const featuredCompanies = toArray(payload.empresas_destaque);
        const quickActions = payload.acoes_rapidas || {};
        const params = new URLSearchParams(window.location.search);

        mountClientHomeSummary({
          linkedCompanies,
          featuredCompanies,
        });

        const welcomeEl = document.getElementById('header-welcome');
        if (welcomeEl) {
          const firstName = safeText(currentUser?.name || currentUser?.nome || 'Cliente').split(' ')[0];
          welcomeEl.textContent = `Olá, ${firstName}`;
        }

        const renderCompanyCard = (company) => {
          const rating = Number(company.avaliacao_media || 0);
          const reviews = Number(company.total_avaliacoes || 0);
          const linkedBadge = company.vinculada
            ? '<span class="tdt-tag tdt-tag--new">Vinculada</span>'
            : '';

          const distTag = company.__distanceLabel
            ? `<span class="dot"></span><span class="inline-flex items-center gap-0.5"><span class="material-symbols-outlined" style="font-size:14px">near_me</span>${company.__distanceLabel}</span>`
            : '';
          return `
            <a href="/detalhe_do_parceiro.html?id=${encodeURIComponent(company.id)}" class="tdt-row">
              <img class="tdt-row__img" src="${safeImage(company.logo, IMAGE_FALLBACKS.store)}" alt="" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
              <div class="tdt-row__body">
                <div class="tdt-row__name">${safeText(company.nome, 'Empresa')}</div>
                <div class="tdt-row__meta">
                  <span class="tdt-row__star"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">star</span>${rating > 0 ? rating.toFixed(1).replace('.', ',') : 'Novo'}</span>
                  <span class="dot"></span><span>${safeText(company.categoria || company.ramo, 'Empresa')}</span>
                  ${distTag}
                </div>
                ${linkedBadge ? `<div class="tdt-row__tags">${linkedBadge}</div>` : ''}
              </div>
              <span class="material-symbols-outlined tdt-row__chevron">chevron_right</span>
            </a>
          `;
        };

        const formatLastInteraction = (iso) => {
          if (!iso) return 'Sem visitas registradas';
          const parsed = new Date(iso);
          if (Number.isNaN(parsed.getTime())) return 'Sem visitas registradas';
          return `Última interação em ${parsed.toLocaleDateString('pt-BR')}`;
        };

        const renderLinkedCompanyCard = (company) => {
          const pontos = Number(company.meus_pontos ?? company.total_pontos ?? 0);
          const cat = safeText(company.categoria || company.ramo, 'Empresa');
          const pointsTag = pontos > 0
            ? `<span class="tdt-tag tdt-tag--points"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">star</span>${pontos.toLocaleString('pt-BR')} pts</span>`
            : '';
          return `
            <a href="/detalhe_do_parceiro.html?id=${encodeURIComponent(company.id)}" class="tdt-row">
              <img class="tdt-row__img" src="${safeImage(company.logo, IMAGE_FALLBACKS.store)}" alt="" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
              <div class="tdt-row__body">
                <div class="tdt-row__name">${safeText(company.nome, 'Empresa')}</div>
                <div class="tdt-row__meta"><span>${cat}</span></div>
                ${pointsTag ? `<div class="tdt-row__tags">${pointsTag}</div>` : ''}
              </div>
              <span class="material-symbols-outlined tdt-row__chevron">chevron_right</span>
            </a>
          `;
        };

        const linkedList = document.getElementById('linkedCompaniesList');
        const linkedEmpty = document.getElementById('linkedCompaniesEmpty');
        const linkedCount = document.getElementById('linkedCompaniesCount');
        const linkedSkeleton = document.getElementById('linkedCompaniesSkeleton');
        linkedSkeleton?.classList.add('hidden');
        if (linkedList) {
          linkedList.innerHTML = linkedCompanies.map(renderLinkedCompanyCard).join('');
        }
        if (linkedEmpty) linkedEmpty.classList.toggle('hidden', linkedCompanies.length > 0);
        if (linkedCount) linkedCount.textContent = linkedCompanies.length ? plural(linkedCompanies.length, 'empresa') : 'Nenhuma empresa vinculada ainda';

        const featuredList = document.getElementById('featuredCompaniesList');
        const featuredSection = document.getElementById('featuredCompaniesSection');
        if (featuredList) {
          featuredList.innerHTML = featuredCompanies.map(renderCompanyCard).join('');
        }
        if (featuredSection) {
          featuredSection.classList.toggle('hidden', featuredCompanies.length === 0);
        }

        const wireFeaturedOpen = () => {
          document.querySelectorAll('[data-company-open]').forEach((button) => {
            if (button.dataset.openBound === '1') return;
            button.dataset.openBound = '1';
            button.addEventListener('click', () => {
              const id = button.getAttribute('data-company-open');
              if (id) window.location.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(id)}`;
            });
          });
        };
        wireFeaturedOpen();

        // Empresas proximas: com geolocalizacao, ordena os destaques por
        // distancia e mostra a distancia no card. Front-only (lat/lng ja vem
        // no payload); sem geo, mantem a ordem original.
        if (navigator.geolocation && featuredList && featuredCompanies.length) {
          const haversineKm = (la1, lo1, la2, lo2) => {
            const rad = (x) => (x * Math.PI) / 180;
            const dLa = rad(la2 - la1);
            const dLo = rad(lo2 - lo1);
            const a = Math.sin(dLa / 2) ** 2 + Math.cos(rad(la1)) * Math.cos(rad(la2)) * Math.sin(dLo / 2) ** 2;
            return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
          };
          const fmtDist = (km) => (km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(1).replace('.', ',')} km`);
          navigator.geolocation.getCurrentPosition((pos) => {
            const { latitude, longitude } = pos.coords;
            const ranked = featuredCompanies.map((c) => {
              const lat = Number(c.latitude);
              const lng = Number(c.longitude);
              if (!Number.isFinite(lat) || !Number.isFinite(lng)) return { ...c, __distKm: null };
              const km = haversineKm(latitude, longitude, lat, lng);
              return { ...c, __distKm: km, __distanceLabel: fmtDist(km) };
            }).sort((a, b) => {
              if (a.__distKm == null && b.__distKm == null) return 0;
              if (a.__distKm == null) return 1;
              if (b.__distKm == null) return -1;
              return a.__distKm - b.__distKm;
            });
            featuredList.innerHTML = ranked.map(renderCompanyCard).join('');
            wireFeaturedOpen();
          }, () => {}, { timeout: 6000, maximumAge: 300000 });
        }

        const readQrUrl = quickActions.ler_qr_empresa_url || '/validar_resgate.html?modo=vinculo-empresa';
        document.getElementById('btnReadCompanyQr')?.addEventListener('click', () => {
          window.location.href = readQrUrl;
        });
        document.getElementById('btnBottomScan')?.addEventListener('click', () => {
          window.location.href = readQrUrl;
        });

        // Busca em destaque na Home: leva o termo para a tela de exploracao.
        const homeSearchForm = document.getElementById('homeSearchForm');
        const homeSearchInput = document.getElementById('homeSearchInput');
        homeSearchForm?.addEventListener('submit', (ev) => {
          ev.preventDefault();
          const term = (homeSearchInput?.value || '').trim();
          window.location.href = term
            ? `/parceiros_tem_de_tudo.html?busca=${encodeURIComponent(term)}`
            : '/parceiros_tem_de_tudo.html';
        });

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
      if (welcomeEl) welcomeEl.textContent = `Olá, ${user?.name || 'Cliente'}`;

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
        if (!historico.length) {
          historicoContainer.innerHTML = '<p class="text-sm text-on-surface-variant text-center py-8">Sem historico de pontos.</p>';
        } else {
          historicoContainer.innerHTML = historico.slice(0, 5).map((item) => {
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
        const pontosPorReal = Number(programa?.acumulo?.pontos_por_real || 1);
        const scanBase = Number(programa?.acumulo?.pontos_base_scan || 100);
        sec.innerHTML = `
          <div class="rounded-2xl border border-surface-variant/30 bg-white/80 shadow-sm p-4">
            <h3 class="text-base font-semibold text-on-surface mb-2">Como funciona sua fidelidade</h3>
            <div class="grid gap-2 text-sm text-on-surface-variant">
              <p><span class="font-semibold text-on-surface">Acumulo por compra:</span> ${plural(pontosPorReal, 'ponto')} por R$ 1,00, com multiplicador da empresa.</p>
              <p><span class="font-semibold text-on-surface">Acumulo por QR:</span> base de ${plural(scanBase, 'ponto')}, ajustada por campanha/multiplicador.</p>
              <p><span class="font-semibold text-on-surface">Resgate:</span> custo prioriza pontos_necessários e limite por usuário/estoque da promoção.</p>
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
      const searchBtn = document.getElementById('parceiroBuscaBtn');
      const searchHint = document.getElementById('partnersSearchHint');
      const emptyMsg = document.getElementById('partners-empty');
      const emptyTitle = emptyMsg?.querySelector('[data-empty-title]');
      const emptySub = emptyMsg?.querySelector('[data-empty-subtitle]');
      const errorBox = document.getElementById('partners-error');
      const retryBtn = document.getElementById('partners-retry');
      const skeleton = document.getElementById('partners-skeleton');
      const loadMoreBtn = document.getElementById('partners-load-more');
      const filterButtons = Array.from(document.querySelectorAll('.parceiro-filtro-btn'));

      const BATCH_SIZE = 8;
      const state = { all: [], term: '', activeCategory: 'todos', coords: null, rendered: 0, view: [] };

      // --- helpers de texto / distância -------------------------------------
      const debounceLocal = (fn, wait = 220) => {
        let timer = null;
        return (...args) => {
          clearTimeout(timer);
          timer = setTimeout(() => fn(...args), wait);
        };
      };

      const normalizeText = (value) => String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');

      const matchesTerm = (item, term) => {
        const query = normalizeText(term).trim();
        if (!query) return true;
        const haystack = normalizeText([item?.nome, item?.categoria, item?.ramo, item?.descricao, item?.endereco]
          .filter(Boolean).join(' '));
        return query.split(/\s+/).every((word) => haystack.includes(word));
      };

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

      const haversineKm = (lat1, lon1, lat2, lon2) => {
        const toRad = (x) => (x * Math.PI) / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2
          + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      };

      const formatDistance = (km) => (km < 1
        ? `${Math.round(km * 1000)} m`
        : `${km.toFixed(1).replace('.', ',')} km`);

      const requestLocation = () => new Promise((resolve) => {
        if (!navigator.geolocation) return resolve(null);
        navigator.geolocation.getCurrentPosition(
          (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
          () => resolve(null),
          { timeout: 6000, maximumAge: 300000 },
        );
      });

      // --- estados visuais ---------------------------------------------------
      const hideAllStates = () => {
        skeleton?.classList.add('hidden');
        emptyMsg?.classList.add('hidden');
        errorBox?.classList.add('hidden');
      };
      const showSkeleton = () => {
        hideAllStates();
        skeleton?.classList.remove('hidden');
        if (grid) grid.innerHTML = '';
        loadMoreBtn?.classList.add('hidden');
      };
      const showError = () => {
        hideAllStates();
        if (grid) grid.innerHTML = '';
        loadMoreBtn?.classList.add('hidden');
        errorBox?.classList.remove('hidden');
      };
      const showEmpty = () => {
        hideAllStates();
        if (grid) grid.innerHTML = '';
        loadMoreBtn?.classList.add('hidden');
        const hasQuery = state.term.trim() !== '' || state.activeCategory !== 'todos';
        if (emptyTitle) emptyTitle.textContent = hasQuery ? 'Nenhuma empresa encontrada.' : 'Nenhuma empresa disponível.';
        if (emptySub) emptySub.textContent = hasQuery ? 'Tente pesquisar outro nome ou categoria.' : 'Volte em breve para ver novos parceiros.';
        emptyMsg?.classList.remove('hidden');
      };

      // --- card --------------------------------------------------------------
      const tpl = (e) => {
        const rating = toNumber(e?.avaliacao_media, e?.rating, 0);
        const reviews = toNumber(e?.total_avaliacoes, e?.reviews_count, 0);
        const ratingLabel = rating > 0
          ? `${rating.toFixed(1).replace('.', ',')} • ${reviews || 0} avaliação(ões)`
          : 'Novo parceiro';

        const linked = Boolean(
          e?.vinculada || e?.inscrito || e?.ja_vinculado || e?.is_linked || e?.cliente_vinculado,
        );
        const actionLabel = perfilViewer === 'cliente' && !linked ? 'Me cadastrar' : 'Abrir empresa';

        const local = safeText(e.endereco, '');
        const localPill = local
          ? `<span class="partner-meta-pill"><span class="material-symbols-outlined">location_on</span>${local}</span>`
          : '';
        const distancePill = (e._distanceKm != null)
          ? `<span class="partner-meta-pill partner-meta-pill--near"><span class="material-symbols-outlined">near_me</span>${formatDistance(e._distanceKm)}</span>`
          : '';
        const metaRow = (localPill || distancePill)
          ? `<div class="mt-2 flex flex-wrap items-center gap-2">${distancePill}${localPill}</div>`
          : '';

        return `
          <article class="bg-surface-container-lowest rounded-[28px] p-4 flex flex-col gap-4 shadow-[0_12px_32px_rgba(11,31,58,0.06)] hover:bg-surface-container-high transition-colors cursor-pointer" data-parceiro-id="${e.id}">
            <div class="flex gap-4">
              <div class="w-20 h-20 rounded-[22px] overflow-hidden flex-shrink-0 bg-surface-container">
                <img class="w-full h-full object-cover" src="${safeImage(e.logo, IMAGE_FALLBACKS.store)}" alt="${safeText(e.nome, 'Parceiro')}" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-label text-[11px] text-tertiary font-bold tracking-[0.18em] mb-1 uppercase">${safeText(e.categoria || e.ramo, 'Parceiro')}</p>
                <h3 class="font-headline font-bold text-lg text-on-surface leading-tight truncate">${safeText(e.nome, 'Parceiro')}</h3>
                <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                  <span class="font-bold text-amber-400">${renderStars(rating)}</span>
                  <span>${ratingLabel}</span>
                </div>
                ${metaRow}
              </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-surface-container">
              <a class="inline-flex h-11 items-center justify-center rounded-full bg-primary px-5 text-sm font-semibold text-on-primary hover:opacity-90 transition-opacity" href="/detalhe_do_parceiro.html?id=${e.id}">${actionLabel}</a>
            </div>
          </article>`;
      };

      const renderNextBatch = () => {
        if (!grid) return;
        const slice = state.view.slice(state.rendered, state.rendered + BATCH_SIZE);
        grid.insertAdjacentHTML('beforeend', slice.map(tpl).join(''));
        state.rendered += slice.length;
        loadMoreBtn?.classList.toggle('hidden', state.rendered >= state.view.length);
      };

      const applyView = () => {
        let list = state.all.filter((item) => matchesCategory(item, state.activeCategory) && matchesTerm(item, state.term));

        if (state.coords) {
          list = list
            .map((item) => {
              const lat = Number(item.latitude);
              const lng = Number(item.longitude);
              const hasGeo = Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);
              return { ...item, _distanceKm: hasGeo ? haversineKm(state.coords.lat, state.coords.lng, lat, lng) : null };
            })
            .sort((a, b) => {
              if (a._distanceKm == null && b._distanceKm == null) return 0;
              if (a._distanceKm == null) return 1;
              if (b._distanceKm == null) return -1;
              return a._distanceKm - b._distanceKm;
            });
        }

        state.view = list;
        state.rendered = 0;
        if (grid) grid.innerHTML = '';

        if (!list.length) {
          showEmpty();
          return;
        }
        hideAllStates();
        renderNextBatch();
      };

      // --- filtros de categoria ---------------------------------------------
      const setActiveFilter = (categoryKey) => {
        state.activeCategory = categoryKey || 'todos';
        filterButtons.forEach((button) => {
          const isActive = (button.dataset.categoryKey || 'todos') === state.activeCategory;
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

      // --- carregamento de dados --------------------------------------------
      const fetchCompanies = async () => {
        const prefersClientEndpoint = perfilViewer === 'cliente' && stored?.token;
        const primaryPath = prefersClientEndpoint ? '/cliente/empresas' : '/empresas';
        const primaryResponse = await api.request(primaryPath, {}, { requireAuth: prefersClientEndpoint, notify: false });
        let anyOk = Boolean(primaryResponse.res?.ok);
        let lista = toArray(primaryResponse.data?.data || primaryResponse.data);

        if ((!anyOk || !lista.length) && prefersClientEndpoint) {
          const publicResponse = await api.request('/empresas', {}, { requireAuth: false, notify: false });
          anyOk = anyOk || Boolean(publicResponse.res?.ok);
          lista = toArray(publicResponse.data?.data || publicResponse.data);
        }

        if (!anyOk) throw new Error('Falha ao carregar empresas');
        return Array.isArray(lista) ? lista : [];
      };

      const loadCatalog = async () => {
        showSkeleton();
        try {
          state.all = await fetchCompanies();
        } catch (err) {
          showError();
          return;
        }
        skeleton?.classList.add('hidden');
        applyView();
      };

      // --- listeners ---------------------------------------------------------
      searchHint?.classList.add('hidden');
      const applySearch = () => {
        state.term = String(searchInput?.value || '');
        applyView();
      };
      searchInput?.addEventListener('input', debounceLocal(applySearch, 220));
      searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          applySearch();
        }
      });
      searchBtn?.addEventListener('click', applySearch);
      loadMoreBtn?.addEventListener('click', renderNextBatch);
      retryBtn?.addEventListener('click', () => loadCatalog());

      grid?.addEventListener('click', (ev) => {
        if (ev.target.closest('a, button')) return;
        const card = ev.target.closest('[data-parceiro-id]');
        if (!card) return;
        const id = card.getAttribute('data-parceiro-id');
        if (id) window.location.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(id)}`;
      });

      filterButtons.forEach((button) => {
        if (button.dataset.boundFilter === '1') return;
        button.dataset.boundFilter = '1';
        button.addEventListener('click', () => {
          setActiveFilter(button.dataset.categoryKey || 'todos');
          applyView();
        });
      });

      // --- init --------------------------------------------------------------
      // Termo vindo da busca da Home (?busca= ou ?q=) pre-preenche a pesquisa.
      const initialParams = new URLSearchParams(window.location.search);
      const initialTerm = (initialParams.get('busca') || initialParams.get('q') || '').trim();
      if (initialTerm && searchInput) {
        searchInput.value = initialTerm;
        state.term = initialTerm;
      }
      setActiveFilter('todos');
      await loadCatalog();

      // Prioriza empresas próximas quando a geolocalização estiver disponível.
      requestLocation().then((coords) => {
        if (coords && state.all.length) {
          state.coords = coords;
          applyView();
        }
      });
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
          ui.setPageState('empty', data?.message || 'Empresa indisponivel no momento.');
          return;
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
        const setText = (id, value, fallback = 'Não informado') => {
          const el = document.getElementById(id);
          if (el) el.textContent = safeText(value, fallback);
        };
        const setLink = (id, value, formatter) => {
          const el = document.getElementById(id);
          if (!el) return;
          if (!value) {
            el.classList.add('hidden');
            el.removeAttribute('href');
            return;
          }
          el.classList.remove('hidden');
          el.href = formatter ? formatter(value) : value;
        };
        // Resgate iniciado pelo cliente: apos escanear/entrar na empresa, ele
        // seleciona o beneficio e resgata aqui mesmo (sem a empresa escanear ninguem).
        const redeemBenefit = async (endpoint, actionEl, successMsg) => {
          if (!actionEl || actionEl.dataset.loading === '1') return;
          actionEl.dataset.loading = '1';
          const prevText = actionEl.textContent;
          actionEl.textContent = 'Processando...';
          actionEl.disabled = true;
          actionEl.classList.add('opacity-60', 'cursor-not-allowed');
          try {
            const { res, data } = await api.request(endpoint, { method: 'POST' }, { notify: false });
            if (res.ok && data?.success !== false) {
              ui.message(successMsg || data?.message || 'Benefício resgatado!', 'success');
              setTimeout(() => window.location.reload(), 900);
              return;
            }
            ui.message(data?.message || 'Não foi possível resgatar agora.', 'error');
          } catch (_) {
            ui.message('Falha de conexão ao resgatar. Tente novamente.', 'error');
          }
          actionEl.textContent = prevText;
          actionEl.disabled = false;
          actionEl.classList.remove('opacity-60', 'cursor-not-allowed');
          actionEl.dataset.loading = '0';
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

          if (titleEl) titleEl.textContent = bonus?.titulo || 'Bônus de adesão';
          if (statusEl) {
            statusEl.textContent = meta.label;
            statusEl.className = `inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${meta.badgeClass}`;
          }
          if (descriptionEl) {
            descriptionEl.textContent = bonus?.descricao || meta.message;
          }
          if (expiryEl) {
            expiryEl.textContent = formatDatePtBr(bonus?.data_expiracao, 'Não informada');
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

          actionEl.disabled = false;
          actionEl.classList.remove('opacity-60', 'cursor-not-allowed');

          if (payload?.status === 'redeemed') {
            actionEl.textContent = 'Bônus já resgatado';
            actionEl.disabled = true;
            actionEl.classList.add('opacity-60', 'cursor-not-allowed');
            actionEl.onclick = null;
            return;
          }

          if (payload?.status === 'available' && bonus?.id) {
            actionEl.textContent = 'Resgatar bônus';
            actionEl.onclick = () => redeemBenefit(`/cliente/bonus-adesao/${bonus.id}/resgatar`, actionEl, 'Bônus de adesão resgatado!');
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

          if (titleEl) {
            titleEl.textContent = status === 'available'
              ? 'FELIZ ANIVERSÁRIO!'
              : (bonus?.titulo || 'Bônus aniversário');
          }
          if (statusEl) {
            statusEl.textContent = meta.label;
            statusEl.className = `inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] ${meta.badgeClass}`;
          }
          if (descriptionEl) {
            descriptionEl.textContent = status === 'available'
              ? (bonus?.descricao || 'Comemore seu aniversário conosco e ganhe uma cortesia especial.')
              : (bonus?.descricao || meta.message);
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

          if (status === 'redeemed') {
            actionEl.textContent = 'Bônus já resgatado';
            actionEl.disabled = true;
            actionEl.classList.add('opacity-60', 'cursor-not-allowed');
            actionEl.onclick = null;
            return;
          }

          if (status === 'available' && bonus?.id) {
            actionEl.textContent = 'Resgatar bônus';
            actionEl.onclick = () => redeemBenefit(`/cliente/bonus-aniversario/${bonus.id}/resgatar`, actionEl, 'Bônus aniversário resgatado!');
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
          const requiredLabelEl = document.getElementById('partnerLoyaltyRequiredLabel');

          const requiredPoints = Math.max(0, Number(loyalty?.pontos_necessarios || progress?.required_points || 0));
          const currentPoints = Math.max(0, Number(progress?.current_points || 0));
          const progressPercent = Math.max(0, Math.min(100, Number(progress?.percentage || 0)));
          const rewardAvailable = Boolean(progress?.reward_available);

          if (titleEl) titleEl.textContent = loyalty?.titulo || 'Cartão fidelidade';
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
          if (requiredLabelEl) {
            requiredLabelEl.textContent = `${requiredPoints || 0} pontos`;
          }
          if (rewardEl) {
            rewardEl.textContent = loyalty?.recompensa_descricao || 'Ainda não informada';
          }
          if (progressLabelEl) {
            progressLabelEl.textContent = `${currentPoints} / ${requiredPoints} pontos`;
          }
          if (progressStatusEl) {
            progressStatusEl.textContent = rewardAvailable
              ? 'Recompensa pronta'
              : (payload?.status === 'not_linked' ? 'Vincule-se' : 'Em andamento');
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
            expiryEl.textContent = formatDatePtBr(loyalty?.data_expiracao, 'Não informada');
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

          actionEl.disabled = false;
          actionEl.classList.remove('opacity-60', 'cursor-not-allowed');

          if (rewardAvailable && loyalty?.id) {
            actionEl.textContent = 'Resgatar recompensa';
            actionEl.onclick = () => redeemBenefit(`/cliente/cartao-fidelidade/${loyalty.id}/resgatar`, actionEl, 'Recompensa resgatada!');
            return;
          }

          if (loyalty?.id) {
            const perVisit = Number(loyalty?.pontos_por_visita || progress?.points_per_visit || 1);
            actionEl.textContent = `Registrar visita (+${perVisit})`;
            actionEl.onclick = () => redeemBenefit(`/cliente/cartao-fidelidade/${loyalty.id}/visita`, actionEl, 'Visita registrada! Ponto adicionado.');
            return;
          }

          actionEl.textContent = 'Ler QR da empresa';
          actionEl.onclick = () => {
            window.location.href = '/validar_resgate.html?modo=vinculo-empresa';
          };
        };
        const renderReminderCard = (payload) => {
          const card = document.querySelector('#partnerBirthdayCard .grid > div:nth-child(2)');
          if (!card) return;

          const reminder = payload || null;
          const active = Boolean(reminder?.ativo);
          const title = safeText(reminder?.titulo, 'Lembrete de retorno');
          const message = safeText(
            reminder?.mensagem,
            'A empresa pode reenviar clientes vinculados que ficaram um periodo sem visitar o estabelecimento.'
          );
          const intervalDays = Number(reminder?.dias_sem_visita || reminder?.dias_ausencia || 0);
          const notificationTitle = safeText(reminder?.notification_title, title);
          const notificationBody = safeText(
            reminder?.notification_body,
            'Ative as notificacoes para receber o convite de retorno no seu dispositivo.'
          );

          card.className = 'overflow-hidden rounded-[24px] bg-slate-50';
          card.innerHTML = `
            <img class="h-44 w-full object-cover" src="${safeImage(reminder?.imagem_url, IMAGE_FALLBACKS.promo)}" alt="${title}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" />
            <div class="space-y-3 p-5">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-bold text-[#111B3F]">Lembrete de retorno</p>
                  <h3 class="mt-2 text-xl font-extrabold text-[#111B3F]">${title}</h3>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.16em] ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-white text-slate-500'}">${active ? 'Ativo' : 'Inativo'}</span>
              </div>
              <p class="text-sm leading-6 text-slate-600">${message}</p>
              <div class="rounded-[18px] bg-white p-4">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Push configurado</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">${notificationTitle} - ${notificationBody}</p>
              </div>
              <p class="text-sm font-semibold text-slate-500">${intervalDays > 0 ? `Envio automático após ${plural(intervalDays, 'dia')} sem visita.` : 'Intervalo de retorno nao configurado.'}</p>
            </div>
          `;
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
            : 'Nenhuma promoção ativa no momento.';

          normalizedItems.forEach((promo) => {
            const meta = promotionStatusMeta(promo.viewer_status || promo.status);
            const validade = formatDatePtBr(promo.data_expiracao || promo.validade, '');
            const card = document.createElement('article');
            card.className = 'tdt-benefit';
            card.innerHTML = `
              <img class="tdt-benefit__img" style="height:140px" src="${safeImage(promo.imagem_url || promo.imagem, IMAGE_FALLBACKS.promo)}" alt="" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" />
              <div class="tdt-benefit__body">
                <div class="tdt-benefit__head">
                  <h3 class="tdt-benefit__title">${safeText(promo.titulo, 'Promoção')}</h3>
                  <span class="tdt-chip-status">${meta.label}</span>
                </div>
                <p class="tdt-benefit__desc">${safeText(promo.descricao, '')}</p>
                ${validade ? `<p class="tdt-benefit__hint">Válido até ${validade}</p>` : ''}
                <div class="mt-3"><button type="button" class="partner-promo-action btn btn--primary">Resgatar</button></div>
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
                actionBtn.textContent = 'Resgatar promoção';
                actionBtn.onclick = () => redeemBenefit(`/cliente/promocoes/${promo.id}/resgatar`, actionBtn, 'Promoção resgatada!');
              } else if (promo.viewer_status === 'redeemed') {
                actionBtn.textContent = 'Já utilizada';
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
          // O cliente nao apresenta mais QR proprio; para cliente o botao e desnecessario.
          if (perfilViewer === 'cliente') {
            reviewShowQrEl.classList.add('hidden');
          }
          reviewShowQrEl.addEventListener('click', () => {
            if (!perfilViewer) {
              window.location.href = '/entrar.html';
            } else if (perfilViewer !== 'cliente') {
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
                  <div class="h-full rounded-full bg-[linear-gradient(135deg,#133f8c_0%,#b01774_100%)]" style="width:${percent}%"></div>
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
            const reviewerName = safeText(review?.cliente?.nome, 'Cliente');
            const reviewerInitial = reviewerName.charAt(0).toUpperCase();
            const card = document.createElement('article');
            card.className = 'partner-review-item rounded-[22px] bg-slate-50 p-4';
            card.innerHTML = `
              <div class="partner-review-avatar">${reviewerInitial}</div>
              <div class="partner-review-copy">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-extrabold text-[#111B3F]">${reviewerName}</p>
                    <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-[#B01774]">${renderStars(Number(review?.nota || review?.estrelas || 0))}</p>
                  </div>
                  <span class="text-xs text-slate-400">${formatDatePtBr(review?.updated_at || review?.created_at, 'Agora')}</span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">${safeText(review?.comentario, 'Cliente avaliou sem comentário adicional.')}</p>
              </div>
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

        setText('partner-category', String(companyInfo.categoria || companyInfo.ramo || 'Empresa').toUpperCase(), 'EMPRESA');
        setText('partner-name', companyInfo.nome, 'Empresa');
        setText('partner-address', companyInfo.endereco, 'Endereço não informado');
        setText('partner-full-address', companyInfo.endereco, 'Não informado');
        setText('partner-about', companyInfo.descricao, 'Esta empresa já está pronta para receber clientes via QR Code e operar bônus ou fidelidade conforme configuração ativa.');
        setText('partner-phone', companyInfo.telefone, 'Não informado');
        setText('partner-whatsapp', companyInfo.whatsapp, 'Não informado');
        setText('partner-instagram', companyInfo.instagram, 'Não informado');
        setText('partner-facebook', companyInfo.facebook, 'Não informado');

        const rating = Number(companyInfo.avaliacao_media || 0);
        const totalReviews = Number(companyInfo.total_avaliacoes || 0);
        setText('partner-rating', rating > 0 ? rating.toFixed(1).replace('.', ',') : 'Novo', 'Novo');
        setText('partner-review-count', totalReviews ? `${totalReviews} avaliações` : 'Sem avaliações ainda', 'Sem avaliações ainda');
        setText('partner-rating-stars', renderStars(rating), renderStars(0));

        setLink('partnerWhatsappLink', companyInfo.whatsapp, (value) => `https://wa.me/${String(value).replace(/\D/g, '')}`);
        setLink('partnerInstagramLink', companyInfo.instagram, (value) => String(value).startsWith('http') ? value : `https://instagram.com/${String(value).replace(/^@/, '')}`);
        setLink('partnerFacebookLink', companyInfo.facebook, (value) => String(value).startsWith('http') ? value : `https://facebook.com/${String(value).replace(/^@/, '')}`);

        const statusBadge = document.getElementById('partner-status-badge');
        if (statusBadge) statusBadge.textContent = companyInfo.publicamente_visivel ? 'Disponível no app' : 'Indisponível no momento';


        // ----- Banner de capa -----
        const bannerImg = document.getElementById('partner-banner');
        const bannerFallback = document.getElementById('partner-banner-fallback');
        const bannerUrl = safeText(companyInfo.banner, '');
        if (bannerImg && bannerUrl) {
          bannerImg.src = bannerUrl;
          bannerImg.style.display = 'block';
          bannerImg.onload = () => { if (bannerFallback) bannerFallback.style.display = 'none'; };
          bannerImg.onerror = () => {
            bannerImg.style.display = 'none';
            if (bannerFallback) bannerFallback.style.display = 'block';
          };
        }

        // ----- Redes sociais (apenas ícones, abrem direto) -----
        const bindSocial = (id, value, hrefBuilder) => {
          const el = document.getElementById(id);
          if (!el) return;
          const raw = safeText(value, '');
          if (raw) {
            el.href = hrefBuilder(raw);
            el.classList.remove('hidden');
          } else {
            el.classList.add('hidden');
          }
        };
        bindSocial('partnerSocialWhatsapp', companyInfo.whatsapp, (v) => `https://wa.me/${String(v).replace(/\D/g, '')}`);
        bindSocial('partnerSocialInstagram', companyInfo.instagram, (v) => String(v).startsWith('http') ? v : `https://instagram.com/${String(v).replace(/^@/, '')}`);
        bindSocial('partnerSocialFacebook', companyInfo.facebook, (v) => String(v).startsWith('http') ? v : `https://facebook.com/${String(v).replace(/^@/, '')}`);

        // ----- Botões principais -----
        const goQr = () => {
          if (perfilViewer === 'cliente') {
            window.location.href = '/validar_resgate.html?modo=vinculo-empresa';
          } else if (!perfilViewer) {
            window.location.href = '/entrar.html';
          } else {
            window.location.href = redirectMap[perfilViewer] || '/meus_pontos.html';
          }
        };
        document.getElementById('partnerActionQr')?.addEventListener('click', goQr);

        // Botao "Cadastrar-me": vincula o cliente sem QR (util quando a camera falha).
        const vincularBtn = document.getElementById('partnerActionVincular');
        const empresaVincId = companyInfo.id || new URLSearchParams(location.search).get('id');
        const jaVinculado = Boolean(companyInfo.vinculada || companyInfo.inscrito || companyInfo.ja_vinculado || companyInfo.cliente_vinculado);
        if (vincularBtn && perfilViewer === 'cliente' && empresaVincId && !jaVinculado) {
          vincularBtn.hidden = false;
          vincularBtn.addEventListener('click', async () => {
            vincularBtn.disabled = true;
            const { res, data: d } = await api.request(`/cliente/empresas/${empresaVincId}/vincular`, { method: 'POST' }, { notify: false });
            if (res.ok && d?.success !== false) {
              ui.message(d?.message || 'Você se vinculou a esta empresa!', 'success');
              setTimeout(() => window.location.reload(), 900);
            } else {
              vincularBtn.disabled = false;
              ui.message(d?.message || 'Não foi possível vincular agora.', 'error');
            }
          });
        }

        const endereco = safeText(companyInfo.endereco, '');
        const mapsBtn = document.getElementById('partnerActionMaps');
        if (mapsBtn && endereco) {
          mapsBtn.hidden = false;
          mapsBtn.addEventListener('click', () => {
            window.open(`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(endereco)}`, '_blank', 'noopener');
          });
        }

        const telefone = safeText(companyInfo.telefone || companyInfo.whatsapp, '');
        const callBtn = document.getElementById('partnerActionCall');
        if (callBtn && telefone.replace(/\D/g, '')) {
          callBtn.hidden = false;
          callBtn.addEventListener('click', () => {
            window.location.href = `tel:${telefone.replace(/[^\d+]/g, '')}`;
          });
        }

        document.getElementById('partnerActionShare')?.addEventListener('click', async () => {
          const shareData = {
            title: safeText(companyInfo.nome, 'Empresa'),
            text: `Confira ${safeText(companyInfo.nome, 'esta empresa')} no Tem de Tudo`,
            url: window.location.href,
          };
          try {
            if (navigator.share) {
              await navigator.share(shareData);
            } else if (navigator.clipboard) {
              await navigator.clipboard.writeText(shareData.url);
              ui.message('Link copiado para compartilhar.', 'success');
            }
          } catch (_) { /* usuário cancelou o compartilhamento */ }
        });

        renderBonusCard(null);
        renderBirthdayCard(companyInfo?.bonus_aniversario
          ? {
              status: companyInfo.bonus_aniversario?.status === 'available'
                ? 'public'
                : companyInfo.bonus_aniversario?.status,
              message: companyInfo.bonus_aniversario?.status === 'available'
                ? 'Entre como cliente vinculado para consultar sua elegibilidade ao bônus aniversário.'
                : 'A empresa não está operando bônus aniversário no momento.',
              bonus: companyInfo.bonus_aniversario,
            }
          : null);
        renderLoyaltyCard(null);
        renderReminderCard(companyInfo?.lembrete_retorno || null);
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
            // Bônus de adesão aparece automaticamente ao entrar na empresa (quando disponível),
            // respeitando a preferência "não mostrar novamente" — EXCETO no cadastro/vínculo
            // recém-concluído (linked=1), quando a celebração é obrigatória.
            if (bonusPayload?.status === 'available') {
              const justLinked = params.get('linked') === '1';
              let hiddenPref = false;
              try { hiddenPref = localStorage.getItem(`tdt_bonus_adesao_hide_${selectedCompanyId}`) === '1'; } catch (_) { hiddenPref = false; }
              if (!hiddenPref || justLinked) {
                const b = bonusPayload.bonus || {};
                setTimeout(() => tdtShowBonusModal({
                  empresaId: selectedCompanyId,
                  bonusId: b.id,
                  titulo: b.titulo || 'Bônus de adesão',
                  descricao: b.descricao || 'Você ganhou um benefício de boas-vindas!',
                  imagem: safeImage(b.imagem_url || b.imagem, IMAGE_FALLBACKS.promo),
                  validade: b.data_expiracao,
                  celebrar: justLinked,
                }), 400);
              }
            }
            if (params.get('linked') === '1') {
              ui.message(
                bonusPayload.status === 'available'
                  ? 'Empresa vinculada com sucesso. Bônus de adesão disponível para resgatar.'
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
        setText('partner-address', info.endereco, 'Endereço não informado');
        setText('partner-about', info.descricao, 'Esta empresa opera benefícios e atendimento presencial por QR Code.');
        setText('partner-phone', info.telefone, 'Não informado');
        setText('partner-whatsapp', info.whatsapp, 'Não informado');
        setText('partner-instagram', info.instagram, 'Não informado');
        setText('partner-facebook', info.facebook, 'Não informado');
        setText('partner-full-address', info.endereco, 'Não informado');
        setLink('partnerWhatsappLink', info.whatsapp, (value) => `https://wa.me/${String(value).replace(/\D/g, '')}`);
        setLink('partnerInstagramLink', info.instagram, (value) => String(value).startsWith('http') ? value : `https://instagram.com/${String(value).replace(/^@/, '')}`);
        setLink('partnerFacebookLink', info.facebook, (value) => String(value).startsWith('http') ? value : `https://facebook.com/${String(value).replace(/^@/, '')}`);
        renderReminderCard(info.lembrete_retorno || null);
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
              if (streak.bonus_pontos > 0) msg += ` Bônus streak: +${streak.bonus_pontos} pts`;
              if (streak.novo_recorde) msg += ` 🏆 Novo recorde!`;
            }
            ui.message(msg, 'success');
            setTimeout(() => {
              window.location.href = '/meus_pontos.html';
            }, 500);
          } else {
            const errMsg = data?.message || 'Não foi possível acumular pontos agora.';
            ui.message(errMsg, 'error');
          }
        });
      }

      return;
    },

    async recompensas() {
      if (!(await auth.guard(['cliente']))) return;
      const [{ data: pontosResp }, { data: dashboardResp }, { data: historicoResp }, { data: promosResp }, { data: cuponsResp }] = await Promise.all([
        api.request('/pontos/meus-dados', {}, { notify: false }),
        api.request('/cliente/dashboard', {}, { notify: false }),
        api.request('/pontos/historico', {}, { notify: false }),
        api.request('/cliente/promocoes', {}, { notify: false }),
        api.request('/pontos/meus-cupons', {}, { notify: false }),
      ]);

      const saldoAtual = pontosResp?.data?.pontos_total ?? pontosResp?.data?.saldo ?? 0;
      const currentUser = await auth.ensure();

      // Saudação
      const greetingEl = document.getElementById('recompensasGreeting');
      if (greetingEl) {
        const firstName = safeText(currentUser?.name || currentUser?.nome || '').split(' ')[0];
        greetingEl.textContent = firstName ? `Olá, ${firstName}` : 'Suas recompensas';
      }

      // 2. Pontuação atual
      const totalPointsEl = document.getElementById('loyaltyTotalPoints');
      if (totalPointsEl) totalPointsEl.textContent = Number(saldoAtual).toLocaleString('pt-BR');

      // 1 + 3 + 4 + 5. Cartões fidelidade por estabelecimento vinculado
      const dashboardData = dashboardResp?.data || {};
      const linkedCompanies = toArray(dashboardData.empresas_vinculadas);
      const loyaltySkeleton = document.getElementById('loyaltySkeleton');
      const loyaltyList = document.getElementById('loyaltyList');
      const loyaltyEmpty = document.getElementById('loyaltyEmpty');
      const loyaltyCount = document.getElementById('loyaltyCount');

      const snapshots = await Promise.all(linkedCompanies.map(async (company) => {
        const { res, data } = await api.request(
          `/cliente/cartao-fidelidade/progresso/${encodeURIComponent(company.id)}`,
          {},
          { notify: false },
        );
        if (!res.ok || data?.success === false) return null;
        return { company, snapshot: data?.data || {} };
      }));

      // Só mantém empresas com cartão configurado; ordena recompensa liberada primeiro, depois maior progresso
      const loyaltyItems = snapshots
        .filter((item) => item && item.snapshot && item.snapshot.status && item.snapshot.status !== 'unavailable')
        .map((item) => {
          const loyalty = item.snapshot.card || {};
          const progress = item.snapshot.progress || {};
          const required = Math.max(0, Number(loyalty.pontos_necessarios || progress.required_points || 0));
          const current = Math.max(0, Number(progress.current_points || 0));
          const pct = Math.max(0, Math.min(100, Number(progress.percentage || (required ? (current / required) * 100 : 0))));
          const rewardAvailable = Boolean(progress.reward_available) || item.snapshot.status === 'reward_available';
          return { ...item, loyalty, progress, required, current, pct, rewardAvailable };
        })
        .sort((a, b) => (Number(b.rewardAvailable) - Number(a.rewardAvailable)) || (b.pct - a.pct));

      const renderLoyaltyProgramCard = (item) => {
        const c = item.company;
        const meta = loyaltyStatusMeta(item.snapshot.status);
        const reward = safeText(item.loyalty.recompensa_descricao, 'Recompensa a definir pelo estabelecimento');
        const ppv = Math.max(1, Number(item.loyalty.pontos_por_visita || item.progress.points_per_visit || 1));
        const remaining = Math.max(0, item.required - item.current);
        const remainingVisits = Math.ceil(remaining / ppv);
        const progressMsg = item.rewardAvailable
          ? 'Recompensa liberada! 🎉'
          : (remaining > 0
              ? `Faltam ${plural(remaining, 'ponto')}${remainingVisits ? ` • ~${plural(remainingVisits, 'visita')}` : ''}`
              : 'Quase lá!');
        return `
          <article class="loyalty-card ${item.rewardAvailable ? 'loyalty-card--ready' : ''} p-5">
            <div class="flex items-center gap-3">
              <img class="loyalty-logo" src="${safeImage(c.logo, IMAGE_FALLBACKS.store)}" alt="${safeText(c.nome, 'Empresa')}" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" />
              <div class="min-w-0 flex-1">
                <h4 class="truncate font-headline text-lg font-extrabold text-on-surface">${safeText(c.nome, 'Empresa')}</h4>
                <p class="text-xs font-medium text-on-surface-variant">Cartão fidelidade</p>
              </div>
              <span class="loyalty-status-badge ${meta.badgeClass}">${meta.label}</span>
            </div>

            <div class="mt-5">
              <div class="flex items-end justify-between gap-2">
                <p class="text-sm font-semibold text-on-surface-variant"><span class="text-2xl font-extrabold text-[#111B3F]">${item.current}</span> / ${item.required} pontos</p>
                <p class="text-xs font-bold ${item.rewardAvailable ? 'text-emerald-600' : 'text-on-surface-variant'}">${progressMsg}</p>
              </div>
              <div class="loyalty-progress-track mt-2"><div class="loyalty-progress-bar" data-target="${item.pct}"></div></div>
            </div>

            <div class="loyalty-reward-chip mt-4">
              <span class="material-symbols-outlined text-[#B01774]" style="font-variation-settings:'FILL' 1;">redeem</span>
              <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Próxima recompensa</p>
                <p class="truncate text-sm font-extrabold text-on-surface">${reward}</p>
              </div>
            </div>

            ${item.rewardAvailable
              ? `<button class="loyalty-redeem-btn mt-4" type="button" data-loyalty-redeem="${c.id}"><span class="material-symbols-outlined">redeem</span> Resgatar benefício</button>`
              : ''}
          </article>`;
      };

      loyaltySkeleton?.classList.add('hidden');
      if (loyaltyList) {
        loyaltyList.innerHTML = loyaltyItems.map(renderLoyaltyProgramCard).join('');
        // Preenchimento suave da barra de progresso
        requestAnimationFrame(() => {
          loyaltyList.querySelectorAll('.loyalty-progress-bar').forEach((bar) => {
            bar.style.width = `${Number(bar.dataset.target || 0)}%`;
          });
        });
        // Resgate acontece na página da empresa (o cliente escaneou o QR e resgata lá).
        loyaltyList.querySelectorAll('[data-loyalty-redeem]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const empresaId = btn.getAttribute('data-loyalty-redeem');
            window.location.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(empresaId)}`;
          });
        });
      }
      if (loyaltyEmpty) loyaltyEmpty.classList.toggle('hidden', loyaltyItems.length > 0);
      if (loyaltyCount) loyaltyCount.textContent = loyaltyItems.length ? plural(loyaltyItems.length, 'cartão', 'cartões') : '';

      // 6. Histórico de resgates (usa o histórico existente; não cria histórico novo)
      const historico = toArray(historicoResp?.data?.data || historicoResp?.data);
      const resgates = historico.filter((i) => {
        const tipo = String(i.tipo || '').toLowerCase();
        return tipo.includes('resg') || tipo.includes('redeem') || Number(i.pontos || 0) < 0;
      }).slice(0, 6);
      const redemptionSection = document.getElementById('redemptionSection');
      const redemptionList = document.getElementById('redemptionList');
      if (redemptionList && resgates.length) {
        redemptionList.innerHTML = resgates.map((i) => {
          const nome = safeText(i.empresa?.nome || i.empresa_nome, 'Empresa');
          const desc = safeText(i.descricao, 'Resgate de benefício');
          const data = i.created_at ? new Date(i.created_at).toLocaleDateString('pt-BR') : '--';
          return `
            <div class="redemption-row">
              <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#b01774]/10 text-[#b01774]"><span class="material-symbols-outlined text-[20px]">redeem</span></span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-on-surface">${desc}</p>
                <p class="truncate text-xs text-on-surface-variant">${nome} • ${data}</p>
              </div>
              <span class="loyalty-status-badge bg-emerald-50 text-emerald-700">Resgatado</span>
            </div>`;
        }).join('');
        redemptionSection?.classList.remove('hidden');
      }

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
            ? 'Resgatar'
            : (p.viewer_status === 'redeemed' ? 'Já utilizada' : 'Ver empresa');
          const card = document.createElement('div');
          card.className = 'rounded-2xl bg-white/80 border border-surface-variant/30 shadow-sm p-4 flex flex-col gap-2';
          card.innerHTML = `
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-bold text-on-surface">${p.titulo || p.nome || 'Promoção'}</p>
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
          const targetUrl = btn.dataset.promoUrl || '/parceiros_tem_de_tudo.html';
          // O resgate acontece na página da empresa (cliente escaneou e resgata lá).
          window.location.href = targetUrl;
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

        if (!empresaData || !empresaData.empresa) {
          empresaData = {
            empresa: {
              id: null,
              nome: '',
              ramo: '',
              categoria: '',
              cnpj: '',
              endereco: '',
              telefone: '',
              logo: '',
              whatsapp: '',
              instagram: '',
              facebook: '',
              descricao: '',
              points_multiplier: 1,
              ativo: false
            },
            total_clientes: 0,
            pontos_distribuidos: 0,
            promocoes_ativas: 0
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
            ['relat_rios_gerais_master.html', 'Relatórios'],
            ['banners_e_categorias_master.html', 'Conteúdo'],
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
          target = perfil === 'admin' ? '/configuracoes_admin.html' : (perfil === 'empresa' ? '/meu_perfil.html' : '/configuracoes_cliente.html');
        } else if (text.includes('ajuda') || text.includes('suporte')) {
          target = 'mailto:contato@temdetudo.com';
        }

        if (!target) return;
        btn.dataset.profileNavBound = '1';
        btn.addEventListener('click', go(target));
      });

      const safeProfileName = safeText(user?.name || user?.nome, 'Usuário');
      if (headerGreeting) headerGreeting.textContent = `Olá, ${safeProfileName}`;
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
        if (heroProgressText) heroProgressText.textContent = plural(promocoesAtivas, 'promoção ativa', 'promoções ativas');
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
          const linkedAt = formatDatePtBr(company?.data_vinculo || company?.data_inscricao, 'Vínculo recente');

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
            ? plural(linkedCompanies.length, 'empresa')
            : 'Nenhum vínculo';
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
        if (pf('pfNascimento')) pf('pfNascimento').value = toDateInputValue(user?.data_nascimento);
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
        if (pf('pfEmpresaWhatsapp')) pf('pfEmpresaWhatsapp').value = emp.whatsapp || '';
        if (pf('pfEmpresaInstagram')) pf('pfEmpresaInstagram').value = emp.instagram || '';
        if (pf('pfEmpresaFacebook')) pf('pfEmpresaFacebook').value = emp.facebook || '';
        if (pf('pfEmpresaDescricao')) pf('pfEmpresaDescricao').value = emp.descricao || '';
        // Upload de imagens via dispositivo + crop (substitui campo de URL)
        bindEmpresaImageUploader('logo', emp.logo || '');
        bindEmpresaImageUploader('banner', emp.banner || '');
        // Atualizar hero com nome da empresa em vez do user
        if (heroName && emp.nome) heroName.textContent = emp.nome;
      }

      // Feedback de loading no botao (desabilita + troca texto, restaura ao fim).
      const setButtonLoading = (btn, loadingText) => {
        if (!btn) return () => {};
        btn.dataset.saving = '1';
        const original = btn.textContent;
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.textContent = loadingText;
        return () => {
          btn.dataset.saving = '';
          btn.disabled = false;
          btn.style.opacity = '';
          btn.textContent = original;
        };
      };

      // Salvar dados
      pf('pfSalvar')?.addEventListener('click', async () => {
        const saveBtn = pf('pfSalvar');
        if (saveBtn?.dataset.saving === '1') return; // evita duplo clique
        if (perfil === 'empresa') {
          const payload = {
            name:             pf('pfNome')?.value,
            email:            pf('pfEmail')?.value,
            telefone:         pf('pfTelefone')?.value,
            empresa_nome:     pf('pfEmpresaNome')?.value,
            empresa_ramo:     pf('pfEmpresaRamo')?.value,
            empresa_cnpj:     pf('pfEmpresaCnpj')?.value,
            empresa_endereco: pf('pfEmpresaEndereco')?.value,
            // Redes sociais: enviamos string vazia (nao null) para permitir limpar.
            empresa_whatsapp: pf('pfEmpresaWhatsapp')?.value ?? '',
            empresa_instagram: pf('pfEmpresaInstagram')?.value ?? '',
            empresa_facebook: pf('pfEmpresaFacebook')?.value ?? '',
            empresa_descricao: pf('pfEmpresaDescricao')?.value ?? '',
            // logo e banner são enviados pelos endpoints dedicados de upload
          };
          const restoreBtn = saveBtn ? setButtonLoading(saveBtn, 'Salvando...') : null;
          const { res, data } = await api.request('/empresa/perfil', { method: 'PUT', body: JSON.stringify(payload) });
          restoreBtn?.();
          if (res.ok && data?.success) {
            ui.message('Perfil atualizado.', 'success');
            // Reflete o que foi persistido (garante que a alteracao "pegou").
            const savedEmp = data?.data?.empresa;
            if (savedEmp) {
              if (pf('pfEmpresaNome')) pf('pfEmpresaNome').value = savedEmp.nome ?? '';
              if (pf('pfEmpresaRamo')) pf('pfEmpresaRamo').value = savedEmp.ramo ?? '';
              if (pf('pfEmpresaCnpj')) pf('pfEmpresaCnpj').value = savedEmp.cnpj ?? '';
              if (pf('pfEmpresaEndereco')) pf('pfEmpresaEndereco').value = savedEmp.endereco ?? '';
              if (pf('pfEmpresaWhatsapp')) pf('pfEmpresaWhatsapp').value = savedEmp.whatsapp ?? '';
              if (pf('pfEmpresaInstagram')) pf('pfEmpresaInstagram').value = savedEmp.instagram ?? '';
              if (pf('pfEmpresaFacebook')) pf('pfEmpresaFacebook').value = savedEmp.facebook ?? '';
              if (heroName && savedEmp.nome) heroName.textContent = savedEmp.nome;
            }
          } else {
            // Nao limpamos o formulario: os dados digitados permanecem.
            ui.message(data?.message || 'Erro ao atualizar perfil. Seus dados foram mantidos.', 'error');
          }
        } else {
          const payload = {
            name:            pf('pfNome')?.value,
            email:           pf('pfEmail')?.value,
            telefone:        pf('pfTelefone')?.value,
            cpf:             pf('pfCpf')?.value,
            data_nascimento: pf('pfNascimento')?.value,
          };
          const restoreBtn = saveBtn ? setButtonLoading(saveBtn, 'Salvando...') : null;
          const { res, data } = await api.request('/perfil', { method: 'PUT', body: JSON.stringify(payload) });
          restoreBtn?.();
          if (res.ok && data?.success) {
            ui.message('Perfil atualizado.', 'success');
            auth.save(auth.getStored().token, data.data);
          } else {
            ui.message(data?.message || 'Erro ao atualizar perfil. Seus dados foram mantidos.', 'error');
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
      // Fluxo único: o cliente lê o QR da empresa. A empresa apenas exibe o
      // QR da loja nesta página (nunca escaneia ninguém).
      const mode = new URLSearchParams(window.location.search).get('modo') || (perfil === 'empresa' ? 'beneficios' : 'vinculo-empresa');

      const titleEl = document.querySelector('main header h1');
      const copyEl = document.getElementById('scanInstructions');
      const buttonLabel = document.getElementById('usarCupomBtn');
      const input = document.getElementById('cupomId');
      const btn = document.getElementById('usarCupomBtn');

      if (!input || !btn) return;

      if (titleEl) {
        titleEl.textContent = perfil === 'empresa'
          ? 'Meu QR da loja'
          : 'Ler QR da Empresa';
      }
      if (copyEl) {
        copyEl.textContent = perfil === 'empresa'
          ? 'Mostre este QR para o cliente escanear. Ele mesmo resgata os benefícios (bônus, fidelidade e promoções) na página da sua empresa.'
          : 'Escaneie o QR do adesivo da empresa para se vincular no app.';
      }
      if (buttonLabel && perfil !== 'empresa') {
        buttonLabel.innerHTML = '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">link</span> Vincular Agora';
      }
      if (perfil === 'cliente' && mode === 'vinculo-empresa') {
        input.placeholder = 'Cole o código do QR da empresa...';
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
          if (handleCompanyAccessFailure(res, data, 'Nao foi possivel carregar o QR Code operacional desta empresa.')) {
            container.innerHTML = '<p class="text-sm text-outline">QR Code indisponivel enquanto o acesso operacional da empresa nao for liberado.</p>';
            return;
          }
          const qrList = data?.data || [];
          if (res.ok && qrList.length && qrList[0].code) {
            const qr = qrList[0];
            container.innerHTML = `
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

      // Cliente: esta pagina e apenas o scanner (ler o QR do adesivo da empresa).

      const emitQrValidationResult = (detail = {}) => {
        window.dispatchEvent(new CustomEvent('tdt-qr-validation-result', {
          detail: {
            perfil,
            mode,
            ...detail,
          },
        }));
      };

      // Apenas o cliente envia códigos: lê o QR da empresa para se vincular.
      const submitQrCode = async (rawCode, options = {}) => {
        if (perfil === 'empresa') return false;
        const codigo = String(rawCode || '').trim();
        if (!codigo) {
          const message = 'Informe o código do QR da empresa.';
          ui.message(message, 'warning');
          emitQrValidationResult({
            ok: false,
            codigo,
            source: options.source || 'manual',
            message,
          });
          return false;
        }

        btn.disabled = true;
        btn.classList.add('opacity-60');

        const { res, data } = await api.request('/cliente/vincular-empresa-qrcode', {
          method: 'POST',
          body: JSON.stringify({ code: codigo }),
        });
        btn.disabled = false;
        btn.classList.remove('opacity-60');

        if (res.ok && data?.success) {
          emitQrValidationResult({
            ok: true,
            codigo,
            source: options.source || 'manual',
            message: data?.message || null,
            data: data?.data || {},
          });

          clearPendingCompanyQr();
          ui.message(data?.message || 'Empresa vinculada com sucesso.', 'success');
          const empresaId = data?.data?.empresa?.id || '';
          const publicUrl = data?.data?.public_page_url
            || (empresaId ? `/detalhe_do_parceiro.html?id=${encodeURIComponent(empresaId)}` : '/parceiros_tem_de_tudo.html');
          const target = publicUrl.includes('?') ? `${publicUrl}&linked=1` : `${publicUrl}?linked=1`;
          setTimeout(() => {
            window.location.href = target;
          }, 500);
          return true;
        }

        const message = data?.message || 'Nao foi possivel vincular esta empresa.';
        emitQrValidationResult({
          ok: false,
          codigo,
          source: options.source || 'manual',
          message,
          data: data?.data || {},
        });
        ui.message(message, 'error');
        return false;
      };

      btn.addEventListener('click', async () => {
        await submitQrCode(input.value, { source: 'manual' });
        return;
      });

      input.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        await submitQrCode(input.value, { source: 'manual' });
      });

      window.addEventListener('tdt-qr-scanned', async (event) => {
        const codigo = String(event?.detail?.codigo || '').trim();
        if (!codigo) return;
        input.value = codigo;
        await submitQrCode(codigo, { source: event?.detail?.source || 'camera' });
      });
    },

    async configuracoes() {
      if (!(await auth.guard(['cliente', 'empresa', 'admin']))) return;
      const user = await auth.ensure();

      const heroName = document.getElementById('cfg-nome');
      const heroEmail = document.getElementById('cfg-email');
      if (heroName) heroName.textContent = user?.name || user?.nome || 'Usuário';
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
        if (!confirm('ATENCAO: Esta acao e IRREVERSIVEL.\nTodos os seus dados pessoais serao removidos permanentemente.\n\nDeseja continuar?')) return;
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
      const [promos, resumo, qrcodes, pushConfigResponse, perfilResponse] = await Promise.all([
        api.request('/empresa/promocoes'),
        api.request('/empresa/relatorios/resumo', {}, { notify: false }),
        api.request('/empresa/qrcodes', {}, { notify: false }),
        api.request('/push/public-key', {}, { requireAuth: false, notify: false }),
        api.request('/empresa/perfil', {}, { notify: false }),
      ]);
      if (
        handleCompanyAccessFailure(promos.res, promos.data, 'Nao foi possivel carregar as ofertas desta empresa.')
        || handleCompanyAccessFailure(resumo.res, resumo.data, 'Nao foi possivel carregar o resumo operacional desta empresa.')
        || handleCompanyAccessFailure(qrcodes.res, qrcodes.data, 'Nao foi possivel carregar o QR Code da empresa.')
      ) {
        return;
      }

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
      const renderCompanyStorefrontBlock = (payload) => {
        const main = document.querySelector('main');
        if (!main) return;

        const info = payload?.info || {};
        const publicUrl = safeText(payload?.publicUrl, '/parceiros_tem_de_tudo.html');
        const anchor = document.getElementById('empresaPushCampaignsSummary')
          || kpiVolume?.closest('section')
          || campanhasBox?.closest('section')
          || main.lastElementChild;
        const section = ensureAdjacentSection(
          'empresaStorefrontSummary',
          anchor,
          'afterend',
          'space-y-4 rounded-2xl bg-surface-container-lowest p-5 shadow-sm'
        );
        if (!section) return;

        section.innerHTML = `
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant">Minha vitrine</p>
              <h2 class="mt-2 font-headline text-xl font-extrabold text-on-surface">Dados públicos da vitrine</h2>
              <p class="mt-2 text-sm leading-6 text-on-surface-variant">${safeText(info?.descricao, 'Atualize o perfil da empresa para mostrar descrição, contatos e proposta comercial da vitrine.')}</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
              <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html">
                <span class="material-symbols-outlined">edit_square</span>
                <span>Editar vitrine</span>
              </a>
              <a class="empresa-shortcut-card" href="${publicUrl}">
                <span class="material-symbols-outlined">open_in_new</span>
                <span>Ver página pública</span>
              </a>
            </div>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">WhatsApp</p>
              <p class="mt-2 text-sm font-bold text-on-surface">${safeText(info?.whatsapp, 'Não informado')}</p>
            </div>
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Instagram</p>
              <p class="mt-2 text-sm font-bold text-on-surface">${safeText(info?.instagram, 'Não informado')}</p>
            </div>
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Facebook</p>
              <p class="mt-2 text-sm font-bold text-on-surface">${safeText(info?.facebook, 'Não informado')}</p>
            </div>
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Endereço</p>
              <p class="mt-2 text-sm font-bold text-on-surface">${safeText(info?.endereco, 'Endereço não informado')}</p>
            </div>
          </div>
        `;
      };
      const renderCompanyPushCampaignsBlock = (payload) => {
        const main = document.querySelector('main');
        if (!main) return;

        const cards = payload?.cards || {};
        const pushSummary = payload?.push || {};
        const pushConfig = payload?.pushConfig || {};
        const promotions = toArray(payload?.promotions);
        const linkedCustomers = Number(pushSummary.clientes_vinculados ?? cards.total_clientes_vinculados ?? 0);
        const activePushCustomers = Number(pushSummary.clientes_com_push_ativo ?? cards.clientes_com_push_ativo ?? 0);
        const inactivePushCustomers = Math.max(0, Number(pushSummary.clientes_sem_push_ativo ?? (linkedCustomers - activePushCustomers)));
        const activePromotions = promotions.filter((item) => item?.ativo !== false && item?.status !== 'pausada').length;
        const lastSent = pushSummary.ultimo_envio_notificacao || cards.ultimo_envio_notificacao || null;
        const serverReady = Boolean(pushConfig?.configured);
        const weeklyLimit = normalizeWeeklyLimitStatus(payload?.weeklyLimit || {});

        let section = document.getElementById('empresaPushCampaignsSummary');
        if (!section) {
          section = document.createElement('section');
          section.id = 'empresaPushCampaignsSummary';
          const anchor = kpiVolume?.closest('section') || campanhasBox?.closest('section');
          if (anchor?.parentNode) {
            anchor.insertAdjacentElement('afterend', section);
          } else {
            main.appendChild(section);
          }
        }

        section.className = 'space-y-4 rounded-2xl bg-surface-container-lowest p-5 shadow-sm';
        section.innerHTML = `
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="font-headline text-xl font-extrabold text-on-surface">Push para clientes</h2>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Clientes vinculados</p>
              <p class="mt-2 text-2xl font-extrabold text-[#133f8c]">${linkedCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Com push ativo</p>
              <p class="mt-2 text-2xl font-extrabold text-[#00AFA8]">${activePushCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Campanhas ativas</p>
              <p class="mt-2 text-2xl font-extrabold text-[#133f8c]">${activePromotions.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-xl bg-surface-container-low p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Último envio</p>
              <p class="mt-2 text-sm font-bold text-on-surface">${formatDatePtBr(lastSent, 'Nenhum envio')}</p>
            </div>
          </div>
          <div class="grid gap-2 sm:grid-cols-3">
            <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html">
              <span class="material-symbols-outlined">campaign</span>
              <span>Gestão de Ofertas</span>
            </a>
            <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html#empresaOffersPushSummary">
              <span class="material-symbols-outlined">send</span>
              <span>Operar campanhas</span>
            </a>
            <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html#formOferta">
              <span class="material-symbols-outlined">add_circle</span>
              <span>Criar promoção instantânea</span>
            </a>
            <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html#returnReminderSection">
              <span class="material-symbols-outlined">notifications_active</span>
              <span>Lembrete de retorno</span>
            </a>
            <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html#birthdayBonusSection">
              <span class="material-symbols-outlined">cake</span>
              <span>Bônus aniversário</span>
            </a>
            <a class="empresa-shortcut-card" href="/gest_o_de_ofertas_parceiro.html#cartaoFidelidadeSection">
              <span class="material-symbols-outlined">loyalty</span>
              <span>Fidelidade</span>
            </a>
            <a class="empresa-shortcut-card" href="/clientes_fidelizados_loja.html">
              <span class="material-symbols-outlined">groups</span>
              <span>Clientes vinculados</span>
            </a>
            <a class="empresa-shortcut-card" href="/validar_resgate.html?modo=beneficios">
              <span class="material-symbols-outlined">qr_code_2</span>
              <span>Meu QR</span>
            </a>
          </div>
        `;
      };
      ui.clearPageState();

      const resumoData = resumo.data?.data || {};
      const cards = resumoData.cards || {};
      const pushConfig = pushConfigResponse.data || {};
      const qrList = toArray(qrcodes.data?.data || qrcodes.data);
      const qrPayload = qrList[0] || {};
      const empresaInfo = qrPayload.empresa || {};
      const perfilData = perfilResponse.res.ok && perfilResponse.data?.success !== false
        ? (perfilResponse.data?.data || {})
        : {};
      const storefrontInfo = {
        ...empresaInfo,
        nome: safeText(perfilData?.nome || empresaInfo?.nome, safeText(currentUser?.name, 'Sua empresa')),
        descricao: safeText(perfilData?.descricao || empresaInfo?.descricao, ''),
        endereco: safeText(perfilData?.endereco || empresaInfo?.endereco, ''),
        whatsapp: safeText(perfilData?.whatsapp || empresaInfo?.whatsapp, ''),
        instagram: safeText(perfilData?.instagram || empresaInfo?.instagram, ''),
        facebook: safeText(perfilData?.facebook || empresaInfo?.facebook, ''),
        logo: safeImage(perfilData?.logo || empresaInfo?.logo, IMAGE_FALLBACKS.store),
      };
      const totalClientes = Number(cards.total_clientes_vinculados || 0);
      const aniversariantes = Number(cards.clientes_aniversariantes_mes || 0);
      const totalAvaliacoes = Number(cards.total_avaliacoes || 0);
      const mediaAvaliacao = Number(cards.media_avaliacao || 0);
      const notificacoes = Number(cards.total_notificacoes_enviadas || 0);
      const listaPromos = promos.data?.data || promos.data || [];
      renderCompanyPushCampaignsBlock({
        cards,
        push: resumoData.push,
        pushConfig,
        promotions: listaPromos,
        weeklyLimit: promos.data?.meta?.weekly_limit || null,
      });
      renderCompanyStorefrontBlock({
        info: storefrontInfo,
        publicUrl: qrPayload?.public_page_url || '/parceiros_tem_de_tudo.html',
      });

      if (heroName) heroName.textContent = safeText(storefrontInfo?.nome, safeText(currentUser?.name, 'Sua empresa'));
      if (heroSubtitle) {
        heroSubtitle.textContent = 'Gerencie clientes, campanhas e benefícios em um só lugar.';
      }
      if (heroMeta) {
        heroMeta.textContent = safeText(currentUser?.name)
          ? `Responsavel: ${safeText(currentUser.name)}`
          : 'Fluxo operacional completo para a equipe da loja';
      }
      if (heroLogo) {
        const heroImage = safeImage(listaPromos[0]?.imagem_url || listaPromos[0]?.imagem || storefrontInfo?.logo, IMAGE_FALLBACKS.store);
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
                <div class="rounded-[18px] bg-white/10 px-4 py-3 ring-1 ring-white/10">
                  <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/60">Codigo da empresa</p>
                  <p class="mt-2 break-all font-mono text-xs text-white">${safeText(qrPayload.code)}</p>
                </div>
              </div>
            </div>
          `;
        } else {
          qrContainer.textContent = 'Nenhum QR da empresa gerado ainda. Gere o QR da loja e divulgue o adesivo para os clientes.';
        }
      }

      if (kpiVolume) kpiVolume.textContent = Number(totalClientes).toLocaleString('pt-BR');
      if (kpiClientes) kpiClientes.textContent = Number(aniversariantes).toLocaleString('pt-BR');
      if (kpiResgates) kpiResgates.textContent = Number(totalAvaliacoes).toLocaleString('pt-BR');
      if (kpiVolumeDesc) kpiVolumeDesc.textContent = `${plural(cards.novos_clientes_mes, 'cliente novo', 'clientes novos')} no mês atual`;
      if (kpiVolumeTrend) kpiVolumeTrend.textContent = notificacoes > 0
        ? `${plural(notificacoes, 'notificação enviada', 'notificações enviadas')} para clientes vinculados`
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
                <img alt="${p.nome || 'Promoção'}" class="w-full h-full object-cover" src="${img}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'"/>
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
                <p class="mt-1 text-xs text-on-surface-variant">${safeText(cliente?.email, 'Sem e-mail')} • ${formatDatePtBr(cliente?.data_vinculo, 'Vínculo recente')}</p>
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
                <p class="mt-1 text-xs text-on-surface-variant">${safeText(evento?.titulo, 'Benefício validado')} • ${safeText(evento?.tipo, 'resgate')}</p>
              </div>
              <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-primary">${formatDatePtBr(evento?.data, 'Agora')}</span>
            </div>
          `;
          latestRedemptionsBox.appendChild(item);
        });
      }

      document.getElementById('empresaNotifBtn')?.addEventListener('click', () => {
        window.location.href = '/gest_o_de_ofertas_parceiro.html#empresaOffersPushSummary';
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
        const { res, data } = await api.request(`/empresa/clientes${qs}`);
        if (handleCompanyAccessFailure(res, data, 'Nao foi possivel carregar os clientes fidelizados.')) {
          return;
        }
        const payload = data?.data || {};
        const lista = payload?.data || data?.data || data || [];
        mountCompanyClientsPushSummary({
          total: Number(payload?.total || lista.length || 0),
          pushActive: Number(payload?.summary?.clientes_com_push_ativo || 0),
          pushInactive: Number(payload?.summary?.clientes_sem_push_ativo || 0),
        });
        const ativos = lista.filter((item) => item?.status_inatividade !== 'inactive').length;
        const inativos = lista.filter((item) => item?.status_inatividade === 'inactive').length;
        if (statTotal) statTotal.textContent = Number(payload?.total || lista.length || 0).toLocaleString('pt-BR');
        if (statAtivos) statAtivos.textContent = Number(ativos || 0).toLocaleString('pt-BR');
        if (statNovos) statNovos.textContent = Number(inativos || 0).toLocaleString('pt-BR');
        if (resumoEl) resumoEl.textContent = `Exibindo ${lista.length} cliente(s) | ${inativos} inativo(s) no filtro atual`;
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
          const pushAtivo = Boolean(c.push_ativo);
          const pushDispositivos = Number(c.push_total_dispositivos || 0);
          const pushUltimaAtividade = c.push_ultima_atividade || c.push_updated_at || null;
          const nascimento = formatDatePtBr(c.data_nascimento, 'Não informado');
          const vinculo = formatDatePtBr(c.data_vinculo, 'Não informado');
          card.innerHTML = `
            <div class="flex items-start gap-4">
              <div class="relative">
                <div class="w-14 h-14 rounded-full overflow-hidden bg-surface-container">
                  <img alt="${nome}" class="w-full h-full object-cover" src="${safeImage(c.avatar, '/img/avatar-admin.png')}" onerror="this.onerror=null;this.src='/img/avatar-admin.png';"/>
                </div>
              </div>
              <div class="flex-1">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <h3 class="font-headline font-bold text-on-surface">${nome}</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">${safeText(c.email, 'Sem e-mail')} | ${safeText(c.telefone, 'Sem telefone')}</p>
                  </div>
                  <div class="flex flex-wrap items-center justify-end gap-2">
                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${inativo ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}">${inativo ? 'Inativo' : 'Ativo'}</span>
                    <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${pushAtivo ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600'}">${pushAtivo ? 'Push ativo' : 'Sem push'}</span>
                  </div>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Pontos atuais</p>
                    <p class="mt-1 text-sm font-bold text-primary">${pontos.toLocaleString('pt-BR')} pts</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Última visita</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${formatDatePtBr(ultima, 'Não informada')}</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Nascimento</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${nascimento}</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Vínculo</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${vinculo}</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Dispositivos push</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${pushDispositivos.toLocaleString('pt-BR')}</p>
                  </div>
                  <div class="rounded-xl bg-surface-container-low px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-on-surface-variant">Última atividade push</p>
                    <p class="mt-1 text-sm font-semibold text-on-surface">${formatDatePtBr(pushUltimaAtividade, 'Não registrada')}</p>
                  </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-on-surface-variant">
                  <span>${plural(c.total_promocoes_resgatadas, 'promoção', 'promoções')}</span>
                  <span>${Number(c.total_recompensas_resgatadas || 0).toLocaleString('pt-BR')} recompensa(s)</span>
                  <span>${plural(c.dias_inatividade, 'dia')} sem visita</span>
                </div>
              </div>
            </div>
          `;
          listaEl?.appendChild(card);
        });
      };

      btn?.addEventListener('click', () => load(input?.value || ''));
      input?.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') {
          ev.preventDefault();
          load(input.value || '');
        }
      });

      await load();
    },

    async promocoes() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando promoções...');
      const [promotionsResponse, summaryResponse, pushConfigResponse] = await Promise.all([
        api.request('/empresa/promocoes'),
        api.request('/empresa/relatorios/resumo', {}, { notify: false }),
        api.request('/push/public-key', {}, { requireAuth: false, notify: false }),
      ]);
      const { res, data } = promotionsResponse;
      const formMessageEl = document.getElementById('ofertaMsg');
      if (handleCompanyAccessFailure(res, data, 'Nao foi possivel carregar a gestao de ofertas desta empresa.', formMessageEl)) {
        return;
      }
      const lista = data?.data || data || [];
      const weeklyStatus = normalizeWeeklyLimitStatus(data?.meta?.weekly_limit || { limit: 5, used: 0, remaining: 5, window_days: 7 });
      const summaryPayload = summaryResponse.res.ok && summaryResponse.data?.success !== false
        ? (summaryResponse.data?.data || {})
        : {};
      const summaryCards = summaryPayload.cards || {};
      const summaryPush = summaryPayload.push || {};
      const pushConfig = pushConfigResponse.data || {};
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
      const renderOffersPushSummary = () => {
        const anchor = document.getElementById('promoWeeklyInfo')?.closest('div')
          || document.getElementById('ofertasLista')
          || document.querySelector('main');
        const section = ensureAdjacentSection(
          'empresaOffersPushSummary',
          anchor,
          'afterend',
          'mb-8 rounded-[28px] bg-surface-container-lowest p-5 shadow-sm ring-1 ring-black/5'
        );
        if (!section) return;

        const linkedCustomers = Number(summaryPush.clientes_vinculados ?? summaryCards.total_clientes_vinculados ?? 0);
        const activePushCustomers = Number(summaryPush.clientes_com_push_ativo ?? summaryCards.clientes_com_push_ativo ?? 0);
        const inactivePushCustomers = Math.max(0, Number(summaryPush.clientes_sem_push_ativo ?? (linkedCustomers - activePushCustomers)));
        const lastSent = summaryPush.ultimo_envio_notificacao || summaryCards.ultimo_envio_notificacao || null;
        const serverReady = Boolean(pushConfig?.configured);

        section.innerHTML = `
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-[620px]">
              <h2 class="text-2xl font-extrabold text-[#111B3F]">Campanhas e push</h2>
            </div>
          </div>
          <div class="mt-5 grid gap-3 sm:grid-cols-4">
            <div class="rounded-[22px] bg-slate-50 p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Clientes vinculados</p>
              <p class="mt-2 text-2xl font-extrabold text-[#133F8C]">${linkedCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-[22px] bg-slate-50 p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Com push ativo</p>
              <p class="mt-2 text-2xl font-extrabold text-[#00AFA8]">${activePushCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-[22px] bg-slate-50 p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Sem push</p>
              <p class="mt-2 text-2xl font-extrabold text-[#B01774]">${inactivePushCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-[22px] bg-slate-50 p-4">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Último envio</p>
              <p class="mt-2 text-sm font-bold text-[#111B3F]">${formatDatePtBr(lastSent, 'Nenhum envio')}</p>
            </div>
          </div>
          <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="#formOferta" class="app-primary-button justify-center">Criar promocao</a>
            <a href="#birthdayBonusSection" class="app-secondary-button justify-center">Bonus aniversario</a>
            <a href="#returnReminderSection" class="app-secondary-button justify-center">Lembrete de retorno</a>
            <a href="/validar_resgate.html?modo=beneficios" class="app-secondary-button justify-center">Meu QR da loja</a>
          </div>
        `;
      };
      const form = {
        titulo: document.getElementById('ofertaTitulo'),
        descricao: document.getElementById('ofertaDescricao'),
        brinde: document.getElementById('ofertaBrinde'),
        validade: document.getElementById('ofertaValidade'),
        preco: document.getElementById('ofertaPreco'),
        tipo: document.getElementById('ofertaTipo'),
        imagem: document.getElementById('ofertaImagem'),
        notificationTitle: document.getElementById('ofertaNotificationTitle'),
        notificationBody: document.getElementById('ofertaNotificationBody'),
        ativa: document.getElementById('ofertaAtiva'),
        salvar: document.getElementById('ofertaSalvar'),
        preview: document.getElementById('ofertaPreview'),
        cancelar: document.getElementById('ofertaCancelar'),
        msg: document.getElementById('ofertaMsg'),
        weeklyInfo: document.getElementById('promoWeeklyInfo'),
      };
      let editingId = null;
      let filtroAtual = 'todas';
      renderOffersPushSummary();

      // ---- (Prompt 04) Upload+crop 1:1, contadores, badges e preview ----
      let croppedBlob = null;
      let imageRemoved = false;
      const promoUploader = document.querySelector('[data-uploader="promo"]');
      const promoImgEl = promoUploader?.querySelector('[data-uploader-image]');
      const promoPlaceholder = promoUploader?.querySelector('[data-uploader-placeholder]');
      const promoPickBtn = promoUploader?.querySelector('[data-uploader-pick]');
      const promoPickLabel = promoUploader?.querySelector('[data-uploader-picklabel]');
      const promoRemoveBtn = promoUploader?.querySelector('[data-uploader-remove]');
      const promoInput = promoUploader?.querySelector('[data-uploader-input]');

      const showPromoPreviewImage = (url) => {
        if (url && promoImgEl) {
          promoImgEl.src = url;
          promoImgEl.classList.remove('hidden');
          promoPlaceholder?.classList.add('hidden');
          promoRemoveBtn?.classList.remove('hidden');
          if (promoPickLabel) promoPickLabel.textContent = 'Alterar';
        } else {
          promoImgEl?.classList.add('hidden');
          promoImgEl?.removeAttribute('src');
          promoPlaceholder?.classList.remove('hidden');
          promoRemoveBtn?.classList.add('hidden');
          if (promoPickLabel) promoPickLabel.textContent = 'Enviar imagem';
        }
      };
      const resetPromoImage = () => {
        croppedBlob = null;
        imageRemoved = false;
        if (form.imagem) form.imagem.value = '';
        showPromoPreviewImage('');
      };
      promoPickBtn?.addEventListener('click', () => promoInput?.click());
      promoInput?.addEventListener('change', async () => {
        const file = promoInput.files && promoInput.files[0];
        promoInput.value = '';
        if (!file) return;
        if (!/^image\//.test(file.type)) { ui.message('Selecione uma imagem válida.', 'warning'); return; }
        const blob = await tdtImageCropper.open({ file, aspect: 1, round: false, outputWidth: 800, title: 'Ajustar imagem (1:1)' });
        if (!blob) return;
        croppedBlob = blob;
        imageRemoved = false;
        showPromoPreviewImage(URL.createObjectURL(blob));
      });
      promoRemoveBtn?.addEventListener('click', () => {
        croppedBlob = null;
        imageRemoved = true;
        if (form.imagem) form.imagem.value = '';
        showPromoPreviewImage('');
      });

      const bindCounter = (id) => {
        const el = document.getElementById(id);
        const counter = document.querySelector(`[data-counter-for="${id}"]`);
        if (!el || !counter) return;
        const max = Number(el.getAttribute('maxlength') || 0);
        const upd = () => {
          const len = (el.value || '').length;
          counter.textContent = `${len}/${max}`;
          counter.classList.toggle('is-limit', max > 0 && len >= max);
        };
        el.addEventListener('input', upd);
        upd();
      };
      bindCounter('ofertaTitulo');
      bindCounter('ofertaDescricao');
      const refreshCounters = () => document.querySelectorAll('[data-counter-for]').forEach((c) => {
        document.getElementById(c.getAttribute('data-counter-for'))?.dispatchEvent(new Event('input'));
      });

      // Badge de status no painel da empresa
      const empresaPromoBadge = (p) => {
        if (p.status === 'expired') return { label: 'Expirada', cls: 'bg-amber-50 text-amber-700' };
        if (!p.ativo || p.status === 'inactive' || p.status === 'pausada') return { label: 'Pausada', cls: 'bg-slate-100 text-slate-600' };
        if (p.data_inicio && new Date(p.data_inicio) > new Date()) return { label: 'Agendada', cls: 'bg-blue-50 text-blue-700' };
        return { label: 'Ativa', cls: 'bg-emerald-50 text-emerald-700' };
      };

      // Preview: exatamente como o cliente enxerga
      const renderPromoPreviewModal = (dataPreview) => {
        const overlay = document.createElement('div');
        overlay.className = 'tdt-modal-overlay';
        const precoLine = dataPreview.preco
          ? `<p class="mt-1 text-sm font-extrabold text-[#B01774]">R$ ${Number(dataPreview.preco).toFixed(2).replace('.', ',')}</p>`
          : '';
        const brindeLine = dataPreview.brinde
          ? `<p class="mt-1 text-xs text-on-surface-variant">🎁 Brinde: ${safeText(dataPreview.brinde)}</p>`
          : '';
        overlay.innerHTML = `
          <div class="tdt-modal-dialog">
            <div class="flex items-center justify-between mb-3">
              <p class="font-headline font-extrabold text-on-surface">Prévia da promoção</p>
              <button type="button" class="text-on-surface-variant" data-close><span class="material-symbols-outlined">close</span></button>
            </div>
            <p class="text-[11px] text-on-surface-variant mb-3">Assim a promoção aparece para o cliente:</p>
            <article class="tdt-promo-preview">
              <img class="tdt-promo-preview__media" src="${dataPreview.imagem}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" alt="" />
              <div class="p-4">
                <span class="tdt-status-badge bg-emerald-50 text-emerald-700">Disponível</span>
                <h3 class="mt-2 font-headline font-extrabold text-on-surface">${safeText(dataPreview.titulo, 'Oferta')}</h3>
                <p class="mt-1 text-sm text-on-surface-variant">${safeText(dataPreview.descricao, '')}</p>
                ${precoLine}${brindeLine}
                <p class="mt-2 text-[11px] text-on-surface-variant">Validade: ${formatDatePtBr(dataPreview.validade, 'Não informada')}</p>
                <p class="mt-3 text-[11px] font-semibold text-[#133F8C]">O cliente resgata direto pelo app.</p>
              </div>
            </article>
          </div>`;
        document.body.appendChild(overlay);
        const close = () => overlay.remove();
        overlay.querySelector('[data-close]')?.addEventListener('click', close);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
      };
      const previewFromForm = () => renderPromoPreviewModal({
        imagem: croppedBlob ? URL.createObjectURL(croppedBlob) : (form.imagem?.value || IMAGE_FALLBACKS.promo),
        titulo: form.titulo?.value,
        descricao: form.descricao?.value,
        preco: form.preco?.value,
        brinde: form.brinde?.value,
        validade: form.validade?.value,
      });
      form.preview?.addEventListener('click', previewFromForm);
      const updatePromotionDeliveryFeedback = (payload, tone = 'info') => {
        const summary = formatPushDeliverySummary(payload?.meta?.delivery || {}, 'clientes vinculados');
        setInlineFeedback(form.msg, summary.detail, tone);

        return summary;
      };

      if (form.weeklyInfo) {
        const weeklyToneClass = weeklyStatus.tone === 'error'
          ? 'bg-rose-50 text-rose-700'
          : (weeklyStatus.tone === 'warning' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700');
        form.weeklyInfo.className = `mt-3 inline-flex rounded-full px-4 py-2 text-xs font-semibold ${weeklyToneClass}`;
        form.weeklyInfo.textContent = weeklyStatus.unlimited
          ? `${weeklyStatus.title}: ${weeklyStatus.detail}`
          : `${weeklyStatus.title}: ${weeklyStatus.detail} | Restantes ${weeklyStatus.remaining}`;
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
          const meta = empresaPromoBadge(p);
          const canSend = Boolean(p.ativo && p.status === 'available' && !p.enviada_em && weeklyStatus.remaining > 0);
          const precoTxt = (p.preco || p.desconto)
            ? `<span class="inline-flex items-center rounded-full bg-[#b01774]/10 px-2 py-0.5 text-[10px] font-bold text-[#b01774]">R$ ${Number(p.preco || p.desconto).toFixed(2).replace('.', ',')}</span>`
            : '';
          const tipoTxt = (p.tipo || p.tipo_recompensa)
            ? `<span class="inline-flex items-center rounded-full bg-surface-container px-2 py-0.5 text-[10px] font-bold text-on-surface-variant uppercase">${safeText(p.tipo || p.tipo_recompensa)}</span>`
            : '';
          const metaRow = (precoTxt || tipoTxt) ? `<div class="mt-1 flex flex-wrap items-center gap-1.5">${tipoTxt}${precoTxt}</div>` : '';
          card.innerHTML = `
            <div class="w-24 h-24 rounded-lg overflow-hidden shrink-0 bg-surface-container">
              <img alt="${p.nome || p.titulo || 'Oferta'}" class="w-full h-full object-cover" src="${img}" loading="lazy" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" />
            </div>
            <div class="flex flex-col justify-between flex-grow min-w-0">
              <div>
                <div class="flex justify-between items-start gap-2">
                  <h3 class="font-headline font-bold text-on-surface text-base leading-tight">${p.nome || p.titulo || 'Oferta'}</h3>
                  <span class="tdt-status-badge ${meta.cls} shrink-0">${meta.label}</span>
                </div>
                <p class="text-xs text-on-surface-variant line-clamp-2">${p.descricao || ''}</p>
                ${metaRow}
                <p class="mt-1 text-[11px] text-on-surface-variant">Validade: ${formatDatePtBr(p.data_expiracao || p.validade, 'Não informada')}</p>
              </div>
              <div class="flex items-center justify-end gap-2 mt-2 text-[10px] text-outline flex-wrap">
                <button class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-surface-container text-on-surface text-xs" data-action="preview"><span class="material-symbols-outlined text-sm">visibility</span>Visualizar</button>
                <button class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-surface-container text-on-surface text-xs" data-action="editar"><span class="material-symbols-outlined text-sm">edit</span>Editar</button>
                <button class="px-3 py-1 rounded-lg ${p.ativo ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white'} text-xs" data-action="toggle">${p.ativo ? 'Pausar' : 'Ativar'}</button>
                <button class="px-3 py-1 rounded-lg ${canSend ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant'} text-xs" data-action="enviar" ${canSend ? '' : 'disabled'}>${p.enviada_em ? 'Push enviado' : 'Enviar push'}</button>
                <button class="px-3 py-1 rounded-lg bg-rose-600 text-white text-xs" data-action="deletar">Excluir</button>
              </div>
            </div>`;
          card.querySelector('[data-action="preview"]')?.addEventListener('click', () => renderPromoPreviewModal({
            imagem: safeImage(p.imagem_url || p.imagem, IMAGE_FALLBACKS.promo),
            titulo: p.titulo || p.nome,
            descricao: p.descricao,
            preco: p.preco || p.desconto,
            brinde: p.brinde,
            validade: p.data_expiracao || p.validade,
          }));
          card.querySelector('[data-action="editar"]')?.addEventListener('click', () => fillForm(p));
          card.querySelector('[data-action="toggle"]')?.addEventListener('click', async () => {
            const { res, data: resp } = await api.request(`/empresa/promocoes/${p.id}/toggle`, {
              method: 'PATCH',
              body: JSON.stringify({ ativo: !p.ativo }),
            });
            if (res.ok && resp?.success !== false) {
              ui.message(resp?.message || 'Promoção atualizada.', 'success');
              location.reload();
            } else {
              ui.message(resp?.message || 'Erro ao atualizar promoção.', 'error');
            }
          });
          card.querySelector('[data-action="enviar"]')?.addEventListener('click', async () => {
            const { res, data: resp } = await api.request(`/empresa/promocoes/${p.id}/enviar`, { method: 'POST' });
            if (res.ok && resp?.success) {
              const preview = formatPushDeliverySummary(resp?.meta?.delivery || {}, 'clientes vinculados');
              const tone = preview.normalized.enviados > 0 ? 'success' : 'warning';
              const summary = updatePromotionDeliveryFeedback(resp, tone);
              const outboundMessage = summary.normalized.enviados > 0
                ? (resp?.message || summary.short)
                : (summary.short || resp?.message || 'A promoção está pronta, mas nenhum cliente vinculado ativou notificações ainda.');
              ui.message(outboundMessage, summary.normalized.enviados > 0 ? 'success' : 'warning');
              if (summary.normalized.enviados > 0) {
                p.data_envio = new Date().toISOString();
                p.enviada_em = p.data_envio;
              }
              renderCards(lista);
              setCounts(lista);
            } else {
              const summary = updatePromotionDeliveryFeedback(resp, resp?.error === 'config_missing' ? 'warning' : 'error');
              ui.message(summary.short || resp?.message || 'Não foi possível enviar a promoção.', resp?.error === 'config_missing' ? 'warning' : 'error');
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
        if (form.brinde) form.brinde.value = p.brinde || '';
        if (form.validade) form.validade.value = (p.data_expiracao || p.validade || '').slice(0, 10);
        if (form.preco) form.preco.value = p.preco || p.desconto || p.valor || '';
        if (form.tipo) form.tipo.value = p.tipo || p.tipo_recompensa || 'desconto';
        const existingUrl = p.imagem_url || p.imagem || '';
        if (form.imagem) form.imagem.value = existingUrl;
        croppedBlob = null;
        imageRemoved = false;
        showPromoPreviewImage(existingUrl ? safeImage(existingUrl, IMAGE_FALLBACKS.promo) : '');
        if (form.notificationTitle) form.notificationTitle.value = p.notification_title || p.titulo || '';
        if (form.notificationBody) form.notificationBody.value = p.notification_body || p.descricao || '';
        if (form.ativa) form.ativa.checked = !(p.status === 'pausada' || p.ativo === false);
        refreshCounters();
        if (form.msg) form.msg.textContent = 'Editando oferta';
        document.getElementById('formOferta')?.scrollIntoView({ behavior: 'smooth' });
      };

      Object.values(filtros).forEach((btn) => btn?.addEventListener('click', () => {
        filtroAtual = btn.dataset.status;
        Object.values(filtros).forEach((b) => b.classList.remove('bg-primary', 'text-on-primary'));
        btn.classList.add('bg-primary', 'text-on-primary');
        renderCards(lista);
      }));

      const resetForm = () => {
        editingId = null;
        if (form.titulo) form.titulo.value = '';
        if (form.descricao) form.descricao.value = '';
        if (form.brinde) form.brinde.value = '';
        if (form.validade) form.validade.value = '';
        if (form.preco) form.preco.value = '';
        if (form.tipo) form.tipo.value = 'desconto';
        if (form.notificationTitle) form.notificationTitle.value = '';
        if (form.notificationBody) form.notificationBody.value = '';
        if (form.ativa) form.ativa.checked = true;
        if (form.msg) form.msg.textContent = '';
        resetPromoImage();
        refreshCounters();
      };

      btnNova?.addEventListener('click', () => {
        resetForm();
        document.getElementById('formOferta')?.scrollIntoView({ behavior: 'smooth' });
      });

      form.cancelar?.addEventListener('click', resetForm);

      form.salvar?.addEventListener('click', async () => {
        const titulo = (form.titulo?.value || '').trim();
        if (!titulo) return ui.message('Informe o nome da oferta.', 'warning');
        if (!(form.descricao?.value || '').trim()) return ui.message('Informe a descrição.', 'warning');
        if (!form.validade?.value) return ui.message('Informe a validade (data final).', 'warning');
        const hasExistingImage = Boolean(form.imagem?.value);
        if (!croppedBlob && !hasExistingImage) return ui.message('Envie a imagem da oferta.', 'warning');

        const fd = new FormData();
        fd.append('titulo', titulo);
        fd.append('nome', titulo);
        fd.append('descricao', (form.descricao?.value || '').trim());
        if (form.brinde?.value) fd.append('brinde', form.brinde.value.trim());
        if (form.validade?.value) fd.append('validade', form.validade.value);
        if (form.preco?.value) fd.append('desconto', String(Number(form.preco.value) || 0));
        if (form.tipo?.value) {
          fd.append('tipo', form.tipo.value);
          fd.append('tipo_recompensa', form.tipo.value);
        }
        if (form.notificationTitle?.value) fd.append('notification_title', form.notificationTitle.value);
        if (form.notificationBody?.value) fd.append('notification_body', form.notificationBody.value);
        fd.append('ativo', form.ativa?.checked ? '1' : '0');
        if (croppedBlob) fd.append('imagem', croppedBlob, 'promocao.jpg');
        else if (hasExistingImage) fd.append('imagem_url', form.imagem.value);
        if (imageRemoved) fd.append('remover_imagem', '1');
        if (editingId) fd.append('_method', 'PUT');

        const path = editingId ? `/empresa/promocoes/${editingId}` : '/empresa/promocoes';
        const btn = form.salvar;
        const original = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Salvando...';
        const { res, data: resp } = await api.request(path, { method: 'POST', body: fd });
        btn.disabled = false;
        btn.textContent = original;
        if (res.ok && resp?.success !== false) {
          ui.message('Promoção salva com sucesso.', 'success');
          window.location.reload();
        } else {
          ui.message(resp?.message || 'Não foi possível salvar. Tente novamente.', 'error');
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
            titulo: bonusUi.titulo?.value?.trim() || 'Bônus de adesão',
            descricao: bonusUi.descricao?.value?.trim() || 'Configure o benefício exibido ao cliente vinculado.',
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
              : 'Validade não informada';
          }
          if (bonusUi.previewImage) {
            bonusUi.previewImage.src = safeImage(payload.imagem_url, IMAGE_FALLBACKS.promo);
            bonusUi.previewImage.onerror = () => {
              bonusUi.previewImage.onerror = null;
              bonusUi.previewImage.src = IMAGE_FALLBACKS.promo;
            };
          }
        };

        const bonusAdesaoPicker = createLocalImagePicker('[data-uploader="bonusAdesao"]', { aspect: 1, title: 'Ajustar imagem do bônus (1:1)' });

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
          bonusAdesaoPicker?.reset();
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
          if (bonusUi.mensagem) bonusUi.mensagem.textContent = 'Editando bônus selecionado.';
          bonusAdesaoPicker?.setExisting(bonus.imagem_url || bonus.imagem || '');
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
                  <p class="text-sm font-bold text-on-surface">${bonus.titulo || 'Bônus de adesão'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${bonus.descricao || 'Sem descrição.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">Validade: ${formatDatePtBr(bonus.data_expiracao, 'Não informada')}</p>
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
                ui.message(data?.message || 'Status do bônus atualizado.', 'success');
                await loadBonusList();
              } else {
                ui.message(data?.message || 'Não foi possível atualizar o bônus.', 'error');
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
          const titulo = bonusUi.titulo?.value?.trim() || '';
          if (!titulo) {
            return ui.message('Informe o título do bônus de adesão.', 'warning');
          }

          const fd = new FormData();
          fd.append('titulo', titulo);
          if (bonusUi.descricao?.value) fd.append('descricao', bonusUi.descricao.value.trim());
          if (bonusUi.validade?.value) fd.append('data_expiracao', bonusUi.validade.value);
          if (bonusUi.termos?.value) fd.append('termos', bonusUi.termos.value.trim());
          fd.append('ativo', bonusUi.ativo?.checked ? '1' : '0');
          bonusAdesaoPicker?.appendTo(fd);
          if (bonusEditingId) fd.append('_method', 'PUT');

          const path = bonusEditingId ? `/empresa/bonus-adesao/${bonusEditingId}` : '/empresa/bonus-adesao';
          const btn = bonusUi.salvar;
          const original = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Salvando...';
          const { res, data } = await api.request(path, { method: 'POST', body: fd });
          btn.disabled = false;
          btn.textContent = original;

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Bônus de adesão salvo.', 'success');
            resetBonusForm();
            await loadBonusList();
          } else {
            ui.message(data?.message || 'Não foi possível salvar o bônus de adesão. Tente novamente.', 'error');
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
            titulo: loyaltyUi.titulo?.value?.trim() || 'Cartão fidelidade',
            descricao: loyaltyUi.descricao?.value?.trim() || 'Configure a regra de pontos e a recompensa que o cliente verá na página pública.',
            regra_ganho: loyaltyUi.regraGanho?.value?.trim() || 'Ganhe 1 ponto a cada visita.',
            pontos_por_visita: Number(loyaltyUi.pontosPorVisita?.value || 1),
            pontos_necessarios: Number(loyaltyUi.pontosNecessarios?.value || 0),
            recompensa_descricao: loyaltyUi.recompensa?.value?.trim() || 'Ainda não informada',
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
            loyaltyUi.previewMeta.textContent = `Meta: ${payload.pontos_necessarios} pontos | ${payload.data_expiracao ? `Validade ${formatDatePtBr(payload.data_expiracao)}` : 'Validade não informada'}`;
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
          if (loyaltyUi.mensagem) loyaltyUi.mensagem.textContent = 'Editando cartão selecionado.';
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
                  <p class="text-sm font-bold text-on-surface">${card.titulo || 'Cartão fidelidade'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${card.regra_ganho || 'Ganhe pontos por visita.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">Meta: ${card.pontos_necessarios || 0} pontos | Recompensa: ${card.recompensa_descricao || 'Não informada'}</p>
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
                ui.message(data?.message || 'Status do cartão atualizado.', 'success');
                await loadLoyaltyList();
              } else {
                ui.message(data?.message || 'Não foi possível atualizar o cartão.', 'error');
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

          if (!payload.titulo) return ui.message('Informe o título do cartão fidelidade.', 'warning');
          if (!payload.pontos_necessarios || payload.pontos_necessarios < 1) return ui.message('Informe a meta de pontos.', 'warning');
          if (!payload.recompensa_descricao) return ui.message('Informe a recompensa do cartão fidelidade.', 'warning');

          const path = loyaltyEditingId ? `/empresa/cartao-fidelidade/${loyaltyEditingId}` : '/empresa/cartao-fidelidade';
          const method = loyaltyEditingId ? 'PUT' : 'POST';
          const { res, data } = await api.request(path, {
            method,
            body: JSON.stringify(payload),
          });

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Cartão fidelidade salvo.', 'success');
            resetLoyaltyForm();
            await loadLoyaltyList();
          } else {
            ui.message(data?.message || 'Não foi possível salvar o cartão fidelidade.', 'error');
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
            titulo: birthdayUi.titulo?.value?.trim() || 'Bônus aniversário',
            descricao: birthdayUi.descricao?.value?.trim() || 'Configure o benefício anual exibido para o cliente elegível.',
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
              ? `Válido por ${plural(payload.dias_validade, 'dia')} a partir do aniversário`
              : 'Válido durante todo o mês do aniversário';
          }
          if (birthdayUi.previewNotification) {
            const title = payload.notification_title || 'Não configurado';
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

        const birthdayPicker = createLocalImagePicker('[data-uploader="bonusAniversario"]', { aspect: 1, title: 'Ajustar imagem do bônus (1:1)' });

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
          birthdayPicker?.reset();
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
          if (birthdayUi.mensagem) birthdayUi.mensagem.textContent = 'Editando bônus aniversário selecionado.';
          birthdayPicker?.setExisting(bonus.imagem_url || bonus.imagem || '');
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
                  <p class="text-sm font-bold text-on-surface">${bonus.titulo || 'Bônus aniversário'}</p>
                  <p class="mt-1 text-xs leading-5 text-on-surface-variant">${bonus.descricao || 'Sem descrição.'}</p>
                  <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">${safeText(bonus.validade_descricao, 'Validade não informada')}</p>
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
                ui.message(data?.message || 'Status do bônus aniversário atualizado.', 'success');
                await loadBirthdayList();
              } else {
                ui.message(data?.message || 'Não foi possível atualizar o bônus aniversário.', 'error');
              }
            });
            card.querySelector('[data-action="send"]')?.addEventListener('click', async () => {
              const { res, data } = await api.request(`/empresa/bonus-aniversario/${bonus.id}/enviar-elegiveis`, {
                method: 'POST',
              });
              if (res.ok && data?.success) {
                const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'aniversariantes elegiveis');
                const tone = summary.normalized.enviados > 0 ? 'success' : 'warning';
                setInlineFeedback(birthdayUi.mensagem, summary.detail, tone);
                ui.message(data?.message || summary.short, tone);
              } else {
                const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'aniversariantes elegiveis');
                setInlineFeedback(birthdayUi.mensagem, summary.detail || (data?.message || ''), data?.error === 'config_missing' ? 'warning' : 'error');
                ui.message(data?.message || summary.short || 'Não foi possível enviar o bônus aniversário.', data?.error === 'config_missing' ? 'warning' : 'error');
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
          const titulo = birthdayUi.titulo?.value?.trim() || '';
          const descricao = birthdayUi.descricao?.value?.trim() || '';
          if (!titulo) return ui.message('Informe o título do bônus aniversário.', 'warning');
          if (!descricao) return ui.message('Informe a descrição do bônus aniversário.', 'warning');

          const fd = new FormData();
          fd.append('titulo', titulo);
          fd.append('descricao', descricao);
          if (birthdayUi.diasValidade?.value) fd.append('dias_validade', String(Number(birthdayUi.diasValidade.value)));
          if (birthdayUi.notificationTitle?.value) fd.append('notification_title', birthdayUi.notificationTitle.value.trim());
          if (birthdayUi.notificationBody?.value) fd.append('notification_body', birthdayUi.notificationBody.value.trim());
          fd.append('ativo', birthdayUi.ativo?.checked ? '1' : '0');
          birthdayPicker?.appendTo(fd);
          if (birthdayEditingId) fd.append('_method', 'PUT');

          const path = birthdayEditingId ? `/empresa/bonus-aniversario/${birthdayEditingId}` : '/empresa/bonus-aniversario';
          const btn = birthdayUi.salvar;
          const original = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Salvando...';
          const { res, data } = await api.request(path, { method: 'POST', body: fd });
          btn.disabled = false;
          btn.textContent = original;

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Bônus aniversário salvo.', 'success');
            resetBirthdayForm();
            await loadBirthdayList();
          } else {
            ui.message(data?.message || 'Não foi possível salvar o bônus aniversário. Tente novamente.', 'error');
          }
        });

        birthdayUi.enviar?.addEventListener('click', async () => {
          const target = birthdayItems.find((item) => item.ativo) || birthdayItems[0];
          if (!target?.id) {
            ui.message('Cadastre um bônus aniversário antes de enviar.', 'warning');
            return;
          }

          const { res, data } = await api.request(`/empresa/bonus-aniversario/${target.id}/enviar-elegiveis`, {
            method: 'POST',
          });
          if (res.ok && data?.success) {
            const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'aniversariantes elegiveis');
            const tone = summary.normalized.enviados > 0 ? 'success' : 'warning';
            setInlineFeedback(birthdayUi.mensagem, summary.detail, tone);
            ui.message(summary.normalized.enviados > 0 ? (data?.message || summary.short) : (summary.short || data?.message), tone);
          } else {
            const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'aniversariantes elegiveis');
            setInlineFeedback(birthdayUi.mensagem, summary.detail || (data?.message || ''), data?.error === 'config_missing' ? 'warning' : 'error');
            ui.message(summary.short || data?.message || 'Não foi possível enviar o bônus aniversário.', data?.error === 'config_missing' ? 'warning' : 'error');
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
        imagem: document.getElementById('returnReminderImage'),
        notificationTitle: document.getElementById('returnReminderNotificationTitle'),
        notificationBody: document.getElementById('returnReminderNotificationBody'),
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
        previewImage: document.getElementById('returnReminderPreviewImage'),
        previewNotification: document.getElementById('returnReminderPreviewNotification'),
      };

      if (reminderUi.salvar && reminderUi.list) {
        let reminderItems = [];
        let reminderEditingId = null;

        const updateReminderPreview = () => {
          const payload = {
            dias_sem_visita: Number(reminderUi.dias?.value || 30),
            titulo: reminderUi.titulo?.value?.trim() || 'Lembrete de retorno',
            mensagem: reminderUi.mensagem?.value?.trim() || 'Sentimos sua falta. Volte para aproveitar as novidades da loja.',
            imagem_url: reminderUi.imagem?.value?.trim() || '',
            notification_title: reminderUi.notificationTitle?.value?.trim() || 'Sentimos sua falta!',
            notification_body: reminderUi.notificationBody?.value?.trim() || 'Volte para aproveitar as novidades da loja.',
            ativo: reminderUi.ativo?.checked ?? false,
          };
          if (reminderUi.previewTitle) reminderUi.previewTitle.textContent = payload.titulo;
          if (reminderUi.previewMessage) reminderUi.previewMessage.textContent = payload.mensagem;
          if (reminderUi.previewStatus) {
            reminderUi.previewStatus.textContent = payload.ativo ? 'Ativo' : 'Inativo';
            reminderUi.previewStatus.className = `rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${payload.ativo ? 'bg-emerald-500/20 text-white' : 'bg-white/10 text-white/80'}`;
          }
          if (reminderUi.previewMeta) {
            reminderUi.previewMeta.textContent = `Envio automático após ${plural(payload.dias_sem_visita, 'dia')} sem visita`;
          }
          if (reminderUi.previewImage) {
            reminderUi.previewImage.src = safeImage(payload.imagem_url, IMAGE_FALLBACKS.promo);
            reminderUi.previewImage.onerror = () => {
              reminderUi.previewImage.onerror = null;
              reminderUi.previewImage.src = IMAGE_FALLBACKS.promo;
            };
          }
          if (reminderUi.previewNotification) {
            reminderUi.previewNotification.textContent = `${payload.notification_title} - ${payload.notification_body}`;
          }
        };

        const reminderPicker = createLocalImagePicker('[data-uploader="bonusLembrete"]', { aspect: 1, title: 'Ajustar imagem do lembrete (1:1)' });

        const resetReminderForm = () => {
          reminderEditingId = null;
          if (reminderUi.id) reminderUi.id.value = '';
          if (reminderUi.dias) reminderUi.dias.value = 30;
          if (reminderUi.titulo) reminderUi.titulo.value = '';
          if (reminderUi.mensagem) reminderUi.mensagem.value = '';
          if (reminderUi.imagem) reminderUi.imagem.value = '';
          if (reminderUi.notificationTitle) reminderUi.notificationTitle.value = '';
          if (reminderUi.notificationBody) reminderUi.notificationBody.value = '';
          if (reminderUi.ativo) reminderUi.ativo.checked = true;
          if (reminderUi.feedback) reminderUi.feedback.textContent = '';
          reminderPicker?.reset();
          updateReminderPreview();
        };

        const fillReminderForm = (reminder) => {
          reminderEditingId = reminder.id;
          if (reminderUi.id) reminderUi.id.value = reminder.id;
          if (reminderUi.dias) reminderUi.dias.value = reminder.dias_sem_visita || reminder.dias_ausencia || 30;
          if (reminderUi.titulo) reminderUi.titulo.value = reminder.titulo || '';
          if (reminderUi.mensagem) reminderUi.mensagem.value = reminder.mensagem || '';
          if (reminderUi.imagem) reminderUi.imagem.value = reminder.imagem_url || '';
          if (reminderUi.notificationTitle) reminderUi.notificationTitle.value = reminder.notification_title || reminder.titulo || '';
          if (reminderUi.notificationBody) reminderUi.notificationBody.value = reminder.notification_body || reminder.mensagem || '';
          if (reminderUi.ativo) reminderUi.ativo.checked = Boolean(reminder.ativo);
          if (reminderUi.feedback) reminderUi.feedback.textContent = 'Editando lembrete selecionado.';
          reminderPicker?.setExisting(reminder.imagem_url || '');
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
                <div class="flex min-w-0 gap-3">
                  <img class="h-14 w-14 rounded-2xl object-cover shadow-sm" src="${safeImage(reminder.imagem_url, IMAGE_FALLBACKS.promo)}" alt="${safeText(reminder.titulo || 'Lembrete')}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.promo}'" />
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-on-surface">${reminder.titulo || 'Lembrete de retorno'}</p>
                    <p class="mt-1 text-xs leading-5 text-on-surface-variant">${reminder.mensagem || 'Sem mensagem.'}</p>
                    <p class="mt-2 text-[11px] font-semibold text-on-surface-variant">Envio automático após ${plural(reminder.dias_sem_visita || reminder.dias_ausencia, 'dia')} sem visita</p>
                    <p class="mt-1 text-[11px] text-on-surface-variant">Push: ${reminder.notification_title || reminder.titulo || 'Não informado'}</p>
                  </div>
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
                ui.message(data?.message || 'Não foi possível atualizar o lembrete.', 'error');
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

        [reminderUi.dias, reminderUi.titulo, reminderUi.mensagem, reminderUi.ativo, reminderUi.imagem, reminderUi.notificationTitle, reminderUi.notificationBody]
          .filter(Boolean)
          .forEach((field) => {
            field.addEventListener('input', updateReminderPreview);
            field.addEventListener('change', updateReminderPreview);
          });

        reminderUi.cancelar?.addEventListener('click', () => {
          resetReminderForm();
        });

        reminderUi.salvar?.addEventListener('click', async () => {
          const dias = Number(reminderUi.dias?.value || 0);
          const titulo = reminderUi.titulo?.value?.trim() || '';
          const mensagem = reminderUi.mensagem?.value?.trim() || '';
          if (!dias || dias < 1) return ui.message('Informe os dias sem visita.', 'warning');
          if (!titulo) return ui.message('Informe o título do lembrete.', 'warning');
          if (!mensagem) return ui.message('Informe a mensagem do lembrete.', 'warning');

          const fd = new FormData();
          fd.append('dias_sem_visita', String(dias));
          fd.append('titulo', titulo);
          fd.append('mensagem', mensagem);
          if (reminderUi.notificationTitle?.value) fd.append('notification_title', reminderUi.notificationTitle.value.trim());
          if (reminderUi.notificationBody?.value) fd.append('notification_body', reminderUi.notificationBody.value.trim());
          fd.append('ativo', reminderUi.ativo?.checked ? '1' : '0');
          reminderPicker?.appendTo(fd);
          if (reminderEditingId) fd.append('_method', 'PUT');

          const path = reminderEditingId ? `/empresa/lembrete-retorno/${reminderEditingId}` : '/empresa/lembrete-retorno';
          const btn = reminderUi.salvar;
          const original = btn.textContent;
          btn.disabled = true;
          btn.textContent = 'Salvando...';
          const { res, data } = await api.request(path, { method: 'POST', body: fd });
          btn.disabled = false;
          btn.textContent = original;

          if (res.ok && data?.success) {
            ui.message(data?.message || 'Lembrete de retorno salvo.', 'success');
            resetReminderForm();
            await loadReminderList();
          } else {
            ui.message(data?.message || 'Não foi possível salvar o lembrete. Tente novamente.', 'error');
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
            const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'clientes inativos');
            const tone = summary.normalized.enviados > 0 ? 'success' : 'warning';
            setInlineFeedback(reminderUi.feedback, summary.detail, tone);
            ui.message(summary.normalized.enviados > 0 ? (data?.message || summary.short) : (summary.short || data?.message), tone);
          } else {
            const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'clientes inativos');
            setInlineFeedback(reminderUi.feedback, summary.detail || (data?.message || ''), data?.error === 'config_missing' ? 'warning' : 'error');
            ui.message(summary.short || data?.message || 'Não foi possível enviar os lembretes.', data?.error === 'config_missing' ? 'warning' : 'error');
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
        ui.message('Promoção atualizada.', 'success');
        location.reload();
      } else {
        ui.message(data?.message || 'Erro ao atualizar promoção.', 'error');
      }
    },

    async deletarPromocao(id) {
      if (!window.confirm('Deseja realmente excluir esta promoção?')) return;
      const { res, data } = await api.request(`/empresa/promocoes/${id}`, { method: 'DELETE' });
      if (res.ok && data?.success !== false) {
        ui.message('Promoção removida.', 'success');
        location.reload();
      } else {
        ui.message(data?.message || 'Erro ao remover promoção.', 'error');
      }
    },

    // ----- Campanhas de multiplicador temporário -----
    async campanhas() {
      if (!(await auth.guard(['empresa']))) return;
      ui.setPageState('loading', 'Carregando relatorio operacional...');

      const [summaryResponse, promotionsResponse, customersResponse, pushConfigResponse] = await Promise.all([
        api.request('/empresa/relatorios/resumo', {}, { notify: false }),
        api.request('/empresa/promocoes', {}, { notify: false }),
        api.request('/empresa/clientes', {}, { notify: false }),
        api.request('/push/public-key', {}, { requireAuth: false, notify: false }),
      ]);

      if (
        handleCompanyAccessFailure(summaryResponse.res, summaryResponse.data, 'Não foi possível carregar o resumo operacional desta empresa.')
        || handleCompanyAccessFailure(promotionsResponse.res, promotionsResponse.data, 'Não foi possível carregar as promoções desta empresa.')
        || handleCompanyAccessFailure(customersResponse.res, customersResponse.data, 'Não foi possível carregar a base de clientes desta empresa.')
      ) {
        return;
      }

      const summaryPayload = summaryResponse.res.ok && summaryResponse.data?.success !== false
        ? (summaryResponse.data?.data || {})
        : {};
      const summaryCards = summaryPayload.cards || {};
      const summaryPush = summaryPayload.push || {};
      const promotions = toArray(promotionsResponse.data?.data || promotionsResponse.data);
      const customersPayload = customersResponse.data?.data || {};
      const customers = toArray(customersPayload?.data || customersResponse.data?.data || customersResponse.data);
      const pushReady = Boolean(pushConfigResponse.data?.configured);

      const linkedCustomers = Number(summaryPush.clientes_vinculados ?? summaryCards.total_clientes_vinculados ?? customersPayload?.total ?? customers.length ?? 0);
      const activePushCustomers = Number(summaryPush.clientes_com_push_ativo ?? customersPayload?.summary?.clientes_com_push_ativo ?? 0);
      const inactivePushCustomers = Math.max(0, Number(summaryPush.clientes_sem_push_ativo ?? customersPayload?.summary?.clientes_sem_push_ativo ?? (linkedCustomers - activePushCustomers)));
      const activePromotions = promotions.filter((item) => item?.ativo !== false && String(item?.status || '').toLowerCase() !== 'pausada').length;
      const lastSent = summaryPush.ultimo_envio_notificacao || summaryCards.ultimo_envio_notificacao || null;
      const recentClients = toArray(summaryPayload.clientes_recentes).slice(0, 5);
      const recentRedemptions = toArray(summaryPayload.ultimos_resgates).slice(0, 5);
      const customersWithPush = customers.filter((item) => item?.push_ativo).slice(0, 5);

      const host = document.querySelector('main') || document.getElementById('content') || document.body;
      host.innerHTML = `
        <section class="space-y-6">
          <div class="rounded-[28px] bg-[linear-gradient(135deg,#111B3F_0%,#133F8C_45%,#B01774_100%)] p-6 text-white shadow-[0_18px_45px_rgba(17,27,63,0.18)]">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div class="max-w-[680px]">
                <h1 class="text-2xl font-extrabold leading-tight">Resultados da sua operação</h1>
              </div>
              <div class="grid gap-3 sm:grid-cols-2">
                <a href="/gest_o_de_ofertas_parceiro.html#empresaOffersPushSummary" class="inline-flex h-12 items-center justify-center rounded-full bg-white px-5 text-sm font-extrabold text-[#111B3F] shadow-sm">Abrir gestao de ofertas</a>
                <a href="/validar_resgate.html?modo=beneficios" class="inline-flex h-12 items-center justify-center rounded-full border border-white/18 bg-white/10 px-5 text-sm font-bold text-white">Meu QR da loja</a>
                <a href="/clientes_fidelizados_loja.html" class="inline-flex h-12 items-center justify-center rounded-full border border-white/18 bg-white/10 px-5 text-sm font-bold text-white">Ver clientes</a>
                <a href="/dashboard_parceiro.html" class="inline-flex h-12 items-center justify-center rounded-full border border-white/18 bg-white/10 px-5 text-sm font-bold text-white">Voltar ao dashboard</a>
              </div>
            </div>
          </div>

          <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Clientes vinculados</p>
              <p class="mt-2 text-3xl font-extrabold text-[#133F8C]">${linkedCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Com push ativo</p>
              <p class="mt-2 text-3xl font-extrabold text-[#00AFA8]">${activePushCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Sem push</p>
              <p class="mt-2 text-3xl font-extrabold text-[#B01774]">${inactivePushCustomers.toLocaleString('pt-BR')}</p>
            </div>
            <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Promocoes ativas</p>
              <p class="mt-2 text-3xl font-extrabold text-[#111B3F]">${activePromotions.toLocaleString('pt-BR')}</p>
            </div>
          </section>

          <section class="grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <article class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Push da empresa</p>
                  <h2 class="mt-2 text-xl font-extrabold text-on-surface">Situacao do envio</h2>
                </div>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-surface-container-low p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Último envio</p>
                  <p class="mt-2 text-sm font-bold text-on-surface">${formatDatePtBr(lastSent, 'Nenhum envio')}</p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Promocoes resgatadas</p>
                  <p class="mt-2 text-2xl font-extrabold text-[#133F8C]">${Number(summaryCards.total_promocoes_resgatadas || 0).toLocaleString('pt-BR')}</p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Clientes inativos</p>
                  <p class="mt-2 text-2xl font-extrabold text-[#B01774]">${Number(summaryCards.clientes_inativos || 0).toLocaleString('pt-BR')}</p>
                </div>
                <div class="rounded-xl bg-surface-container-low p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-on-surface-variant">Media de avaliacao</p>
                  <p class="mt-2 text-2xl font-extrabold text-[#111B3F]">${Number(summaryCards.media_avaliacao || 0).toFixed(1).replace('.', ',')}</p>
                </div>
              </div>
              <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <a href="/gest_o_de_ofertas_parceiro.html#formOferta" class="app-primary-button justify-center">Criar promocao</a>
                <a href="/gest_o_de_ofertas_parceiro.html#returnReminderSection" class="app-secondary-button justify-center">Configurar lembrete</a>
                <a href="/gest_o_de_ofertas_parceiro.html#birthdayBonusSection" class="app-secondary-button justify-center">Bonus aniversario</a>
                <a href="/gest_o_de_ofertas_parceiro.html#cartaoFidelidadeSection" class="app-secondary-button justify-center">Cartao fidelidade</a>
              </div>
            </article>

            <article class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Clientes prontos para push</p>
              <h2 class="mt-2 text-xl font-extrabold text-on-surface">Base com notificacoes ativas</h2>
              <div class="mt-4 space-y-3">
                ${customersWithPush.length ? customersWithPush.map((customer) => `
                  <div class="rounded-xl bg-surface-container-low p-4">
                    <div class="flex items-center justify-between gap-3">
                      <div>
                        <p class="text-sm font-bold text-on-surface">${safeText(customer?.nome || customer?.name, 'Cliente')}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">${safeText(customer?.email, 'Sem e-mail')}</p>
                      </div>
                      <span class="rounded-full bg-sky-100 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-sky-700">${Number(customer?.push_total_dispositivos || 0).toLocaleString('pt-BR')} dispositivo(s)</span>
                    </div>
                  </div>
                `).join('') : '<p class="text-sm text-on-surface-variant">Nenhum cliente vinculado ativou notificacoes ainda. Oriente o cliente a instalar o app e tocar em Ativar notificacoes.</p>'}
              </div>
            </article>
          </section>

          <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Promocoes da vitrine</p>
                  <h2 class="mt-2 text-xl font-extrabold text-on-surface">Campanhas publicadas</h2>
                </div>
                <a href="/gest_o_de_ofertas_parceiro.html" class="text-sm font-bold text-primary">Gerenciar</a>
              </div>
              <div class="mt-4 space-y-3">
                ${promotions.length ? promotions.slice(0, 6).map((promo) => `
                  <div class="rounded-xl bg-surface-container-low p-4">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <p class="text-sm font-bold text-on-surface">${safeText(promo?.nome || promo?.titulo, 'Promocao')}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">${safeText(promo?.descricao, 'Sem descricao')}</p>
                      </div>
                      <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.14em] ${promo?.ativo !== false && String(promo?.status || '').toLowerCase() !== 'pausada' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">${promo?.ativo !== false && String(promo?.status || '').toLowerCase() !== 'pausada' ? 'Ativa' : 'Pausada'}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] text-on-surface-variant">
                      <span>Validade: ${formatDatePtBr(promo?.data_expiracao || promo?.validade, 'Nao informada')}</span>
                      <span>Push: ${safeText(promo?.notification_title || promo?.titulo, 'Não informado')}</span>
                    </div>
                  </div>
                `).join('') : '<p class="text-sm text-on-surface-variant">Nenhuma promocao ativa. Abra a Gestão de Ofertas para publicar a primeira campanha.</p>'}
              </div>
            </article>

            <article class="rounded-2xl bg-surface-container-lowest p-5 shadow-sm">
              <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Movimento recente</p>
              <h2 class="mt-2 text-xl font-extrabold text-on-surface">Clientes e validacoes</h2>
              <div class="mt-4 space-y-4">
                <div>
                  <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant">Clientes recentes</p>
                  <div class="mt-3 space-y-3">
                    ${recentClients.length ? recentClients.map((customer) => `
                      <div class="rounded-xl bg-surface-container-low p-4">
                        <p class="text-sm font-bold text-on-surface">${safeText(customer?.nome, 'Cliente')}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">${safeText(customer?.email, 'Sem e-mail')} | Vínculo ${formatDatePtBr(customer?.data_vinculo, 'recente')}</p>
                      </div>
                    `).join('') : '<p class="text-sm text-on-surface-variant">Nenhum cliente recente para mostrar.</p>'}
                  </div>
                </div>
                <div>
                  <p class="text-xs font-bold uppercase tracking-[0.14em] text-on-surface-variant">Ultimas validacoes</p>
                  <div class="mt-3 space-y-3">
                    ${recentRedemptions.length ? recentRedemptions.map((event) => `
                      <div class="rounded-xl bg-surface-container-low p-4">
                        <p class="text-sm font-bold text-on-surface">${safeText(event?.cliente_nome, 'Cliente')}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">${safeText(event?.titulo, 'Benefício validado')} | ${formatDatePtBr(event?.data, 'agora')}</p>
                      </div>
                    `).join('') : '<p class="text-sm text-on-surface-variant">Nenhuma validação recente.</p>'}
                  </div>
                </div>
              </div>
            </article>
          </section>
        </section>
      `;
      ui.clearPageState();
      return;
    },
  };

  // ---------------------- Paginas: Admin ---------------------- //
  const admin = {
    enrichUsersDataset(baseList = []) {
      return Array.isArray(baseList) ? [...baseList] : [];
    },

    enrichCompaniesDataset(baseList = []) {
      return Array.isArray(baseList) ? [...baseList] : [];
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

      return {
        ok: false,
        list: [],
        message: fallback.data?.message || primary.data?.message || 'Nao foi possivel carregar a base de usuarios.',
      };
    },

    async dashboard() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando dashboard admin...');
      const [stats, recent, empresas, ticketsStatsResp, adminSummaryResp, usersDataset] = await Promise.all([
        api.request('/admin/dashboard-stats', {}, { notify: false }),
        api.request('/admin/recent-activity', {}, { notify: false }),
        api.request('/admin/empresas', {}, { notify: false }),
        api.request('/admin/tickets/stats', {}, { notify: false }),
        api.request('/admin/relatorios/resumo', {}, { notify: false }),
        admin.loadUsersDataset(),
      ]);
      ui.clearPageState();
      if (!stats.res.ok || !recent.res.ok || !ticketsStatsResp.res.ok || !adminSummaryResp.res.ok || !usersDataset.ok) {
        ui.message('Parte dos indicadores administrativos nao respondeu. A tela esta exibindo apenas dados reais disponiveis agora.', 'warning');
      }

      const ids = (id) => document.getElementById(id);
      const dashboardSearchUi = {
        input: ids('adminDashboardSearchInput'),
        button: ids('adminDashboardSearchBtn'),
        hint: ids('adminDashboardSearchHint'),
      };
      const growthSubtitle = document.querySelector('#adminGrowthChart')?.closest('.admin-card')?.querySelector('p.text-xs');
      if (growthSubtitle) growthSubtitle.textContent = 'Usuarios vs estabelecimentos - ultimos 30 dias';
      const statsData = stats.data?.data || stats.data || {};
      const adminSummary = adminSummaryResp.data?.data || {};
      const summaryCards = adminSummary?.cards || {};
      const totals = statsData?.totais || {};
      const empresasListApi = toArray(empresas.data?.data?.empresas || empresas.data?.data || empresas.data);
      const empresasList = admin.enrichCompaniesDataset(empresasListApi);
      // Foco do painel master: vencimento das empresas (cobranca).
      const empresasAtivas = empresasList.filter((item) => ['active', 'ativo'].includes(safeText(item?.status, '').toLowerCase())).length;
      const empresasVencidas = empresasList.filter((item) => item?.dias_restantes != null && item.dias_restantes < 0).length;
      const empresasProximas = empresasList.filter((item) => item?.dias_restantes != null && item.dias_restantes >= 0 && item.dias_restantes <= 15).length;
      const usersList = usersDataset?.ok ? usersDataset.list : [];
      // Novos usuarios nos ultimos 7 dias (pedido do master).
      const seteDiasAtras = Date.now() - 7 * 86400000;
      const novos7d = usersList.filter((u) => {
        const t = new Date(u?.created_at || 0).getTime();
        return Number.isFinite(t) && t >= seteDiasAtras;
      }).length;
      const mergedTotals = {
        ...totals,
      };

      const totalUsuarios = toNumber(mergedTotals.usuarios, statsData.usuarios, statsData.total_users);
      const totalEmpresas = toNumber(summaryCards.total_empresas, mergedTotals.empresas, statsData.empresas, statsData.total_empresas, empresasList.length);
      const totalCampanhas = toNumber(summaryCards.total_promocoes, mergedTotals.campanhas, statsData.campanhas, statsData.promocoes);
      const totalResgates = toNumber(summaryCards.total_resgates, mergedTotals.resgates, statsData.resgates);
      const totalVolume = toNumber(mergedTotals.volume, statsData.volume);
      const ticketStatsData = ticketsStatsResp.data?.data || {};
      const hasTicketData = toNumber(ticketStatsData.total, ticketStatsData.pendentes, ticketStatsData.resolvidos) > 0;
      const ticketStats = hasTicketData ? ticketStatsData : {};
      const totalTicketsPendentes = toNumber(ticketStats.pendentes, 0);
      const totalTicketsUrgentes = toNumber(ticketStats.urgentes, 0);

      if (ids('adminUsers')) ids('adminUsers').textContent = Number(totalUsuarios || 0).toLocaleString('pt-BR');
      if (ids('adminEmpresas')) ids('adminEmpresas').textContent = Number(totalEmpresas || 0).toLocaleString('pt-BR');
      if (ids('adminEmpresasAtivas')) ids('adminEmpresasAtivas').textContent = Number(empresasAtivas || 0).toLocaleString('pt-BR');
      if (ids('adminEmpresasVencidas')) ids('adminEmpresasVencidas').textContent = Number(empresasVencidas || 0).toLocaleString('pt-BR');
      if (ids('adminEmpresasProximas')) ids('adminEmpresasProximas').textContent = Number(empresasProximas || 0).toLocaleString('pt-BR');
      if (ids('adminNovos7d')) ids('adminNovos7d').textContent = Number(novos7d || 0).toLocaleString('pt-BR');
      if (ids('adminCampanhas')) ids('adminCampanhas').textContent = Number(totalCampanhas || 0).toLocaleString('pt-BR');
      if (ids('adminResgates')) ids('adminResgates').textContent = Number(totalResgates || 0).toLocaleString('pt-BR');
      if (ids('adminVolume')) ids('adminVolume').textContent = `R$ ${Number(totalVolume || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      if (ids('adminCrescimentoMsg')) ids('adminCrescimentoMsg').textContent = safeText(
        statsData.crescimento_texto,
        summaryCards.total_notificacoes !== undefined
          ? `${plural(summaryCards.total_notificacoes, 'notificação enviada', 'notificações enviadas')} na base.`
          : 'Dados consolidados dos ultimos 30 dias'
      );
      if (ids('adminTicketsPendentes')) ids('adminTicketsPendentes').textContent = `${Number(totalTicketsPendentes || 0).toLocaleString('pt-BR')} ticket(s) pendente(s)`;
      if (ids('adminTicketsUrgentes')) ids('adminTicketsUrgentes').textContent = totalTicketsUrgentes > 0
        ? `${Number(totalTicketsUrgentes).toLocaleString('pt-BR')} ticket(s) urgente(s)`
        : 'Sem urgencias no momento';

      const atividadesApi = toArray(recent.data?.data || recent.data);
      const atividades = atividadesApi;
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

      const renderMetricRows = (containerId, rows, accentClass, emptyText) => {
        const container = ids(containerId);
        if (!container) return;
        container.innerHTML = '';
        if (!rows.length) {
          container.innerHTML = `<p class="text-sm text-on-surface-variant">${emptyText}</p>`;
          return;
        }

        rows.forEach(({ label, value, subtitle = '' }) => {
          const row = document.createElement('div');
          row.className = 'admin-rank-row';
          row.innerHTML = `
            <div class="admin-rank-meta">
              <p class="admin-rank-title">${safeText(label, 'Item')}</p>
              ${subtitle ? `<p class="admin-rank-subtitle">${safeText(subtitle, '')}</p>` : ''}
            </div>
            <span class="admin-rank-value ${accentClass}">${safeText(value, '--')}</span>
          `;
          container.appendChild(row);
        });
      };

      const dashboardSummary = {
        pending: toNumber(summaryCards.empresas_pending, empresasList.filter((item) => safeText(item?.status, '').toLowerCase() === 'pending').length),
        active: toNumber(summaryCards.empresas_active, empresasList.filter((item) => ['active', 'ativo'].includes(safeText(item?.status, '').toLowerCase())).length),
        suspended: toNumber(summaryCards.empresas_suspended, empresasList.filter((item) => ['suspended', 'suspenso'].includes(safeText(item?.status, '').toLowerCase())).length),
        rejected: toNumber(summaryCards.empresas_rejected, empresasList.filter((item) => ['rejected', 'rejeitado'].includes(safeText(item?.status, '').toLowerCase())).length),
      };

      renderMetricRows(
        'adminCompanyStatusList',
        [
          { label: 'Empresas pendentes', value: Number(dashboardSummary.pending || 0).toLocaleString('pt-BR'), subtitle: 'Aguardando aprovacao comercial' },
          { label: 'Empresas ativas', value: Number(dashboardSummary.active || 0).toLocaleString('pt-BR'), subtitle: 'Operando no app e no QR fisico' },
          { label: 'Empresas suspensas', value: Number(dashboardSummary.suspended || 0).toLocaleString('pt-BR'), subtitle: 'Bloqueadas temporariamente' },
          { label: 'Empresas rejeitadas', value: Number(dashboardSummary.rejected || 0).toLocaleString('pt-BR'), subtitle: 'Cadastros que nao entraram na vitrine' },
        ],
        'text-secondary',
        'Resumo de status indisponivel.'
      );

      const topCompaniesByClients = toArray(adminSummary.empresas_com_mais_clientes).length
        ? toArray(adminSummary.empresas_com_mais_clientes).map((item) => ({
            label: safeText(item?.nome, 'Empresa'),
            value: `${Number(item?.total_clientes || 0).toLocaleString('pt-BR')} cliente(s)`,
            subtitle: 'Base vinculada',
          }))
        : [...empresasList]
            .sort((left, right) => toNumber(right?.clientes, right?.total_clientes) - toNumber(left?.clientes, left?.total_clientes))
            .slice(0, 5)
            .map((item) => ({
              label: safeText(item?.nome || item?.nome_fantasia, 'Empresa'),
              value: `${Number(toNumber(item?.clientes, item?.total_clientes, 0)).toLocaleString('pt-BR')} cliente(s)`,
              subtitle: safeText(item?.categoria || item?.ramo, 'Sem categoria'),
            }));

      renderMetricRows(
        'adminTopCompaniesList',
        topCompaniesByClients,
        'text-primary',
        'Nenhum ranking de empresas disponível.'
      );

      const growthChart = ids('adminGrowthChart');
      if (growthChart) {
        const shell = growthChart.closest('.admin-graph-shell');
        shell?.querySelector('.admin-graph-legend')?.remove();
        const now = new Date();
        const windows = Array.from({ length: 6 }, (_, index) => {
          const start = new Date(now);
          start.setDate(start.getDate() - (5 - index) * 7);
          start.setHours(0, 0, 0, 0);
          const end = new Date(start);
          end.setDate(end.getDate() + 6);
          end.setHours(23, 59, 59, 999);
          return {
            start,
            end,
            label: `${String(start.getDate()).padStart(2, '0')}/${String(start.getMonth() + 1).padStart(2, '0')}`,
          };
        });

        const countByWindow = (items) => windows.map(({ start, end }) => {
          return items.filter((item) => {
            const raw = item?.created_at || item?.updated_at;
            if (!raw) return false;
            const parsed = new Date(raw);
            if (Number.isNaN(parsed.getTime())) return false;
            return parsed >= start && parsed <= end;
          }).length;
        });

        const userSeries = countByWindow(usersList);
        const companySeries = countByWindow(empresasList);
        const maxValue = Math.max(1, ...userSeries, ...companySeries);

        growthChart.innerHTML = windows.map((windowRef, index) => {
          const usersValue = userSeries[index];
          const companiesValue = companySeries[index];
          const userHeight = Math.max(usersValue ? 18 : 8, Math.round((usersValue / maxValue) * 150));
          const companyHeight = Math.max(companiesValue ? 18 : 8, Math.round((companiesValue / maxValue) * 150));
          return `
            <div class="admin-growth-column">
              <div class="admin-growth-bars">
                <div class="admin-growth-bar admin-growth-bar--users" style="height:${userHeight}px" title="${usersValue} usuario(s)"></div>
                <div class="admin-growth-bar admin-growth-bar--companies" style="height:${companyHeight}px" title="${plural(companiesValue, 'empresa')}"></div>
              </div>
              <div class="admin-growth-label">${windowRef.label}</div>
              <div class="admin-growth-subtitle">${usersValue} / ${companiesValue}</div>
            </div>
          `;
        }).join('');

        growthChart.insertAdjacentHTML('beforebegin', `
          <div class="admin-graph-legend">
            <span class="admin-graph-legend-item"><span class="admin-graph-dot" style="background:#133f8c"></span>Usuarios cadastrados</span>
            <span class="admin-graph-legend-item"><span class="admin-graph-dot" style="background:#b01774"></span>Empresas cadastradas</span>
          </div>
        `);
      }

      const setDashboardSearchHint = (message, tone = 'muted') => {
        if (!dashboardSearchUi.hint) return;
        dashboardSearchUi.hint.textContent = message;
        dashboardSearchUi.hint.style.color = (
          tone === 'success' ? '#00afa8'
            : tone === 'warning' ? '#b01774'
              : '#5b5b62'
        );
      };

      const resolveDashboardSearchTarget = (rawTerm) => {
        const term = safeText(rawTerm, '')
          .trim()
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '');

        if (!term) return null;

        const sectionMatches = [
          { patterns: ['crescimento', 'base', 'grafico'], element: ids('adminGrowthChart'), label: 'Crescimento da Base' },
          { patterns: ['atividade', 'recente'], element: ids('adminRecentList'), label: 'Atividade Recente' },
          { patterns: ['status', 'pendente', 'suspensa', 'rejeitada'], element: ids('adminCompanyStatusList'), label: 'Status das empresas' },
          { patterns: ['top', 'ranking', 'clientes empresa'], element: ids('adminTopCompaniesList'), label: 'Top empresas por clientes' },
        ];

        const sectionTarget = sectionMatches.find(({ patterns }) => patterns.some((pattern) => term.includes(pattern)));
        if (sectionTarget?.element) {
          return { type: 'section', element: sectionTarget.element, label: sectionTarget.label };
        }

        const routeMatches = [
          { patterns: ['cliente', 'push', 'assinatura', 'subscription'], href: '/gest_o_de_clientes_master.html', label: 'Clientes' },
          { patterns: ['empresa', 'parceiro', 'estabelecimento', 'aprovacao'], href: '/gest_o_de_estabelecimentos.html', label: 'Estabelecimentos' },
          { patterns: ['relatorio', 'relatorio', 'metrica', 'volume'], href: '/relat_rios_gerais_master.html', label: 'Relatorios' },
          { patterns: ['conteudo', 'banner', 'categoria'], href: '/banners_e_categorias_master.html', label: 'Conteudo' },
          { patterns: ['usuario', 'acesso', 'perfil'], href: '/gest_o_de_usu_rios_master.html', label: 'Usuarios' },
          { patterns: ['config', 'ajuste', 'parametro'], href: '/configuracoes_admin.html', label: 'Configuracoes' },
          { patterns: ['ticket', 'suporte'], href: '/tickets_admin_master.html', label: 'Suporte' },
        ];

        const routeTarget = routeMatches.find(({ patterns }) => patterns.some((pattern) => term.includes(pattern)));
        if (routeTarget?.href) {
          return { type: 'route', href: routeTarget.href, label: routeTarget.label };
        }

        return null;
      };

      const runDashboardSearch = () => {
        const term = dashboardSearchUi.input?.value || '';
        const target = resolveDashboardSearchTarget(term);

        if (!target) {
          setDashboardSearchHint('Busque por clientes, parceiros, relatorios, conteudo, crescimento ou atividade.', 'warning');
          dashboardSearchUi.input?.focus();
          return;
        }

        if (target.type === 'section' && target.element) {
          target.element.scrollIntoView({ behavior: 'smooth', block: 'start' });
          setDashboardSearchHint(`Mostrando ${target.label}.`, 'success');
          return;
        }

        if (target.type === 'route' && target.href) {
          setDashboardSearchHint(`Abrindo ${target.label}...`, 'success');
          window.location.href = target.href;
        }
      };

      if (dashboardSearchUi.input && dashboardSearchUi.input.dataset.bound !== '1') {
        dashboardSearchUi.input.dataset.bound = '1';
        dashboardSearchUi.input.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            runDashboardSearch();
          }
        });
        dashboardSearchUi.input.addEventListener('input', () => {
          if (!dashboardSearchUi.input?.value?.trim()) {
            setDashboardSearchHint('Busque por clientes, parceiros, relatorios ou conteudo.');
          }
        });
      }

      if (dashboardSearchUi.button && dashboardSearchUi.button.dataset.bound !== '1') {
        dashboardSearchUi.button.dataset.bound = '1';
        dashboardSearchUi.button.addEventListener('click', runDashboardSearch);
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

      // Sem banner de erro global aqui para evitar ruido visual duplicado.
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
        const tickets = apiTickets;
        const stats = hasStatsData ? statsData : {};
        if (!ticketsResp.res.ok && !statsResp.res.ok) {
          ui.message('Nao foi possivel carregar os tickets agora.', 'warning');
        }

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
      const pendingEl = document.getElementById('estabsPendingBadge');
      const activeEl = document.getElementById('estabsActiveBadge');
      const suspendedEl = document.getElementById('estabsSuspendedBadge');
      const rejectedEl = document.getElementById('estabsRejectedBadge');
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
        if (action === 'pagamento') return `/admin/empresas/${id}/pagamento`;
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
          ui.clearPageState();
          ui.message(data?.message || 'Nao foi possivel carregar os estabelecimentos agora.', 'warning');
          return {
            empresas: [],
            summary: admin.summarizeCompanies([]),
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

      // ---- (Painel Master) Vencimento, tipo de conta, planos e modais ----
      let planosCache = [];

      // Cores de vencimento: verde > 30d | amarelo 1-30d | vermelho vencido.
      const vencInfo = (e) => {
        const dias = e?.dias_restantes;
        if (e?.vencimento == null || dias == null) {
          return { label: 'Sem vencimento', cls: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400', dias: null };
        }
        if (dias < 0) return { label: `Vencido há ${Math.abs(dias)}d`, cls: 'bg-rose-100 text-rose-700', dot: 'bg-rose-500', dias };
        if (dias <= 15) return { label: `Vence em ${dias}d`, cls: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500', dias };
        if (dias <= 30) return { label: `Vence em ${dias}d`, cls: 'bg-yellow-100 text-yellow-800', dot: 'bg-yellow-500', dias };
        return { label: `${dias}d restantes`, cls: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500', dias };
      };

      const accountBadge = (tipo) => {
        const t = safeText(tipo, 'oficial').toLowerCase();
        if (t === 'demo') return '<span class="px-2 py-1 rounded-full text-[10px] uppercase font-black bg-violet-100 text-violet-700">Demo</span>';
        if (t === 'teste') return '<span class="px-2 py-1 rounded-full text-[10px] uppercase font-black bg-sky-100 text-sky-700">Teste</span>';
        return '<span class="px-2 py-1 rounded-full text-[10px] uppercase font-black bg-emerald-600 text-white">Oficial</span>';
      };

      const planoOptions = (selectedId = '') => planosCache
        .map((p) => `<option value="${p.id}" ${String(p.id) === String(selectedId) ? 'selected' : ''}>${safeText(p.nome, 'Plano')}</option>`)
        .join('');

      // Modal generico com fade (reaproveita .tdt-modal-overlay do CSS).
      const openModal = (innerHtml) => {
        const overlay = document.createElement('div');
        overlay.className = 'tdt-modal-overlay';
        overlay.innerHTML = `<div class="tdt-modal-dialog">${innerHtml}</div>`;
        document.body.appendChild(overlay);
        const close = () => overlay.remove();
        overlay.addEventListener('click', (ev) => { if (ev.target === overlay) close(); });
        overlay.querySelectorAll('[data-close]').forEach((el) => el.addEventListener('click', close));
        return { overlay, close };
      };

      const openRenewModal = (e) => {
        const { overlay, close } = openModal(`
          <div class="flex items-center justify-between mb-4">
            <p class="font-headline font-extrabold text-on-surface">Renovar assinatura</p>
            <button type="button" class="text-on-surface-variant" data-close><span class="material-symbols-outlined">close</span></button>
          </div>
          <p class="text-sm text-on-surface-variant mb-1">${safeText(e.nome, 'Empresa')}</p>
          <p class="text-xs text-on-surface-variant mb-4">Vencimento atual: <b>${e.vencimento ? formatDatePtBr(e.vencimento) : 'sem assinatura'}</b></p>
          ${planosCache.length ? `<label class="text-xs font-semibold uppercase text-on-surface-variant">Plano</label>
          <select data-renew-plano class="w-full mb-4 rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm">${planoOptions(e.plano_id)}</select>` : ''}
          <label class="text-xs font-semibold uppercase text-on-surface-variant">Adicionar período</label>
          <div class="grid grid-cols-3 gap-2 mt-2 mb-4">
            ${[30, 60, 90, 180, 365].map((d) => `<button type="button" data-renew-dias="${d}" class="rounded-xl border border-primary/30 py-2 text-sm font-bold text-primary hover:bg-primary hover:text-white transition-colors">${d} dias</button>`).join('')}
          </div>
          <p data-renew-msg class="text-xs text-on-surface-variant"></p>
        `);
        const msg = overlay.querySelector('[data-renew-msg]');
        overlay.querySelectorAll('[data-renew-dias]').forEach((btn) => {
          btn.addEventListener('click', async () => {
            const dias = Number(btn.dataset.renewDias);
            const planoId = overlay.querySelector('[data-renew-plano]')?.value || undefined;
            overlay.querySelectorAll('button').forEach((b) => (b.disabled = true));
            if (msg) { msg.textContent = 'Renovando...'; msg.className = 'text-xs text-on-surface-variant'; }
            const body = JSON.stringify(planoId ? { dias, plano_id: Number(planoId) } : { dias });
            const { res, data } = await api.request(`/admin/empresas/${e.id}/renovar`, { method: 'POST', body }, { notify: false });
            if (res.ok && data?.success !== false) {
              ui.message(data?.message || 'Assinatura renovada.', 'success');
              close();
              await renderLista();
            } else {
              if (msg) { msg.textContent = data?.message || 'Não foi possível renovar agora.'; msg.className = 'text-xs text-rose-600'; }
              overlay.querySelectorAll('button').forEach((b) => (b.disabled = false));
            }
          });
        });
      };

      const openProfileModal = (e) => {
        const venc = vencInfo(e);
        openModal(`
          <div class="flex items-center justify-between mb-4">
            <p class="font-headline font-extrabold text-on-surface">Perfil da empresa</p>
            <button type="button" class="text-on-surface-variant" data-close><span class="material-symbols-outlined">close</span></button>
          </div>
          <div class="flex items-center gap-3 mb-4">
            <img src="${safeImage(e.logo, IMAGE_FALLBACKS.store)}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'" alt="" class="w-16 h-16 rounded-2xl object-cover shadow-inner"/>
            <div>
              <p class="font-headline font-extrabold text-on-surface">${safeText(e.nome, 'Empresa')}</p>
              <div class="flex flex-wrap gap-1 mt-1">${accountBadge(e.tipo_conta)}<span class="px-2 py-1 rounded-full text-[10px] uppercase font-black ${(statusMeta[e.status] || statusMeta.pending).badge}">${(statusMeta[e.status] || statusMeta.pending).label}</span></div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div><p class="text-[10px] uppercase font-bold text-on-surface-variant">Responsável</p><p class="font-semibold text-on-surface">${safeText(e.responsavel, '-')}</p></div>
            <div><p class="text-[10px] uppercase font-bold text-on-surface-variant">Contato</p><p class="font-semibold text-on-surface">${safeText(e.email, '-')}</p></div>
            <div><p class="text-[10px] uppercase font-bold text-on-surface-variant">Telefone</p><p class="font-semibold text-on-surface">${safeText(e.telefone, '-')}</p></div>
            <div><p class="text-[10px] uppercase font-bold text-on-surface-variant">Plano</p><p class="font-semibold text-on-surface">${safeText(e.plano, 'Sem plano')}</p></div>
            <div><p class="text-[10px] uppercase font-bold text-on-surface-variant">Criação</p><p class="font-semibold text-on-surface">${e.created_at ? formatDatePtBr(e.created_at) : '-'}</p></div>
            <div><p class="text-[10px] uppercase font-bold text-on-surface-variant">Vencimento</p><p class="font-semibold text-on-surface">${e.vencimento ? formatDatePtBr(e.vencimento) : '-'}</p></div>
          </div>
          <div class="mt-3"><span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold ${venc.cls}"><span class="w-2 h-2 rounded-full ${venc.dot}"></span>${venc.label}</span></div>
          <div class="grid grid-cols-3 gap-2 mt-4 text-center">
            <div class="rounded-xl bg-surface-container-low p-3"><p class="text-lg font-extrabold text-[#133F8C]">${Number(toNumber(e.total_promocoes, e.campanhas, 0)).toLocaleString('pt-BR')}</p><p class="text-[10px] uppercase font-bold text-on-surface-variant">Campanhas</p></div>
            <div class="rounded-xl bg-surface-container-low p-3"><p class="text-lg font-extrabold text-[#00AFA8]">${Number(toNumber(e.total_clientes, e.clientes, 0)).toLocaleString('pt-BR')}</p><p class="text-[10px] uppercase font-bold text-on-surface-variant">Clientes</p></div>
            <div class="rounded-xl bg-surface-container-low p-3"><p class="text-lg font-extrabold text-[#B01774]">${Number(toNumber(e.total_resgates, e.resgates, 0)).toLocaleString('pt-BR')}</p><p class="text-[10px] uppercase font-bold text-on-surface-variant">Resgates</p></div>
          </div>
        `);
      };

      const openCreateModal = () => {
        const { overlay, close } = openModal(`
          <div class="flex items-center justify-between mb-4">
            <p class="font-headline font-extrabold text-on-surface">Nova empresa</p>
            <button type="button" class="text-on-surface-variant" data-close><span class="material-symbols-outlined">close</span></button>
          </div>
          <div class="space-y-3">
            <div><label class="text-xs font-semibold uppercase text-on-surface-variant">Nome</label><input data-new-nome class="w-full rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm" placeholder="Nome da empresa"/></div>
            <div><label class="text-xs font-semibold uppercase text-on-surface-variant">Usuário (e-mail)</label><input data-new-email type="email" class="w-full rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm" placeholder="empresa@exemplo.com"/></div>
            <div><label class="text-xs font-semibold uppercase text-on-surface-variant">Senha</label><input data-new-senha type="password" class="w-full rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm" placeholder="Mínimo 6 caracteres"/></div>
            <div><label class="text-xs font-semibold uppercase text-on-surface-variant">Telefone</label><input data-new-telefone class="w-full rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm" placeholder="(00) 00000-0000"/></div>
            <div class="grid grid-cols-2 gap-2">
              ${planosCache.length ? `<div><label class="text-xs font-semibold uppercase text-on-surface-variant">Plano</label><select data-new-plano class="w-full rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm">${planoOptions()}</select></div>` : ''}
              <div><label class="text-xs font-semibold uppercase text-on-surface-variant">Validade</label><select data-new-dias class="w-full rounded-xl border border-surface-variant/60 bg-white px-3 py-2 text-sm">${[30, 60, 90, 180, 365].map((d) => `<option value="${d}">${d} dias</option>`).join('')}</select></div>
            </div>
          </div>
          <button type="button" data-new-save class="mt-4 w-full bg-primary text-white rounded-xl py-3 font-semibold">Salvar empresa</button>
          <p data-new-msg class="mt-2 text-xs text-on-surface-variant"></p>
        `);
        const msg = overlay.querySelector('[data-new-msg]');
        overlay.querySelector('[data-new-save]')?.addEventListener('click', async (ev) => {
          const btn = ev.currentTarget;
          const nome = overlay.querySelector('[data-new-nome]')?.value.trim();
          const email = overlay.querySelector('[data-new-email]')?.value.trim();
          const senha = overlay.querySelector('[data-new-senha]')?.value;
          const telefone = overlay.querySelector('[data-new-telefone]')?.value.trim();
          const planoId = overlay.querySelector('[data-new-plano]')?.value || undefined;
          const dias = Number(overlay.querySelector('[data-new-dias]')?.value || 30);
          if (!nome || !email || !senha || senha.length < 6) {
            if (msg) { msg.textContent = 'Preencha nome, e-mail e senha (mín. 6 caracteres).'; msg.className = 'mt-2 text-xs text-rose-600'; }
            return;
          }
          btn.disabled = true;
          if (msg) { msg.textContent = 'Criando empresa...'; msg.className = 'mt-2 text-xs text-on-surface-variant'; }
          const payload = { nome, email, senha, telefone, dias };
          if (planoId) payload.plano_id = Number(planoId);
          const { res, data } = await api.request('/admin/empresas', { method: 'POST', body: JSON.stringify(payload) }, { notify: false });
          if (res.ok && data?.success !== false) {
            ui.message(data?.message || 'Empresa criada.', 'success');
            close();
            await renderLista();
          } else {
            if (msg) { msg.textContent = data?.message || 'Não foi possível criar a empresa.'; msg.className = 'mt-2 text-xs text-rose-600'; }
            btn.disabled = false;
          }
        });
      };

      const renderLista = async () => {
        const payload = await fetchCompanies();
        if (!payload) return;
        planosCache = toArray(payload?.planos);

        const lista = toArray(payload?.empresas).map((item) => ({
          ...item,
          nome: safeText(item?.nome || item?.nome_fantasia, 'Estabelecimento'),
          categoria: safeText(item?.categoria || item?.ramo || 'Sem categoria', 'Sem categoria'),
          endereco: safeText(item?.endereco, 'Endereço não informado'),
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
        if (pendingEl) pendingEl.textContent = `${payload?.summary?.pending ?? 0}`;
        if (activeEl) activeEl.textContent = `${payload?.summary?.active ?? 0}`;
        if (suspendedEl) suspendedEl.textContent = `${payload?.summary?.suspended ?? 0}`;
        if (rejectedEl) rejectedEl.textContent = `${payload?.summary?.rejected ?? 0}`;
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
          const venc = vencInfo(e);
          const card = document.createElement('div');
          card.className = 'bg-surface-container-lowest p-5 rounded-xl flex flex-col md:flex-row gap-6 items-start group hover:bg-surface-container-low transition-all border border-transparent hover:border-primary/10';
          card.innerHTML = /* html */ `
            <div class="relative">
              <div class="w-20 h-20 rounded-2xl overflow-hidden bg-surface-container shadow-inner">
                <img alt="${e.nome}" class="w-full h-full object-cover" src="${e.logo}" onerror="this.onerror=null;this.src='${IMAGE_FALLBACKS.store}'"/>
              </div>
            </div>
            <div class="flex-1 w-full">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-headline font-bold text-on-surface text-lg">${e.nome}</h3>
                    ${accountBadge(e.tipo_conta)}
                    <span class="px-2 py-1 rounded-full text-[10px] uppercase font-black ${meta.badge}">${meta.label}</span>
                  </div>
                  <p class="text-xs text-on-surface-variant mt-2">Usuário: <span class="font-bold">${e.responsavel}</span> · ${e.email}</p>
                  <p class="text-xs text-on-surface-variant mt-1">Plano: <span class="font-bold">${safeText(e.plano, 'Sem plano')}</span> · Criada em ${e.created_at ? formatDatePtBr(e.created_at) : '-'}</p>
                </div>
                <div class="flex flex-col items-start md:items-end gap-2">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold ${venc.cls}"><span class="w-2 h-2 rounded-full ${venc.dot}"></span>${venc.label}</span>
                  ${e.vencimento ? `<span class="text-[10px] text-on-surface-variant">Vence em ${formatDatePtBr(e.vencimento)}</span>` : ''}
                </div>
              </div>
              <div class="flex flex-wrap gap-4 mt-3 text-sm text-on-surface-variant">
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="call">call</span><span class="font-semibold text-on-surface">${e.telefone}</span></div>
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-primary" data-icon="storefront">storefront</span><span>${e.qr_code_ready ? 'QR pronto' : 'QR pendente'}</span></div>
                <div class="flex items-center gap-1"><span class="material-symbols-outlined ${e.pagamento_confirmado ? 'text-emerald-600' : 'text-amber-600'}" data-icon="payments">payments</span><span>${e.pagamento_confirmado ? 'Pago' : 'Aguardando pagamento'}</span></div>
              </div>
              <div class="mt-4 flex flex-wrap gap-2">
                <button data-company-view class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-secondary/10 text-secondary text-xs font-bold hover:opacity-90 transition-opacity"><span class="material-symbols-outlined text-base">visibility</span>Visualizar</button>
                <button data-company-renew class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-on-primary text-xs font-bold hover:opacity-90 transition-opacity"><span class="material-symbols-outlined text-base">event_repeat</span>Renovar</button>
                <button data-company-action="${meta.action}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg ${meta.actionClass} text-xs font-bold hover:opacity-90 transition-opacity">${meta.actionLabel}</button>
                ${meta.secondaryAction ? `<button data-company-action="${meta.secondaryAction}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg ${meta.secondaryClass} text-xs font-bold hover:opacity-90 transition-opacity">${meta.secondaryLabel}</button>` : ''}
                <a href="${detailsHref}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-outline/40 text-on-surface-variant text-xs font-bold hover:bg-surface-container-high transition-colors"><span class="material-symbols-outlined text-base">edit</span>Editar</a>
              </div>
            </div>`;

          card.querySelector('[data-company-view]')?.addEventListener('click', (ev) => { ev.stopPropagation(); openProfileModal(e); });
          card.querySelector('[data-company-renew]')?.addEventListener('click', (ev) => { ev.stopPropagation(); openRenewModal(e); });

          card.querySelectorAll('button[data-company-action]').forEach((el) => {
            el.addEventListener('click', async (ev) => {
              ev.stopPropagation();
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
        fab.addEventListener('click', () => openCreateModal());
        document.body.appendChild(fab);
      }

      // "Criar conta" no admin abre direto o cadastro de estabelecimento.
      if (new URLSearchParams(window.location.search).get('novo') === '1') {
        openCreateModal();
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

      // ---- (Revenda) Gestao de submasters: tabela + cadastrar + renovar/creditos ----
      const mountRevendas = async () => {
        const host = document.querySelector('main');
        if (!host) return;
        let section = document.getElementById('revendasSection');
        if (!section) {
          section = document.createElement('section');
          section.id = 'revendasSection';
          section.className = 'admin-card p-5 mb-6';
          section.innerHTML = `
            <div class="flex items-center justify-between gap-3 mb-4">
              <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Submaster</p>
                <h2 class="text-xl font-extrabold text-[#111B3F]">Revendas</h2>
              </div>
              <button id="btnNovaRevenda" type="button" class="ui-btn ui-btn--primary ui-btn--sm"><span class="material-symbols-outlined text-base">add</span>Cadastrar revenda</button>
            </div>
            <div id="revendasList" class="overflow-x-auto"></div>
            <p id="revendasEmpty" class="text-sm text-slate-500 hidden">Nenhuma revenda cadastrada ainda.</p>`;
          host.prepend(section);
        }
        const listEl = section.querySelector('#revendasList');
        const emptyEl = section.querySelector('#revendasEmpty');
        const brl = (v) => `R$ ${Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const vencChip = (r) => {
          const d = r.dias_restantes;
          if (r.vencimento == null || d == null) return '<span class="ui-badge ui-badge--neutral">Sem vencimento</span>';
          if (d < 0) return `<span class="ui-badge ui-badge--error">Vencido ${Math.abs(d)}d</span>`;
          if (d <= 15) return `<span class="ui-badge ui-badge--warning">${d}d</span>`;
          return `<span class="ui-badge ui-badge--success">${d}d</span>`;
        };
        const openModal = (inner) => {
          const ov = document.createElement('div');
          ov.className = 'tdt-modal-overlay';
          ov.innerHTML = `<div class="tdt-modal-dialog">${inner}</div>`;
          document.body.appendChild(ov);
          const close = () => ov.remove();
          ov.addEventListener('click', (e) => { if (e.target === ov) close(); });
          ov.querySelectorAll('[data-close]').forEach((el) => el.addEventListener('click', close));
          return { ov, close };
        };
        const load = async () => {
          const { res, data } = await api.request('/admin/revendas', {}, { notify: false });
          const revendas = (res.ok && data?.success !== false) ? toArray(data?.data) : [];
          emptyEl?.classList.toggle('hidden', revendas.length > 0);
          if (!listEl) return;
          if (!revendas.length) { listEl.innerHTML = ''; return; }
          listEl.innerHTML = `
            <table class="w-full text-sm min-w-[640px]">
              <thead><tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-200">
                <th class="py-2 pr-3">Usuário</th><th class="py-2 pr-3">Status</th><th class="py-2 pr-3">Início</th><th class="py-2 pr-3">Vencimento</th><th class="py-2 pr-3">Telefone</th><th class="py-2 pr-3">Créditos</th><th class="py-2 pr-3"></th>
              </tr></thead>
              <tbody>${revendas.map((r) => `
                <tr class="border-b border-slate-100">
                  <td class="py-2 pr-3"><p class="font-semibold text-[#111B3F]">${safeText(r.nome, 'Revenda')}</p><p class="text-xs text-slate-500">${safeText(r.email, '')}</p></td>
                  <td class="py-2 pr-3"><span class="ui-badge ${String(r.status).toLowerCase() === 'ativo' ? 'ui-badge--success' : 'ui-badge--neutral'}">${safeText(r.status, '-')}</span></td>
                  <td class="py-2 pr-3 whitespace-nowrap">${r.inicio ? formatDatePtBr(r.inicio) : '-'}</td>
                  <td class="py-2 pr-3 whitespace-nowrap">${vencChip(r)}</td>
                  <td class="py-2 pr-3 whitespace-nowrap">${safeText(r.telefone, '-')}</td>
                  <td class="py-2 pr-3 whitespace-nowrap font-bold text-[#133F8C]">${brl(r.creditos)}</td>
                  <td class="py-2 pr-3"><button type="button" data-renew="${r.id}" class="ui-btn ui-btn--outline ui-btn--sm">Renovar / Créditos</button></td>
                </tr>`).join('')}</tbody>
            </table>`;
          listEl.querySelectorAll('[data-renew]').forEach((btn) => btn.addEventListener('click', () => openRenew(revendas.find((x) => String(x.id) === btn.dataset.renew))));
        };
        const openRenew = (r) => {
          if (!r) return;
          const { ov, close } = openModal(`
            <div class="flex items-center justify-between mb-4"><p class="font-headline font-extrabold text-on-surface">${safeText(r.nome, 'Revenda')}</p><button type="button" data-close class="text-on-surface-variant"><span class="material-symbols-outlined">close</span></button></div>
            <p class="text-xs text-on-surface-variant mb-3">Saldo atual: <b>${brl(r.creditos)}</b> · Vence: <b>${r.vencimento ? formatDatePtBr(r.vencimento) : 'sem vencimento'}</b></p>
            <label class="ui-label">Renovar acesso</label>
            <div class="grid grid-cols-4 gap-2 mb-4">${[['30d', 30], ['3m', 90], ['6m', 180], ['12m', 365]].map(([l, d]) => `<button type="button" data-dias="${d}" class="ui-btn ui-btn--outline ui-btn--sm">${l}</button>`).join('')}</div>
            <label class="ui-label">Adicionar créditos (R$)</label>
            <div class="flex gap-2">
              <input type="number" step="0.01" min="0" data-cred class="ui-input" placeholder="Ex: 100.00" />
              <button type="button" data-add-cred class="ui-btn ui-btn--primary">Adicionar</button>
            </div>
            <p data-msg class="text-xs text-on-surface-variant mt-2"></p>`);
          const msg = ov.querySelector('[data-msg]');
          const doRenew = async (body, okText) => {
            ov.querySelectorAll('button').forEach((b) => (b.disabled = true));
            if (msg) { msg.textContent = 'Salvando...'; msg.className = 'text-xs text-on-surface-variant mt-2'; }
            const { res, data } = await api.request(`/admin/revendas/${r.id}/renovar`, { method: 'POST', body: JSON.stringify(body) }, { notify: false });
            if (res.ok && data?.success !== false) { ui.message(okText, 'success'); close(); await load(); }
            else { if (msg) { msg.textContent = data?.message || 'Erro ao salvar.'; msg.className = 'text-xs text-rose-600 mt-2'; } ov.querySelectorAll('button').forEach((b) => (b.disabled = false)); }
          };
          ov.querySelectorAll('[data-dias]').forEach((b) => b.addEventListener('click', () => doRenew({ dias: Number(b.dataset.dias) }, 'Acesso renovado.')));
          ov.querySelector('[data-add-cred]')?.addEventListener('click', () => { const v = Number(ov.querySelector('[data-cred]')?.value); if (!(v > 0)) { if (msg) { msg.textContent = 'Informe um valor válido.'; msg.className = 'text-xs text-rose-600 mt-2'; } return; } doRenew({ creditos: v }, 'Créditos adicionados.'); });
        };
        const openCreate = () => {
          const { ov, close } = openModal(`
            <div class="flex items-center justify-between mb-4"><p class="font-headline font-extrabold text-on-surface">Cadastrar revenda</p><button type="button" data-close class="text-on-surface-variant"><span class="material-symbols-outlined">close</span></button></div>
            <div class="space-y-3">
              <div><label class="ui-label">Nome</label><input data-f="nome" class="ui-input" placeholder="Nome da revenda" /></div>
              <div><label class="ui-label">Usuário (e-mail)</label><input data-f="email" type="email" class="ui-input" placeholder="revenda@exemplo.com" /></div>
              <div><label class="ui-label">Senha</label><input data-f="senha" type="password" class="ui-input" placeholder="Mínimo 6 caracteres" /></div>
              <div class="grid grid-cols-2 gap-2"><div><label class="ui-label">Telefone</label><input data-f="telefone" class="ui-input" placeholder="(00) 0000-0000" /></div><div><label class="ui-label">WhatsApp</label><input data-f="whatsapp" class="ui-input" placeholder="(00) 00000-0000" /></div></div>
              <div class="grid grid-cols-2 gap-2"><div><label class="ui-label">Créditos (R$)</label><input data-f="creditos" type="number" step="0.01" min="0" class="ui-input" placeholder="0.00" /></div><div><label class="ui-label">Validade</label><select data-f="dias" class="ui-select">${[['30 dias', 30], ['3 meses', 90], ['6 meses', 180], ['12 meses', 365]].map(([l, d]) => `<option value="${d}">${l}</option>`).join('')}</select></div></div>
            </div>
            <button type="button" data-save class="ui-btn ui-btn--primary ui-btn--block mt-4">Salvar revenda</button>
            <p data-msg class="text-xs text-on-surface-variant mt-2"></p>`);
          const g = (f) => ov.querySelector(`[data-f="${f}"]`)?.value;
          const msg = ov.querySelector('[data-msg]');
          ov.querySelector('[data-save]')?.addEventListener('click', async (e) => {
            const nome = g('nome')?.trim(); const email = g('email')?.trim(); const senha = g('senha');
            if (!nome || !email || !senha || senha.length < 6) { if (msg) { msg.textContent = 'Preencha nome, e-mail e senha (mín. 6).'; msg.className = 'text-xs text-rose-600 mt-2'; } return; }
            e.currentTarget.disabled = true; if (msg) { msg.textContent = 'Criando...'; msg.className = 'text-xs text-on-surface-variant mt-2'; }
            const body = { nome, email, senha, telefone: g('telefone')?.trim(), whatsapp: g('whatsapp')?.trim(), creditos: Number(g('creditos') || 0), dias: Number(g('dias') || 30) };
            const { res, data } = await api.request('/admin/revendas', { method: 'POST', body: JSON.stringify(body) }, { notify: false });
            if (res.ok && data?.success !== false) { ui.message(data?.message || 'Revenda criada.', 'success'); close(); await load(); }
            else { if (msg) { msg.textContent = data?.message || 'Erro ao criar revenda.'; msg.className = 'text-xs text-rose-600 mt-2'; } e.currentTarget.disabled = false; }
          });
        };
        section.querySelector('#btnNovaRevenda')?.addEventListener('click', openCreate);
        await load();
      };
      mountRevendas();

      const tbody = document.getElementById('adminUsersTable');
      if (!tbody) {
        ui.clearPageState();
        if (!admins.length) return ui.message('Nenhum usuário retornado.', 'warning');
        return render.section(
          'Usuários',
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
          const userName = tr.dataset.userName || 'Usuário';
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
              ui.message(resp.data?.message || `Não foi possível ${actionLabel} a conta.`, 'error');
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
              ui.message('Nenhuma alteração informada.', 'info');
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
              ui.message(resp.data?.message || 'Não foi possível atualizar os dados do usuário.', 'error');
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
        metric('adminUsersReviso', bloqueados ? `${bloqueados} em revisão` : 'OK');
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
          const nome = u.name || u.nome || u.email || 'Usuário';
          const email = u.email || '';
          const perfil = u.perfil || u.role || 'admin';
          const status = suspenso(u) ? 'Suspenso' : ativo(u) ? 'Ativo' : 'Inativo';
          const inicio = formatDatePtBr(u.created_at, '-');
          const telefone = safeText(u.telefone, '-');
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
            <td class="px-6 py-4 text-sm text-on-surface-variant whitespace-nowrap">${inicio}</td>
            <td class="px-6 py-4 text-sm text-on-surface-variant whitespace-nowrap">${telefone}</td>
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
        window.location.href = '/gest_o_de_estabelecimentos.html?novo=1';
      });

      bindBusca(admins);
      renderLista(admins);
    },

    async clientesMaster() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando clientes...');
      const usersDataset = await admin.loadUsersDataset();
      if (!usersDataset.ok) {
        ui.message(usersDataset.message || 'Nao foi possivel carregar os clientes agora.', 'warning');
      }
      
      let clientes = [];
      if (usersDataset.ok && usersDataset.list.length) {
        const lista = usersDataset.list;
        clientes = lista.filter((u) => (u.perfil || u.role || '').toString().toLowerCase().includes('cliente'));
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
      const pushUi = {
        email: document.getElementById('adminPushClientEmail'),
        lookup: document.getElementById('adminPushLookupBtn'),
        send: document.getElementById('adminPushSendBtn'),
        title: document.getElementById('adminPushTitle'),
        body: document.getElementById('adminPushBody'),
        url: document.getElementById('adminPushUrl'),
        name: document.getElementById('adminPushClientName'),
        emailValue: document.getElementById('adminPushClientEmailValue'),
        subscription: document.getElementById('adminPushClientSubscription'),
        devices: document.getElementById('adminPushClientDevices'),
        lastSeen: document.getElementById('adminPushClientLastSeen'),
        hint: document.getElementById('adminPushClientHint'),
        summaryTitle: document.getElementById('adminPushSummaryTitle'),
        summaryDetail: document.getElementById('adminPushSummaryDetail'),
        feedback: document.getElementById('adminPushTestFeedback'),
      };
      let selectedPushClient = null;

      const formatPushDateTime = (value) => {
        if (!value) return '--';
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return '--';
        return parsed.toLocaleString('pt-BR');
      };

      const normalizePushLookupTerm = (value) => safeText(value, '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9@._-]+/g, '');

      const canSendAdminPushTest = () => {
        const title = safeText(pushUi.title?.value, '').trim();
        const body = safeText(pushUi.body?.value, '').trim();
        const url = safeText(pushUi.url?.value, '').trim();

        return Boolean(selectedPushClient?.id && title && body && url);
      };

      const syncAdminPushSendState = () => {
        if (pushUi.send) {
          pushUi.send.disabled = !canSendAdminPushTest();
        }
      };

      const resetPushTester = (message = 'Consulte um cliente e confirme se ha notificacoes ativas neste dispositivo.', tone = 'info') => {
        selectedPushClient = null;
        if (pushUi.name) pushUi.name.textContent = 'Nenhum cliente selecionado';
        if (pushUi.emailValue) pushUi.emailValue.textContent = 'Busque por nome, e-mail, CPF ou telefone';
        if (pushUi.subscription) pushUi.subscription.textContent = 'Nao verificado';
        if (pushUi.devices) pushUi.devices.textContent = '0 dispositivo(s)';
        if (pushUi.lastSeen) pushUi.lastSeen.textContent = '--';
        if (pushUi.hint) pushUi.hint.textContent = 'Peca para o cliente abrir o app instalado, fazer login e tocar em Ativar notificacoes.';
        if (pushUi.summaryTitle) pushUi.summaryTitle.textContent = 'Nenhum envio realizado nesta sessao.';
        if (pushUi.summaryDetail) pushUi.summaryDetail.textContent = 'Confirme se existe ao menos uma subscription ativa antes de disparar o teste.';
        syncAdminPushSendState();
        setInlineFeedback(pushUi.feedback, message, tone);
      };

      const paintPushTester = (payload) => {
        const user = payload?.user || {};
        const push = payload?.push || {};
        const total = Number(push?.total_subscriptions ?? push?.active_subscriptions ?? 0);
        selectedPushClient = user?.id ? user : null;

        if (pushUi.name) pushUi.name.textContent = safeText(user?.name, 'Cliente');
        if (pushUi.emailValue) pushUi.emailValue.textContent = safeText(user?.email, pushUi.email?.value || '--');
        if (pushUi.subscription) pushUi.subscription.textContent = push?.has_active_subscription ? 'Sim' : 'Nao';
        if (pushUi.devices) {
          const deviceLabels = Array.isArray(push?.devices) && push.devices.length ? ` (${push.devices.join(', ')})` : '';
          pushUi.devices.textContent = `${total} dispositivo(s)${deviceLabels}`;
        }
        if (pushUi.lastSeen) pushUi.lastSeen.textContent = formatPushDateTime(push?.last_seen_at);
        if (pushUi.hint) {
          pushUi.hint.textContent = push?.has_active_subscription
            ? 'Cliente apto para receber push neste(s) dispositivo(s).'
            : 'Cliente ainda nao ativou notificacoes. Peça para abrir o app no iPhone instalado e tocar em Ativar notificacoes.';
        }
        if (pushUi.summaryTitle) {
          pushUi.summaryTitle.textContent = push?.has_active_subscription
            ? 'Cliente pronto para teste individual.'
            : 'Cliente localizado, mas sem subscription ativa.';
        }
        if (pushUi.summaryDetail) {
          pushUi.summaryDetail.textContent = push?.has_active_subscription
            ? 'Use Enviar para validar o recebimento neste dispositivo.'
            : 'Sem subscription real, o admin recebera um aviso amigavel e nenhum envio sera marcado como entregue.';
        }
        syncAdminPushSendState();
        setInlineFeedback(pushUi.feedback, 'Cliente localizado. O status exibido reflete a subscription real salva pelo navegador.', push?.has_active_subscription ? 'success' : 'warning');
      };

      const loadAdminPushClient = async (queryOverride = null) => {
        const lookupTerm = safeText(queryOverride ?? pushUi.email?.value, '').trim();
        if (!lookupTerm) {
          resetPushTester('Informe nome, e-mail, CPF ou telefone para consultar o status de push.', 'warning');
          return;
        }

        if (pushUi.email) pushUi.email.value = lookupTerm;

        if (pushUi.lookup) pushUi.lookup.disabled = true;
        if (pushUi.send) pushUi.send.disabled = true;
        setInlineFeedback(pushUi.feedback, 'Buscando status real da subscription do cliente...', 'info');

        const result = await api.request(`/admin/push/client-status?q=${encodeURIComponent(lookupTerm)}`, {}, { notify: false });

        if (pushUi.lookup) pushUi.lookup.disabled = false;

        if (!result.res.ok || result.data?.success === false) {
          resetPushTester(result.data?.message || 'Nao foi possivel localizar o cliente para teste de push.', result.res.status === 404 ? 'warning' : 'error');
          return;
        }

        paintPushTester(result.data?.data || {});
      };

      const sendAdminPushTest = async () => {
        if (!selectedPushClient?.id) {
          setInlineFeedback(pushUi.feedback, 'Localize primeiro o cliente que recebera o push teste.', 'warning');
          return;
        }

        const title = safeText(pushUi.title?.value, '').trim();
        const body = safeText(pushUi.body?.value, '').trim();
        const url = safeText(pushUi.url?.value, '').trim();

        if (!title || !body || !url) {
          syncAdminPushSendState();
          setInlineFeedback(pushUi.feedback, 'Preencha titulo, mensagem e URL antes de enviar o push teste.', 'warning');
          return;
        }

        if (pushUi.send) pushUi.send.disabled = true;
        setInlineFeedback(pushUi.feedback, 'Enviando push teste para o cliente selecionado...', 'info');

        const { res, data } = await api.request('/admin/push/test-client', {
          method: 'POST',
          body: JSON.stringify({
            user_id: selectedPushClient.id,
            title,
            body,
            url,
          }),
        }, { notify: false });

        const summary = formatPushDeliverySummary(data?.meta?.delivery || {}, 'cliente selecionado');
        if (pushUi.summaryTitle) pushUi.summaryTitle.textContent = summary.short || (data?.message || 'Resumo do envio indisponivel.');
        if (pushUi.summaryDetail) pushUi.summaryDetail.textContent = summary.detail || '';

        if (res.ok && data?.success !== false) {
          setInlineFeedback(pushUi.feedback, data?.message || 'Push teste enviado com sucesso.', 'success');
          ui.message(data?.message || 'Push teste enviado com sucesso.', 'success');
        } else {
          const tone = data?.error === 'no_subscription' || data?.error === 'config_missing' ? 'warning' : 'error';
          const message = data?.message || 'Nao foi possivel enviar o push teste para este cliente.';
          setInlineFeedback(pushUi.feedback, message, tone);
          ui.message(message, tone);
        }

        await loadAdminPushClient(selectedPushClient.email || pushUi.email?.value || '');
      };

      if (pushUi.lookup && pushUi.lookup.dataset.bound !== '1') {
        pushUi.lookup.dataset.bound = '1';
        pushUi.lookup.addEventListener('click', () => loadAdminPushClient());
      }

      if (pushUi.email && pushUi.email.dataset.bound !== '1') {
        pushUi.email.dataset.bound = '1';
        pushUi.email.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            loadAdminPushClient();
          }
        });
      }

      if (pushUi.send && pushUi.send.dataset.bound !== '1') {
        pushUi.send.dataset.bound = '1';
        pushUi.send.addEventListener('click', () => sendAdminPushTest());
      }

      [pushUi.title, pushUi.body, pushUi.url].filter(Boolean).forEach((field) => {
        if (field.dataset.boundInput === '1') return;
        field.dataset.boundInput = '1';
        field.addEventListener('input', () => syncAdminPushSendState());
      });

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
          const ultima = formatDatePtBr(c.last_login || c.updated_at || c.created_at, '-');
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
      resetPushTester();
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
      if (!stats.res.ok || !checkins.res.ok || !adminSummaryResp.res.ok || !usersDataset.ok) {
        ui.message('Parte dos dados de relatorios nao respondeu. Apenas informacoes reais disponiveis estao sendo exibidas.', 'warning');
      }

      const statsData = stats.data?.data || stats.data || {};
      const adminSummary = adminSummaryResp.data?.data || {};
      const summaryCards = adminSummary?.cards || {};
      const totals = statsData?.totais || {};
      const usersList = usersDataset.ok ? usersDataset.list : [];
      const usersPayload = { total: usersList.length };
      const empresasListApi = toArray(empresasResp.data?.data || empresasResp.data);
      const empresasList = admin.enrichCompaniesDataset(empresasListApi);
      const checkData = checkins.data?.data || checkins.data || {};
      const fallbackUsers = usersList.length;

      const totalEmpresas = toNumber(summaryCards.total_empresas, totals.empresas, statsData.empresas, statsData.total_empresas, empresasList.length);
      const totalUsuarios = toNumber(totals.usuarios, statsData.usuarios, statsData.total_users, usersPayload.total, usersList.length, fallbackUsers);
      const totalClientes = toNumber(
        summaryCards.total_clientes,
        statsData.clientes,
        usersList.filter((u) => (u?.perfil || u?.role || '').toString().toLowerCase().includes('cliente')).length,
        Math.round(totalUsuarios * 0.76)
      );
      const totalPromocoes = toNumber(summaryCards.total_promocoes, totals.campanhas, statsData.promocoes, statsData.campanhas, 0);
      const totalResgates = toNumber(summaryCards.total_resgates, totals.resgates, statsData.resgates, 0);
      const totalVínculos = toNumber(summaryCards.total_vinculos_cliente_empresa);
      const totalNotificacoes = toNumber(summaryCards.total_notificacoes);
      const mediaGeralAvaliacoes = Number(toNumber(summaryCards.media_geral_avaliacoes, 0)).toFixed(1);
      const totalVolume = toNumber(totals.volume, statsData.volume, 0);

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
            { label: 'Usuários', value: Number(totalUsuarios || 0).toLocaleString('pt-BR') },
            { label: 'Clientes', value: Number(totalClientes || 0).toLocaleString('pt-BR') },
            { label: 'Empresas', value: Number(totalEmpresas || 0).toLocaleString('pt-BR') },
            { label: 'Vínculos cliente x empresa', value: Number(totalVínculos || 0).toLocaleString('pt-BR') },
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
        'Nenhum ranking de clientes disponível.'
      );

      renderMetricRows(
        'relTopCompaniesRedemptions',
        toArray(adminSummary.empresas_com_mais_resgates).map((item) => ({
          label: safeText(item?.nome, 'Empresa'),
          value: `${Number(item?.total_resgates || 0).toLocaleString('pt-BR')} resgate(s)`,
        })),
        'text-tertiary',
        'Nenhum ranking de resgates disponível.'
      );

      const relCheckinsList = document.getElementById('relCheckinsList');
      if (relCheckinsList) {
        relCheckinsList.innerHTML = '';
        const entries = Object.entries(checkData || {}).filter(([, value]) => Number.isFinite(Number(value)));
        const statsEntries = entries;
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
            ui.message('Não foi possível gerar o relatório agora.', 'error');
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
          checkinsContainer.innerHTML = '<p class="text-sm text-on-surface-variant text-center py-4">Nenhum check-in pendente de aprovação.</p>';
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

      // Sem banner de erro global aqui para evitar ruido visual duplicado.
    },

    async configuracoes() {
      if (!(await auth.guard(['admin']))) return;
      ui.setPageState('loading', 'Carregando configurações...');
      const { res, data } = await api.request('/admin/settings', {}, { notify: false });
      ui.clearPageState();

      if (!res.ok || data?.success === false || !data?.data) {
        ui.message(data?.message || 'Não foi possível carregar configurações.', 'error');
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
          ui.message(resp.data?.message || errors || 'Erro ao salvar configurações.', 'error');
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
      const target = resolvePostLoginTarget(perfil);
      if (res.ok && token && user) {
        auth.save(token, user);
        if (perfil === 'cliente') savePushPrompt('login');
        ui.clearPageState();
        ui.message('Login realizado, redirecionando...', 'success');
        setTimeout(() => (window.location.href = target), 300);
      } else {
        ui.clearPageState();
        ui.message(data?.message || payload?.message || 'Não foi possível entrar.', 'error');
      }
    });
  }

  // ---------------------- Paginas: Revenda ---------------------- //
  const revenda = {
    async painel() {
      if (!(await auth.guard(['revenda']))) return;
      const brl = (v) => `R$ ${Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      const saldoEl = document.getElementById('revSaldo');
      const vencEl = document.getElementById('revVencimento');
      const listEl = document.getElementById('revEmpresasList');
      const skelEl = document.getElementById('revEmpresasSkeleton');
      const emptyEl = document.getElementById('revEmpresasEmpty');
      let saldoAtual = 0;

      document.getElementById('revLogout')?.addEventListener('click', () => auth.logout());

      const loadSaldo = async () => {
        const { res, data } = await api.request('/revenda/me', {}, { notify: false });
        if (res.ok && data?.success !== false) {
          saldoAtual = Number(data?.data?.creditos || 0);
          if (saldoEl) saldoEl.textContent = brl(saldoAtual);
          if (vencEl) vencEl.textContent = `Vencimento: ${data?.data?.vencimento ? formatDatePtBr(data.data.vencimento) : 'sem vencimento'}`;
        }
      };

      const loadEmpresas = async () => {
        const { res, data } = await api.request('/revenda/empresas', {}, { notify: false });
        const lista = (res.ok && data?.success !== false) ? toArray(data?.data) : [];
        skelEl?.classList.add('hidden');
        emptyEl?.classList.toggle('hidden', lista.length > 0);
        if (!listEl) return;
        listEl.innerHTML = lista.map((e) => `
          <article class="ui-card ui-card--hover !p-4 flex items-center justify-between gap-3">
            <div>
              <p class="font-bold text-[#111B3F]">${safeText(e.nome, 'Estabelecimento')}</p>
              <p class="text-xs text-slate-500">${safeText(e.plano, 'Sem plano')} · ${safeText(e.telefone, 'sem telefone')}</p>
            </div>
            <div class="text-right">
              <span class="ui-badge ${e.status === 'active' ? 'ui-badge--success' : 'ui-badge--neutral'}">${e.status === 'active' ? 'Ativo' : safeText(e.status, '-')}</span>
              <p class="text-[11px] text-slate-500 mt-1">${e.vencimento ? 'Vence ' + formatDatePtBr(e.vencimento) : ''}</p>
            </div>
          </article>`).join('');
      };

      const openCreate = async () => {
        // busca planos para o seletor (reusa /admin? nao: revenda nao tem. usa lista fixa dos planos via /revenda/me? )
        const overlay = document.createElement('div');
        overlay.className = 'tdt-modal-overlay';
        overlay.innerHTML = `
          <div class="tdt-modal-dialog">
            <div class="flex items-center justify-between mb-4"><p class="font-headline font-extrabold text-on-surface">Novo estabelecimento</p><button type="button" data-close class="text-on-surface-variant"><span class="material-symbols-outlined">close</span></button></div>
            <p class="text-xs text-on-surface-variant mb-3">Saldo: <b>${brl(saldoAtual)}</b>. O valor do plano é descontado ao criar.</p>
            <div class="space-y-3">
              <div><label class="ui-label">Nome</label><input data-f="nome" class="ui-input" placeholder="Nome do estabelecimento" /></div>
              <div><label class="ui-label">Usuário (e-mail)</label><input data-f="email" type="email" class="ui-input" placeholder="empresa@exemplo.com" /></div>
              <div><label class="ui-label">Senha</label><input data-f="senha" type="password" class="ui-input" placeholder="Mínimo 6 caracteres" /></div>
              <div><label class="ui-label">Telefone</label><input data-f="telefone" class="ui-input" placeholder="(00) 00000-0000" /></div>
              <div><label class="ui-label">Validade</label><select data-f="dias" class="ui-select">${[['30 dias', 30], ['3 meses', 90], ['6 meses', 180], ['12 meses', 365]].map(([l, d]) => `<option value="${d}">${l}</option>`).join('')}</select></div>
            </div>
            <button type="button" data-save class="ui-btn ui-btn--primary ui-btn--block mt-4">Criar estabelecimento</button>
            <p data-msg class="text-xs text-on-surface-variant mt-2"></p>`;
        document.body.appendChild(overlay);
        const close = () => overlay.remove();
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
        overlay.querySelector('[data-close]')?.addEventListener('click', close);
        const g = (f) => overlay.querySelector(`[data-f="${f}"]`)?.value;
        const msg = overlay.querySelector('[data-msg]');
        overlay.querySelector('[data-save]')?.addEventListener('click', async (ev) => {
          const nome = g('nome')?.trim(); const email = g('email')?.trim(); const senha = g('senha');
          if (!nome || !email || !senha || senha.length < 6) { if (msg) { msg.textContent = 'Preencha nome, e-mail e senha (mín. 6).'; msg.className = 'text-xs text-rose-600 mt-2'; } return; }
          ev.currentTarget.disabled = true; if (msg) { msg.textContent = 'Criando...'; msg.className = 'text-xs text-on-surface-variant mt-2'; }
          const body = { nome, email, senha, telefone: g('telefone')?.trim(), dias: Number(g('dias') || 30) };
          const { res, data } = await api.request('/revenda/empresas', { method: 'POST', body: JSON.stringify(body) }, { notify: false });
          if (res.ok && data?.success !== false) { ui.message(data?.message || 'Estabelecimento criado.', 'success'); close(); await Promise.all([loadSaldo(), loadEmpresas()]); }
          else { if (msg) { msg.textContent = data?.message || 'Não foi possível criar.'; msg.className = 'text-xs text-rose-600 mt-2'; } ev.currentTarget.disabled = false; }
        });
      };

      document.getElementById('btnRevNovaEmpresa')?.addEventListener('click', openCreate);
      await Promise.all([loadSaldo(), loadEmpresas()]);
    },
  };

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
        if (perfil === 'cliente') savePushPrompt('login');
        window.location.href = resolvePostLoginTarget(perfil);
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
      const spinnerEl = document.getElementById('companyLinkSpinner');
      const stopSpinner = () => spinnerEl?.classList.add('hidden');

      if (!code) {
        stopSpinner();
        if (messageEl) messageEl.textContent = 'QR Code da empresa nao informado.';
        return;
      }

      ui.setPageState('loading', 'Validando QR da empresa...');
      const { res, data } = await api.request(`/qrcode/empresa/${encodeURIComponent(code)}`, {}, { requireAuth: false, notify: false });
      ui.clearPageState();

      if (!res.ok || data?.success === false) {
        stopSpinner();
        // QR invalido/expirado: NUNCA prender o cliente num loop de vinculo->login.
        // Limpa o codigo pendente para a home nao redirecionar de novo.
        clearPendingCompanyQr();
        if (statusEl) statusEl.textContent = 'QR indisponivel';
        if (messageEl) messageEl.textContent = data?.message || 'Não foi possível identificar esta empresa. Verifique o QR e tente de novo.';
        const storedNow = auth.getStored();
        const perfilNow = auth.normalizePerfil(auth.normalizeUser(storedNow?.user)?.perfil || storedNow?.user?.perfil || storedNow?.user?.role);
        if (storedNow?.token) {
          const home = redirectMap[perfilNow] || '/meus_pontos.html';
          const back = home.includes('?') ? `${home}&ignore_pending_qr=1` : `${home}?ignore_pending_qr=1`;
          if (primaryBtn) { primaryBtn.textContent = 'Voltar ao início'; primaryBtn.addEventListener('click', () => { window.location.href = back; }); }
          setTimeout(() => { window.location.href = back; }, 1400);
        } else if (primaryBtn) {
          primaryBtn.textContent = 'Entrar';
          primaryBtn.addEventListener('click', () => { window.location.href = '/entrar.html'; });
        }
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
        stopSpinner();
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
        // Falha no vinculo: limpa o pendente para nao repetir o loop na home.
        clearPendingCompanyQr();
        stopSpinner();
        if (messageEl) messageEl.textContent = linkResponse.data?.message || 'Não foi possível concluir o vínculo.';
        if (primaryBtn) { primaryBtn.textContent = 'Voltar ao início'; primaryBtn.addEventListener('click', () => { window.location.href = '/meus_pontos.html?ignore_pending_qr=1'; }); }
        return;
      }

      clearPendingCompanyQr();
      const target = linkResponse.data?.data?.public_page_url || `/detalhe_do_parceiro.html?id=${encodeURIComponent(company.id || '')}`;
      if (messageEl) messageEl.textContent = 'Vínculo concluído. Abrindo a página da empresa...';
      setTimeout(() => {
        window.location.href = target.includes('?') ? `${target}&linked=1` : `${target}?linked=1`;
      }, 350);
    },
    // Comprovante do bônus de adesão: tela cheia pós-resgate, fora da página da empresa.
    bonus_resgatado: async () => {
      if (!(await auth.guard(['cliente']))) return;
      const params = new URLSearchParams(window.location.search);
      const empresaId = (params.get('empresa') || '').trim();
      const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
      };

      const backLink = document.getElementById('receiptBackToCompany');
      if (empresaId && backLink) {
        backLink.href = `/detalhe_do_parceiro.html?id=${encodeURIComponent(empresaId)}`;
        backLink.classList.remove('hidden');
      }

      if (!empresaId) {
        setText('receiptDescription', 'Não encontramos os dados deste resgate. Confira seu histórico.');
        return;
      }

      const { res, data } = await api.request(`/cliente/bonus-adesao/disponivel/${encodeURIComponent(empresaId)}`, {}, { notify: false });
      if (!res.ok || data?.success === false) {
        setText('receiptDescription', data?.message || 'Não foi possível confirmar o resgate agora. Confira seu histórico.');
        return;
      }

      const payload = data?.data || {};
      const bonus = payload.bonus || {};
      const empresa = payload.empresa || {};

      setText('receiptCompany', safeText(empresa.nome, 'Estabelecimento'));
      setText('receiptTitle', safeText(bonus.titulo, 'Você ganhou!'));
      setText('receiptDescription', safeText(bonus.descricao, 'Benefício de boas-vindas resgatado com sucesso.'));

      const img = document.getElementById('receiptImage');
      if (img) {
        img.src = safeImage(bonus.imagem_url || bonus.imagem, IMAGE_FALLBACKS.promo);
        img.onerror = () => { img.onerror = null; img.src = IMAGE_FALLBACKS.promo; };
      }

      const redeemedAt = payload.redeemed_at ? new Date(payload.redeemed_at) : new Date();
      setText('receiptDate', redeemedAt.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
      }));

      const bonusRef = (params.get('bonus') || bonus.id || '0').toString().replace(/\D/g, '') || '0';
      setText('receiptCode', `AD-${empresaId}-${bonusRef}`);

      // Confirmação de verdade vem da API: se ainda não consta como resgatado,
      // rebaixa o tom (sem celebrar um resgate que não aconteceu).
      if (payload.status !== 'redeemed') {
        setText('receiptKicker', 'Bônus de adesão');
        setText('receiptTitle', safeText(bonus.titulo, 'Bônus de adesão'));
        setText('receiptDescription', safeText(payload.message, 'Este bônus ainda não consta como resgatado.'));
      }
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
    revenda_painel: revenda.painel,
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
        const createdToken = data?.token || data?.access_token || data?.data?.token || null;
        const createdUser = auth.normalizeUser(data?.user || data?.data?.user || data?.usuario || null);
        ui.clearPageState();
        if (res.ok && data?.success !== false) {
          if (perfil === 'empresa' && isAdminCompanyFlow) {
            ui.message('Estabelecimento criado com sucesso!', 'success');
            setTimeout(() => (window.location.href = '/gest_o_de_estabelecimentos.html'), 800);
          } else if (perfil === 'empresa') {
            ui.message(data?.message || 'Solicitação enviada. Aguarde aprovação do administrador.', 'success');
            setTimeout(() => (window.location.href = '/entrar.html'), 1200);
          } else {
            if (createdToken && createdUser) {
              auth.save(createdToken, createdUser);
              savePushPrompt('register');
              const target = resolveCompanyQrRedirect('cliente') || resolvePostLoginTarget('cliente');
              ui.message('Conta criada. Vamos ativar as notificacoes no proximo passo.', 'success');
              setTimeout(() => (window.location.href = target), 500);
            } else {
              savePushPrompt('register');
              saveAccessNotice('Conta criada. Faca login para continuar.', 'success');
              ui.message('Conta criada. Faca login.', 'success');
              setTimeout(() => (window.location.href = '/entrar.html'), 800);
            }
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
      const heroSection = sections[0];
      const heroTexts = heroSection?.querySelectorAll('p, h1');
      if (heroTexts?.length) {
        if (heroTexts[0]) heroTexts[0].textContent = 'Conteúdo & Banners';
        if (heroTexts[1]) heroTexts[1].textContent = 'Gestão de Banners e Categorias';
        if (heroTexts[2]) heroTexts[2].textContent = 'Edite banners e categorias em tempo real. O que você salvar aqui será refletido no aplicativo.';
      }
      const heroButton = document.getElementById('btnConteudoAviso');
      if (heroButton) heroButton.textContent = 'Entendi';

      const escapeHtml = (value) =>
        String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/\"/g, '&quot;')
          .replace(/'/g, '&#39;');

      const fetchContent = async () => {
        const { res, data } = await api.request('/admin/content', {}, { notify: false });
        if (res.ok && data?.success !== false) {
          return {
            ...(data?.data || { banners: [], categorias: [] }),
            unavailable: false,
          };
        }
        return {
          banners: [],
          categorias: [],
          unavailable: true,
          message: data?.message || 'Não foi possível carregar o conteúdo administrativo agora.',
        };
      };

      const renderContent = async () => {
        const payload = await fetchContent();
        const { banners = [], categorias = [] } = payload;
        const isPartial = Boolean(payload?.partial);
        const isUnavailable = Boolean(payload?.unavailable);
        const isReadOnly = isPartial || isUnavailable;
        const sourceLabel = isUnavailable
          ? 'indisponível'
          : isPartial
            ? 'modo somente leitura'
            : 'API administrativa';

        if (status) {
          status.innerHTML = `
            <div class="space-y-2">
              <p><strong>${Number(banners.length).toLocaleString('pt-BR')}</strong> banner(s) e <strong>${Number(categorias.length).toLocaleString('pt-BR')}</strong> categoria(s) carregados.</p>
              <p>Origem atual: <strong>${sourceLabel}</strong>.</p>
              <p>${isUnavailable ? (payload?.message || 'A API de conteúdo não respondeu. Tente novamente mais tarde.') : isPartial ? 'A API de conteúdo respondeu em modo somente leitura.' : 'A operação está pronta para editar sem popups.'}</p>
            </div>
          `;
        }

        const bannerFormMarkup = (item = null) => {
          const isDraft = !item?.id;
          return `
            <article class="content-admin-item ${isDraft ? 'content-admin-item--draft' : ''}" data-banner-editor="${item?.id || 'draft'}">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">${isDraft ? 'Novo banner' : 'Banner ativo'}</p>
                  <h4 class="mt-2 text-lg font-headline font-extrabold text-on-surface">${escapeHtml(item?.title || 'Novo banner de campanha')}</h4>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] ${item?.active === false ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700'}">${item?.active === false ? 'Inativo' : 'Ativo'}</span>
              </div>
              <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="space-y-4">
                  <label class="block">
                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-on-surface-variant">Titulo</span>
                    <input data-banner-field="title" class="mt-2 w-full rounded-2xl border border-outline-variant/20 bg-white px-4 py-3 text-sm focus:ring-2 focus:ring-primary" value="${escapeHtml(item?.title || '')}" placeholder="Ex.: Semana de pontos em dobro" />
                  </label>
                  <label class="block">
                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-on-surface-variant">Link</span>
                    <input data-banner-field="link" class="mt-2 w-full rounded-2xl border border-outline-variant/20 bg-white px-4 py-3 text-sm focus:ring-2 focus:ring-primary" value="${escapeHtml(item?.link || '')}" placeholder="/parceiros_tem_de_tudo.html" />
                  </label>
                  <label class="block">
                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-on-surface-variant">Imagem</span>
                    <input data-banner-field="image_url" class="mt-2 w-full rounded-2xl border border-outline-variant/20 bg-white px-4 py-3 text-sm focus:ring-2 focus:ring-primary" value="${escapeHtml(item?.image_url || '')}" placeholder="/img/placeholder-store.svg" />
                  </label>
                </div>
                <div class="space-y-4">
                  <div class="content-admin-preview">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Preview</p>
                    <p class="mt-3 text-base font-headline font-extrabold text-on-surface">${escapeHtml(item?.title || 'Novo banner de campanha')}</p>
                    <p class="mt-2 text-sm text-on-surface-variant">${escapeHtml(item?.link || 'Defina o link de destino do banner')}</p>
                    <p class="mt-4 text-xs text-on-surface-variant">Imagem: ${escapeHtml(item?.image_url || 'placeholder interno')}</p>
                  </div>
                  <label class="inline-flex items-center gap-3 text-sm font-semibold text-on-surface">
                    <input data-banner-field="active" type="checkbox" class="rounded border-outline-variant/30 text-primary focus:ring-primary" ${item?.active === false ? '' : 'checked'} />
                    Banner ativo na vitrine
                  </label>
                </div>
              </div>
              <div class="mt-5 content-admin-actions">
                <button data-banner-action="${isDraft ? 'create' : 'save'}" data-id="${item?.id || ''}" class="content-admin-button content-admin-button--primary">${isDraft ? 'Criar banner' : 'Salvar banner'}</button>
                ${isDraft ? '<button data-banner-action="cancel-draft" class="content-admin-button content-admin-button--secondary">Cancelar</button>' : `<button data-banner-action="toggle" data-id="${item.id}" class="content-admin-button content-admin-button--secondary">${item?.active === false ? 'Ativar banner' : 'Pausar banner'}</button><button data-banner-action="delete" data-id="${item.id}" class="content-admin-button content-admin-button--danger">Excluir</button>`}
              </div>
            </article>
          `;
        };

        const categoryFormMarkup = (item = null) => {
          const isDraft = !item?.id;
          return `
            <article class="content-admin-item ${isDraft ? 'content-admin-item--draft' : ''}" data-category-editor="${item?.id || 'draft'}">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">${isDraft ? 'Nova categoria' : 'Categoria ativa'}</p>
                  <h4 class="mt-2 text-lg font-headline font-extrabold text-on-surface">${escapeHtml(item?.name || 'Nova categoria')}</h4>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] ${item?.active === false ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700'}">${item?.active === false ? 'Inativa' : 'Ativa'}</span>
              </div>
              <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div class="space-y-4">
                  <label class="block">
                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-on-surface-variant">Nome</span>
                    <input data-category-field="name" class="mt-2 w-full rounded-2xl border border-outline-variant/20 bg-white px-4 py-3 text-sm focus:ring-2 focus:ring-primary" value="${escapeHtml(item?.name || '')}" placeholder="Ex.: Restaurantes" />
                  </label>
                  <label class="block">
                    <span class="text-xs font-bold uppercase tracking-[0.16em] text-on-surface-variant">Slug</span>
                    <input data-category-field="slug" class="mt-2 w-full rounded-2xl border border-outline-variant/20 bg-white px-4 py-3 text-sm focus:ring-2 focus:ring-primary" value="${escapeHtml(item?.slug || '')}" placeholder="restaurantes" />
                  </label>
                </div>
                <div class="space-y-4">
                  <div class="content-admin-preview">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-on-surface-variant">Preview</p>
                    <p class="mt-3 text-base font-headline font-extrabold text-on-surface">${escapeHtml(item?.name || 'Nova categoria')}</p>
                    <p class="mt-2 text-sm text-on-surface-variant">Slug público: ${escapeHtml(item?.slug || 'defina um slug')}</p>
                  </div>
                  <label class="inline-flex items-center gap-3 text-sm font-semibold text-on-surface">
                    <input data-category-field="active" type="checkbox" class="rounded border-outline-variant/30 text-primary focus:ring-primary" ${item?.active === false ? '' : 'checked'} />
                    Categoria ativa na busca
                  </label>
                </div>
              </div>
              <div class="mt-5 content-admin-actions">
                <button data-category-action="${isDraft ? 'create' : 'save'}" data-id="${item?.id || ''}" class="content-admin-button content-admin-button--primary">${isDraft ? 'Criar categoria' : 'Salvar categoria'}</button>
                ${isDraft ? '<button data-category-action="cancel-draft" class="content-admin-button content-admin-button--secondary">Cancelar</button>' : `<button data-category-action="toggle" data-id="${item.id}" class="content-admin-button content-admin-button--secondary">${item?.active === false ? 'Ativar categoria' : 'Pausar categoria'}</button><button data-category-action="delete" data-id="${item.id}" class="content-admin-button content-admin-button--danger">Excluir</button>`}
              </div>
            </article>
          `;
        };

        if (bannersSection) {
          bannersSection.innerHTML = `
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-headline font-bold text-on-surface">Banners</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Edite titulo, link, imagem e status sem abrir popup.</p>
              </div>
              <button id="novoBannerBtn" class="content-admin-button ${isReadOnly ? 'content-admin-button--secondary opacity-60 cursor-not-allowed' : 'content-admin-button--primary'}" ${isReadOnly ? 'disabled' : ''}>Novo banner</button>
            </div>
            <div id="bannersList" class="content-admin-grid"></div>
          `;

          const list = bannersSection.querySelector('#bannersList');
          list.innerHTML = banners.length
            ? banners.map((item) => bannerFormMarkup(item)).join('')
            : '<p class="text-sm text-on-surface-variant">Nenhum banner cadastrado. Crie o primeiro banner da vitrine.</p>';

          bannersSection.querySelector('#novoBannerBtn')?.addEventListener('click', () => {
            if (isReadOnly) {
              ui.message(payload?.message || 'Conteudo em modo somente leitura no momento.', 'warning');
              return;
            }
            if (list.querySelector('[data-banner-editor="draft"]')) return;
            list.insertAdjacentHTML('afterbegin', bannerFormMarkup());
          });

          list?.addEventListener('click', async (event) => {
            if (isReadOnly) {
              ui.message(payload?.message || 'Conteudo em modo somente leitura no momento.', 'warning');
              return;
            }
            const trigger = event.target.closest('[data-banner-action]');
            if (!trigger) return;
            const action = trigger.getAttribute('data-banner-action');
            const id = trigger.getAttribute('data-id');
            const card = trigger.closest('[data-banner-editor]');
            if (!card) return;

            if (action === 'cancel-draft') {
              card.remove();
              return;
            }

            if (action === 'delete') {
              if (!window.confirm('Excluir banner?')) return;
              const { res, data } = await api.request(`/admin/content/banners/${id}`, { method: 'DELETE' });
              if (res.ok && data?.success !== false) {
                ui.message('Banner removido.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao remover banner.', 'error');
              }
              return;
            }

            const payloadData = {
              title: safeText(card.querySelector('[data-banner-field="title"]')?.value, '').trim(),
              link: safeText(card.querySelector('[data-banner-field="link"]')?.value, '').trim(),
              image_url: safeText(card.querySelector('[data-banner-field="image_url"]')?.value, '').trim(),
              active: Boolean(card.querySelector('[data-banner-field="active"]')?.checked),
            };

            if (!payloadData.title) {
              ui.message('Informe o titulo do banner.', 'warning');
              return;
            }

            if (action === 'toggle') {
              payloadData.active = !payloadData.active;
            }

            const targetPath = action === 'create' ? '/admin/content/banners' : `/admin/content/banners/${id}`;
            const method = action === 'create' ? 'POST' : 'PUT';
            const { res, data } = await api.request(targetPath, { method, body: JSON.stringify(payloadData) });
            if (res.ok && data?.success !== false) {
              ui.message(action === 'create' ? 'Banner criado com sucesso.' : 'Banner atualizado com sucesso.', 'success');
              await renderContent();
            } else {
              ui.message(data?.message || 'Erro ao salvar banner.', 'error');
            }
          });
        }

        if (categoriasSection) {
          categoriasSection.innerHTML = `
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-headline font-bold text-on-surface">Categorias</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Ajuste nome, slug e status da navegação pública em linha.</p>
              </div>
              <button id="novaCategoriaBtn" class="content-admin-button ${isReadOnly ? 'content-admin-button--secondary opacity-60 cursor-not-allowed' : 'content-admin-button--primary'}" ${isReadOnly ? 'disabled' : ''}>Nova categoria</button>
            </div>
            <div id="categoriasList" class="content-admin-grid"></div>
          `;

          const list = categoriasSection.querySelector('#categoriasList');
          list.innerHTML = categorias.length
            ? categorias.map((item) => categoryFormMarkup(item)).join('')
            : '<p class="text-sm text-on-surface-variant">Nenhuma categoria cadastrada. Crie a primeira categoria visível na busca.</p>';

          categoriasSection.querySelector('#novaCategoriaBtn')?.addEventListener('click', () => {
            if (isReadOnly) {
              ui.message(payload?.message || 'Conteudo em modo somente leitura no momento.', 'warning');
              return;
            }
            if (list.querySelector('[data-category-editor="draft"]')) return;
            list.insertAdjacentHTML('afterbegin', categoryFormMarkup());
          });

          list?.addEventListener('click', async (event) => {
            if (isReadOnly) {
              ui.message(payload?.message || 'Conteudo em modo somente leitura no momento.', 'warning');
              return;
            }
            const trigger = event.target.closest('[data-category-action]');
            if (!trigger) return;
            const action = trigger.getAttribute('data-category-action');
            const id = trigger.getAttribute('data-id');
            const card = trigger.closest('[data-category-editor]');
            if (!card) return;

            if (action === 'cancel-draft') {
              card.remove();
              return;
            }

            if (action === 'delete') {
              if (!window.confirm('Excluir categoria?')) return;
              const { res, data } = await api.request(`/admin/content/categorias/${id}`, { method: 'DELETE' });
              if (res.ok && data?.success !== false) {
                ui.message('Categoria removida.', 'success');
                await renderContent();
              } else {
                ui.message(data?.message || 'Erro ao remover categoria.', 'error');
              }
              return;
            }

            const payloadData = {
              name: safeText(card.querySelector('[data-category-field="name"]')?.value, '').trim(),
              slug: safeText(card.querySelector('[data-category-field="slug"]')?.value, '').trim() || undefined,
              active: Boolean(card.querySelector('[data-category-field="active"]')?.checked),
            };

            if (!payloadData.name) {
              ui.message('Informe o nome da categoria.', 'warning');
              return;
            }

            if (action === 'toggle') {
              payloadData.active = !payloadData.active;
            }

            const targetPath = action === 'create' ? '/admin/content/categorias' : `/admin/content/categorias/${id}`;
            const method = action === 'create' ? 'POST' : 'PUT';
            const { res, data } = await api.request(targetPath, { method, body: JSON.stringify(payloadData) });
            if (res.ok && data?.success !== false) {
              ui.message(action === 'create' ? 'Categoria criada.' : 'Categoria atualizada.', 'success');
              await renderContent();
            } else {
              ui.message(data?.message || 'Erro ao salvar categoria.', 'error');
            }
          });
        }
      };

      try {
        await renderContent();
      } catch (err) {
        console.error('admin_content_render_fail', err);
        if (status) status.textContent = 'Conteudo administrativo indisponivel no momento.';
        if (bannersSection) {
          bannersSection.innerHTML = '<p class="text-sm text-on-surface-variant">Nao foi possivel carregar os banners agora.</p>';
        }
        if (categoriasSection) {
          categoriasSection.innerHTML = '<p class="text-sm text-on-surface-variant">Nao foi possivel carregar as categorias agora.</p>';
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
    normalizePageEncodingArtifacts();
    mountAdminMobileLogout();
    wireAvatarFallbacks();
    remapNavigationForPerfil();
    harmonizeLinksByStoredPerfil();
    wireFallbackLinks();
    mountUnifiedMobileDock();
    mountDesktopSidebar();
    wireFallbackButtons();
    wireSettingsShortcuts();
    mountPageBackButton();
    wireUtilityButtons();
    wirePushButtons();
    mountInstallPrompt();
    consumeAccessNotice();
    const handler = handlers[page];
    if (handler) {
      try {
        await handler();
        normalizePageEncodingArtifacts();
      } catch (err) {
        console.error(err);
        ui.message('Erro ao carregar pagina.', 'error');
      }
    }
    window.__tdtAppReady = true;
    window.dispatchEvent(new CustomEvent('tdt-app-ready', { detail: { page } }));
  });
})();
