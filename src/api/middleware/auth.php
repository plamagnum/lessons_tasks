<?php
// Middleware для JWT автентифікації
// Реалізація JWT без сторонніх бібліотек (чистий PHP + HMAC-SHA256)

// Отримання секретного ключа з змінних середовища
function getJwtSecret(): string {
    return getenv('JWT_SECRET') ?: 'default_secret_change_in_production';
}

// Кодування в Base64URL (URL-безпечний формат без відступів)
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Декодування з Base64URL
function base64UrlDecode(string $data): string {
    $padding = strlen($data) % 4;
    if ($padding) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

// Генерація JWT токену для користувача
function generateToken(int $userId, string $role): string {
    $header  = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode([
        'user_id' => $userId,
        'role'    => $role,
        'iat'     => time(),
        'exp'     => time() + 86400, // токен дійсний 24 години
    ]));
    $signature = base64UrlEncode(
        hash_hmac('sha256', "$header.$payload", getJwtSecret(), true)
    );
    return "$header.$payload.$signature";
}

// Перевірка та декодування JWT токену
function validateToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $payload, $signature] = $parts;

    // Перевірка підпису — захист від підробки
    $expected = base64UrlEncode(
        hash_hmac('sha256', "$header.$payload", getJwtSecret(), true)
    );
    if (!hash_equals($expected, $signature)) return null;

    $data = json_decode(base64UrlDecode($payload), true);
    if (!is_array($data)) return null;

    // Перевірка терміну дії
    if (isset($data['exp']) && $data['exp'] < time()) return null;

    return $data;
}

// Отримання токену з заголовку Authorization: Bearer <token>
function getTokenFromHeader(): ?string {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
        return trim($m[1]);
    }
    return null;
}

// Обов'язкова автентифікація (для захищених маршрутів)
function requireAuth(): array {
    $token = getTokenFromHeader();
    if (!$token) { sendError('Необхідна автентифікація', 401); exit; }
    $data = validateToken($token);
    if (!$data) { sendError('Недійсний або прострочений токен', 401); exit; }
    return $data;
}

// Перевірка прав адміністратора
function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') { sendError('Недостатньо прав доступу', 403); exit; }
    return $user;
}
