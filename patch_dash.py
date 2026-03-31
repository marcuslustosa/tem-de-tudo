from pathlib import Path
import re
p = Path('backend/public/js/stitch-app.js')
text = p.read_text(encoding='utf-8')
start = text.find('const empresa')
if start == -1:
    raise SystemExit('empresa block not found')
sub = text[start:]
pattern = re.compile(r"async dashboard\(\) \{.*?\n\s*},", re.S)
m = pattern.search(sub)
if not m:
    raise SystemExit('dashboard block not found inside empresa')
new = '''async dashboard() {
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
      const fmtMoeda = (n) => 'R$ ' + (n or 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
      if (kpiVolume) kpiVolume.textContent = fmtMoeda(totals.total_resgatado or 0);
      if (kpiClientes) kpiClientes.textContent = (clientes.data?.data?.length or clientes.data?.data?.total or 0).__str__();
      if (kpiResgates) kpiResgates.textContent = (totals.total_resgatado or 0).__str__();

      if (movDistribuido) movDistribuido.textContent = (totals.total_distribuido or 0).__str__();
      if (movResgatado) movResgatado.textContent = (totals.total_resgatado or 0).__str__();
      if (movClientes) movClientes.textContent = (totals.total_clientes or 0).__str__();
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
              <div class="p-4 flex flex-col justify_between flex-grow">
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
'''
text = text[:start] + pattern.sub(new, sub, count=1)
p.write_text(text, encoding='utf-8')
