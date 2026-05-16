# Final i9Plus Parity Audit

## Fonte obrigatória

- `docs/i9plus/I9PLUS_REFERENCE_SPEC.md`
- `docs/i9plus/DEMO_GUIDE.md`
- `backend/database/seeders/I9PlusDemoSeeder.php`

## Escopo P0 consolidado

### 1. `backend/public/index.html`

- Status atual:
  - ruim
- Decisão:
  - reestruturar
- Motivo:
  - a entrada pública ainda vendia o Tem de Tudo antigo com cara de landing genérica e pouca clareza sobre QR Code, fidelidade, campanhas e validação presencial
- O que já estava bom:
  - brand Tem de Tudo e acesso para entrar/criar conta
- O que estava errado:
  - proposta de valor pouco aderente ao modelo i9Plus
  - pouca ênfase em QR Code e fidelidade
  - hierarquia visual distante de app mobile de aquisição
- Ação aplicada:
  - hero novo mobile-first com gradiente, QR Code como conceito central, CTAs `Entrar`, `Criar conta` e `Cadastrar estabelecimento`
  - cards explicando os fluxos cliente/empresa/admin no vocabulário correto
- Status depois:
  - igual

### 2. `backend/public/criar_conta.html`

- Status atual:
  - parcial
- Decisão:
  - corrigir forte
- Motivo:
  - a tela precisava deixar mais claro o split entre cliente e estabelecimento e comunicar aprovação pendente da empresa
- O que já estava bom:
  - estrutura do formulário, alternância de perfil e campos de empresa
- O que estava errado:
  - microcopy fraca para o fluxo real de empresa
  - mensagens e labels com acentuação inconsistente
- Ação aplicada:
  - texto reforçando `Tipo de conta`
  - mensagem explícita para empresa pendente após pagamento
  - revisão de labels, placeholders e instruções de nascimento/benefícios
- Status depois:
  - igual

### 3. `backend/public/entrar.html`

- Status atual:
  - próximo
- Decisão:
  - ajustar levemente
- Motivo:
  - layout já estava bom, mas o texto ainda não comunicava corretamente QR, campanhas e validação presencial
- O que já estava bom:
  - shell visual, campos, estados de erro e CTA principal
- O que estava errado:
  - mensagem de acesso genérica
  - fallback de erro com linguagem menos comercial
- Ação aplicada:
  - subtítulo reescrito para QR, benefícios e campanhas presenciais
  - fallback de login alinhado em português comercial
- Status depois:
  - igual

### 4. `backend/public/meus_pontos.html`

- Status atual:
  - igual
- Decisão:
  - manter
- Motivo:
  - a home cliente já tinha a estrutura principal correta e só precisava revisão fina de texto
- O que já estava bom:
  - header gradiente
  - saudação
  - ações `Ler QR Code` e `Meu QR Code`
  - lista de empresas e bottom nav app-like
- O que estava errado:
  - alguns rótulos e estados vazios ainda estavam menos naturais
- Ação aplicada:
  - revisão leve de linguagem, estados vazios e nomenclatura de navegação
- Status depois:
  - igual

### 5. `backend/public/parceiros_tem_de_tudo.html`

- Status atual:
  - igual
- Decisão:
  - manter
- Motivo:
  - a tela já estava aderente ao modelo de busca/categorias e exigia só consistência textual
- O que já estava bom:
  - título de busca
  - helper de 4 caracteres
  - grid de categorias
  - CTA de cadastro para cliente não vinculado
- O que estava errado:
  - pequenos rótulos e consistência geral do português
- Ação aplicada:
  - preservação do layout e revisão de copy operacional
- Status depois:
  - igual

### 6. `backend/public/detalhe_do_parceiro.html`

- Status atual:
  - igual
- Decisão:
  - manter
- Motivo:
  - a tela crítica da empresa já havia sido alinhada com a especificação e só precisava permanecer íntegra
- O que já estava bom:
  - hero dark
  - bônus de adesão promocional
  - fidelidade no padrão i9Plus
  - promoções, aniversário, contatos e avaliações
- O que estava errado:
  - apenas pequenos textos residuais e consistência de rótulos
- Ação aplicada:
  - preservação da estrutura
  - revisão de textos e placeholders para manter leitura comercial consistente
- Status depois:
  - igual

### 7. `backend/public/validar_resgate.html`

- Status atual:
  - igual
- Decisão:
  - ajustar levemente
- Motivo:
  - a estrutura operacional estava correta, mas havia rótulos e placeholders sem acento e pequenos detalhes de leitura
- O que já estava bom:
  - scanner
  - modo manual
  - painel pós-leitura com bônus, fidelidade, promoções e aniversário
- O que estava errado:
  - `Operação`, `Vínculo`, `Início` e helper inicial ainda inconsistentes
- Ação aplicada:
  - correção de microcopy da tela, mantendo IDs, regras e fluxo operacional
- Status depois:
  - igual

### 8. `backend/public/dashboard_parceiro.html`

- Status atual:
  - próximo
- Decisão:
  - ajustar levemente
- Motivo:
  - a estrutura já estava boa, mas parte do texto ainda quebrava a percepção premium do painel
- O que já estava bom:
  - QR da empresa em destaque
  - CTA para ler QR do cliente
  - cards de ferramentas
  - resumo, clientes e movimentação
- O que estava errado:
  - acentuação quebrada em pontos importantes
  - labels de atalhos, métricas e CTA público menos polidos
- Ação aplicada:
  - correção de textos-chave, acentuação, CTA de página pública e rótulos de ferramentas/métricas
