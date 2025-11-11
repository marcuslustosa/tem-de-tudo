/**
 * TEM DE TUDO - Funcoes Globais
 * Funcoes utilitarias usadas em multiplas paginas
 */

// Toggle Mobile Menu
function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    if (mobileNav) {
        mobileNav.classList.toggle('active');
    }
}

// Set Filter - Estabelecimentos
let currentFilter = 'all';
function setFilter(filter) {
    currentFilter = filter;
    
    // Update active state on buttons
    const buttons = document.querySelectorAll('.filter-chip[data-filter]');
    buttons.forEach(btn => {
        if (btn.getAttribute('data-filter') === filter) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Filter establishments
    const establishments = document.querySelectorAll('.establishment-card');
    establishments.forEach(card => {
        if (filter === 'all') {
            card.style.display = 'block';
        } else {
            const category = card.getAttribute('data-category');
            card.style.display = category === filter ? 'block' : 'none';
        }
    });
}

// Set FAQ Filter
let currentFAQFilter = 'all';
function setFAQFilter(filter) {
    currentFAQFilter = filter;
    
    // Update active state on buttons
    const buttons = document.querySelectorAll('.filter-chip[data-filter]');
    buttons.forEach(btn => {
        if (btn.getAttribute('data-filter') === filter) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Filter FAQ items
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        if (filter === 'all') {
            item.style.display = 'block';
        } else {
            const category = item.getAttribute('data-category');
            item.style.display = category === filter ? 'block' : 'none';
        }
    });
}

// Search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.establishment-card, .faq-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matchesSearch = text.includes(searchTerm);
            const matchesFilter = currentFilter === 'all' || 
                                 item.getAttribute('data-category') === currentFilter;
            
            item.style.display = (matchesSearch && matchesFilter) ? 'block' : 'none';
        });
    });
}

// Toast Notification
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 12px;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
    `;
    
    // Icon based on type
    const icons = {
        success: '<i class="fas fa-check-circle" style="color: #10b981; font-size: 1.25rem;"></i>',
        error: '<i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 1.25rem;"></i>',
        warning: '<i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 1.25rem;"></i>',
        info: '<i class="fas fa-info-circle" style="color: #3b82f6; font-size: 1.25rem;"></i>'
    };
    
    toast.innerHTML = `
        ${icons[type] || icons.info}
        <span style="font-weight: 500; color: #1f2937;">${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: #6b7280; font-size: 1.25rem; margin-left: auto;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Loading State
function setLoading(element, isLoading) {
    if (isLoading) {
        element.classList.add('loading');
        element.disabled = true;
    } else {
        element.classList.remove('loading');
        element.disabled = false;
    }
}

// Format Currency (BRL)
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Format Date (PT-BR)
function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(new Date(date));
}

// Format DateTime (PT-BR)
function formatDateTime(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Copy to Clipboard
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copiado para a area de transferencia!', 'success');
        }).catch(() => {
            showToast('Erro ao copiar', 'error');
        });
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showToast('Copiado para a area de transferencia!', 'success');
        } catch (err) {
            showToast('Erro ao copiar', 'error');
        }
        document.body.removeChild(textarea);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupSearch();
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        const mobileNav = document.getElementById('mobileNav');
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        
        if (mobileNav && mobileNav.classList.contains('active')) {
            if (!mobileNav.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                mobileNav.classList.remove('active');
            }
        }
    });
});

// Add CSS animations
const globalStyle = document.createElement('style');
globalStyle.textContent = `
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
document.head.appendChild(globalStyle);
