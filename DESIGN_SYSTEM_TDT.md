# 🎨 TEM DE TUDO - Design System

## 🎯 Identidade Visual

### Baseado no i9plus com branding próprio

**Nomenclatura:** `.tdt-` (Tem De Tudo)  
**Paleta:** Roxo i9plus (#667eea → #764ba2)  
**Tema:** Escuro moderno (#0a0a0f)  
**PWA:** Mobile-first otimizado  

---

## 🎨 Cores

```css
/* Roxo Primário (i9plus) */
--primary-start: #667eea;
--primary-end: #764ba2;

/* Tema Escuro (Tem de Tudo) */
--background: #0a0a0f;
--card-bg: #1a1a2e;
--card-bg-alt: #16213e;

/* Status */
--success: #26de81;
--warning: #fed330;
--error: #fc5c65;
--info: #45aaf2;
```

---

## 📦 Componentes Disponíveis

### Botões
```html
<button class="tdt-btn tdt-btn-primary">Primário</button>
<button class="tdt-btn tdt-btn-secondary">Secundário</button>
<button class="tdt-btn tdt-btn-outline">Outline</button>
<button class="tdt-btn tdt-btn-success">Sucesso</button>
<button class="tdt-btn tdt-btn-warning">Aviso</button>
<button class="tdt-btn tdt-btn-error">Erro</button>

<!-- Tamanhos -->
<button class="tdt-btn tdt-btn-primary tdt-btn-sm">Pequeno</button>
<button class="tdt-btn tdt-btn-primary">Normal</button>
<button class="tdt-btn tdt-btn-primary tdt-btn-lg">Grande</button>

<!-- Ícone -->
<button class="tdt-btn tdt-btn-icon"><i class="fas fa-heart"></i></button>
```

### Cards
```html
<div class="tdt-card">
    <div class="tdt-card-header">
        <h3 class="tdt-card-title">Título</h3>
    </div>
    <div class="tdt-card-body">
        Conteúdo do card
    </div>
</div>
```

### Inputs
```html
<div class="tdt-input-group">
    <label class="tdt-label">Nome</label>
    <input type="text" class="tdt-input" placeholder="Digite seu nome">
</div>
```

### Navbar
```html
<header class="tdt-header">
    <nav class="tdt-navbar">
        <a href="/" class="tdt-logo">Tem de Tudo</a>
        <ul class="tdt-nav-links">
            <li><a href="#" class="tdt-nav-link active">Início</a></li>
            <li><a href="#" class="tdt-nav-link">Planos</a></li>
            <li><a href="#" class="tdt-nav-link">Contato</a></li>
        </ul>
    </nav>
</header>
```

### Grid
```html
<div class="tdt-grid tdt-grid-2">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<div class="tdt-grid tdt-grid-3">
    <div>Item 1</div>
    <div>Item 2</div>
    <div>Item 3</div>
</div>
```

### Badges
```html
<span class="tdt-badge">Novo</span>
<span class="tdt-badge tdt-badge-success">Ativo</span>
<span class="tdt-badge tdt-badge-warning">Pendente</span>
<span class="tdt-badge tdt-badge-error">Inativo</span>
```

### Alerts
```html
<div class="tdt-alert tdt-alert-success">
    <i class="fas fa-check-circle"></i>
    Operação realizada com sucesso!
</div>

<div class="tdt-alert tdt-alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    Atenção: Verifique os dados
</div>

<div class="tdt-alert tdt-alert-error">
    <i class="fas fa-times-circle"></i>
    Erro ao processar solicitação
</div>
```

### Avatar
```html
<div class="tdt-avatar">JD</div>
<div class="tdt-avatar tdt-avatar-sm">P</div>
<div class="tdt-avatar tdt-avatar-lg">M</div>
<div class="tdt-avatar tdt-avatar-xl">G</div>
```

### Progress Bar
```html
<div class="tdt-progress">
    <div class="tdt-progress-bar" style="width: 75%"></div>
</div>

<div class="tdt-progress tdt-progress-lg">
    <div class="tdt-progress-bar" style="width: 50%"></div>
</div>
```

### Modal
```html
<div class="tdt-modal active">
    <div class="tdt-modal-content">
        <button class="tdt-modal-close">&times;</button>
        <div class="tdt-modal-header">
            <h2 class="tdt-modal-title">Título do Modal</h2>
        </div>
        <div class="tdt-modal-body">
            Conteúdo do modal
        </div>
    </div>
</div>
```

### Tabs
```html
<div class="tdt-tabs">
    <button class="tdt-tab active">Tab 1</button>
    <button class="tdt-tab">Tab 2</button>
    <button class="tdt-tab">Tab 3</button>
</div>
```

### Loading
```html
<div class="tdt-loading">
    <div class="tdt-spinner"></div>
    Carregando...
</div>
```

### Empty State
```html
<div class="tdt-empty">
    <div class="tdt-empty-icon">
        <i class="fas fa-inbox"></i>
    </div>
    <h3 class="tdt-empty-title">Nenhum item encontrado</h3>
    <p class="tdt-empty-text">Adicione seu primeiro item para começar</p>
    <button class="tdt-btn tdt-btn-primary">Adicionar Item</button>
</div>
```

### Stat Card
```html
<div class="tdt-stat-card">
    <div class="tdt-stat-icon">
        <i class="fas fa-users"></i>
    </div>
    <div class="tdt-stat-label">Total de Clientes</div>
    <div class="tdt-stat-value">1,234</div>
</div>
```

### FAB (Floating Action Button)
```html
<button class="tdt-fab">
    <i class="fas fa-plus"></i>
</button>
```

---

## 🎨 Utilities

### Flexbox
```html
<div class="tdt-flex">Flex container</div>
<div class="tdt-flex tdt-flex-center">Centralizado</div>
<div class="tdt-flex tdt-flex-between">Space between</div>
<div class="tdt-flex tdt-flex-column">Coluna</div>
```

### Gaps
```html
<div class="tdt-flex tdt-gap-sm">Gap pequeno</div>
<div class="tdt-flex tdt-gap-md">Gap médio</div>
<div class="tdt-flex tdt-gap-lg">Gap grande</div>
```

### Spacing
```html
<div class="tdt-mt-lg">Margin top</div>
<div class="tdt-mb-md">Margin bottom</div>
<div class="tdt-p-lg">Padding</div>
```

### Shadows
```html
<div class="tdt-shadow-sm">Sombra pequena</div>
<div class="tdt-shadow-md">Sombra média</div>
<div class="tdt-shadow-lg">Sombra grande</div>
```

### Rounded
```html
<div class="tdt-rounded-sm">8px</div>
<div class="tdt-rounded-md">12px</div>
<div class="tdt-rounded-lg">16px</div>
<div class="tdt-rounded-full">9999px</div>
```

### Text Align
```html
<div class="tdt-text-center">Centralizado</div>
<div class="tdt-text-left">Esquerda</div>
<div class="tdt-text-right">Direita</div>
```

---

## 🎬 Animações

```html
<div class="tdt-animate-fadeIn">Fade in</div>
<div class="tdt-animate-slideIn">Slide in</div>
<div class="tdt-animate-pulse">Pulse</div>
```

---

## 📱 PWA Mobile-First

### Características
- ✅ Safe areas (notch/dynamic island)
- ✅ Touch optimization
- ✅ Service Worker
- ✅ Offline-first
- ✅ App-like experience
- ✅ No zoom on double tap
- ✅ Smooth scroll
- ✅ Native feel

### Uso
```html
<!-- Já aplicado automaticamente em mobile-native.css -->
<link rel="stylesheet" href="/css/temdetudo-theme.css">
<link rel="stylesheet" href="/css/mobile-native.css">
```

---

## 🎯 Boas Práticas

1. **Use classes .tdt-** em vez de estilos inline
2. **Componentes reutilizáveis** para consistência
3. **Mobile-first** sempre
4. **Tema escuro** como padrão
5. **Gradiente roxo** para destaque
6. **Animações suaves** para feedback

---

## 📚 Arquivos do Sistema

```
backend/public/css/
├── temdetudo-theme.css    # Design system completo (.tdt- classes)
└── mobile-native.css      # Otimizações PWA mobile
```

**Total:** 50+ componentes prontos  
**Paleta:** i9plus (roxo) + Tem de Tudo (escuro)  
**Classes:** Prefixo .tdt- (Tem De Tudo)  
