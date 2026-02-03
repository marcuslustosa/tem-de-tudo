/**
 * UI HELPERS - TEM DE TUDO
 * Funções auxiliares para interface do usuário
 * 
 * @version 2.0.0
 * @author Tem de Tudo Team
 */

// ================================
// TOAST NOTIFICATIONS
// ================================
function showToast(message, type = 'info', duration = 5000) {
    // Remover toast existente
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }

    // Criar toast
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    
    // Estilos
    const styles = {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '16px 24px',
        background: 'white',
        borderRadius: '12px',
        boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
        zIndex: '10000',
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        maxWidth: '400px',
        animation: 'slideInRight 0.3s ease',
        fontFamily: 'system-ui, -apple-system, sans-serif'
    };

    Object.assign(toast.style, styles);

    // Ícones por tipo
    const icons = {
        success: '<i class="fas fa-check-circle" style="color: #10b981; font-size: 1.25rem;"></i>',
        error: '<i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 1.25rem;"></i>',
        warning: '<i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 1.25rem;"></i>',
        info: '<i class="fas fa-info-circle" style="color: #3b82f6; font-size: 1.25rem;"></i>'
    };

    // Conteúdo do toast
    toast.innerHTML = `
        ${icons[type] || icons.info}
        <span style="font-weight: 500; color: #1f2937;">${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: #6b7280; font-size: 1.25rem; margin-left: auto;">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Adicionar CSS de animação
    if (!document.querySelector('#toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);

    // Auto remover
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, duration);
}

// ================================
// LOADING STATE
// ================================
function setLoading(element, isLoading, originalText = null) {
    if (!element) return;

    if (isLoading) {
        // Salvar texto original se não fornecido
        if (!originalText && element.textContent) {
            element.dataset.originalText = element.textContent;
        }
        
        element.disabled = true;
        element.style.opacity = '0.7';
        element.style.cursor = 'not-allowed';
        
        // Adicionar spinner se for botão
        if (element.tagName === 'BUTTON') {
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
        }
    } else {
        element.disabled = false;
        element.style.opacity = '1';
        element.style.cursor = 'pointer';
        
        // Restaurar texto original
        if (originalText) {
            element.textContent = originalText;
        } else if (element.dataset.originalText) {
            element.textContent = element.dataset.originalText;
            delete element.dataset.originalText;
        }
    }
}

// ================================
// MOSTRAR/OCULTAR ELEMENTO
// ================================
function show(elementId) {
    const element = typeof elementId === 'string' 
        ? document.getElementById(elementId) 
        : elementId;
    
    if (element) {
        element.style.display = 'block';
    }
}

function hide(elementId) {
    const element = typeof elementId === 'string' 
        ? document.getElementById(elementId) 
        : elementId;
    
    if (element) {
        element.style.display = 'none';
    }
}

// ================================
// FORMATAR VALORES
// ================================
const Formatters = {
    /**
     * Formatar moeda brasileira
     */
    currency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    /**
     * Formatar número
     */
    number(value) {
        return new Intl.NumberFormat('pt-BR').format(value);
    },

    /**
     * Formatar data
     */
    date(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR').format(date);
    },

    /**
     * Formatar data e hora
     */
    datetime(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short'
        }).format(date);
    },

    /**
     * Formatar data relativa (ex: "há 2 dias")
     */
    relativeDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'agora mesmo';
        if (diffInSeconds < 3600) return `há ${Math.floor(diffInSeconds / 60)} minutos`;
        if (diffInSeconds < 86400) return `há ${Math.floor(diffInSeconds / 3600)} horas`;
        if (diffInSeconds < 604800) return `há ${Math.floor(diffInSeconds / 86400)} dias`;
        
        return this.date(dateString);
    }
};

// ================================
// MODAL HELPER
// ================================
function showConfirm(message, onConfirm, onCancel = null) {
    const result = confirm(message);
    if (result && onConfirm) {
        onConfirm();
    } else if (!result && onCancel) {
        onCancel();
    }
    return result;
}

// ================================
// DEBOUNCE
// ================================
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ================================
// COPIAR PARA CLIPBOARD
// ================================
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copiado para área de transferência!', 'success');
        return true;
    } catch (error) {
        console.error('Erro ao copiar:', error);
        showToast('Erro ao copiar', 'error');
        return false;
    }
}

// Expor globalmente
window.showToast = showToast;
window.setLoading = setLoading;
window.show = show;
window.hide = hide;
window.Formatters = Formatters;
window.showConfirm = showConfirm;
window.debounce = debounce;
window.copyToClipboard = copyToClipboard;

console.log('🎨 UI Helpers carregado');
