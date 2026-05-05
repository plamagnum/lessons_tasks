<?php
// API для учня: список тестів, проходження та результати
// GET  /api/student/tests           — тести призначені учню (через клас або особисто)
// GET  /api/student/tests/{id}      — отримати тест із запитаннями (без правильних відповідей)
// POST /api/student/tests/{id}/submit — здати відповіді та отримати оцінку
// GET  /api/student/results         — всі результати учня

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$sub    = $segments[1] ?? '';     // 'tests' або 'results'
$testId = is_numeric($segments[2] ?? '') ? (int)$segments[2] : null;
$action = $segments[3] ?? '';

$auth = requireAuth();
$userId = (int)$auth['user_id'];

// Визначення class_id поточного учня
$stmt = $db->prepare('SELECT class_id FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userClassId = $stmt->fetchColumn();

if ($method === 'GET' && $sub === 'tests' && !$testId) {
    // Список тестів, призначених учню (через клас або особисто)
    $stmt = $db->prepare(
        'SELECT DISTINCT t.id, t.name, t.question_count, tp.name AS topic_name,
                s.name AS subject_name, ta.assigned_at, ta.deadline,
                (SELECT id FROM test_results WHERE test_id = t.id AND student_id = ?) AS result_id
         FROM tests t
         JOIN topics tp ON t.topic_id = tp.id
         JOIN subjects s ON tp.subject_id = s.id
         JOIN test_assignments ta ON ta.test_id = t.id
         WHERE ta.student_id = ? OR ta.class_id = ?
         ORDER BY ta.assigned_at DESC'
    );
    $stmt->execute([$userId, $userId, $userClassId ?: 0]);
    sendJson($stmt->fetchAll());
}

elseif ($method === 'GET' && $sub === 'tests' && $testId && !$action) {
    // Деталі тесту із запитаннями (правильна відповідь прихована)
    $stmt = $db->prepare('SELECT id, name, question_count FROM tests WHERE id = ?');
    $stmt->execute([$testId]);
    $test = $stmt->fetch();
    if (!$test) sendError('Тест не знайдено', 404);

    // Отримання запитань тесту без поля correct_option
    $stmt = $db->prepare(
        'SELECT q.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d
         FROM test_questions tq
         JOIN questions q ON tq.question_id = q.id
         WHERE tq.test_id = ?
         ORDER BY tq.sort_order'
    );
    $stmt->execute([$testId]);
    $test['questions'] = $stmt->fetchAll();
    sendJson($test);
}

elseif ($method === 'POST' && $sub === 'tests' && $testId && $action === 'submit') {
    // Здача відповідей та підрахунок оцінки
    $answers = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!is_array($answers) || empty($answers)) sendError('Надайте відповіді');

    // Перевірка чи тест не здано раніше
    $stmt = $db->prepare('SELECT id FROM test_results WHERE test_id = ? AND student_id = ?');
    $stmt->execute([$testId, $userId]);
    if ($stmt->fetch()) sendError('Тест вже здано', 409);

    // Отримання правильних відповідей
    $stmt = $db->prepare(
        'SELECT q.id, q.correct_option
         FROM test_questions tq JOIN questions q ON tq.question_id = q.id
         WHERE tq.test_id = ?'
    );
    $stmt->execute([$testId]);
    $correctMap = [];
    foreach ($stmt->fetchAll() as $row) {
        $correctMap[$row['id']] = $row['correct_option'];
    }

    // Підрахунок правильних відповідей
    $correctCount = 0;
    foreach ($answers as $qId => $chosen) {
        if (isset($correctMap[$qId]) && strtolower($chosen) === $correctMap[$qId]) {
            $correctCount++;
        }
    }

    // Обчислення оцінки згідно з правилами
    $stmt = $db->prepare('SELECT question_count FROM tests WHERE id = ?');
    $stmt->execute([$testId]);
    $questionCount = (int)$stmt->fetchColumn();

    if ($questionCount === 24) {
        $grade = $correctCount / 2; // для 24 запитань: ділимо на 2
    } else {
        $grade = $correctCount; // для 12 запитань: оцінка = кількість правильних
    }

    // Пошук assignment_id
    $stmt = $db->prepare(
        'SELECT id FROM test_assignments WHERE test_id = ? AND (student_id = ? OR class_id = ?) LIMIT 1'
    );
    $stmt->execute([$testId, $userId, $userClassId ?: 0]);
    $assignmentId = $stmt->fetchColumn() ?: null;

    // Збереження результату
    $stmt = $db->prepare(
        'INSERT INTO test_results (test_id, student_id, assignment_id, answers, correct_count, grade)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $testId, $userId, $assignmentId,
        json_encode($answers), $correctCount, $grade
    ]);

    // Повертаємо результат разом з правильними відповідями
    sendJson([
        'correct_count'  => $correctCount,
        'total'          => $questionCount,
        'grade'          => $grade,
        'correct_answers'=> $correctMap,
    ]);
}

elseif ($method === 'GET' && $sub === 'results') {
    // Всі результати учня
    $stmt = $db->prepare(
        'SELECT r.*, t.name AS test_name, t.question_count,
                tp.name AS topic_name, s.name AS subject_name
         FROM test_results r
         JOIN tests t ON r.test_id = t.id
         JOIN topics tp ON t.topic_id = tp.id
         JOIN subjects s ON tp.subject_id = s.id
         WHERE r.student_id = ?
         ORDER BY r.completed_at DESC'
    );
    $stmt->execute([$userId]);
    sendJson($stmt->fetchAll());
}

else {
    sendError('Маршрут не знайдено', 404);
}
