// app.js - Script para menu responsivo e manipulação básica de formulários

// URL da API no Render - ajustar conforme necessário
const API_BASE_URL = 'https://tem-de-tudo.onrender.com/api';


document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menu-toggle');
  const navLinks = document.getElementById('nav-links');

  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });

  // Handler para formulário de login
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(loginForm);
      const data = Object.fromEntries(formData);

      try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data),
        });

        const result = await response.json();
        if (response.ok) {
          localStorage.setItem('token', result.token);
          localStorage.setItem('role', result.user.role || 'cliente');
          alert('Login realizado com sucesso!');
          if (result.user.role === 'empresa') {
            window.location.href = 'profile-company.html';
          } else if (result.user.role === 'admin') {
            window.location.href = 'admin.html';
          } else {
            window.location.href = 'profile-client.html';
          }
        } else {
          alert(result.message || 'Erro no login');
        }
      } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao conectar com o servidor');
      }
    });
  }

  // Handler para formulário de registro
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(registerForm);
      const data = Object.fromEntries(formData);

      if (data.password !== data['confirm-password']) {
        alert('Senhas não coincidem');
        return;
      }

      try {
        const response = await fetch(`${API_BASE_URL}/auth/register`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data),
        });

        const result = await response.json();
        if (response.ok) {
          alert('Registro realizado com sucesso! Faça login.');
          window.location.href = 'login.html';
        } else {
          alert(result.message || 'Erro no registro');
        }
      } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao conectar com o servidor');
      }
    });
  }

  // Registrar Service Worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
      .then(registration => console.log('Service Worker registrado:', registration))
      .catch(error => console.log('Erro ao registrar Service Worker:', error));
  }

  // Permissão para notificações push
  if ('Notification' in window) {
    Notification.requestPermission().then(permission => {
      if (permission === 'granted') console.log('Permissão para notificações concedida');
    });
  }
});
