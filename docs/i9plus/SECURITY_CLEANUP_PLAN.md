# Security Cleanup Plan

## Situação

- O `JWT_SECRET` antigo deve ser considerado comprometido.
- O primeiro passo obrigatório é rotacionar o `JWT_SECRET` no ambiente real.
- Limpar o histórico Git não substitui a rotação do segredo.
- `origin/main` ainda precisa de limpeza histórica.

## Recomendação

Recomendação principal: remover `ITEM_3_SEGURANCA_CONCLUIDO.md` inteiro do histórico em ambiente controlado com `git-filter-repo`.

Comando recomendado:

```bash
git filter-repo --path ITEM_3_SEGURANCA_CONCLUIDO.md --invert-paths
```

## Validações após a limpeza

```bash
git log --all -- ITEM_3_SEGURANCA_CONCLUIDO.md
git log --all -p | grep -i "JWT_SECRET"
git log --all -p | grep -i "eyJ"
```

## Push forçado

Somente com coordenação explícita:

```bash
git push --force-with-lease origin main
```

## Riscos

- Reescreve o histórico remoto.
- Colaboradores precisam realinhar clones locais.
- PRs antigos podem quebrar.
- Forks e caches podem continuar contendo o segredo antigo.
- O segredo precisa ser rotacionado mesmo após a limpeza do histórico.
