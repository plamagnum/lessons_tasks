// auth.js — Управління станом автентифікації

let currentUser = null; // Поточний авторизований користувач

// Збереження токену та завантаження даних користувача
async function loginUser(token) {
  localStorage.setItem('token', token);
  try {
    currentUser = await Auth.me();
  } catch {
    logout();
  }
}

// Вихід з системи — очищення стану
function logout() {
  localStorage.removeItem('token');
  currentUser = null;
  showSection('login');
  updateHeader();
}

// Оновлення шапки сайту залежно від стану
function updateHeader() {
  const header    = document.getElementById('header');
  const userInfo  = document.getElementById('userInfo');
  const logoutBtn = document.getElementById('logoutBtn');

  if (currentUser) {
    header.style.display = '';
    userInfo.textContent = currentUser.full_name + (currentUser.class_name ? ` (${currentUser.class_name})` : '');
    logoutBtn.style.display = '';
  } else {
    header.style.display = 'none';
    logoutBtn.style.display = 'none';
  }
}

// Перевірка чи є активна сесія при завантаженні
async function checkAuth() {
  const token = localStorage.getItem('token');
  if (!token) return false;
  try {
    currentUser = await Auth.me();
    return true;
  } catch {
    localStorage.removeItem('token');
    return false;
  }
}

// Визначення стартової сторінки залежно від ролі
function getDefaultRoute() {
  if (!currentUser) return 'login';
  return currentUser.role === 'admin' ? 'admin' : 'dashboard';
}
