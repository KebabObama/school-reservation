<?php
function render_auth_component(): void {
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
    <form method="POST" class="space-y-4">
      <input type="hidden" name="action" value="login" />
      <input required name="email" type="email" placeholder="Email" class="w-full p-2 border rounded" />
      <input required name="password" type="password" placeholder="Password" class="w-full p-2 border rounded" />
      <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
    </form>
  </div>

  <div id="register" class="auth-tab hidden">
    <form method="POST" class="space-y-4">
      <input type="hidden" name="action" value="register" />
      <input required name="email" type="email" placeholder="Email" class="w-full p-2 border rounded" />
      <input required name="name" type="text" placeholder="Name" class="w-full p-2 border rounded" />
      <input required name="surname" type="text" placeholder="Surname" class="w-full p-2 border rounded" />
      <input required name="password" type="password" placeholder="Password" class="w-full p-2 border rounded" />
      <button type="submit" class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700">Register</button>
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
</script>
HTML;
}