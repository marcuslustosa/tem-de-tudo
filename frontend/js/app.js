// app.js - Script para menu responsivo e manipulação básica de formulários

const API_BASE_URL = 'https://seu-backend-url-no-railway-ou-vercel/api';

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menu-toggle');
  const navLinks = document.getElementById('nav-links');

  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });

  // Aqui podem ser adicionados handlers para formulários de login, cadastro, etc.
  // Exemplo de chamada API:
  // fetch(`${API_BASE_URL}/clientes`)
  //   .then(response => response.json())
  //   .then(data => console.log(data));
});
