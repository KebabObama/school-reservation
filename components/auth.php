<?php
function render_auth_component(): void
{
  echo <<<HTML
<h2 class="text-2xl font-bold mb-6 text-center">Room Manager</h2>

<div>
  <ul class="flex mb-6 border-b" id="authTabs">
    <li class="mr-6">
      <button class="pb-2 border-b-2 border-blue-600 font-semibold" onclick="showTab('login')">Login</button>
    </li>
    <li class="mr-6">
      <button class="pb-2 border-b-2 border-transparent hover:border-blue-400" onclick="showTab('register')">Register</button>
    </li>
  </ul>

  <div id="login" class="auth-tab">
    <form id="login-form" class="space-y-4">
      <input required name="email" type="email" placeholder="Email" class="w-full p-2 border rounded" />
      <input required name="password" type="password" placeholder="Password" class="w-full p-2 border rounded" />
      <button type="button" onclick="handleLogin()" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
    </form>
  </div>

  <div id="register" class="auth-tab hidden">
    <form id="register-form" class="space-y-4">
      <input required name="email" type="email" placeholder="Email" class="w-full p-2 border rounded" />
      <input required name="name" type="text" placeholder="Name" class="w-full p-2 border rounded" />
      <input required name="surname" type="text" placeholder="Surname" class="w-full p-2 border rounded" />
      <input required name="password" type="password" placeholder="Password" class="w-full p-2 border rounded" />
      <button type="button" onclick="handleRegister()" class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700">Register</button>
    </form>
  </div>
</div>

<script>
  function showTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(el => el.classList.add('hidden'));
    document.getElementById(tab).classList.remove('hidden');

    const buttons = document.querySelectorAll('#authTabs button');
    buttons.forEach(btn => {
      btn.classList.remove('border-blue-600', 'font-semibold');
      btn.classList.add('border-transparent', 'hover:border-blue-400');
    });

    const activeBtn = Array.from(buttons).find(b => b.textContent.toLowerCase() === tab);
    activeBtn.classList.add('border-blue-600', 'font-semibold');
    activeBtn.classList.remove('border-transparent', 'hover:border-blue-400');
  }

  async function handleLogin() {
    const form = document.getElementById('login-form');
    const formData = new FormData(form);

    const email = formData.get('email');
    const password = formData.get('password');

    if (!email || !password) {
      if (window.popupSystem) {
        popupSystem.error('Please enter both email and password');
      } else {
        alert('Please enter both email and password');
      }
      return;
    }

    try {
      const result = await authManager.login(email, password);

      if (result.success) {
        // Login successful - reload the page
        if (window.popupSystem) {
          popupSystem.success('Login successful!');
        }
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        // Login failed
        let errorMessage = result.error || 'Login failed';
        if (result.remaining_attempts !== undefined) {
          errorMessage += ' (' + result.remaining_attempts + ' attempts remaining)';
        }

        if (window.popupSystem) {
          popupSystem.error(errorMessage);
        } else {
          alert(errorMessage);
        }
      }
    } catch (error) {
      if (window.popupSystem) {
        popupSystem.error('Network error: ' + error.message);
      } else {
        alert('Network error: ' + error.message);
      }
    }
  }

  async function handleRegister() {
    const form = document.getElementById('register-form');
    const formData = new FormData(form);

    const data = {
      action: 'register',
      email: formData.get('email'),
      name: formData.get('name'),
      surname: formData.get('surname'),
      password: formData.get('password')
    };

    try {
      const response = await fetch('/', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
      });

      if (response.redirected || response.ok) {
        // Registration successful - reload the page
        window.location.reload();
      } else {
        // Registration failed
        if (window.popupSystem) {
          popupSystem.error('Registration failed. Email might already be in use.');
        } else {
          alert('Registration failed. Email might already be in use.');
        }
      }
    } catch (error) {
      if (window.popupSystem) {
        popupSystem.error('Network error: ' + error.message);
      } else {
        alert('Network error: ' + error.message);
      }
    }
  }

  // Handle Enter key in forms
  document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
      loginForm.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          handleLogin();
        }
      });
    }

    if (registerForm) {
      registerForm.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          handleRegister();
        }
      });
    }
  });
</script>
HTML;
}
