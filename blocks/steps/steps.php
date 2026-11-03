<?php
/**
 * Как оформить заказ: пять шагов, только текст.
 *
 * Список нумерованный (ol), а видимый номер помечен aria-hidden:
 * читалка и так объявляет «1 из 5», и дубль «01, один из пяти» лишний.
 * Сам номер блок считает по порядку, в контенте его нет.
 *
 * Шапка и шаг стоят на одной сетке: номер под заголовком, текст шага
 * под пояснением. Сетка задана переменной блока, чтобы два места не
 * разошлись.
 *
 * Знака «смотри ниже» в этой секции на сайте нет.
 *
 * Уровень заголовка приходит снаружи в $stepsHeadingTag.
 */
$data = $steps ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/steps.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $stepsHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $data['title'];
?>
<section class="sds kf-steps">
	<div class="kf-steps__head rs-anim">
		<<?= $hTag ?> class="kf-steps__title"><?= $te($title[0]) ?><?php
			if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>
		<?php if (!empty($data['note'])): ?>
		<div class="kf-steps__note">
			<?php foreach ($data['note'] as $line): ?>
			<p><?= $te($line) ?></p>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
	<ol class="kf-steps__list">
		<?php foreach ($data['steps'] as $i => $step): ?>
		<li class="kf-steps__step rs-anim">
			<p class="kf-steps__number" aria-hidden="true"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></p>
			<div class="kf-steps__body">
				<p class="kf-steps__name"><?= $te($step['name']) ?></p>
				<p class="kf-steps__text"><?= $te($step['text']) ?></p>
			</div>
		</li>
		<?php endforeach; ?>
	</ol>
</section>
