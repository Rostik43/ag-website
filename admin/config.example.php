<?php
// Пароль администратора. Сменить пароль можно на странице «Пароль» внутри админки.
const ADMIN_PASSWORD_HASH = 'СЮДА_ХЕШ_ПАРОЛЯ';  // password_hash('пароль', PASSWORD_BCRYPT)

const ROOT = __DIR__ . '/..';
const MAX_SIDE  = 1800;   // длинная сторона полного изображения
const THUMB_SIZE = 720;   // квадратная миниатюра для сеток
const Q_FULL = 85;
const Q_THUMB = 80;

// Наборы данных сайта
const SETS = [
  'projects' => [
    'title' => 'Проекты', 'var' => 'AG_PROJECTS',
    'file' => 'projects-data.js', 'dir' => 'assets/projects',
    'fields' => [
      'name'     => ['Краткое название', 'text', true],
      'full'     => ['Полное наименование объекта', 'textarea', false],
      'place'    => ['Адрес', 'text', false],
      'years'    => ['Годы проектирования', 'text', false],
      'client'   => ['Заказчик', 'text', false],
      'status'   => ['Статус', 'select:Реализован,Строится,Проектирование,Эскизный проект,Мастер-план', false],
      'category' => ['Категория', 'select:Жильё,Общественное', false],
      'region'   => ['Регион', 'select:Ижевск,Регион', false],
      'stages'   => ['Стадии (необязательно)', 'text', false],
    ],
  ],
  'consortium' => [
    'title' => 'Консорциум', 'var' => 'AG_CONSORTIUM',
    'file' => 'consortium-data.js', 'dir' => 'assets/consortium',
    'fields' => [
      'name'   => ['Краткое название', 'text', true],
      'full'   => ['Полное наименование объекта', 'textarea', false],
      'place'  => ['Адрес', 'text', false],
      'years'  => ['Годы проектирования', 'text', false],
      'status' => ['Статус', 'select:Реализован,Строится,Проектирование', false],
      'gap'    => ['Автор проекта (ФИО)', 'text', false],
      'client' => ['Партнёр по консорциуму', 'text', false],
    ],
  ],
  'design' => [
    'title' => 'Дизайн-проекты', 'var' => 'AG_DESIGN',
    'file' => 'design-data.js', 'dir' => 'assets/design',
    'fields' => [
      'name'    => ['Объект', 'text', true],
      'type'    => ['Раздел', 'select:Интерьеры МОП,Благоустройство двора', true],
      'full'    => ['Полное наименование', 'textarea', false],
      'place'   => ['Адрес', 'text', false],
      'years'   => ['Годы', 'text', false],
      'client'  => ['Заказчик', 'text', false],
      'partner' => ['Совместно с', 'text', false],
      'status'  => ['Статус', 'text', false],
    ],
  ],
  'konkurs' => [
    'title' => 'Конкурсы и концепции', 'var' => 'AG_KONKURS',
    'file' => 'konkurs-data.js', 'dir' => 'assets/konkurs',
    'fields' => [
      'name'   => ['Название', 'text', true],
      'full'   => ['Полное наименование', 'textarea', false],
      'year'   => ['Год', 'text', false],
      'status' => ['Результат', 'text', false],
    ],
  ],
];
