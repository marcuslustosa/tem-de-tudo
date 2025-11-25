# TODO List - Correções Sistema Tem de Tudo

- [x] Remover suporte e páginas para perfil inexistente "funcionario"
  - Apagar diretório backend/public/funcionario/
  - Apagar arquivo backend/public/dashboard-funcionario.html
  - Remover caso 'funcionario' de redirecionamentos no frontend (/public/js/auth.js e auth-middleware.js)

- [ ] Revisar e corrigir funcionamento das páginas e APIs para perfis existentes:
  - cliente
  - estabelecimento (empresa)
  - admin master

- [ ] Garantir dados fictícios suficientes para popular dashboards e perfis dos usuários existentes:
  - Atualizar backend/database/seeders/DataSeeder.php para incluir dados robustos para cliente e empresa
  - Garantir users criados para estes perfis em DatabaseSeeder.php

- [ ] Revisar e ajustar:
  - Registros de usuário (AuthController.php) para atender os perfis citados, garantir funcionamento correto
  - Login e redirecionamento para perfis corretos
  - APIs de dashboard para cliente e empresa com dados completos e consistentes

- [ ] Garantir que o código modificado seja commitado corretamente e com mensagens descritivas

- [ ] Testes básicos para validar fluxo de cadastro, login, redirecionamento e visualização dados nos dashboards dos três perfis válidos

- [ ] Remover qualquer código legado, comentários e arquivos desnecessários que possam gerar confusão

- [ ] Documentar melhorias feitas para facilitar manutenção futura
