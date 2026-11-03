<?php
/**
 * Шапка сайта: логотип, меню, плашка города, бургер с панелью.
 *
 * Блок собственный, с сайтовым префиксом. Начинал он снимком
 * библиотечного site-header, но вид решили привести к утверждённому
 * сайту — а подогнанный блок под библиотечным именем делает diff
 * лживым, и это единственное, на чём держится модель снимка. Поэтому
 * имя сайтовое, а библиотечная шапка осталась в библиотеке.
 *
 * Из снимка сюда переехали три приёма, они себя оправдали:
 * раскрытие нативным details/summary (работает без JS и не просит id,
 * которые блокам запрещены), замок прокрутки на :has, и inert на
 * соседях шапки, пока панель открыта.
 *
 * Шапка sticky и стоит в потоке, поэтому место под себя у первого
 * блока не просит — только отбивку (kf-hero, верхнее поле).
 *
 * Шапка одна на страницу.
 */
$menu = $menu ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/menu.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$logo = $menu['logo'] ?? [];
$items = $menu['items'] ?? [];
$info = $menu['info'] ?? [];
?>
<header class="kf-site-header">
	<div class="kf-site-header__bar">
		<a class="kf-site-header__logo" href="<?= $te($logo['href'] ?? '/') ?>"><?php
			if (!empty($logo['src'])): ?><img src="<?= $te($logo['src']) ?>" alt="<?= $te($logo['label'] ?? '') ?>" width="177" height="48" fetchpriority="high" decoding="async"><?php
			else: ?><span class="kf-site-header__logo-text"><?= $te($logo['label'] ?? '') ?></span><?php
			endif; ?></a>

		<nav class="kf-site-header__nav" aria-label="Основное меню">
			<ul class="kf-site-header__menu">
				<?php foreach ($items as $item): ?>
				<li><a class="kf-site-header__link" href="<?= $te($item['href']) ?>"><?= $te($item['label']) ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<?php if (!empty($info['lines'])): ?>
		<p class="kf-site-header__info">
			<span><?= implode('<br>', array_map($te, $info['lines'])) ?></span>
			<?php if (!empty($info['icon'])): ?>
			<img src="<?= $te($info['icon']) ?>" alt="" width="54" height="54" loading="lazy">
			<?php endif; ?>
		</p>
		<?php endif; ?>

		<details class="kf-site-header__toggle">
			<summary class="kf-site-header__burger" aria-label="Меню"></summary>
			<div class="kf-site-header__panel">
				<ul class="kf-site-header__panel-menu">
					<?php foreach ($items as $item): ?>
					<li><a class="kf-site-header__panel-link" href="<?= $te($item['href']) ?>"><?= $te($item['label']) ?></a></li>
					<?php endforeach; ?>
				</ul>
				<div class="kf-site-header__panel-foot">
					<?php $buttonLabel = $menu['cta'] ?? ''; $buttonWide = true;
					include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/button/button.php';
					$buttonLabel = ''; $buttonWide = false; ?>
				</div>
			</div>
		</details>
	</div>
</header>
