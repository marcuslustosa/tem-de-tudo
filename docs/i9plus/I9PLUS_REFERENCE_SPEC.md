# i9Plus — Especificação de Referência Visual e Funcional

## Objetivo

Esta especificação descreve a experiência visual e funcional esperada para o PWA de fidelização inspirado no i9Plus.

O objetivo não é criar uma interface genérica, nem um dashboard SaaS tradicional. O objetivo é reproduzir a experiência de um app mobile de fidelização com QR Code, benefícios, promoções, fidelidade e validação presencial pelo estabelecimento.

---

# 1. Princípios gerais

## 1.1 Mobile-first

A experiência principal é mobile.

O cliente deve sentir que está usando um aplicativo instalado, não um site responsivo comum.

O visual deve ter:

- cards brancos;
- cantos arredondados;
- botões grandes;
- botões pill;
- gradiente azul/verde/magenta;
- navegação inferior;
- ícones simples;
- QR Code como ação central;
- telas com aparência de app.

## 1.2 Desktop

No desktop, empresa e admin podem parecer painel web.

O desktop deve ter:

- cards em grid;
- tabelas legíveis;
- filtros simples;
- boa largura útil;
- aparência de painel;
- não deve parecer um celular gigante esticado.

## 1.3 Regra principal de funcionamento

O fluxo central é:

1. empresa possui QR Code próprio;
2. cliente escaneia QR Code da empresa;
3. cliente se cadastra ou se vincula;
4. cliente recebe/visualiza benefícios;
5. empresa valida ações lendo o QR Code do cliente.

O cliente não deve concluir benefício sozinho.

---

# 2. Tela de QR físico / material de aquisição

Referência visual:

- fundo azul/verde em gradiente;
- logo i9Plus/projeto grande;
- slogan: "CONECTANDO PESSOAS";
- QR Code grande central;
- texto: "BAIXE O APP";
- selos App Store / Google Play;
- aparência de cartaz/adesivo físico.

Uso no sistema:

- QR da empresa;
- página `vincular_empresa.html`;
- área da empresa para visualizar/imprimir QR Code;
- material para adesivo físico.

A tela/arte do QR da empresa não deve parecer apenas um QR técnico. Deve parecer peça comercial.

---

# 3. Home do cliente

Arquivo provável:

- `backend/public/meus_pontos.html`

A home cliente deve ter:

## Topo

- header com gradiente verde/azul/roxo;
- logo pequeno à esquerda;
- saudação do usuário, exemplo: "Olá, Alexandre";
- menu de três pontos à direita;
- aparência limpa e mobile.

## Ações principais

Logo abaixo do topo:

- botão/aba "Ler QR Code";
- botão/aba "Meu QR Code";
- os dois lado a lado;
- formato arredondado/pill;
- contraste com gradiente.

## Empresas vinculadas

Lista vertical de empresas que o cliente já se cadastrou/vinculou.

Cada empresa deve mostrar:

- logo à esquerda;
- nome da empresa em destaque;
- categoria/ramo ou benefício em texto menor;
- estrelas/avaliação;
- badge/novidade quando houver;
- toque/click abre a página da empresa.

Exemplo visual esperado:

- Malagueta Galpão
- Texano Burger
- Florenza Boutique
- Makoto Sushi

## Destaques

A home pode mostrar banner de empresa ou promoção em destaque entre a lista.

## Menu inferior

Bottom navigation app-like com itens como:

- Início;
- Novidades/Notificações;
- Buscar lojas;
- Perfil.

O item ativo deve ficar em magenta/rosa.

---

# 4. Busca e categorias

Arquivos prováveis:

- `backend/public/parceiros_tem_de_tudo.html`
- `backend/public/home_tem_de_tudo.html`

A tela de busca deve ter:

## Topo

- logo pequeno;
- saudação do usuário;
- aparência mobile.

## Busca

- campo com placeholder: "Digite o nome ou código do comércio";
- ícone de lupa magenta/rosa;
- botão quadrado roxo/magenta para buscar;
- mensagem quando aplicável: "No mínimo 4 caracteres!".

## Categorias

Grid de 2 colunas no mobile.

Categorias obrigatórias:

- Restaurantes;
- Sorveterias;
- Bares;
- Japonesa;
- Petshops;
- Beleza.

Cada categoria deve ter:

- imagem ou placeholder grande arredondado;
- label abaixo;
- espaçamento claro.

## Resultados

Lista de empresas com:

- logo;
- nome;
- categoria;
- estrelas;
- CTA "Me cadastrar" quando o cliente ainda não estiver vinculado.

## Menu inferior

