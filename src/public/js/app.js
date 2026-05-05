// app.js — Головний файл застосунку: роутинг, теми, ініціалізація

// ---- Управління секціями ----
const ALL_SECTIONS = ['login','register','student','results','quiz','quiz-result','admin'];

function showSection(name) {
  ALL_SECTIONS.forEach(id => {
    const el = document.getElementById(id + '-section');
    if (el) el.style.display = (id === name) ? '' : 'none';
  });
}

// ---- Тема (темна / світла) ----
function initTheme() {
  const saved = localStorage.getItem('theme') || 'light';
  applyTheme(saved);
}

function applyTheme(theme) {
  document.body.classList.toggle('dark-theme', theme === 'dark');
  document.getElementById('themeToggle').textContent = theme === 'dark' ? '☀️' : '🌙';
  localStorage.setItem('theme', theme);
}

document.getElementById('themeToggle').addEventListener('click', () => {
  const isDark = document.body.classList.contains('dark-theme');
  applyTheme(isDark ? 'light' : 'dark');
});

// ---- Роутер (hash-based) ----
async function navigate(route, params) {
  if (!currentUser) {
    if (route !== 'register') {
      showSection('login');
      await loadRegisterClasses();
    } else {
      showSection('register');
      await loadRegisterClasses();
    }
    updateHeader();
    return;
  }

  updateHeader();

  if (currentUser.role === 'admin') {
    // Адмін бачить лише свою панель
    showSection('admin');
    await initAdmin();
    return;
  }

  // Учень
  switch (route) {
    case 'quiz':
      await startQuiz(params);
      break;
    case 'results':
      showSection('results');
      await loadResults();
      break;
    default:
      showSection('student');
      await loadStudentTests();
  }
}

// Слухаємо зміну хешу URL
window.addEventListener('hashchange', () => handleHash());

function handleHash() {
  const hash   = location.hash.replace('#', '');
  const [route, param] = hash.split('/');
  navigate(route || '', param);
}

// ---- Завантаження класів для форми реєстрації ----
async function loadRegisterClasses() {
  try {
    const classes = await Classes.list();
    const sel = document.getElementById('regClass');
    const cur = sel.value;
    sel.innerHTML = '<option value="">— Оберіть клас —</option>' +
      classes.map(c => `<option value="${c.id}" ${cur==c.id?'selected':''}>${c.name}</option>`).join('');
  } catch {}
}

// ---- Дашборд учня ----
async function loadStudentTests() {
  const list = document.getElementById('studentTestList');
  list.innerHTML = '<p class="text-muted">Завантаження...</p>';
  try {
    const tests = await Student.tests();
    if (!tests.length) { list.innerHTML = '<p class="text-muted">Тестів не призначено</p>'; return; }

    list.innerHTML = tests.map(t => {
      const done = t.result_id;
      return `<div class="card test-card">
        <div class="test-info">
          <h3>${t.name}</h3>
          <p>${t.subject_name} / ${t.topic_name} · ${t.question_count} запитань</p>
          ${t.deadline ? `<p class="text-muted" style="font-size:.8rem;">Дедлайн: ${new Date(t.deadline).toLocaleString('uk')}</p>` : ''}
        </div>
        <div>
          ${done
            ? '<span class="badge badge-success">✅ Здано</span>'
            : `<a href="#quiz/${t.id}" class="btn btn-primary btn-sm">▶ Пройти</a>`}
        </div>
      </div>`;
    }).join('');
  } catch (e) {
    list.innerHTML = `<p class="text-muted">Помилка: ${e.message}</p>`;
  }
}

// ---- Результати учня ----
async function loadResults() {
  const list = document.getElementById('resultsList');
  list.innerHTML = '<p class="text-muted">Завантаження...</p>';
  try {
    const results = await Student.results();
    if (!results.length) { list.innerHTML = '<p class="text-muted">Результатів поки немає</p>'; return; }

    list.innerHTML = results.map(r => `
      <div class="card test-card">
        <div class="test-info">
          <h3>${r.test_name}</h3>
          <p>${r.subject_name} / ${r.topic_name}</p>
          <p class="text-muted" style="font-size:.8rem;">${new Date(r.completed_at).toLocaleString('uk')}</p>
        </div>
        <div style="text-align:center;">
          <div style="font-size:2rem;font-weight:700;color:var(--primary);">${r.grade}</div>
          <div class="text-muted" style="font-size:.8rem;">${r.correct_count}/${r.question_count}</div>
        </div>
      </div>`).join('');
  } catch (e) {
    list.innerHTML = `<p class="text-muted">Помилка: ${e.message}</p>`;
  }
}

// ---- Toast повідомлення ----
let toastTimer;
function showToast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className   = `toast ${type} show`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
}

// ---- Модальні вікна ----
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Закриття модалки при кліку на фон
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

// ---- Форми входу / реєстрації ----
document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const username = document.getElementById('loginUsername').value;
  const password = document.getElementById('loginPassword').value;
  try {
    const res = await Auth.login(username, password);
    await loginUser(res.token);
    location.hash = '';
    navigate(getDefaultRoute());
  } catch (err) {
    showToast(err.message, 'error');
  }
});

document.getElementById('registerForm').addEventListener('submit', async e => {
  e.preventDefault();
  const d = {
    username:  document.getElementById('regUsername').value,
    full_name: document.getElementById('regFullName').value,
    class_id:  +document.getElementById('regClass').value || null,
    password:  document.getElementById('regPassword').value,
  };
  try {
    const res = await Auth.register(d);
    await loginUser(res.token);
    navigate('dashboard');
  } catch (err) {
    showToast(err.message, 'error');
  }
});

document.getElementById('logoutBtn').addEventListener('click', () => {
  logout();
  location.hash = '#login';
});

// ---- Запуск застосунку ----
(async () => {
  initTheme();
  const authed = await checkAuth();
  if (!authed) {
    showSection('login');
    await loadRegisterClasses();
    document.getElementById('header').style.display = 'none';
  } else {
    handleHash();
  }
})();
