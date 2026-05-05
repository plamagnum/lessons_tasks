<?php
// API для управління користувачами (тільки адмін)
// GET    /api/users               — список всіх учнів
// PUT    /api/users/{id}/class    — змінити клас учня
// DELETE /api/users/{id}          — видалити учня

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = is_numeric($segments[1] ?? '') ? (int)$segments[1] : null;
$action = $segments[2] ?? '';

if ($method === 'GET' && !$id) {
    // Список всіх користувачів (адмін)
    requireAdmin();
    $rows = $db->query(
        'SELECT u.id, u.username, u.full_name, u.role, u.class_id, c.name AS class_name, u.created_at
         FROM users u LEFT JOIN classes c ON u.class_id = c.id
         ORDER BY u.role, u.full_name'
    )->fetchAll();
    sendJson($rows);
}

elseif ($method === 'PUT' && $id && $action === 'class') {
    // Зміна класу учня
    requireAdmin();
    $b        = json_decode(file_get_contents('php://input'), true) ?? [];
    $class_id = !empty($b['class_id']) ? (int)$b['class_id'] : null;

    $stmt = $db->prepare('UPDATE users SET class_id=? WHERE id=? AND role="student"');
    $stmt->execute([$class_id, $id]);
    sendJson(['success' => true]);
}

elseif ($method === 'DELETE' && $id) {
    // Видалення учня
    requireAdmin();
    $stmt = $db->prepare('DELETE FROM users WHERE id=? AND role="student"');
    $stmt->execute([$id]);
    sendJson(['success' => true]);
}

else {
    sendError('Маршрут не знайдено', 404);
}
