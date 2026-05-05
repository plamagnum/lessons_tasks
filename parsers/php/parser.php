#!/usr/bin/env php
<?php
/**
 * parser.php — Парсер запитань для шкільних освітніх сайтів
 *
 * Використання:
 *   php parser.php [site_name] [url]
 *
 * Приклад:
 *   php parser.php site1 https://example-edu.ua/math/algebra
 *
 * Результат зберігається у JSON файл у директорії output/
 *
 * ===========================================================
 * ЯК ЗМІНИТИ СЕЛЕКТОРИ:
 * Відкрийте config.php і знайдіть блок потрібного сайту.
 * Змінюйте лише значення XPath у масиві 'selectors'.
 * Інша логіка залишається без змін.
 * ===========================================================
 */

require_once __DIR__ . '/config.php';

// Перевірка аргументів командного рядка
if ($argc < 3) {
    fwrite(STDERR, "Використання: php parser.php [site_name] [url]\n");
    fwrite(STDERR, "Доступні сайти: " . implode(', ', array_keys($config['sites'])) . "\n");
    exit(1);
}

$siteName = $argv[1];
$url      = $argv[2];

if (!isset($config['sites'][$siteName])) {
    fwrite(STDERR, "Невідомий сайт: $siteName\n");
    fwrite(STDERR, "Доступні: " . implode(', ', array_keys($config['sites'])) . "\n");
    exit(1);
}

$siteConfig = $config['sites'][$siteName];
$selectors  = $siteConfig['selectors'];

echo "Парсинг сайту: {$siteConfig['name']}\n";
echo "URL: $url\n";

// Завантаження HTML сторінки
$html = fetchPage($url);
if (!$html) {
    fwrite(STDERR, "Не вдалося завантажити: $url\n");
    exit(1);
}

// Парсинг запитань
$questions = parseQuestions($html, $selectors, $siteConfig['topic_id']);
echo "Знайдено запитань: " . count($questions) . "\n";

if (empty($questions)) {
    fwrite(STDERR, "Запитань не знайдено. Перевірте XPath селектори в config.php\n");
    exit(1);
}

// Збереження у JSON файл
$outputDir = $config['output_dir'];
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$timestamp = date('Ymd_His');
$filename  = "$outputDir/questions_{$siteName}_{$timestamp}.json";
file_put_contents($filename, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Збережено у: $filename\n";
echo "\nДля імпорту в систему відправте POST /api/questions/import з вмістом файлу.\n";

// ============================================================
// Функції парсингу
// ============================================================

/**
 * Завантажує HTML сторінку за URL.
 * Повертає рядок HTML або false при помилці.
 */
function fetchPage(string $url): string|false {
    $ctx = stream_context_create([
        'http' => [
            'method'     => 'GET',
            'timeout'    => 15,
            'user_agent' => 'Mozilla/5.0 (compatible; SchoolParser/1.0)',
            'header'     => "Accept: text/html\r\n",
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);
    return @file_get_contents($url, false, $ctx);
}

/**
 * Парсить запитання зі сторінки за XPath селекторами.
 *
 * @param string $html     HTML сторінки
 * @param array  $sel      Масив XPath селекторів з config.php
 * @param int    $topicId  ID теми для збереження в БД
 * @return array           Масив запитань для імпорту
 */
function parseQuestions(string $html, array $sel, int $topicId): array {
    // Ховаємо попередження від DOMDocument про некоректний HTML
    libxml_use_internal_errors(true);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath     = new DOMXPath($dom);
    $questions = [];

    // Отримуємо всі блоки запитань
    // ↓ XPath береться з config.php → $sel['question_block']
    $blocks = $xpath->query($sel['question_block']);

    foreach ($blocks as $block) {
        // Отримуємо текст запитання
        // ↓ XPath береться з config.php → $sel['question_text']
        $qNodes = $xpath->query($sel['question_text'], $block);
        if (!$qNodes || $qNodes->length === 0) continue;

        $questionText = trim($qNodes->item(0)->textContent);
        if (!$questionText) continue;

        // Отримуємо варіанти відповідей (перші 4)
        // ↓ XPath береться з config.php → $sel['options']
        $optNodes = $xpath->query($sel['options'], $block);
        if (!$optNodes || $optNodes->length < 4) {
            echo "  [ПРОПУСК] Менше 4 варіантів: «" . mb_substr($questionText, 0, 40) . "»\n";
            continue;
        }

        $options = [];
        $correctIndex = 0; // за замовчуванням перший варіант — 'a'

        for ($i = 0; $i < 4; $i++) {
            $node = $optNodes->item($i);
            $options[] = trim($node->textContent);

            // Визначаємо правильний варіант за CSS-класом
            // ↓ Клас береться з config.php → $sel['correct_class']
            $classes = $node->getAttribute('class');
            if (str_contains($classes, $sel['correct_class'])) {
                $correctIndex = $i;
            }
        }

        // Перетворюємо індекс у літеру: 0→'a', 1→'b', 2→'c', 3→'d'
        $correctOption = chr(ord('a') + $correctIndex);

        $questions[] = [
            'topic_id'       => $topicId,
            'question_text'  => $questionText,
            'option_a'       => $options[0],
            'option_b'       => $options[1],
            'option_c'       => $options[2],
            'option_d'       => $options[3],
            'correct_option' => $correctOption,
        ];
    }

    return $questions;
}
