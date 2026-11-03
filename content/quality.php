<?php
/**
 * Контроль качества: 5 групп, 37 фотографий.
*
 * Картинок в репозитории пока нет: на утверждённом сайте лежат уже
 * нарезанные под слот файлы (640px и 1704px), апскейлить их нельзя.
 * Поэтому здесь сохранены только исходные имена — по ним картиночная
 * итерация подставит мастера через make-images.py. Пустой image
 * означает «место под фотографию», плитка рисует заглушку.
*
 * Раскладка чередуется: широкая плитка 2/3, за ней узкая 1/3 — так на
 * утверждённом сайте, и порядок здесь её и задаёт.
 */
return [
  [
    'group' => 'Форма',
    'items' => [
      ['image' => '', 'source' => 'forma-img-01'],
      ['image' => '', 'source' => 'forma-img-02'],
      ['image' => '', 'source' => 'forma-img-03'],
      ['image' => '', 'source' => 'forma-img-04'],
      ['image' => '', 'source' => 'forma-img-05'],
      ['image' => '', 'source' => 'forma-img-06'],
    ],
  ],
  [
    'group' => 'Оклейка',
    'items' => [
      ['image' => '', 'source' => 'okleika-img-01'],
      ['image' => '', 'source' => 'okleika-img-02'],
      ['image' => '', 'source' => 'okleika-img-03'],
      ['image' => '', 'source' => 'okleika-img-04'],
      ['image' => '', 'source' => 'okleika-img-05'],
      ['image' => '', 'source' => 'okleika-img-06'],
    ],
  ],
  [
    'group' => 'Брендирование и отделка',
    'items' => [
      ['image' => '', 'source' => 'branding-img-01'],
      ['image' => '', 'source' => 'branding-img-02'],
      ['image' => '', 'source' => 'branding-img-03'],
      ['image' => '', 'source' => 'branding-img-04'],
      ['image' => '', 'source' => 'branding-img-05'],
      ['image' => '', 'source' => 'branding-img-06'],
      ['image' => '', 'source' => 'branding-img-07'],
      ['image' => '', 'source' => 'branding-img-08'],
      ['image' => '', 'source' => 'branding-img-09'],
      ['image' => '', 'source' => 'branding-img-10'],
      ['image' => '', 'source' => 'branding-img-11'],
      ['image' => '', 'source' => 'branding-img-12'],
      ['image' => '', 'source' => 'branding-img-13'],
    ],
  ],
  [
    'group' => 'Ложементы и вложения',
    'items' => [
      ['image' => '', 'source' => 'logement-img-01'],
      ['image' => '', 'source' => 'logement-img-02'],
      ['image' => '', 'source' => 'logement-img-03'],
      ['image' => '', 'source' => 'logement-img-04'],
      ['image' => '', 'source' => 'logement-img-05'],
      ['image' => '', 'source' => 'logement-img-06'],
    ],
  ],
  [
    'group' => 'Фурнитура',
    'items' => [
      ['image' => '', 'source' => 'furnitura-img-01'],
      ['image' => '', 'source' => 'furnitura-img-02'],
      ['image' => '', 'source' => 'furnitura-img-03'],
      ['image' => '', 'source' => 'furnitura-img-04'],
      ['image' => '', 'source' => 'furnitura-img-05'],
      ['image' => '', 'source' => 'furnitura-img-06'],
    ],
  ],
];
