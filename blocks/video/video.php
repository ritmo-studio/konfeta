<?php
/**
 * Процесс создания упаковки: заголовок со стрелкой и один кадр видео.
 *
 * Знак «смотри ниже» прижат к правому краю: на сайте заголовок
 * растягивается, а знак стоит по правому краю содержимого. Разметка
 * знака — atoms/arrow-down, он же в kf-filter-photos и kf-cards-grid.
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
		<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/arrow-down/arrow-down.php'; ?>
	</div>
	<?php if ($src !== ''): ?>
	<video class="kf-video__player rs-anim" src="<?= $te($src) ?>"<?php
		if ($poster !== ''): ?> poster="<?= $te($poster) ?>"<?php endif; ?>
		preload="metadata" autoplay muted playsinline></video>
	<?php else: ?>
	<div class="kf-video__player rs-anim"></div>
	<?php endif; ?>
</section>
