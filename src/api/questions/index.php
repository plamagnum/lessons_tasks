<?php
// CRUD для запитань + масовий імпорт через JSON
// GET    /api/questions?topic_id=N — список запитань
// POST   /api/questions            — додати запитання (адмін)
// POST   /api/questions/import     — імпорт JSON масиву запитань (адмін)
// PUT    /api/questions/{id}       — оновити запитання (адмін)
// DELETE /api/questions/{id}       — видалити запитання (адмін)

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';
$id     = is_numeric($action) ? (int)$action : null;

if ($method === 'GET') {
    $topicId = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : null;

    if ($topicId) {
        $stmt = $db->prepare('SELECT * FROM questions WHERE topic_id = ? ORDER BY id');
        $stmt->execute([$topicId]);
    } else {
        $stmt = $db->query('SELECT q.*, t.name AS topic_name FROM questions q JOIN topics t ON q.topic_id = t.id ORDER BY q.id');
    }
    sendJson($stmt->fetchAll());
}

elseif ($method === 'POST' && $action === 'import') {
    // Масовий імпорт запитань з JSON масиву
    requireAdmin();
    $items = json_decode(file_get_contents('php://input'), true);

    if (!is_array($items)) sendError('Очікується JSON масив запитань');

    $stmt = $db->prepare(
        'INSERT INTO questions (topic_id, question_text, option_a, option_b, option_c, option_d, correct_option)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $count = 0;
    foreach ($items as $item) {
        // Перевірка обов'язкових полів
        if (empty($item['question_text']) || empty($item['topic_id'])
            || empty($item['option_a']) || empty($item['option_b'])
            || empty($item['option_c']) || empty($item['option_d'])
            || empty($item['correct_option'])) {
            continue; // Пропускаємо некоректні записи
        }
        $stmt->execute([
            (int)$item['topic_id'],
            $item['question_text'],
            $item['option_a'],
            $item['option_b'],
            $item['option_c'],
            $item['option_d'],
            strtolower($item['correct_option']),
        ]);
        $count++;
    }
    sendJson(['imported' => $count], 201);
}

elseif ($method === 'POST') {
    // Додавання одного запитання
    requireAdmin();
    $b = json_decode(file_get_contents('php://input'), true) ?? [];

    foreach (['topic_id','question_text','option_a','option_b','option_c','option_d','correct_option'] as $f) {
        if (empty($b[$f])) sendError("Поле $f є обов'язковим");
    }

    $stmt = $db->prepare(
        'INSERT INTO questions (topic_id, question_text, option_a, option_b, option_c, option_d, correct_option)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int)$b['topic_id'], $b['question_text'],
        $b['option_a'], $b['option_b'], $b['option_c'], $b['option_d'],
        strtolower($b['correct_option']),
    ]);
    sendJson(['id' => (int)$db->lastInsertId()], 201);
}

elseif ($method === 'PUT' && $id) {
    requireAdmin();
    $b = json_decode(file_get_contents('php://input'), true) ?? [];
    $stmt = $db->prepare(
        'UPDATE questions SET topic_id=?, question_text=?, option_a=?, option_b=?,
         option_c=?, option_d=?, correct_option=? WHERE id=?'
    );
    $stmt->execute([
        (int)$b['topic_id'], $b['question_text'],
        $b['option_a'], $b['option_b'], $b['option_c'], $b['option_d'],
        strtolower($b['correct_option']), $id,
    ]);
    sendJson(['success' => true]);
}

elseif ($method === 'DELETE' && $id) {
    requireAdmin();
    $stmt = $db->prepare('DELETE FROM questions WHERE id=?');
    $stmt->execute([$id]);
    sendJson(['success' => true]);
}

else {
    sendError('Маршрут не знайдено', 404);
}
