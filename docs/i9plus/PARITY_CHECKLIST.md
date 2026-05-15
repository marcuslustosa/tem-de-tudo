# Parity Checklist i9Plus

Referencia usada: `docs/i9plus/ui-screens.json`, `design-tokens.json`, `i9plus-theme.css`, `VISUAL_AUDIT.md` e briefing funcional ja consolidado. A pasta `references/i9plus` nao contem assets reais, entao a comparacao abaixo usa a referencia documental.

## Cliente

| Tela | Referencia | Visual | Funcional | Ajustes necessarios | Arquivos |
|---|---|---|---|---|---|
| 1. Login | customer_login | proximo | igual | apenas QA final em celular | `backend/public/entrar.html`, `backend/public/css/i9plus-phase8.css` |
| 2. Cadastro cliente | customer_register | proximo | igual | confirmar ergonomia mobile dos campos longos | `backend/public/criar_conta.html`, `backend/public/css/i9plus-phase8.css` |
| 3. Home do cliente | customer_home | proximo | proximo | validar microcopy e empty state no celular | `backend/public/meus_pontos.html`, `backend/public/js/stitch-app.js`, `backend/public/css/i9plus-phase8.css` |
| 4. Ler QR Code | customer_scan | proximo | proximo | conferir permissao de camera em Android/iOS | `backend/public/validar_resgate.html` |
| 5. Meu QR Code | customer_my_qr | proximo | igual | conferir legibilidade do QR em celulares menores | `backend/public/meus_pontos.html`, `backend/public/js/stitch-app.js` |
| 6. Lista de empresas vinculadas | customer_linked_companies | proximo | igual | revisar densidade visual com base maior | `backend/public/meus_pontos.html`, `backend/public/js/stitch-app.js` |
| 7. Empresas em destaque | customer_featured_companies | proximo | proximo | depende de base seed populada | `backend/public/meus_pontos.html`, `backend/public/js/stitch-app.js` |
| 8. Busca/categorias | customer_search_categories | proximo | proximo | validar categorias sem resultados e busca por codigo | `backend/public/parceiros_tem_de_tudo.html`, `backend/public/js/stitch-app.js`, `backend/public/css/i9plus-phase8.css` |
| 9. Pagina da empresa | company_public_page | proximo | proximo | revisar fallback de contatos sem dado | `backend/public/detalhe_do_parceiro.html`, `backend/public/js/stitch-app.js` |
| 10. Bonus de adesao | customer_bonus_adesao | igual | igual | sem ajuste funcional pendente | `backend/public/detalhe_do_parceiro.html`, `backend/public/js/stitch-app.js` |
| 11. Cartao fidelidade | customer_loyalty | proximo | igual | validar cenarios 0/5/14/16 pontos na seed | `backend/public/detalhe_do_parceiro.html`, `backend/database/seeders/I9PlusDemoSeeder.php` |
| 12. Promocoes | customer_promotions | proximo | igual | revisar banner/fallback em rede lenta | `backend/public/detalhe_do_parceiro.html`, `backend/public/js/stitch-app.js` |
| 13. Bonus aniversario | customer_birthday_bonus | proximo | igual | depende de seed atual do mes | `backend/public/detalhe_do_parceiro.html`, `backend/database/seeders/I9PlusDemoSeeder.php` |
| 14. Avaliacoes | customer_reviews | igual | igual | sem ajuste funcional pendente | `backend/public/detalhe_do_parceiro.html`, `backend/public/js/stitch-app.js` |
| 15. Perfil do cliente | customer_profile | parcial | proximo | acabamento visual ainda mais simples que a referencia | `backend/public/meu_perfil.html`, `backend/public/css/i9plus-phase8.css` |

## Empresa

