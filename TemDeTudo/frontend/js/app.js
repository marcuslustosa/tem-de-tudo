// app.js - Configurações e chamadas para API backend

// Atualize a URL abaixo com a URL do backend no Railway após deploy
const API_BASE_URL = 'https://your-railway-backend-url/api';

// Exemplo de função para login
async function login(email, password) {
  const response = await fetch(`${API_BASE_URL}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data.error || 'Erro no login');
  return data.token;
}

// Outras funções para chamadas API podem ser adicionadas aqui
