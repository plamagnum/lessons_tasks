-- ============================================================
-- Ініціалізація бази даних: Шкільна система тестування
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Таблиця класів (5-А, 5-Б, ... 11-Б)
CREATE TABLE IF NOT EXISTS classes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(20) NOT NULL COMMENT 'Назва класу, напр. 10-А',
    grade      TINYINT NOT NULL COMMENT 'Номер класу від 5 до 11',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця користувачів (учні та адміни)
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    class_id      INT NULL REFERENCES classes(id) ON DELETE SET NULL,
    role          ENUM('admin','student') NOT NULL DEFAULT 'student',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця предметів (Математика, Фізика, Хімія...)
CREATE TABLE IF NOT EXISTS subjects (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця тем (тема належить до предмету)
CREATE TABLE IF NOT EXISTS topics (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL REFERENCES subjects(id) ON DELETE CASCADE,
    name       VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця запитань (4 варіанти відповідей)
CREATE TABLE IF NOT EXISTS questions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    topic_id       INT NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
    question_text  TEXT NOT NULL,
    option_a       VARCHAR(500) NOT NULL,
    option_b       VARCHAR(500) NOT NULL,
    option_c       VARCHAR(500) NOT NULL,
    option_d       VARCHAR(500) NOT NULL,
    correct_option ENUM('a','b','c','d') NOT NULL COMMENT 'Правильна відповідь',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця тестів (12 або 24 запитання)
CREATE TABLE IF NOT EXISTS tests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(200) NOT NULL,
    topic_id       INT NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
    question_count TINYINT NOT NULL DEFAULT 12 COMMENT '12 або 24 запитання',
    created_by     INT NOT NULL REFERENCES users(id),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця запитань у тесті (які запитання потрапили до тесту)
CREATE TABLE IF NOT EXISTS test_questions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    test_id     INT NOT NULL REFERENCES tests(id) ON DELETE CASCADE,
    question_id INT NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
    sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця призначень тестів (класу або окремому учню)
CREATE TABLE IF NOT EXISTS test_assignments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    test_id     INT NOT NULL REFERENCES tests(id) ON DELETE CASCADE,
    class_id    INT NULL REFERENCES classes(id) ON DELETE CASCADE COMMENT 'NULL якщо індивідуально',
    student_id  INT NULL REFERENCES users(id) ON DELETE CASCADE COMMENT 'NULL якщо всьому класу',
    assigned_by INT NOT NULL REFERENCES users(id),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблиця результатів тестів
CREATE TABLE IF NOT EXISTS test_results (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    test_id       INT NOT NULL REFERENCES tests(id) ON DELETE CASCADE,
    student_id    INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    assignment_id INT NULL REFERENCES test_assignments(id) ON DELETE SET NULL,
    answers       JSON NOT NULL COMMENT 'JSON {question_id: chosen_option}',
    correct_count TINYINT NOT NULL,
    grade         DECIMAL(4,1) NOT NULL COMMENT 'Оцінка: 12 пит = correct, 24 пит = correct/2',
    completed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Початкові дані
-- ============================================================

-- Адміністратор (пароль: admin123)
INSERT INTO users (username, password_hash, full_name, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Адміністратор', 'admin');

-- Класи
INSERT INTO classes (name, grade) VALUES
('10-А', 10), ('10-Б', 10), ('11-А', 11), ('5-А', 5), ('9-Б', 9);

-- Предмети
INSERT INTO subjects (name) VALUES ('Математика'), ('Фізика'), ('Хімія'), ('Українська мова');

-- Теми
INSERT INTO topics (subject_id, name) VALUES
(1, 'Алгебра: квадратні рівняння'),
(1, 'Геометрія: трикутники'),
(2, 'Механіка: закони Ньютона'),
(2, 'Електрика: сила струму');

-- Запитання (тема 1 — Алгебра)
INSERT INTO questions (topic_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
(1, 'Яке з рівнянь є квадратним?', 'x + 5 = 0', 'x² + 3x - 4 = 0', '2x³ = 8', '5/x = 2', 'b'),
(1, 'Дискримінант рівняння x² - 4x + 4 = 0 дорівнює:', '0', '4', '8', '16', 'a'),
(1, 'Корені рівняння x² - 5x + 6 = 0:', 'x=1, x=6', 'x=2, x=3', 'x=-2, x=-3', 'x=1, x=-6', 'b'),
(1, 'За формулою дискримінанта D = b² - 4ac. Якщо D > 0, то:', 'Немає коренів', 'Один корінь', 'Два різні корені', 'Нескінченно багато коренів', 'c'),
(1, 'Розв\'яжіть: x² = 9', 'x = 3', 'x = -3', 'x = ±3', 'Немає коренів', 'c'),
(1, 'Яка формула для знаходження коренів квадратного рівняння?', 'x = -b/2a', 'x = (-b ± √D) / 2a', 'x = D/2a', 'x = b/2a', 'b'),
(1, 'Добуток коренів квадратного рівняння ax²+bx+c=0 за формулою Вієта:', 'c/a', '-b/a', 'b/a', '-c/a', 'a'),
(1, 'Сума коренів квадратного рівняння ax²+bx+c=0 за формулою Вієта:', 'c/a', '-b/a', 'b/a', 'a/c', 'b'),
(1, 'Рівняння x² + 1 = 0 має:', 'Два дійсні корені', 'Один дійсний корінь', 'Немає дійсних коренів', 'Нескінченно багато коренів', 'c'),
(1, 'Повне квадратне рівняння — це:', 'ax² = 0', 'ax² + c = 0', 'ax² + bx = 0', 'ax² + bx + c = 0, де a,b,c ≠ 0', 'd'),
(1, 'Якщо D = 0, квадратне рівняння має:', 'Два різні корені', 'Один корінь (кратний)', 'Немає коренів', 'Три корені', 'b'),
(1, 'Зведене квадратне рівняння — це рівняння виду:', 'ax² + bx + c = 0', 'x² + px + q = 0', 'bx + c = 0', 'ax² = 0', 'b'),
(1, 'Коефіцієнт a у рівнянні 3x² - 2x + 1 = 0 дорівнює:', '1', '-2', '3', '-1', 'c'),
(1, 'Корінь рівняння 2x² - 8 = 0:', 'x = 4', 'x = ±2', 'x = 2', 'x = -4', 'b'),
(1, 'Дискримінант рівняння x² + 2x + 5 = 0:', '-16', '24', '-16', '16', 'a'),
(1, 'Яка умова існування двох різних коренів?', 'D = 0', 'D < 0', 'D > 0', 'D ≥ 0', 'c'),
(1, 'Формула скороченого множення: (a+b)² =', 'a² + b²', 'a² - 2ab + b²', 'a² + 2ab + b²', '2a² + 2b²', 'c'),
(1, 'Рівняння x² - 7x + 12 = 0 має корені:', 'x=3, x=4', 'x=2, x=6', 'x=1, x=12', 'x=-3, x=-4', 'a'),
(1, 'Неповне квадратне рівняння виду ax²+c=0 розв''язується:', 'Формулою дискримінанта', 'Виражаючи x² = -c/a', 'Формулою Вієта', 'Методом підстановки', 'b'),
(1, 'При розв''язанні рівняння x² - 9 = 0 отримаємо:', 'x = 3', 'x = 9', 'x = ±3', 'x = ±9', 'c'),
(1, 'Яке рівняння немає коренів у дійсних числах?', 'x² - 4 = 0', 'x² + 4 = 0', 'x² = 0', 'x² - 1 = 0', 'b'),
(1, 'Для ax² + bx + c = 0, якщо a = 1, b = -6, c = 9, то корінь:', 'x = -3', 'x = 3', 'x = ±3', 'x = 6', 'b'),
(1, 'Бі-квадратне рівняння має вигляд:', 'ax² + bx + c = 0', 'ax⁴ + bx² + c = 0', 'ax³ + bx = 0', 'ax + b = 0', 'b'),
(1, 'Дискримінант рівняння 2x² - 3x + 1 = 0:', '1', '9', '-1', '17', 'a'),

-- Запитання (тема 3 — Механіка)
(3, 'Перший закон Ньютона стверджує:', 'F = ma', 'Тіло рухається рівномірно без сили', 'Дія дорівнює протидії', 'a = F/m', 'b'),
(3, 'Другий закон Ньютона: F = ?', 'mv', 'ma', 'm/a', 'v/t', 'b'),
(3, 'Одиниця вимірювання сили в СІ:', 'Паскаль', 'Джоуль', 'Ньютон', 'Ват', 'c'),
(3, 'Третій закон Ньютона:', 'F = ma', 'Тіло рухається по інерції', 'Сила дії = силі протидії', 'a = v/t', 'c');