| Tela | Referencia | Visual | Funcional | Ajustes necessarios | Arquivos |
|---|---|---|---|---|---|
| 16. Login empresa | company_login | proximo | igual | QA de redirecionamento pos-login | `backend/public/entrar.html` |
| 17. Dashboard empresa | company_dashboard | proximo | proximo | validar leitura do QR e cards com dados seed | `backend/public/dashboard_parceiro.html`, `backend/public/js/stitch-app.js`, `backend/public/css/i9plus-phase8.css` |
| 18. QR Code da empresa | company_qr | proximo | igual | confirmar qualidade visual do SVG local | `backend/public/dashboard_parceiro.html`, `backend/public/js/stitch-app.js`, `backend/app/Services/QRCodeService.php` |
| 19. Scanner QR do cliente | company_scan_customer | proximo | proximo | conferir camera real e fallback manual | `backend/public/validar_resgate.html`, `backend/public/js/stitch-app.js` |
| 20. Validacao de bonus | company_validate_bonus | igual | igual | sem ajuste funcional pendente | `backend/public/validar_resgate.html`, `backend/public/js/stitch-app.js` |
| 21. Adicionar ponto | company_add_point | igual | igual | sem ajuste funcional pendente | `backend/public/validar_resgate.html`, `backend/public/js/stitch-app.js` |
| 22. Resgatar recompensa | company_redeem_reward | igual | igual | sem ajuste funcional pendente | `backend/public/validar_resgate.html`, `backend/public/js/stitch-app.js` |
| 23. Validar promocao | company_validate_promotion | igual | igual | sem ajuste funcional pendente | `backend/public/validar_resgate.html`, `backend/public/js/stitch-app.js` |
| 24. Validar bonus aniversario | company_validate_birthday | igual | igual | sem ajuste funcional pendente | `backend/public/validar_resgate.html`, `backend/public/js/stitch-app.js` |
| 25. Clientes cadastrados | company_customers | proximo | igual | revisar listagem em base muito grande | `backend/public/clientes_fidelizados_loja.html`, `backend/public/js/stitch-app.js` |
| 26. Cadastro/edicao de promocoes | company_promotions_crud | proximo | igual | revisar preview do card em telas pequenas | `backend/public/gest_o_de_ofertas_parceiro.html`, `backend/public/js/stitch-app.js` |
| 27. Cadastro/edicao de bonus | company_bonus_crud | proximo | igual | QA de textos e toggles | `backend/public/gest_o_de_ofertas_parceiro.html`, `backend/public/js/stitch-app.js` |
| 28. Cadastro/edicao de fidelidade | company_loyalty_crud | proximo | igual | conferir preview e estados vazios | `backend/public/gest_o_de_ofertas_parceiro.html`, `backend/public/js/stitch-app.js` |
| 29. Cadastro/edicao de lembrete | company_reminder_crud | proximo | igual | revisar fluxo de envio elegivel | `backend/public/gest_o_de_ofertas_parceiro.html`, `backend/public/js/stitch-app.js` |
| 30. Relatorios da empresa | company_reports | proximo | igual | bug do resumo operacional corrigido; falta QA em staging | `backend/app/Services/RelatorioOperacionalService.php`, `backend/public/dashboard_parceiro.html`, `backend/public/js/stitch-app.js` |
| 31. Configuracoes da empresa | company_settings | parcial | parcial | ainda distribuido em tela de ofertas/gestao, sem tela dedicada | `backend/public/dashboard_parceiro.html`, `backend/public/gest_o_de_ofertas_parceiro.html` |

## Admin

| Tela | Referencia | Visual | Funcional | Ajustes necessarios | Arquivos |
|---|---|---|---|---|---|
| 32. Login admin | admin_login | proximo | igual | QA de login em ambiente limpo | `backend/public/entrar.html` |
| 33. Dashboard admin | admin_dashboard | proximo | proximo | validar cards e rankings em base demo | `backend/public/dashboard_admin_master.html`, `backend/public/js/stitch-app.js`, `backend/public/css/i9plus-phase8.css` |
| 34. Aprovacao de empresas | admin_approval | igual | igual | sem ajuste funcional pendente | `backend/public/gest_o_de_estabelecimentos.html`, `backend/public/js/stitch-app.js` |
| 35. Empresas pendentes/ativas/suspensas | admin_company_status | igual | igual | sem ajuste funcional pendente | `backend/public/gest_o_de_estabelecimentos.html`, `backend/public/js/stitch-app.js` |
| 36. Relatorios gerais | admin_reports | proximo | igual | revisar leitura em desktop ultrawide | `backend/public/relat_rios_gerais_master.html`, `backend/public/js/stitch-app.js` |

## PWA

| Tela | Referencia | Visual | Funcional | Ajustes necessarios | Arquivos |
|---|---|---|---|---|---|
| 37. Manifest | pwa_manifest | proximo | proximo | manter assets internos sincronizados | `backend/public/manifest.json` |
| 38. Tela instalada/mobile | pwa_installed_shell | proximo | proximo | QA em instalacao real Android/iOS | `backend/public/css/i9plus-phase8.css`, HTMLs mobile |
| 39. Service worker/push | pwa_push | parcial | proximo | push real ainda depende de `VAPID_*` e subscription do navegador | `backend/public/sw-push.js`, backend canonicamente ja existente |
