<?php
/**
 * Сайтовые значения для движка (образец — engine/site.example.php).
 * Единственное место, где сайт называет домен, счётчик и значения
 * по умолчанию для шеринга: в engine/ их нет.
 */
return [
    'prodHost'     => 'konfeta.group',
    'name'         => 'Конфета',
    'url'          => 'https://konfeta.group/',
    'title'        => 'Изготовление картонной упаковки на заказ в СПб',
    'metaImage'    => '',       // картинки шеринга пока нет; появится — https://konfeta.group/assets/img/share.jpg
    'metaImageW'   => 2400,
    'metaImageH'   => 1260,
    'themeColor'   => '#FBF7F3',  // = --kf-color-page
    'preloadFonts' => [],       // долг: preload для pt-root-ui-vf и ambidexter, см. README
    'metrikaId'    => null,     // счётчика у сайта пока нет
];
