# Regras de negocio i9Plus PWA

## Plataforma

- PWA por enquanto.
- Nao implementar app nativo.
- Mesmo sistema para cliente, empresa e admin.
- A base atual deve ser adaptada com reaproveitamento maximo da arquitetura existente.

## Perfis

- `customer`;
- `company`;
- `admin`.

Cada perfil deve operar na mesma base de produto, com rotas, permissoes e experiencias distintas.

## Cadastro do cliente

- nome;
- e-mail;
- telefone;
- data de nascimento;
- senha;
- QR Code exclusivo.

Regras complementares:

- Se o cliente chegar por QR Code de uma empresa, o sistema deve preservar esse contexto para vinculo posterior.
- Depois do cadastro, o produto deve sugerir adicionar o PWA a tela inicial.
- O QR Code do cliente sera usado pela empresa para validacao operacional de acoes.

## Cadastro da empresa

- empresa se cadastra;
- fica pendente;
- admin aprova;
- empresa ativa aparece no app;
- pagamento fora do sistema.

Regras complementares:

- Nao implementar pagamento online no MVP.
- Empresa pendente nao deve aparecer publicamente.
- A liberacao operacional depende de aprovacao administrativa manual.

## QR Code da empresa

- token seguro;
- usado no adesivo fisico;
- cliente escaneia para se vincular.

Regras complementares:

- O QR Code nao deve expor identificadores sensiveis diretamente.
- O fluxo deve abrir a pagina da empresa dentro do PWA.
- Se o cliente ainda nao tiver conta, deve ser direcionado ao cadastro mantendo o contexto da empresa.

## QR Code do cliente

- token seguro;
- empresa escaneia para validar acoes.

Regras complementares:

- O QR Code do cliente deve suportar validacao de bonus, promocoes, pontos e recompensas.
- O token nao deve expor dados sensiveis nem ser facilmente manipulavel.

## Validacao de resgate

- cliente nao conclui resgate sozinho;
- empresa valida lendo QR Code do cliente.

Regras complementares:

- O cliente pode visualizar o beneficio e sinalizar intencao de uso.
- A confirmacao real da acao deve acontecer do lado da empresa.
- Essa regra vale para bonus, promocao, recompensa e aniversario.

## Bonus de adesao

- uma vez por cliente por empresa;
- primeira compra;
- validado pela empresa.

Regras complementares:

- Pode ter imagem, titulo e descricao.
- So aparece se a empresa tiver bonus ativo.
- Depois de validado, nao deve aparecer novamente para o mesmo cliente naquela empresa.

## Promocoes instantaneas

- imagem obrigatoria;
- push notification;
- apenas clientes vinculados;
- limite de envio.

Regras complementares:

- Devem ter titulo com limite de caracteres.
- Devem ter descricao com limite de caracteres.
- Devem ter validade.
- O resgate precisa de validacao da empresa via QR Code do cliente.
- O envio deve respeitar limites basicos para evitar spam por empresa.

## Cartao fidelidade

- pontos por visita;
- empresa adiciona pontos;
- empresa valida recompensa.

Regras complementares:

- O cliente visualiza o progresso do cartao.
- A empresa registra o ponto lendo o QR Code do cliente.
- A recompensa final tambem depende de validacao pela empresa.

## Avaliacoes

- cliente vinculado avalia;
- media atualiza.

Regras complementares:

- Nota de 1 a 5.
- Comentario opcional.
- Avaliacoes aparecem na pagina da empresa.

## Bonus aniversario

- usa data de nascimento;
- uma vez por ano por empresa;
- validado pela empresa.

Regras complementares:

- Empresa define o brinde.
- Cliente recebe push notification apenas das empresas a que esta vinculado.
- O resgate depende de leitura do QR Code do cliente pela empresa.

## Lembrete

- usa ultima visita;
- envia push apos dias de inatividade;
- evitar spam.

Regras complementares:

- A empresa define o intervalo de dias, como 20, 30 ou 45.
- A empresa define titulo e mensagem.
- O sistema deve registrar historico de envios.

## Desktop vs mobile

- mobile parece app;
- desktop parece site/painel.

Regras complementares:

- Mobile deve priorizar navegacao inferior, cards empilhados, QR Code e acoes rapidas.
- Desktop deve aproveitar largura de tela com layout mais amplo, podendo usar sidebar, menu superior, tabelas e formularios em duas colunas.
- Desktop nao deve parecer apenas um celular esticado.
- Mobile nao deve parecer dashboard desktop comprimido.
