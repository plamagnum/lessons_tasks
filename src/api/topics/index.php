<?php
// CRUD для тем
// GET    /api/topics?subject_id=N — список тем (можна фільтрувати за предметом)
// POST   /api/topics              — додати тему (адмін)
// PUT    /api/topics/{id}         — оновити тему (адмін)
// DELETE /api/topics/{id}         — видалити тему (адмін)

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($segments[1]) ? (int)$segments[1] : null;

if ($method === 'GET') {
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;

    if ($subjectId) {
        // Теми конкретного предмету
        $stmt = $db->prepare(
            'SELECT t.*, s.name AS subject_name FROM topics t
             JOIN subjects s ON t.subject_id = s.id
             WHERE t.subject_id = ? ORDER BY t.name'
        );
        $stmt->execute([$subjectId]);
    } else {
        // Всі теми з назвою предмету
        $stmt = $db->query(
            'SELECT t.*, s.name AS subject_name FROM topics t
             JOIN subjects s ON t.subject_id = s.id ORDER BY s.name, t.name'
        );
    }
    sendJson($stmt->fetchAll());
}

elseif ($method === 'POST') {
    requireAdmin();
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $name       = trim($body['name'] ?? '');
    $subject_id = (int)($body['subject_id'] ?? 0);

    if (!$name || !$subject_id) sendError('Введіть назву теми та оберіть предмет');

    $stmt = $db->prepare('INSERT INTO topics (subject_id, name) VALUES (?, ?)');
    $stmt->execute([$subject_id, $name]);
    sendJson(['id' => (int)$db->lastInsertId(), 'subject_id' => $subject_id, 'name' => $name], 201);
}

elseif ($method === 'PUT' && $id) {
    requireAdmin();
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $name       = trim($body['name'] ?? '');
    $subject_id = (int)($body['subject_id'] ?? 0);

    if (!$name || !$subject_id) sendError('Введіть назву теми та оберіть предмет');

    $stmt = $db->prepare('UPDATE topics SET name=?, subject_id=? WHERE id=?');
    $stmt->execute([$name, $subject_id, $id]);
    sendJson(['id' => $id, 'subject_id' => $subject_id, 'name' => $name]);
}

elseif ($method === 'DELETE' && $id) {
    requireAdmin();
    $stmt = $db->prepare('DELETE FROM topics WHERE id=?');
    $stmt->execute([$id]);
    sendJson(['success' => true]);
}

else {
    sendError('Маршрут не знайдено', 404);
}
