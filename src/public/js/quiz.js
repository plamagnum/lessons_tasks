// quiz.js — Логіка проходження тесту

let quizData      = null; // Дані тесту (запитання)
let quizAnswers   = {};   // Відповіді учня {questionId: 'a'/'b'/'c'/'d'}
let currentQIndex = 0;    // Індекс поточного питання

// Завантаження та початок тесту
async function startQuiz(testId) {
  showSection('quiz');
  try {
    quizData      = await Student.test(testId);
    quizAnswers   = {};
    currentQIndex = 0;
    renderQuestion();
  } catch (e) {
    showToast('Помилка завантаження тесту: ' + e.message, 'error');
    navigate('dashboard');
  }
}

// Відображення поточного питання
function renderQuestion() {
  const q     = quizData.questions[currentQIndex];
  const total = quizData.questions.length;
  const num   = currentQIndex + 1;

  document.getElementById('quizTitle').textContent        = quizData.name;
  document.getElementById('quizProgressText').textContent = `Питання ${num} з ${total}`;
  document.getElementById('progressBar').style.width      = ((num - 1) / total * 100) + '%';
  document.getElementById('questionText').textContent     = q.question_text;
  document.getElementById('quizNav').style.display        = 'none';

  const grid    = document.getElementById('optionsGrid');
  grid.innerHTML = '';

  const letters  = ['а', 'б', 'в', 'г'];
  const optKeys  = ['option_a', 'option_b', 'option_c', 'option_d'];
  const optVals  = ['a', 'b', 'c', 'd'];

  optKeys.forEach((key, i) => {
    const btn = document.createElement('button');
    btn.className = 'option-btn';
    btn.innerHTML = `<span class="option-letter">${letters[i].toUpperCase()}</span> ${q[key]}`;
    btn.addEventListener('click', () => onOptionClick(btn, optVals[i], q, optKeys, optVals));
    grid.appendChild(btn);
  });
}

// Обробник вибору відповіді
function onOptionClick(clickedBtn, chosen, q, optKeys, optVals) {
  // Блокуємо повторний вибір
  document.querySelectorAll('.option-btn').forEach(b => b.disabled = true);

  clickedBtn.classList.add('selected');

  // Запам'ятовуємо відповідь
  quizAnswers[q.id] = chosen;

  // Підсвічуємо вибрану кнопку — покажемо результат після submit
  // (правильні відповіді ми отримаємо тільки при здачі)
  clickedBtn.classList.add('selected');

  // Показуємо кнопку "Далі"
  const navDiv = document.getElementById('quizNav');
  navDiv.style.display = '';
  const nextBtn = document.getElementById('nextBtn');
  const isLast  = currentQIndex === quizData.questions.length - 1;
  nextBtn.textContent = isLast ? '✅ Здати тест' : 'Наступне питання →';

  nextBtn.onclick = async () => {
    if (isLast) {
      await submitQuiz();
    } else {
      currentQIndex++;
      renderQuestion();
    }
  };
}

// Здача тесту та показ результату
async function submitQuiz() {
  showSection('quiz'); // Залишаємо квіз поки вантажимо
  try {
    const result = await Student.submit(quizData.id, quizAnswers);

    // Підсвічуємо результати поточного питання перед показом екрану результатів
    showQuizResult(result);
  } catch (e) {
    showToast('Помилка здачі тесту: ' + e.message, 'error');
  }
}

// Відображення екрану результатів
function showQuizResult(result) {
  showSection('quiz-result');
  document.getElementById('resultGrade').textContent   = result.grade;
  document.getElementById('resultCorrect').textContent = result.correct_count;
  document.getElementById('resultTotal').textContent   = result.total;

  // Огляд відповідей
  const review = document.getElementById('answerReview');
  review.innerHTML = '<h3 class="card-title" style="font-size:1rem;">Аналіз відповідей</h3>';

  const letters  = { a: 'А', b: 'Б', c: 'В', d: 'Г' };

  quizData.questions.forEach(q => {
    const chosen  = quizAnswers[q.id];
    const correct = result.correct_answers[q.id];
    const isOk    = chosen === correct;

    const div = document.createElement('div');
    div.className = 'answer-item ' + (isOk ? 'ok' : 'wrong');
    div.innerHTML = `
      <strong>${q.question_text}</strong><br>
      <span>Ваша відповідь: <b>${letters[chosen] || '—'}</b></span>
      ${!isOk ? ` &nbsp;·&nbsp; Правильна: <b style="color:var(--success)">${letters[correct]}</b>` : ''}
    `;
    review.appendChild(div);
  });
}
