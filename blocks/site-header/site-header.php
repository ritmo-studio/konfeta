<?php
/**
 * Шапка сайта — снимок библиотечного site-header 1.2 (Figma 2111:337).
 *
 * Блок приехал из библиотеки и носит её имя, поэтому разметка и классы
 * здесь ровно библиотечные: отличие от библиотеки должно быть видно
 * обычным diff, а не прятаться в переименованиях. Значения блок берёт
 * из токенов сайта (tokens.css, набор --sds-*).
 *
 * Отличия снимка от библиотеки — три, все записаны в README:
 * класс JS-признака (`rs-js` вместо `sds-js`: библиотечный base.js в
 * сайт не едет, класс ставит движок под своим именем), кнопка в панели
 * (atoms/button сайта вместо библиотечной) и перевод шаблонов в PHP.
 *
 * Шаблонов в библиотеке три (пункт меню, пункт подменю, пункт
 * мобильной панели), и два из них рекурсивны. Здесь это две
 * локальные функции: заводить три php-файла ради трёх строк разметки
 * дороже, чем держать их рядом.
 *
 * Шапка одна на страницу (uniquePerPage) и position:fixed — первый
 * блок страницы обязан оставить под неё место сверху
 * (--kf-header-space).
 */
$menu = $menu ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/menu.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$logo = $menu['logo'] ?? [];
$phone = $menu['phone'] ?? [];
$items = $menu['items'] ?? [];

/* Пункт подменю: та же панель вторым уровнем, выезжает вправо. */
$drop = function (array $item) use (&$drop, $te) {
	$kids = $item['children'] ?? [];
	echo '<li class="sds-header__drow-wrap">';
	if ($kids) {
		echo '<details class="sds-header__drop"><summary class="sds-header__drow">' . $te($item['label'])
			. '<span class="sds-header__chevron" aria-hidden="true"></span></summary>'
			. '<ul class="sds-header__dropdown sds-header__dropdown--sub">';
		foreach ($kids as $kid) $drop($kid);
		echo '</ul></details>';
	} else {
		echo '<a class="sds-header__drow" href="' . $te($item['href'] ?? '') . '">' . $te($item['label']) . '</a>';
	}
	echo '</li>';
};

/* Пункт мобильной панели: подпункты раскрываются аккордеоном. */
$mrow = function (array $item) use (&$mrow, $te) {
	$kids = $item['children'] ?? [];
	echo '<li class="sds-header__mrow-wrap">';
	if ($kids) {
		echo '<details class="sds-header__macc"><summary class="sds-header__mrow">' . $te($item['label'])
			. '<span class="sds-header__chevron sds-header__chevron--m" aria-hidden="true"></span></summary>'
			. '<ul class="sds-header__msub">';
		foreach ($kids as $kid) $mrow($kid);
		echo '</ul></details>';
	} else {
		echo '<a class="sds-header__mrow" href="' . $te($item['href'] ?? '') . '">' . $te($item['label']) . '</a>';
	}
	echo '</li>';
};
?>
<header class="sds sds-header">
	<div class="sds-header__bar">
		<a class="sds-header__logo" href="<?= $te($logo['href'] ?? '/') ?>"><?php
			if (!empty($logo['src'])): ?><img class="sds-header__logo-img" src="<?= $te($logo['src']) ?>" alt="<?= $te($logo['label'] ?? '') ?>" width="175" height="40" fetchpriority="high" decoding="async"><?php
			else: ?><span class="sds-header__logo-text"><?= $te($logo['label'] ?? '') ?></span><?php
			endif; ?></a>

		<nav class="sds-header__nav" aria-label="Основное меню">
			<ul class="sds-header__menu"><?php foreach ($items as $item):
				$kids = $item['children'] ?? [];
				echo '<li class="sds-header__item">';
				if ($kids) {
					echo '<details class="sds-header__drop" name="sh-nav"><summary class="sds-header__link">' . $te($item['label'])
						. '<span class="sds-header__chevron" aria-hidden="true"></span></summary><ul class="sds-header__dropdown">';
					foreach ($kids as $kid) $drop($kid);
					echo '</ul></details>';
				} else {
					echo '<a class="sds-header__link" href="' . $te($item['href'] ?? '') . '">' . $te($item['label']) . '</a>';
				}
				echo '</li>';
			endforeach; ?></ul>
		</nav>

		<a class="sds-header__phone sds-header__phone--bar" href="tel:<?= $te($phone['href'] ?? '') ?>"><?= $te($phone['label'] ?? '') ?></a>

		<details class="sds-header__toggle">
			<summary class="sds-header__burger" aria-label="Меню"></summary>
			<div class="sds-header__panel">
				<ul class="sds-header__mobile-menu"><?php foreach ($items as $item) $mrow($item); ?></ul>
				<div class="sds-header__foot">
					<a class="sds-header__phone sds-header__phone--panel" href="tel:<?= $te($phone['href'] ?? '') ?>"><?= $te($phone['label'] ?? '') ?></a>
					<?php $buttonLabel = $menu['cta'] ?? ''; $buttonWide = true;
					include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/button/button.php';
					$buttonLabel = ''; $buttonWide = false; ?>
				</div>
			</div>
		</details>
	</div>
</header>
