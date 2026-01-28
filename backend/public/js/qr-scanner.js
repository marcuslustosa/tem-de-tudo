/**
 * Sistema de QR Code Scanner
 * Substitui o sistema de foto por escaneamento de QR Codes
 */

class QRCodeScanner {
    constructor() {
        this.scanner = null;
        this.isScanning = false;
        this.lastScanResult = null;
        this.scanTimeout = null;
        this.init();
    }

    init() {
        // Verificar se temos acesso à câmera
        this.checkCameraSupport();
        
        // Configurar eventos
        this.setupEvents();
        
        // Carregar biblioteca do QR Scanner
        this.loadQRScanner();
    }

    async checkCameraSupport() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(track => track.stop());
            console.log('✅ Câmera disponível para QR Scanner');
        } catch (error) {
            console.error('❌ Câmera não disponível:', error);
            this.showError('Câmera não disponível. Verifique as permissões.');
        }
    }

    loadQRScanner() {
        // Carregar biblioteca QR Scanner (jsQR ou similar)
        if (!window.jsQR) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
            script.onload = () => {
                console.log('✅ QR Scanner library loaded');
                this.initScanner();
            };
            document.head.appendChild(script);
        } else {
            this.initScanner();
        }
    }

    initScanner() {
        const video = document.getElementById('qr-video');
        const canvas = document.getElementById('qr-canvas');
        const context = canvas.getContext('2d');

        if (!video || !canvas) {
            console.error('❌ Elementos de QR não encontrados');
            return;
        }

        this.video = video;
        this.canvas = canvas;
        this.context = context;

        console.log('✅ QR Scanner inicializado');
    }

    setupEvents() {
        // Botão para iniciar escaneamento
        const scanBtn = document.getElementById('start-qr-scan');
        if (scanBtn) {
            scanBtn.addEventListener('click', () => this.startScan());
        }

        // Botão para parar escaneamento
        const stopBtn = document.getElementById('stop-qr-scan');
        if (stopBtn) {
            stopBtn.addEventListener('click', () => this.stopScan());
        }

        // Botão manual de QR Code
        const manualBtn = document.getElementById('manual-qr-input');
        if (manualBtn) {
            manualBtn.addEventListener('click', () => this.showManualInput());
        }
    }

    async startScan() {
        if (this.isScanning) {
            console.log('⚠️ Scanner já está ativo');
            return;
        }

        try {
            console.log('🔄 Iniciando QR Scanner...');
            
            // Solicitar acesso à câmera
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' } // Câmera traseira preferida
            });

            this.video.srcObject = stream;
            this.video.play();

            this.isScanning = true;
            this.updateUI('scanning');

            // Aguardar vídeo carregar
            this.video.onloadedmetadata = () => {
                this.canvas.width = this.video.videoWidth;
                this.canvas.height = this.video.videoHeight;
                this.scanFrame();
            };

        } catch (error) {
            console.error('❌ Erro ao acessar câmera:', error);
            this.showError('Não foi possível acessar a câmera. Verifique as permissões.');
        }
    }

    scanFrame() {
        if (!this.isScanning) return;

        // Desenhar frame do vídeo no canvas
        this.context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
        
        // Obter dados da imagem
        const imageData = this.context.getImageData(0, 0, this.canvas.width, this.canvas.height);
        
        // Escanear QR Code
        const code = jsQR(imageData.data, imageData.width, imageData.height);

        if (code) {
            console.log('✅ QR Code detectado:', code.data);
            this.handleQRScan(code.data);
        } else {
            // Continuar escaneamento
            requestAnimationFrame(() => this.scanFrame());
        }
    }

    async handleQRScan(qrData) {
        // Evitar escaneamentos duplicados
        if (this.lastScanResult === qrData) {
            console.log('⚠️ QR Code já escaneado recentemente');
            return;
        }

        this.lastScanResult = qrData;
        this.stopScan();
        
        try {
            console.log('🔄 Processando QR Code:', qrData);
            
            // Mostrar loading
            this.updateUI('processing');
            
            // Primeiro, escanear o QR para obter informações
            const scanResponse = await this.scanQRCode(qrData);
            
            if (scanResponse.success) {
                // Mostrar informações e confirmar check-in
                this.showQRInfo(scanResponse.data);
            } else {
                this.showError(scanResponse.message || 'QR Code inválido');
            }

        } catch (error) {
            console.error('❌ Erro ao processar QR:', error);
            this.showError('Erro ao processar QR Code');
        }

        // Reset após 3 segundos
        setTimeout(() => {
            this.lastScanResult = null;
        }, 3000);
    }

    async scanQRCode(qrData) {
        try {
            const response = await fetch('/api/qrcode/scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('tem_de_tudo_token')}`,
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    qr_code: qrData,
                    location: await this.getCurrentLocation()
                })
            });

            return await response.json();

        } catch (error) {
            console.error('❌ Erro na API de scan:', error);
            return { success: false, message: 'Erro de conexão' };
        }
    }

    async getCurrentLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                (error) => {
                    console.log('⚠️ Localização não disponível:', error);
                    resolve(null);
                },
                { timeout: 5000, enableHighAccuracy: true }
            );
        });
    }

    showQRInfo(qrInfo) {
        const { empresa, ofertas, pontos_base, multiplicador } = qrInfo;
        
        // Criar modal de confirmação
        const modal = document.createElement('div');
        modal.className = 'qr-info-modal';
        modal.innerHTML = `
            <div class="modal-backdrop">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>✅ QR Code Encontrado</h3>
                        <button class="modal-close" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="empresa-info">
                            <h4>${empresa.nome}</h4>
                            <p>${empresa.endereco || 'Estabelecimento parceiro'}</p>
                        </div>
                        
                        <div class="pontos-info">
                            <div class="pontos-base">
                                <strong>Pontos Base: ${pontos_base}</strong>
                            </div>
                            ${multiplicador > 1 ? `<div class="multiplicador">Multiplicador: ${multiplicador}x</div>` : ''}
                            <div class="pontos-total">
                                Total: <strong>${pontos_base * multiplicador} pontos</strong>
                            </div>
                        </div>
                        
                        ${ofertas && ofertas.length > 0 ? `
                            <div class="ofertas-ativas">
                                <h5>🎁 Ofertas Ativas:</h5>
                                ${ofertas.map(oferta => `
                                    <div class="oferta-item">
                                        <strong>${oferta.titulo}</strong>
                                        <p>${oferta.descricao}</p>
                                        ${oferta.bonus_pontos ? `<span class="bonus">+${oferta.bonus_pontos} pontos bônus</span>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="modal-footer">
                        <button class="btn btn-outline" onclick="this.parentElement.parentElement.parentElement.remove()">
                            Cancelar
                        </button>
                        <button class="btn btn-success" onclick="qrScanner.confirmCheckin('${qrInfo.qr_code}')">
                            Confirmar Check-in
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    async confirmCheckin(qrCode) {
        try {
            // Remover modal
            document.querySelector('.qr-info-modal')?.remove();
            
            // Mostrar loading
            this.updateUI('processing');
            
            const response = await fetch('/api/qrcode/checkin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('tem_de_tudo_token')}`,
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    qr_code: qrCode,
                    location: await this.getCurrentLocation()
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(result);
                // Atualizar dados do usuário
                if (window.updateUserData) {
                    updateUserData();
                }
            } else {
                this.showError(result.message || 'Erro no check-in');
            }

        } catch (error) {
            console.error('❌ Erro no check-in:', error);
            this.showError('Erro ao fazer check-in');
        } finally {
            this.updateUI('idle');
        }
    }

    showSuccess(result) {
        const { pontos_ganhos, total_pontos, bonus_aplicados, nivel } = result.data;
        
        // Mostrar animação de sucesso
        const successModal = document.createElement('div');
        successModal.className = 'success-modal';
        successModal.innerHTML = `
            <div class="modal-backdrop">
                <div class="modal-content success">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h3>🎉 Check-in Realizado!</h3>
                    
                    <div class="pontos-ganhos">
                        <div class="pontos-principal">
                            +${pontos_ganhos} pontos
                        </div>
                        
                        ${bonus_aplicados && bonus_aplicados.length > 0 ? `
                            <div class="bonus-aplicados">
                                ${bonus_aplicados.map(bonus => `
                                    <div class="bonus-item">🎁 ${bonus.descricao}: +${bonus.pontos}</div>
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        <div class="total-pontos">
                            Total: ${total_pontos} pontos
                        </div>
                        
                        ${nivel ? `<div class="nivel">Nível ${nivel}</div>` : ''}
                    </div>
                    
                    <button class="btn btn-primary" onclick="this.parentElement.parentElement.remove(); qrScanner.resetScanner();">
                        Continuar
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(successModal);

        // Auto-fechar após 5 segundos
        setTimeout(() => {
            successModal.remove();
            this.resetScanner();
        }, 5000);
    }

    showError(message) {
        console.error('❌', message);
        
        // Mostrar toast de erro
        if (window.showToast) {
            showToast(message, 'error');
        } else {
            alert(message);
        }
        
        this.updateUI('idle');
    }

    showManualInput() {
        const input = prompt('Digite o código QR manualmente:');
        if (input && input.trim()) {
            this.handleQRScan(input.trim());
        }
    }

    stopScan() {
        if (!this.isScanning) return;

        console.log('⏹️ Parando QR Scanner...');

        this.isScanning = false;

        // Parar stream da câmera
        if (this.video && this.video.srcObject) {
            const tracks = this.video.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            this.video.srcObject = null;
        }

        this.updateUI('idle');
    }

    resetScanner() {
        this.stopScan();
        this.lastScanResult = null;
        
        // Resetar UI
        setTimeout(() => {
            this.updateUI('idle');
        }, 500);
    }

    updateUI(state) {
        const elements = {
            scanBtn: document.getElementById('start-qr-scan'),
            stopBtn: document.getElementById('stop-qr-scan'),
            video: document.getElementById('qr-video'),
            canvas: document.getElementById('qr-canvas'),
            status: document.getElementById('qr-status'),
            container: document.getElementById('qr-scanner-container')
        };

        // Remover classes existentes
        if (elements.container) {
            elements.container.className = `qr-scanner-container state-${state}`;
        }

        switch (state) {
            case 'scanning':
                if (elements.scanBtn) elements.scanBtn.style.display = 'none';
                if (elements.stopBtn) elements.stopBtn.style.display = 'block';
                if (elements.video) elements.video.style.display = 'block';
                if (elements.canvas) elements.canvas.style.display = 'block';
                if (elements.status) elements.status.textContent = '📸 Aponte para o QR Code...';
                break;

            case 'processing':
                if (elements.status) elements.status.textContent = '⚡ Processando...';
                break;

            case 'idle':
            default:
                if (elements.scanBtn) elements.scanBtn.style.display = 'block';
                if (elements.stopBtn) elements.stopBtn.style.display = 'none';
                if (elements.video) elements.video.style.display = 'none';
                if (elements.canvas) elements.canvas.style.display = 'none';
                if (elements.status) elements.status.textContent = '📱 Toque para escanear QR Code';
                break;
        }
    }

    // Método público para testar QR
    testQR(qrData = 'TEST_QR_CODE_123') {
        console.log('🧪 Testando QR Scanner com:', qrData);
        this.handleQRScan(qrData);
    }
}

// Inicializar scanner quando a página carregar
let qrScanner;

document.addEventListener('DOMContentLoaded', function() {
    // Só inicializar se estivermos na página de check-in
    if (document.getElementById('qr-scanner-container')) {
        console.log('🔄 Inicializando QR Scanner...');
        qrScanner = new QRCodeScanner();
        window.qrScanner = qrScanner; // Para debug
    }
});

// CSS para os modais (adicionar ao head)
const qrStyles = document.createElement('style');
qrStyles.textContent = `
    .qr-info-modal, .success-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal-backdrop {
        background: rgba(0,0,0,0.8);
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        border-radius: 1rem;
        max-width: 400px;
        width: 100%;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--gray-500);
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--gray-200);
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .empresa-info h4 {
        margin-bottom: 0.5rem;
        color: var(--primary-purple);
    }

    .pontos-info {
        margin: 1rem 0;
        padding: 1rem;
        background: var(--gray-50);
        border-radius: 0.5rem;
    }

    .pontos-total {
        font-size: 1.25rem;
        color: var(--success-green);
        margin-top: 0.5rem;
    }

    .ofertas-ativas {
        margin-top: 1rem;
    }

    .oferta-item {
        padding: 0.75rem;
        background: var(--accent-orange);
        color: white;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .bonus {
        background: var(--success-green);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        margin-left: 0.5rem;
    }

    .success-modal .modal-content.success {
        text-align: center;
    }

    .success-icon i {
        font-size: 4rem;
        color: var(--success-green);
        margin-bottom: 1rem;
    }

    .pontos-ganhos {
        margin: 1.5rem 0;
    }

    .pontos-principal {
        font-size: 2rem;
        font-weight: 800;
        color: var(--success-green);
        margin-bottom: 1rem;
    }

    .bonus-aplicados {
        margin: 1rem 0;
    }

    .bonus-item {
        background: var(--accent-orange);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .total-pontos {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-900);
    }

    .qr-scanner-container {
        position: relative;
        text-align: center;
    }

    .qr-scanner-container video,
    .qr-scanner-container canvas {
        max-width: 100%;
        border-radius: 1rem;
        border: 3px solid var(--primary-purple);
    }

    .state-scanning {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
`;

document.head.appendChild(qrStyles);