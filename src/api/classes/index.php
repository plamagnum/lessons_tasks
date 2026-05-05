<?php
// CRUD для класів
// GET    /api/classes        — список класів (публічний)
// POST   /api/classes        — додати клас (адмін)
// PUT    /api/classes/{id}   — оновити клас (адмін)
// DELETE /api/classes/{id}   — видалити клас (адмін)

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($segments[1]) ? (int)$segments[1] : null;

if ($method === 'GET') {
    // Публічний список усіх класів
    $rows = $db->query('SELECT * FROM classes ORDER BY grade, name')->fetchAll();
    sendJson($rows);
}

elseif ($method === 'POST') {
    requireAdmin();
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $name  = trim($body['name'] ?? '');
    $grade = (int)($body['grade'] ?? 0);

    if (!$name || $grade < 5 || $grade > 11) {
        sendError('Введіть назву класу та клас від 5 до 11');
    }
    $stmt = $db->prepare('INSERT INTO classes (name, grade) VALUES (?, ?)');
    $stmt->execute([$name, $grade]);
    sendJson(['id' => (int)$db->lastInsertId(), 'name' => $name, 'grade' => $grade], 201);
}

elseif ($method === 'PUT' && $id) {
    requireAdmin();
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $name  = trim($body['name'] ?? '');
    $grade = (int)($body['grade'] ?? 0);

    if (!$name || $grade < 5 || $grade > 11) {
        sendError('Введіть назву класу та клас від 5 до 11');
    }
    $stmt = $db->prepare('UPDATE classes SET name=?, grade=? WHERE id=?');
    $stmt->execute([$name, $grade, $id]);
    sendJson(['id' => $id, 'name' => $name, 'grade' => $grade]);
}

elseif ($method === 'DELETE' && $id) {
    requireAdmin();
    $stmt = $db->prepare('DELETE FROM classes WHERE id=?');
    $stmt->execute([$id]);
    sendJson(['success' => true]);
}

else {
    sendError('Маршрут не знайдено', 404);
}
