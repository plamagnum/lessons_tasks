<?php
// Допоміжні функції для формування JSON відповідей API

// Встановлення CORS заголовків для доступу з будь-якого джерела
function setCorsHeaders(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');

    // Для preflight запитів браузера
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Відправити успішну JSON відповідь
function sendJson(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Відправити відповідь з помилкою
function sendError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Відправити успішну відповідь з обгорткою
function sendSuccess(mixed $data, string $message = 'Успішно'): void {
    sendJson(['success' => true, 'message' => $message, 'data' => $data]);
}