- Status depois:
  - igual

### 9. `backend/public/gest_o_de_ofertas_parceiro.html`

- Status atual:
  - parcial
- Decisão:
  - corrigir forte
- Motivo:
  - a página ainda tinha traços claros do legado Tem de Tudo antigo, inclusive encoding ruim e textos pobres em relação ao fluxo real
- O que já estava bom:
  - estrutura funcional dos formulários
  - previews e seções principais
- O que estava errado:
  - vários textos sem acento
  - leitura fraca de campanha/fidelidade/aniversário
  - topo e tool cards menos polidos
- Ação aplicada:
  - revisão dos rótulos das ferramentas
  - correção de promoções, bônus de adesão, fidelidade e aniversário
  - ajuste de instruções de validação presencial e labels de preço/validade
- Status depois:
  - igual

### 10. `backend/public/gest_o_de_estabelecimentos.html`

- Status atual:
  - próximo
- Decisão:
  - ajustar levemente
- Motivo:
  - o painel admin já era funcional e visualmente bom, mas precisava consistência de navegação e linguagem
- O que já estava bom:
  - cards de status
  - tabela/lista de empresas
  - ações de aprovação/suspensão
- O que estava errado:
  - alguns labels e navegação ainda vinham do legado
- Ação aplicada:
  - manutenção da estrutura
  - correção de labels, navegação mobile e textos dos status
- Status depois:
  - igual

### 11. `backend/public/dashboard_admin_master.html`

- Status atual:
  - próximo
- Decisão:
  - ajustar levemente
- Motivo:
  - o painel já estava bom como painel desktop, mas tinha rótulos e textos ainda pouco refinados
- O que já estava bom:
  - shell desktop
  - KPIs
  - visão geral da plataforma
- O que estava errado:
  - alguns textos operacionais, labels de navegação e linguagem da plataforma
- Ação aplicada:
  - revisão de rótulos de menu, métricas, cabeçalhos e CTA internos
- Status depois:
  - igual

### 12. `backend/public/meu_perfil.html`

- Status atual:
  - próximo
- Decisão:
  - corrigir forte
- Motivo:
  - o perfil tinha boa estrutura, mas ainda carregava acentuação quebrada em textos importantes da jornada do cliente
- O que já estava bom:
  - hero app-like
  - card de dados
  - QR host
  - empresas vinculadas
  - ações e logout
- O que estava errado:
  - vários textos com mojibake
  - labels de benefícios, configurações e relacionamento comprometiam a sensação de produto pronto
- Ação aplicada:
  - limpeza completa dos textos do hero, empresas vinculadas, dados e ações
  - preservação integral dos IDs e do fluxo dinâmico
- Status depois:
  - igual

## Resumo da rodada

- P0 preservado:
  - `meus_pontos.html`
  - `parceiros_tem_de_tudo.html`
  - `detalhe_do_parceiro.html`

- P0 ajustado levemente:
  - `entrar.html`
  - `validar_resgate.html`
  - `dashboard_parceiro.html`
  - `gest_o_de_estabelecimentos.html`
  - `dashboard_admin_master.html`

- P0 corrigido forte:
  - `criar_conta.html`
  - `gest_o_de_ofertas_parceiro.html`
  - `meu_perfil.html`

- P0 reestruturado:
  - `index.html`

## Rodada de unificação visual global

| Tela | Status atual | Decisão | Motivo | Ação aplicada |
| --- | --- | --- | --- | --- |
| `index.html` | igual | ajustar levemente | a copy já estava correta, mas a identidade ainda parecia mais landing do que app | unificação de fonte, botões, cards e hero com o mesmo design system do restante do produto |
| `criar_conta.html` | igual | ajustar levemente | a jornada estava correta, mas precisava parecer a mesma família visual da home e do pós-login | hero de entrada, card de formulário, CTA e copy de fluxo cliente/empresa unificados |
| `entrar.html` | igual | ajustar levemente | tela funcional, mas menos conectada à narrativa de QR Code e validação presencial | hero, CTA, font stack e microcopy alinhados ao shell do app |
| `meus_pontos.html` | igual | manter | estrutura da home cliente já estava boa | preservação do layout, com consolidação de fonte e correção fina de texto |
| `parceiros_tem_de_tudo.html` | igual | manter | já estava coerente com busca/categorias no modelo i9Plus | apenas herança do CSS unificado e versionamento consistente |
| `detalhe_do_parceiro.html` | igual | manter | tela crítica já estava forte e aderente | limpeza residual de labels e alinhamento à mesma família visual |
| `validar_resgate.html` | igual | manter | fluxo operacional já estava correto | manutenção da estrutura com limpeza de alertas, estados e acentuação |
| `dashboard_parceiro.html` | igual | ajustar levemente | painel já bom, mas com rótulos menos polidos | alinhamento de copy e reforço da leitura de operação presencial |
| `gest_o_de_ofertas_parceiro.html` | igual | manter | já estava consistente em seções e ferramentas | herança do design system central, sem refazer a tela |
| `gest_o_de_estabelecimentos.html` | igual | ajustar levemente | painel admin bom, com labels legadas em ASCII | correção de acentos e preservação da estrutura |
| `dashboard_admin_master.html` | igual | ajustar levemente | painel bom, mas ainda com CTA e rótulos antigos | padronização de linguagem e consistência visual |
| `meu_perfil.html` | igual | ajustar levemente | estrutura boa, mas com textos ainda quebrados | correção de acentuação e reforço da mesma linguagem do app |
