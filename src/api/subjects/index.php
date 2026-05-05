<?php
// CRUD для предметів
// GET    /api/subjects        — список предметів
// POST   /api/subjects        — додати предмет (адмін)
// PUT    /api/subjects/{id}   — оновити предмет (адмін)
// DELETE /api/subjects/{id}   — видалити предмет (адмін)

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($segments[1]) ? (int)$segments[1] : null;

if ($method === 'GET') {
    $rows = $db->query('SELECT * FROM subjects ORDER BY name')->fetchAll();
    sendJson($rows);
}

elseif ($method === 'POST') {
    requireAdmin();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($body['name'] ?? '');
    if (!$name) sendError('Введіть назву предмету');

    $stmt = $db->prepare('INSERT INTO subjects (name) VALUES (?)');
    $stmt->execute([$name]);
    sendJson(['id' => (int)$db->lastInsertId(), 'name' => $name], 201);
}

elseif ($method === 'PUT' && $id) {
    requireAdmin();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($body['name'] ?? '');
    if (!$name) sendError('Введіть назву предмету');

    $stmt = $db->prepare('UPDATE subjects SET name=? WHERE id=?');
    $stmt->execute([$name, $id]);
    sendJson(['id' => $id, 'name' => $name]);
}

elseif ($method === 'DELETE' && $id) {
    requireAdmin();
    $stmt = $db->prepare('DELETE FROM subjects WHERE id=?');
    $stmt->execute([$id]);
    sendJson(['success' => true]);
}

else {
    sendError('Маршрут не знайдено', 404);
}
