<?php
// Управління тестами та їх призначення
// GET    /api/tests              — список тестів (адмін)
// POST   /api/tests              — створити тест (адмін)
// PUT    /api/tests/{id}         — оновити тест (адмін)
// DELETE /api/tests/{id}         — видалити тест (адмін)
// POST   /api/tests/{id}/assign  — призначити тест класу або учню (адмін)
// GET    /api/tests/{id}/assignments — список призначень тесту

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = is_numeric($segments[1] ?? '') ? (int)$segments[1] : null;
$action = $segments[2] ?? '';

if ($method === 'GET' && !$id) {
    requireAdmin();
    $rows = $db->query(
        'SELECT t.*, tp.name AS topic_name, s.name AS subject_name, u.full_name AS created_by_name
         FROM tests t
         JOIN topics tp ON t.topic_id = tp.id
         JOIN subjects s ON tp.subject_id = s.id
         JOIN users u ON t.created_by = u.id
         ORDER BY t.created_at DESC'
    )->fetchAll();
    sendJson($rows);
}

elseif ($method === 'GET' && $id && $action === 'assignments') {
    requireAdmin();
    $stmt = $db->prepare(
        'SELECT ta.*, c.name AS class_name, u.full_name AS student_name
         FROM test_assignments ta
         LEFT JOIN classes c ON ta.class_id = c.id
         LEFT JOIN users u ON ta.student_id = u.id
         WHERE ta.test_id = ?'
    );
    $stmt->execute([$id]);
    sendJson($stmt->fetchAll());
}

elseif ($method === 'POST' && !$id) {
    // Створення нового тесту з випадковим вибором запитань
    $admin = requireAdmin();
    $b     = json_decode(file_get_contents('php://input'), true) ?? [];

    $name           = trim($b['name'] ?? '');
    $topic_id       = (int)($b['topic_id'] ?? 0);
    $question_count = (int)($b['question_count'] ?? 12);

    if (!$name || !$topic_id) sendError('Введіть назву тесту та оберіть тему');
    if (!in_array($question_count, [12, 24])) sendError('Кількість запитань має бути 12 або 24');

    // Перевірка наявності достатньої кількості запитань
    $stmt = $db->prepare('SELECT COUNT(*) FROM questions WHERE topic_id = ?');
    $stmt->execute([$topic_id]);
    $available = (int)$stmt->fetchColumn();

    if ($available < $question_count) {
        sendError("Недостатньо запитань у темі. Наявно: $available, потрібно: $question_count");
    }

    // Збереження тесту
    $stmt = $db->prepare('INSERT INTO tests (name, topic_id, question_count, created_by) VALUES (?,?,?,?)');
    $stmt->execute([$name, $topic_id, $question_count, $admin['user_id']]);
    $testId = (int)$db->lastInsertId();

    // Випадковий вибір запитань
    $stmt = $db->prepare(
        'SELECT id FROM questions WHERE topic_id = ? ORDER BY RAND() LIMIT ' . $question_count
    );
    $stmt->execute([$topic_id]);
    $qIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $ins = $db->prepare('INSERT INTO test_questions (test_id, question_id, sort_order) VALUES (?,?,?)');
    foreach ($qIds as $i => $qId) {
        $ins->execute([$testId, $qId, $i + 1]);
    }

    sendJson(['id' => $testId, 'name' => $name, 'question_count' => $question_count], 201);
}

elseif ($method === 'POST' && $id && $action === 'assign') {
    // Призначення тесту класу або учню
    $admin = requireAdmin();
    $b     = json_decode(file_get_contents('php://input'), true) ?? [];

    $class_id   = !empty($b['class_id'])   ? (int)$b['class_id']   : null;
    $student_id = !empty($b['student_id']) ? (int)$b['student_id'] : null;
    $deadline   = $b['deadline'] ?? null;

    if (!$class_id && !$student_id) sendError('Оберіть клас або учня');

    $stmt = $db->prepare(
        'INSERT INTO test_assignments (test_id, class_id, student_id, assigned_by, deadline)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$id, $class_id, $student_id, $admin['user_id'], $deadline]);
    sendJson(['id' => (int)$db->lastInsertId()], 201);
}

elseif ($method === 'PUT' && $id) {
    requireAdmin();
    $b    = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($b['name'] ?? '');
    if (!$name) sendError('Введіть назву тесту');

    $stmt = $db->prepare('UPDATE tests SET name=? WHERE id=?');
    $stmt->execute([$name, $id]);
    sendJson(['success' => true]);
}

elseif ($method === 'DELETE' && $id) {
    requireAdmin();
    $stmt = $db->prepare('DELETE FROM tests WHERE id=?');
    $stmt->execute([$id]);
    sendJson(['success' => true]);
}

else {
    sendError('Маршрут не знайдено', 404);
}
