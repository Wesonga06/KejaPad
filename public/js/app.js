const root = document.documentElement;
const storedTheme = localStorage.getItem('kejapad-theme') || 'dark';
root.dataset.theme = storedTheme;

function showToast(message) {
  let toast = document.querySelector('.toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = '<strong>KejaPad updated</strong><span></span>';
    document.body.appendChild(toast);
  }
  toast.querySelector('span').textContent = message;
  toast.classList.add('show');
  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => toast.classList.remove('show'), 2600);
}

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
  button.textContent = root.dataset.theme === 'dark' ? 'Moon' : 'Sun';
  button.addEventListener('click', () => {
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    localStorage.setItem('kejapad-theme', next);
    document.querySelectorAll('[data-theme-toggle]').forEach((item) => {
      item.textContent = next === 'dark' ? 'Moon' : 'Sun';
    });
    showToast(`${next === 'dark' ? 'Dark' : 'Light'} mode activated.`);
  });
});

document.querySelectorAll('[data-toast]').forEach((button) => {
  button.addEventListener('click', () => showToast(button.dataset.toast));
});

document.querySelectorAll('[data-role]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-role]').forEach((item) => item.classList.remove('active'));
    button.classList.add('active');
    showToast(`${button.dataset.role} workspace selected.`);
  });
});

const userBadge = document.querySelector('[data-user-badge]');
if (userBadge) {
  userBadge.textContent = localStorage.getItem('kejapad-user') || 'Guest mode';
}

const loginForm = document.querySelector('[data-login-form]');
if (loginForm) {
  loginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const email = document.querySelector('#email')?.value || 'tenant@kejapad.app';
    localStorage.setItem('kejapad-user', email);
    showToast('Identity verified. Opening your KejaPad dashboard.');
    window.setTimeout(() => window.location.href = 'index.html', 700);
  });
}

const signupForm = document.querySelector('[data-signup-form]');
if (signupForm) {
  signupForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const email = document.querySelector('#email')?.value || 'new-user@kejapad.app';
    localStorage.setItem('kejapad-user', email);
    showToast('Account created. Welcome to KejaPad.');
    window.setTimeout(() => window.location.href = 'index.html', 700);
  });
}

const logoutButton = document.querySelector('[data-logout]');
if (logoutButton) {
  logoutButton.addEventListener('click', () => {
    localStorage.removeItem('kejapad-user');
    showToast('You have been signed out securely.');
    window.setTimeout(() => window.location.href = 'login.html', 800);
  });
}