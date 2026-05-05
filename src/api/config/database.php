<?php
// Конфігурація підключення до бази даних
// Використовується шаблон Singleton для єдиного з'єднання

class Database {
    private static ?PDO $instance = null;

    // Повертає єдиний екземпляр PDO підключення
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = getenv('MYSQL_HOST') ?: 'mysql';
            $db   = getenv('MYSQL_DATABASE') ?: 'school_tests';
            $user = getenv('MYSQL_USER') ?: 'school_user';
            $pass = getenv('MYSQL_PASSWORD') ?: '';

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Помилка підключення до бази даних']);
                exit;
            }
        }
        return self::$instance;
    }
}
