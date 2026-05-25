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