Bottom nav com "Buscar lojas" ativo.

---

# 5. Página da empresa

Arquivo provável:

- `backend/public/detalhe_do_parceiro.html`

A página da empresa é uma das telas mais importantes.

## Topo

Deve ter:

- fundo preto/dark;
- botão X ou voltar no canto superior esquerdo;
- estrela + nota no canto superior direito;
- logo da empresa;
- nome da empresa em destaque, preferencialmente uppercase;
- categoria/ramo;
- aparência de app.

Exemplo:

- POPEYE HAMBURGUERIA
- nota 5
- estrela no canto direito

## Conteúdo

A página deve ter seções/cards separados:

- bônus de adesão;
- cartão fidelidade;
- promoções;
- bônus aniversário;
- contatos;
- avaliações.

---

# 6. Bônus de adesão

O bônus de adesão deve parecer uma mensagem de boas-vindas/benefício forte.

Referência funcional:

- aparece para cliente vinculado quando ainda não resgatado;
- é usado uma única vez;
- empresa valida lendo o QR Code do cliente;
- cliente não auto-resgata.

Referência visual:

- card branco grande;
- imagem/logo;
- chamada promocional;
- instrução clara;
- botão/CTA visual;
- pode aparecer em formato de modal/card destacado.

Texto exemplo:

- "Bem-vindo ao programa de fidelidade"
- "Você ganhou 10% de desconto na primeira compra"
- "Apresente seu QR Code no estabelecimento para validar"

---

# 7. Cartão fidelidade

O cartão fidelidade deve seguir o padrão visual da referência.

## Estrutura

Card branco arredondado com:

- título verde:
  "GANHE 1 PONTO A CADA VISITA"

- validade em cinza;

- texto:
  "Com 15 pontos:"

- recompensa em destaque/roxo:
  "Ganhe 1 Porção de Fritas ou 1 Lanche"

- bloco azul de progresso:
  "Você já acumulou"
  "0 / 15 pontos"

- barra de progresso.

## Regras

- empresa adiciona ponto lendo QR Code do cliente;
- empresa resgata recompensa lendo QR Code do cliente;
- cliente apenas acompanha progresso;
- cliente não adiciona ponto sozinho;
- cliente não resgata sozinho.

---

# 8. Promoções instantâneas

Promoções devem ter imagem/banner.

Referência visual:

- banner grande;
- título forte;
- descrição curta;
- validade;
- aparência promocional;
- pode aparecer em modal/notificação.

Exemplo:

- "VOCÊ GANHOU 10% DE DESCONTO"
- "SOMENTE HOJE: 10% DE DESCONTO!"

## Regras

- empresa cria promoção;
- promoção tem imagem obrigatória;
- clientes vinculados recebem/visualizam;
- push depende de VAPID/subscription;
- empresa valida presencialmente via QR Code do cliente;
- cliente não auto-resgata.

---

# 9. Bônus aniversário

Referência visual:

- banner grande:
  "FELIZ ANIVERSÁRIO!"
- texto:
  "MUITAS FELICIDADES NESTA DATA ESPECIAL"
- card branco com título "Notificações";
- conteúdo:
  "FELIZ ANIVERSÁRIO!"
  "Comemore seu aniversário conosco e ganhe uma cortesia!"
- botão OK verde/azulado.

## Regras

- depende de `data_nascimento`;
- aparece para cliente elegível;
- empresa define o brinde;
- validação presencial via QR Code do cliente;
- uma vez por ano por empresa.

---

# 10. Notificações

Referência visual:

- fundo escurecido;
- card branco arredondado;
- título: "Notificações";
- ícones no topo:
  - mudo;
  - toggle;
  - sino;
- banner promocional;
- título da campanha;
- descrição;
- validade;
- checkbox "Não exibir novamente";
- botão OK verde/azulado.

Essa experiência deve ser usada visualmente para:

- promoção;
- bônus aniversário;
- comunicação de campanha.

Não precisa implementar push novo nesta etapa se já existir infraestrutura. O foco visual é a experiência da notificação.

---

# 11. Avaliações

A tela/seção de avaliações deve ter:

- topo dark quando em página da empresa;
- nota grande, exemplo: "5";
- estrelas;
- total de avaliações;
- distribuição simples de notas, se possível;
- seção "Minha última avaliação";
- seção "Avaliações dos usuários";
- filtro/ordenação "Mais recentes";
- lista com:
  - avatar;
  - nome;
  - estrelas;
  - comentário.

Regras:

- cliente vinculado pode avaliar;
- uma avaliação por cliente/empresa;
- cliente pode editar;
- empresa mostra média e total.

---

# 12. Área da empresa

