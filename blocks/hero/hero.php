<?php
/**
 * Первый экран: заголовок с пояснением, дорожка кадров, кнопка.
 * Данные — content/hero.php.
 *
 * Уровень заголовка приходит снаружи в $heroHeadingTag. Блок не
 * назначает себе h1 сам: он может стоять не первым и повторяться, а
 * единственный h1 на странице назначает тот, кто её собирает. На
 * главной это именно он — на утверждённом сайте h1 не было ни одного.
 *
 * Кнопка показывается только на узком экране и стоит после кадров:
 * так на утверждённом сайте, где на широком её роль берёт шапка.
 */
$hero = $hero ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/hero.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $heroHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $hero['title'] ?? [];

$frames = $hero['frames'] ?? [];
$framesLabel = 'Кадры производства';
$buttonLabel = $hero['cta'] ?? '';
$buttonWide = true;
?>
<section class="sds kf-hero sds-slider">
	<div class="kf-hero__head">
		<<?= $hTag ?> class="kf-hero__title rs-anim"><?= $te($title[0] ?? '') ?><?php
			if (!empty($title[1])): ?><br><span><?= $te($title[1]) ?></span><?php endif; ?><?php
			if (!empty($title[2])): ?> <?= $te($title[2]) ?><?php endif; ?></<?= $hTag ?>>
		<?php if (!empty($hero['lead'])): ?>
		<p class="kf-hero__lead rs-anim"><?= $te($hero['lead']) ?></p>
		<?php endif; ?>
	</div>

	<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/frames/frames.php'; ?>
	<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/counter/counter.php'; ?>

	<div class="kf-hero__cta">
		<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/button/button.php'; ?>
	</div>
</section>
