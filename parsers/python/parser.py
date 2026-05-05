#!/usr/bin/env python3
"""
parser.py — Парсер запитань для шкільних освітніх сайтів
Використання:
    python parser.py [site_name] [url]

Приклад:
    python parser.py site1 https://example-edu.ua/math/algebra

Результат зберігається у JSON файл у директорії output/

===========================================================
ЯК ДОДАТИ НОВИЙ САЙТ:
1. Скопіюйте один з блоків у словнику SITES (наприклад 'site1')
2. Задайте нову ключову назву (наприклад 'monoschool')
3. Відкрийте сторінку сайту у браузері, натисніть F12
4. Знайдіть HTML блок з запитанням і скопіюйте CSS-селектор
5. Замініть значення в 'selectors' на нові

ЯК ЗМІНИТИ СЕЛЕКТОРИ:
- question:  CSS-селектор блоку тексту запитання
- options:   CSS-селектор всіх блоків варіантів відповіді
- correct:   CSS-селектор правильного варіанту (або атрибут)
- topic_id:  ID теми в БД (встановити вручну перед запуском)
===========================================================
"""

import sys
import json
import os
import re
from datetime import datetime
from pathlib import Path

import requests
from bs4 import BeautifulSoup

# ===========================================================
# КОНФІГУРАЦІЯ САЙТІВ
# Змінюйте лише CSS-селектори у відповідних блоках
# ===========================================================
SITES = {
    # Сайт 1: Освітній портал типу "quiz-блок"
    # Структура: <div class="question-block">
    #              <p class="q-text">Текст запитання</p>
    #              <ul class="answers">
    #                <li class="answer correct">Правильна</li>
    #                <li class="answer">Неправильна</li>
    #              </ul>
    #            </div>
    'site1': {
        'name': 'Освітній портал 1',
        'topic_id': 1,  # ← змінити на потрібний topic_id із вашої БД
        'selectors': {
            # Контейнер одного запитання — змінюйте цей селектор
            'question_block': 'div.question-block',
            # Текст самого запитання всередині блоку
            'question_text': 'p.q-text',
            # Всі варіанти відповідей (отримуємо перші 4)
            'options': 'ul.answers li.answer',
            # Клас, який позначає правильний варіант
            'correct_class': 'correct',
        },
    },

    # Сайт 2: Тест у форматі таблиці
    # Структура: <table class="test-table">
    #              <tr class="question-row">
    #                <td class="question">Текст</td>
    #                <td class="opt opt-correct">А</td>
    #                <td class="opt">Б</td>
    #                <td class="opt">В</td>
    #                <td class="opt">Г</td>
    #              </tr>
    #            </table>
    'site2': {
        'name': 'Освітній портал 2 (таблиця)',
        'topic_id': 1,  # ← змінити на потрібний topic_id
        'selectors': {
            # Рядок таблиці з одним запитанням
            'question_block': 'table.test-table tr.question-row',
            # Клітинка тексту запитання
            'question_text': 'td.question',
            # Всі клітинки варіантів відповідей
            'options': 'td.opt',
            # Клас правильного варіанту
            'correct_class': 'opt-correct',
        },
    },
}

OUTPUT_DIR = Path(__file__).parent / 'output'


def fetch_page(url: str) -> BeautifulSoup | None:
    """Завантажує HTML сторінку і повертає об'єкт BeautifulSoup."""
    headers = {'User-Agent': 'Mozilla/5.0 (compatible; SchoolParser/1.0)'}
    try:
        resp = requests.get(url, headers=headers, timeout=15)
        resp.raise_for_status()
        # Визначаємо кодування сторінки
        resp.encoding = resp.apparent_encoding
        return BeautifulSoup(resp.text, 'lxml')
    except requests.RequestException as e:
        print(f'[ПОМИЛКА] Не вдалося завантажити сторінку: {e}', file=sys.stderr)
        return None


def parse_questions(soup: BeautifulSoup, config: dict) -> list[dict]:
    """
    Парсить запитання зі сторінки згідно з конфігурацією.
    Повертає список словників у форматі для імпорту в БД.
    """
    sel = config['selectors']
    topic_id = config['topic_id']
    questions = []

    # Знаходимо всі блоки з запитаннями
    blocks = soup.select(sel['question_block'])
    print(f'Знайдено блоків: {len(blocks)}')

    for block in blocks:
        # Отримуємо текст запитання
        q_el = block.select_one(sel['question_text'])
        if not q_el:
            continue
        question_text = q_el.get_text(strip=True)
        if not question_text:
            continue

        # Отримуємо варіанти відповідей (перші 4)
        option_els = block.select(sel['options'])[:4]
        if len(option_els) < 4:
            print(f'  [ПРОПУСК] Менше 4 варіантів: «{question_text[:40]}»')
            continue

        options = [el.get_text(strip=True) for el in option_els]

        # Знаходимо правильний варіант за CSS-класом
        correct_option = 'a'
        for i, el in enumerate(option_els):
            if sel['correct_class'] in el.get('class', []):
                correct_option = chr(ord('a') + i)  # 0→'a', 1→'b', 2→'c', 3→'d'
                break

        questions.append({
            'topic_id':      topic_id,
            'question_text': question_text,
            'option_a':      options[0],
            'option_b':      options[1],
            'option_c':      options[2],
            'option_d':      options[3],
            'correct_option': correct_option,
        })

    return questions


def save_to_json(questions: list[dict], site_name: str) -> Path:
    """Зберігає запитання у JSON файл з міткою часу."""
    OUTPUT_DIR.mkdir(exist_ok=True)
    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    filename  = OUTPUT_DIR / f'questions_{site_name}_{timestamp}.json'

    with open(filename, 'w', encoding='utf-8') as f:
        json.dump(questions, f, ensure_ascii=False, indent=2)

    return filename


def main():
    if len(sys.argv) < 3:
        print(f'Використання: python parser.py [site_name] [url]')
        print(f'Доступні сайти: {", ".join(SITES.keys())}')
        sys.exit(1)

    site_name = sys.argv[1]
    url       = sys.argv[2]

    if site_name not in SITES:
        print(f'[ПОМИЛКА] Невідомий сайт: {site_name}')
        print(f'Доступні: {", ".join(SITES.keys())}')
        sys.exit(1)

    config = SITES[site_name]
    print(f'Парсинг сайту: {config["name"]}')
    print(f'URL: {url}')

    soup = fetch_page(url)
    if not soup:
        sys.exit(1)

    questions = parse_questions(soup, config)
    print(f'Знайдено запитань: {len(questions)}')

    if not questions:
        print('[ПОМИЛКА] Запитань не знайдено. Перевірте CSS-селектори у SITES.')
        sys.exit(1)

    output_file = save_to_json(questions, site_name)
    print(f'Збережено у: {output_file}')
    print(f'\nДля імпорту в систему відправте POST /api/questions/import з вмістом файлу.')


if __name__ == '__main__':
    main()
