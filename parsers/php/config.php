<?php
/**
 * config.php — Конфігурація парсера запитань
 *
 * ===========================================================
 * ЯК ДОДАТИ НОВИЙ САЙТ:
 * 1. Скопіюйте один з блоків у масиві $config['sites']
 * 2. Задайте нову ключову назву (наприклад 'monoschool')
 * 3. Відкрийте сторінку сайту у браузері, натисніть F12
 * 4. Знайдіть XPath до потрібних елементів
 * 5. Замінить значення в 'selectors' на нові
 *
 * ЯК ЗМІНИТИ СЕЛЕКТОРИ:
 * - question_block: XPath до контейнера одного запитання
 * - question_text:  XPath до тексту запитання (відносно question_block)
 * - options:        XPath до варіантів відповідей (відносно question_block)
 * - correct_attr:   атрибут або клас, що позначає правильну відповідь
 * ===========================================================
 */

$config = [
    // Директорія для збереження JSON файлів
    'output_dir' => __DIR__ . '/output',

    // Список підтримуваних сайтів
    'sites' => [

        // Сайт 1: Освітній портал — блочна структура запитань
        // HTML структура:
        //   <div class="question-block">
        //     <p class="q-text">Текст запитання</p>
        //     <ul class="answers">
        //       <li class="answer correct">Варіант А (правильний)</li>
        //       <li class="answer">Варіант Б</li>
        //       <li class="answer">Варіант В</li>
        //       <li class="answer">Варіант Г</li>
        //     </ul>
        //   </div>
        'site1' => [
            'name'     => 'Освітній портал 1',
            'topic_id' => 1, // ← замінити на ID теми у вашій БД
            'selectors' => [
                // XPath контейнера одного запитання — ЗМІНЮВАТИ ЦЕЙ РЯДОК
                'question_block' => '//div[contains(@class,"question-block")]',
                // XPath тексту запитання (відносно question_block)
                './/p[contains(@class,"q-text")]',
                // XPath варіантів відповідей (відносно question_block)
                'options'         => './/ul[contains(@class,"answers")]/li[contains(@class,"answer")]',
                // Клас HTML-елемента правильної відповіді
                'correct_class'   => 'correct',
            ],
        ],

        // Сайт 2: Тест у форматі таблиці
        // HTML структура:
        //   <table class="test-table">
        //     <tr class="question-row">
        //       <td class="question">Текст запитання</td>
        //       <td class="opt opt-correct">Варіант А</td>
        //       <td class="opt">Варіант Б</td>
        //       <td class="opt">Варіант В</td>
        //       <td class="opt">Варіант Г</td>
        //     </tr>
        //   </table>
        'site2' => [
            'name'     => 'Освітній портал 2 (таблиця)',
            'topic_id' => 1, // ← замінити на ID теми у вашій БД
            'selectors' => [
                // XPath рядка таблиці з запитанням — ЗМІНЮВАТИ ЦЕЙ РЯДОК
                'question_block'  => '//table[contains(@class,"test-table")]//tr[contains(@class,"question-row")]',
                // XPath клітинки тексту запитання
                'question_text'   => './/td[contains(@class,"question")]',
                // XPath клітинок варіантів відповідей
                'options'         => './/td[contains(@class,"opt")]',
                // Клас правильного варіанту
                'correct_class'   => 'opt-correct',
            ],
        ],
    ],
];
