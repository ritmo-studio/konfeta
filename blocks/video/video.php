<?php
/**
 * Процесс создания упаковки: заголовок со стрелкой и один кадр видео.
 *
 * Стрелка та же, что в kf-filter-photos, — на утверждённом сайте это
 * один и тот же знак «смотри ниже» (36 × 52, тонкая обводка). В атом
 * она не вынесена: два вхождения на страницу, а разметка — один svg.
 *
 * Видео повторяет поведение сайта: без звука, без органов управления,
 * проигрывается один раз. Повтора (loop) на сайте нет — добавлять его
 * самим нельзя, это решение заказчика. preload метаданными: кадр на
 * 50 секунд не нужен в сети до того, как секция покажется.
 *
 * Уровень заголовка приходит снаружи в $videoHeadingTag.
 */
$data = $video ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/video.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $videoHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $data['title'];
$src = (string)($data['src'] ?? '');
$poster = (string)($data['poster'] ?? '');
?>
<section class="sds kf-video">
	<div class="kf-video__head rs-anim">
		<<?= $hTag ?> class="kf-video__title"><?= $te($title[0]) ?><?php
			if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>
		<div class="kf-video__hint" aria-hidden="true">
			<svg viewBox="0 0 36 52" fill="none"><path d="M18 0v50m0 0 15-15M18 50 3 35" stroke="currentColor" stroke-width="1.5"/></svg>
		</div>
	</div>
	<?php if ($src !== ''): ?>
	<video class="kf-video__player rs-anim" src="<?= $te($src) ?>"<?php
		if ($poster !== ''): ?> poster="<?= $te($poster) ?>"<?php endif; ?>
		preload="metadata" autoplay muted playsinline></video>
	<?php else: ?>
	<div class="kf-video__player rs-anim"></div>
	<?php endif; ?>
</section>
