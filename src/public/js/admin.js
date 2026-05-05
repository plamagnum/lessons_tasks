// admin.js — Логіка адмін-панелі

// Кеш даних
let adminData = { classes: [], subjects: [], topics: [], questions: [], tests: [], users: [] };
let editingId = null; // ID запису, що редагується
let assignTestId = null; // ID тесту для призначення
let editingUserId = null; // ID учня для зміни класу

// ---- Ініціалізація адмін-панелі ----
async function initAdmin() {
  setupAdminTabs();
  await loadAll();
}

// Завантаження всіх даних
async function loadAll() {
  try {
    [adminData.classes, adminData.subjects, adminData.topics] = await Promise.all([
      Classes.list(), Subjects.list(), Topics.list()
    ]);
    renderClasses(); renderSubjects(); renderTopics();
    fillSubjectSelects(); fillTopicSelects();

    [adminData.tests, adminData.questions] = await Promise.all([Tests.list(), Questions.list()]);
    renderTests(); renderQuestions();

    await loadUsers();
  } catch (e) {
    showToast('Помилка завантаження: ' + e.message, 'error');
  }
}

// ---- Таби ----
function setupAdminTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');
    });
  });
}

// ---- КЛАСИ ----
function renderClasses() {
  const tb = document.getElementById('classesTBody');
  tb.innerHTML = adminData.classes.map(c => `
    <tr>
      <td>${c.id}</td><td>${c.name}</td><td>${c.grade}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="editClass(${c.id})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteClass(${c.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

document.getElementById('addClassBtn').addEventListener('click', () => {
  editingId = null;
  document.getElementById('classModalTitle').textContent = 'Додати клас';
  document.getElementById('className').value  = '';
  document.getElementById('classGrade').value = '';
  openModal('classModal');
});

function editClass(id) {
  const c = adminData.classes.find(x => x.id === id);
  if (!c) return;
  editingId = id;
  document.getElementById('classModalTitle').textContent = 'Редагувати клас';
  document.getElementById('className').value  = c.name;
  document.getElementById('classGrade').value = c.grade;
  openModal('classModal');
}

document.getElementById('saveClassBtn').addEventListener('click', async () => {
  const d = { name: document.getElementById('className').value.trim(), grade: +document.getElementById('classGrade').value };
  if (!d.name || !d.grade) return showToast('Заповніть всі поля', 'error');
  try {
    editingId ? await Classes.update(editingId, d) : await Classes.create(d);
    closeModal('classModal');
    adminData.classes = await Classes.list();
    renderClasses(); fillSubjectSelects();
    showToast('Збережено', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

async function deleteClass(id) {
  if (!confirm('Видалити клас?')) return;
  try { await Classes.remove(id); adminData.classes = await Classes.list(); renderClasses(); showToast('Видалено', 'success'); }
  catch (e) { showToast(e.message, 'error'); }
}

// ---- ПРЕДМЕТИ ----
function renderSubjects() {
  document.getElementById('subjectsTBody').innerHTML = adminData.subjects.map(s => `
    <tr><td>${s.id}</td><td>${s.name}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="editSubject(${s.id})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteSubject(${s.id})">🗑️</button>
      </td></tr>`).join('');
}

document.getElementById('addSubjectBtn').addEventListener('click', () => {
  editingId = null;
  document.getElementById('subjectModalTitle').textContent = 'Додати предмет';
  document.getElementById('subjectName').value = '';
  openModal('subjectModal');
});

function editSubject(id) {
  const s = adminData.subjects.find(x => x.id === id);
  editingId = id;
  document.getElementById('subjectModalTitle').textContent = 'Редагувати предмет';
  document.getElementById('subjectName').value = s.name;
  openModal('subjectModal');
}

document.getElementById('saveSubjectBtn').addEventListener('click', async () => {
  const d = { name: document.getElementById('subjectName').value.trim() };
  if (!d.name) return showToast('Введіть назву', 'error');
  try {
    editingId ? await Subjects.update(editingId, d) : await Subjects.create(d);
    closeModal('subjectModal');
    adminData.subjects = await Subjects.list();
    renderSubjects(); fillSubjectSelects();
    showToast('Збережено', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

async function deleteSubject(id) {
  if (!confirm('Видалити предмет?')) return;
  try { await Subjects.remove(id); adminData.subjects = await Subjects.list(); renderSubjects(); fillSubjectSelects(); }
  catch (e) { showToast(e.message, 'error'); }
}

// ---- ТЕМИ ----
function renderTopics() {
  document.getElementById('topicsTBody').innerHTML = adminData.topics.map(t => `
    <tr><td>${t.id}</td><td>${t.subject_name||''}</td><td>${t.name}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="editTopic(${t.id})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteTopic(${t.id})">🗑️</button>
      </td></tr>`).join('');
}

function fillSubjectSelects() {
  const opts = adminData.subjects.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
  document.getElementById('topicSubjectId').innerHTML = '<option value="">— Оберіть предмет —</option>' + opts;
}

document.getElementById('addTopicBtn').addEventListener('click', () => {
  editingId = null;
  document.getElementById('topicModalTitle').textContent = 'Додати тему';
  document.getElementById('topicName').value = '';
  openModal('topicModal');
});

function editTopic(id) {
  const t = adminData.topics.find(x => x.id === id);
  editingId = id;
  document.getElementById('topicModalTitle').textContent = 'Редагувати тему';
  document.getElementById('topicName').value = t.name;
  document.getElementById('topicSubjectId').value = t.subject_id;
  openModal('topicModal');
}

document.getElementById('saveTopicBtn').addEventListener('click', async () => {
  const d = { name: document.getElementById('topicName').value.trim(), subject_id: +document.getElementById('topicSubjectId').value };
  if (!d.name || !d.subject_id) return showToast('Заповніть всі поля', 'error');
  try {
    editingId ? await Topics.update(editingId, d) : await Topics.create(d);
    closeModal('topicModal');
    adminData.topics = await Topics.list();
    renderTopics(); fillTopicSelects();
    showToast('Збережено', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

async function deleteTopic(id) {
  if (!confirm('Видалити тему?')) return;
  try { await Topics.remove(id); adminData.topics = await Topics.list(); renderTopics(); fillTopicSelects(); }
  catch (e) { showToast(e.message, 'error'); }
}

// ---- ЗАПИТАННЯ ----
function renderQuestions(list) {
  const data = list || adminData.questions;
  document.getElementById('questionsTBody').innerHTML = data.map(q => `
    <tr>
      <td>${q.id}</td>
      <td>${q.topic_name||''}</td>
      <td>${q.question_text.substring(0,60)}${q.question_text.length>60?'…':''}</td>
      <td>${q.correct_option.toUpperCase()}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="editQuestion(${q.id})">✏️</button>
        <button class="btn btn-danger btn-sm" onclick="deleteQuestion(${q.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

function fillTopicSelects() {
  const opts = adminData.topics.map(t => `<option value="${t.id}">${t.subject_name ? t.subject_name+' / ' : ''}${t.name}</option>`).join('');
  const emptyOpt = '<option value="">— Оберіть тему —</option>';
  document.getElementById('questionTopicId').innerHTML = emptyOpt + opts;
  document.getElementById('testTopicId').innerHTML     = emptyOpt + opts;
  document.getElementById('filterTopicQ').innerHTML    = '<option value="">— Всі теми —</option>' + opts;
}

document.getElementById('filterTopicQ').addEventListener('change', async function() {
  const topicId = +this.value || null;
  const list = topicId ? await Questions.list(topicId) : adminData.questions;
  renderQuestions(list);
});

document.getElementById('addQuestionBtn').addEventListener('click', () => {
  editingId = null;
  document.getElementById('questionModalTitle').textContent = 'Додати запитання';
  ['questionTopicId','questionText2','qOptA','qOptB','qOptC','qOptD'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('qCorrect').value = 'a';
  openModal('questionModal');
});

function editQuestion(id) {
  const q = adminData.questions.find(x => x.id === id);
  if (!q) return;
  editingId = id;
  document.getElementById('questionModalTitle').textContent = 'Редагувати запитання';
  document.getElementById('questionTopicId').value = q.topic_id;
  document.getElementById('questionText2').value   = q.question_text;
  document.getElementById('qOptA').value           = q.option_a;
  document.getElementById('qOptB').value           = q.option_b;
  document.getElementById('qOptC').value           = q.option_c;
  document.getElementById('qOptD').value           = q.option_d;
  document.getElementById('qCorrect').value        = q.correct_option;
  openModal('questionModal');
}

document.getElementById('saveQuestionBtn').addEventListener('click', async () => {
  const d = {
    topic_id:       +document.getElementById('questionTopicId').value,
    question_text:  document.getElementById('questionText2').value.trim(),
    option_a:       document.getElementById('qOptA').value.trim(),
    option_b:       document.getElementById('qOptB').value.trim(),
    option_c:       document.getElementById('qOptC').value.trim(),
    option_d:       document.getElementById('qOptD').value.trim(),
    correct_option: document.getElementById('qCorrect').value,
  };
  if (!d.topic_id || !d.question_text || !d.option_a || !d.option_b || !d.option_c || !d.option_d) {
    return showToast('Заповніть всі поля', 'error');
  }
  try {
    editingId ? await Questions.update(editingId, d) : await Questions.create(d);
    closeModal('questionModal');
    adminData.questions = await Questions.list();
    renderQuestions();
    showToast('Збережено', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

async function deleteQuestion(id) {
  if (!confirm('Видалити запитання?')) return;
  try { await Questions.remove(id); adminData.questions = await Questions.list(); renderQuestions(); }
  catch (e) { showToast(e.message, 'error'); }
}

// Імпорт JSON
document.getElementById('importQBtn').addEventListener('click', () => {
  document.getElementById('importJson').value = '';
  openModal('importModal');
});

document.getElementById('doImportBtn').addEventListener('click', async () => {
  let arr;
  try { arr = JSON.parse(document.getElementById('importJson').value); }
  catch { return showToast('Некоректний JSON', 'error'); }
  try {
    const res = await Questions.import(arr);
    closeModal('importModal');
    adminData.questions = await Questions.list();
    renderQuestions();
    showToast('Імпортовано: ' + res.imported + ' запитань', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

// ---- ТЕСТИ ----
function renderTests() {
  document.getElementById('testsTBody').innerHTML = adminData.tests.map(t => `
    <tr>
      <td>${t.id}</td><td>${t.name}</td>
      <td>${t.topic_name||''}</td><td>${t.question_count}</td>
      <td>
        <button class="btn btn-primary btn-sm" onclick="openAssign(${t.id})">📋 Призначити</button>
        <button class="btn btn-danger btn-sm" onclick="deleteTest(${t.id})">🗑️</button>
      </td>
    </tr>`).join('');
}

document.getElementById('addTestBtn').addEventListener('click', () => {
  editingId = null;
  document.getElementById('testModalTitle').textContent = 'Створити тест';
  document.getElementById('testName').value = '';
  document.getElementById('testQCount').value = '12';
  openModal('testModal');
});

document.getElementById('saveTestBtn').addEventListener('click', async () => {
  const d = {
    name:           document.getElementById('testName').value.trim(),
    topic_id:       +document.getElementById('testTopicId').value,
    question_count: +document.getElementById('testQCount').value,
  };
  if (!d.name || !d.topic_id) return showToast('Заповніть всі поля', 'error');
  try {
    await Tests.create(d);
    closeModal('testModal');
    adminData.tests = await Tests.list();
    renderTests();
    showToast('Тест створено', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

async function deleteTest(id) {
  if (!confirm('Видалити тест?')) return;
  try { await Tests.remove(id); adminData.tests = await Tests.list(); renderTests(); }
  catch (e) { showToast(e.message, 'error'); }
}

// Призначення тесту
async function openAssign(testId) {
  assignTestId = testId;
  const classOpts   = adminData.classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  document.getElementById('assignClass').innerHTML   = '<option value="">— Не вибрано —</option>' + classOpts;
  document.getElementById('assignStudent').innerHTML = '<option value="">— Завантаження... —</option>';

  openModal('assignModal');

  try {
    const users = await loadUsersRaw();
    const studentOpts = users.filter(u => u.role === 'student')
      .map(u => `<option value="${u.id}">${u.full_name}</option>`).join('');
    document.getElementById('assignStudent').innerHTML = '<option value="">— Не вибрано —</option>' + studentOpts;
  } catch { document.getElementById('assignStudent').innerHTML = '<option value="">— Помилка —</option>'; }
}

document.getElementById('doAssignBtn').addEventListener('click', async () => {
  const d = {
    class_id:   +document.getElementById('assignClass').value   || null,
    student_id: +document.getElementById('assignStudent').value || null,
    deadline:   document.getElementById('assignDeadline').value || null,
  };
  if (!d.class_id && !d.student_id) return showToast('Оберіть клас або учня', 'error');
  try {
    await Tests.assign(assignTestId, d);
    closeModal('assignModal');
    showToast('Тест призначено!', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

// ---- УЧНІ ----
let cachedUsers = [];

async function loadUsersRaw() {
  // Отримуємо учнів через admin endpoint
  try {
    cachedUsers = await apiFetch('/users');
  } catch {
    cachedUsers = [];
  }
  return cachedUsers;
}

async function loadUsers() {
  try {
    cachedUsers = await apiFetch('/users');
    renderUsers(cachedUsers);
  } catch {
    document.getElementById('usersTBody').innerHTML = '<tr><td colspan="5">Немає даних</td></tr>';
  }
}

function renderUsers(users) {
  const students = users.filter(u => u.role === 'student');
  document.getElementById('usersTBody').innerHTML = students.map(u => `
    <tr>
      <td>${u.id}</td><td>${u.username}</td><td>${u.full_name}</td>
      <td>${u.class_name||'—'}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="openUserClass(${u.id})">🏫</button>
        <button class="btn btn-danger btn-sm" onclick="deleteUser(${u.id})">🗑️</button>
      </td>
    </tr>`).join('') || '<tr><td colspan="5">Учнів немає</td></tr>';
}

function openUserClass(userId) {
  editingUserId = userId;
  const opts = adminData.classes.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  document.getElementById('userClassSelect').innerHTML = '<option value="">— Без класу —</option>' + opts;
  const user = cachedUsers.find(u => u.id === userId);
  if (user && user.class_id) document.getElementById('userClassSelect').value = user.class_id;
  openModal('userClassModal');
}

document.getElementById('saveUserClassBtn').addEventListener('click', async () => {
  const classId = +document.getElementById('userClassSelect').value || null;
  try {
    await apiFetch('/users/'+editingUserId+'/class', { method: 'PUT', body: JSON.stringify({ class_id: classId }) });
    closeModal('userClassModal');
    await loadUsers();
    showToast('Клас оновлено', 'success');
  } catch (e) { showToast(e.message, 'error'); }
});

async function deleteUser(id) {
  if (!confirm('Видалити учня?')) return;
  try { await apiFetch('/users/'+id, { method: 'DELETE' }); await loadUsers(); showToast('Видалено', 'success'); }
  catch (e) { showToast(e.message, 'error'); }
}
