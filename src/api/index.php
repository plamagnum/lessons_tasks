<?php
// Головний маршрутизатор API
// Визначає яку папку-обробник викликати залежно від URL

require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/config/database.php';

setCorsHeaders();

// Отримання шляху запиту після /api/
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('#^/api/?#', '', $path);
$segments = array_filter(explode('/', trim($path, '/')));
$segments = array_values($segments);

$resource = $segments[0] ?? '';

// Маршрутизація до відповідного обробника
match ($resource) {
    'auth'     => require __DIR__ . '/auth/index.php',
    'classes'  => require __DIR__ . '/classes/index.php',
    'subjects' => require __DIR__ . '/subjects/index.php',
    'topics'   => require __DIR__ . '/topics/index.php',
    'questions'=> require __DIR__ . '/questions/index.php',
    'tests'    => require __DIR__ . '/tests/index.php',
    'student'  => require __DIR__ . '/student/index.php',
    'users'    => require __DIR__ . '/users/index.php',
    default    => sendError('Маршрут не знайдено', 404),
};
