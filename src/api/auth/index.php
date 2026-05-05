<?php
// Обробник автентифікації: реєстрація, вхід, профіль
// POST /api/auth/register — реєстрація учня
// POST /api/auth/login    — вхід у систему
// GET  /api/auth/me       — дані поточного користувача

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $segments[1] ?? '';

if ($method === 'POST' && $action === 'register') {
    // Реєстрація нового учня
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $username  = trim($body['username'] ?? '');
    $password  = trim($body['password'] ?? '');
    $full_name = trim($body['full_name'] ?? '');
    $class_id  = !empty($body['class_id']) ? (int)$body['class_id'] : null;

    if (!$username || !$password || !$full_name) {
        sendError('Логін, пароль і повне ім\'я є обов\'язковими');
    }

    // Перевірка унікальності логіну
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        sendError('Такий логін вже існує');
    }

    // Збереження користувача з хешованим паролем
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        'INSERT INTO users (username, password_hash, full_name, class_id, role) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([$username, $hash, $full_name, $class_id, 'student']);
    $userId = (int)$db->lastInsertId();

    $token = generateToken($userId, 'student');
    sendJson(['token' => $token, 'role' => 'student', 'full_name' => $full_name], 201);
}

elseif ($method === 'POST' && $action === 'login') {
    // Вхід у систему
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = trim($body['username'] ?? '');
    $password = trim($body['password'] ?? '');

    if (!$username || !$password) {
        sendError('Введіть логін і пароль');
    }

    $stmt = $db->prepare('SELECT id, password_hash, role, full_name FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendError('Неправильний логін або пароль', 401);
    }

    $token = generateToken((int)$user['id'], $user['role']);
    sendJson([
        'token'     => $token,
        'role'      => $user['role'],
        'full_name' => $user['full_name'],
        'user_id'   => (int)$user['id'],
    ]);
}

elseif ($method === 'GET' && $action === 'me') {
    // Дані поточного авторизованого користувача
    $auth = requireAuth();
    $stmt = $db->prepare(
        'SELECT u.id, u.username, u.full_name, u.role, u.class_id, c.name AS class_name
         FROM users u LEFT JOIN classes c ON u.class_id = c.id
         WHERE u.id = ?'
    );
    $stmt->execute([$auth['user_id']]);
    $user = $stmt->fetch();
    if (!$user) sendError('Користувача не знайдено', 404);
    sendJson($user);
}

else {
    sendError('Маршрут не знайдено', 404);
}
