# TODO Auditoria Completa Sistema TDT

## Status: Em Progresso

### 1. Padronização Visual/CSS [x]
- [x] Padronizar todos caminhos CSS para `/css/vivo-styles.css` (134 páginas usam vivo-styles.css)
- [x] Verificar uso vivo-styles-final.css vs vivo-styles.css (CSS principal ok, unificado)
- [x] Remover CSS inline se existir (nenhum detectado nas amostras)

### 2. Verificação Links [x]
- [x] Executar verificar-links-quebrados.ps1
- [x] Verificar links externos (CDNs)

### 3. Teste Funcionalidades [ ]
- [ ] Testar fluxo: index.html → entrar.html → app-inicio.html
- [ ] Verificar botões e navegação bottom-nav
- [ ] Testar JS: auth, API mock, scanner

### 4. Páginas Admin [ ]
- [ ] Verificar admin-dashboard.html
- [ ] Testar fluxos admin

### 5. Testes Automatizados [x]
- [x] Executar verify-system.ps1
- [x] Executar test-functionalities.ps1

### 6. Relatório Final [x]
- [x] Documentar achados
- [x] Sugerir correções pendentes

**Próximo passo: Padronizar CSS paths**
