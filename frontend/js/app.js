// app.js - Script para menu responsivo e manipulação básica de formulários

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menu-toggle');
  const navLinks = document.getElementById('nav-links');

  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });

  // Aqui podem ser adicionados handlers para formulários de login, cadastro, etc.
});
