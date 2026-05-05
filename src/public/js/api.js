// api.js — Клієнт для взаємодії з REST API
// Всі запити до /api/* відправляються через ці функції

const API_BASE = '/api';

// Отримання JWT токену з localStorage
function getToken() {
  return localStorage.getItem('token');
}

// Базова функція для HTTP запитів
async function apiFetch(path, options = {}) {
  const token = getToken();
  const headers = { 'Content-Type': 'application/json', ...options.headers };
  if (token) headers['Authorization'] = 'Bearer ' + token;

  const res = await fetch(API_BASE + path, { ...options, headers });
  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new Error(data.error || `HTTP ${res.status}`);
  }
  return data;
}

// ---- Автентифікація ----
const Auth = {
  login:    (u, p)        => apiFetch('/auth/login',    { method: 'POST', body: JSON.stringify({ username: u, password: p }) }),
  register: (d)           => apiFetch('/auth/register', { method: 'POST', body: JSON.stringify(d) }),
  me:       ()            => apiFetch('/auth/me'),
};

// ---- Класи ----
const Classes = {
  list:   ()     => apiFetch('/classes'),
  create: (d)    => apiFetch('/classes',     { method: 'POST',   body: JSON.stringify(d) }),
  update: (id,d) => apiFetch('/classes/'+id, { method: 'PUT',    body: JSON.stringify(d) }),
  remove: (id)   => apiFetch('/classes/'+id, { method: 'DELETE' }),
};

// ---- Предмети ----
const Subjects = {
  list:   ()     => apiFetch('/subjects'),
  create: (d)    => apiFetch('/subjects',     { method: 'POST',   body: JSON.stringify(d) }),
  update: (id,d) => apiFetch('/subjects/'+id, { method: 'PUT',    body: JSON.stringify(d) }),
  remove: (id)   => apiFetch('/subjects/'+id, { method: 'DELETE' }),
};

// ---- Теми ----
const Topics = {
  list:      (subjectId) => apiFetch('/topics' + (subjectId ? '?subject_id='+subjectId : '')),
  create:    (d)         => apiFetch('/topics',     { method: 'POST',   body: JSON.stringify(d) }),
  update:    (id,d)      => apiFetch('/topics/'+id, { method: 'PUT',    body: JSON.stringify(d) }),
  remove:    (id)        => apiFetch('/topics/'+id, { method: 'DELETE' }),
};

// ---- Запитання ----
const Questions = {
  list:   (topicId) => apiFetch('/questions' + (topicId ? '?topic_id='+topicId : '')),
  create: (d)       => apiFetch('/questions',         { method: 'POST',   body: JSON.stringify(d) }),
  update: (id,d)    => apiFetch('/questions/'+id,     { method: 'PUT',    body: JSON.stringify(d) }),
  remove: (id)      => apiFetch('/questions/'+id,     { method: 'DELETE' }),
  import: (arr)     => apiFetch('/questions/import',  { method: 'POST',   body: JSON.stringify(arr) }),
};

// ---- Тести ----
const Tests = {
  list:   ()       => apiFetch('/tests'),
  create: (d)      => apiFetch('/tests',         { method: 'POST',   body: JSON.stringify(d) }),
  update: (id,d)   => apiFetch('/tests/'+id,     { method: 'PUT',    body: JSON.stringify(d) }),
  remove: (id)     => apiFetch('/tests/'+id,     { method: 'DELETE' }),
  assign: (id,d)   => apiFetch('/tests/'+id+'/assign', { method: 'POST', body: JSON.stringify(d) }),
};

// ---- Учень ----
const Student = {
  tests:   ()        => apiFetch('/student/tests'),
  test:    (id)      => apiFetch('/student/tests/'+id),
  submit:  (id, ans) => apiFetch('/student/tests/'+id+'/submit', { method: 'POST', body: JSON.stringify(ans) }),
  results: ()        => apiFetch('/student/results'),
};

// ---- Користувачі (адмін) ----
const Users = {
  list:        ()        => apiFetch('/classes').then(() => apiFetch('/auth/me')).catch(() => []),
  // Отримуємо учнів через окремий ендпоінт (додати при потребі)
  listStudents: ()       => apiFetch('/student/users').catch(() => []),
  remove:      (id)      => apiFetch('/users/'+id, { method: 'DELETE' }),
  setClass:    (id, cid) => apiFetch('/users/'+id+'/class', { method: 'PUT', body: JSON.stringify({ class_id: cid }) }),
};