Arquivos prováveis:

- `backend/public/dashboard_parceiro.html`
- `backend/public/gest_o_de_ofertas_parceiro.html`
- `backend/public/clientes_fidelizados_loja.html`
- `backend/public/validar_resgate.html`

A área da empresa deve parecer app/painel simples, não dashboard genérico.

## Dashboard empresa

Deve ter:

- logo/nome da empresa;
- engrenagem/configurações;
- QR Code da empresa em destaque;
- botão "Ler QR Code do cliente";
- ferramentas em cards:
  - promoção instantânea;
  - bônus de adesão;
  - cartão fidelidade;
  - bônus aniversário;
  - lembrete;
  - clientes;
  - relatórios.

## Configurações/gestão

A empresa deve conseguir configurar:

- nome da promoção/campanha;
- descrição;
- imagem;
- validade/status;
- recompensa;
- prazo de lembrete;
- dados da empresa.

Visual esperado:

- cards;
- seções por ferramenta;
- preview do que o cliente verá;
- botões pill;
- sem parecer tabela administrativa crua.

---

# 13. Scanner da empresa

Arquivo provável:

- `backend/public/validar_resgate.html`

A tela deve ter:

- título: "Leitor de QR Code";
- instrução: "Aponte para o QR Code do cliente";
- área de câmera/scanner;
- opção de inserir código manual;
- painel pós-leitura com:
  - nome do cliente;
  - telefone;
  - data de nascimento;
  - vínculo com a empresa;
  - bônus de adesão;
  - pontos/fidelidade;
  - promoções;
  - bônus aniversário.

Botões elegíveis:

- Validar bônus;
- Adicionar ponto;
- Resgatar recompensa;
- Validar promoção;
- Validar aniversário.

Botões inelegíveis não devem aparecer ou devem estar desabilitados com explicação.

---

# 14. Admin

Arquivos prováveis:

- `backend/public/dashboard_admin_master.html`
- `backend/public/gest_o_de_estabelecimentos.html`
- `backend/public/relat_rios_gerais_master.html`

Admin deve ter:

- empresas pendentes;
- empresas ativas;
- empresas suspensas;
- aprovar;
- rejeitar;
- suspender;
- métricas em cards;
- relatórios gerais;
- rankings/listas simples.

Admin desktop pode ser painel web.

---

# 15. PWA

O sistema deve manter:

- `manifest.json` válido;
- ícones existentes;
- start_url funcional;
- páginas apontadas existentes;
- possibilidade de instalar/adicionar à tela inicial;
- service worker se já existir.

Após cadastro, o sistema pode orientar o usuário a adicionar à tela inicial.

---

# 16. Telas P0

As telas P0 que determinam aprovação visual são:

1. `meus_pontos.html` — home cliente;
2. `parceiros_tem_de_tudo.html` — busca/categorias;
3. `detalhe_do_parceiro.html` — página da empresa;
4. `validar_resgate.html` — scanner/validação empresa;
5. `dashboard_parceiro.html` — dashboard empresa;
6. `gest_o_de_ofertas_parceiro.html` — gestão de benefícios;
7. `gest_o_de_estabelecimentos.html` — aprovação/admin empresas.

Essas telas devem ser corrigidas antes de qualquer refinamento secundário.

---

# 17. Critérios de aceite visual

Uma tela só pode ser marcada como "próxima" ou "igual" se atender:

- estrutura parecida com a referência;
- hierarquia visual parecida;
- ações principais na mesma lógica;
- cards e botões coerentes;
- mobile app-like;
- conteúdo demo preenchido;
- sem parecer dashboard genérico;
- sem cards vazios;
- sem auto-resgate do cliente.

---

# 18. Critérios de aceite funcional

A demo deve permitir:

Cliente:
- login;
- ver home;
- ver QR;
- buscar empresas;
- abrir página de empresa;
- ver bônus/fidelidade/promoções/aniversário/avaliações.

Empresa:
- login;
- ver QR empresa;
- ler QR cliente;
- validar bônus;
- adicionar ponto;
- resgatar recompensa;
- validar promoção;
- validar aniversário;
- ver clientes;
- ver relatórios.

Admin:
- login;
- ver empresas pending/active/suspended;
- aprovar/rejeitar/suspender;
- ver relatórios.

---

# 19. Pendências conhecidas

- push real depende de VAPID;
- câmera/scanner precisa de QA em dispositivo real;
- histórico Git/segredo é trilha separada;
- assets proprietários reais não estão disponíveis, então banners/logos devem usar placeholders coerentes;
- pixel-perfect depende de assets finais.

Fim da especificação.
