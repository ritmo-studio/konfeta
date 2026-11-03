<?php
/**
 * Фотографии под фильтром: группы снимков, раскладка чередуется —
 * широкий кадр две трети ширины, за ним узкий одна треть.
 * Данные — content/quality.php.
 *
 * Уровень заголовка приходит снаружи в $filterPhotosHeadingTag.
 *
 * Фотографий в репозитории пока нет: на утверждённом сайте лежат уже
 * нарезанные под слот файлы, апскейлить их нельзя. Плитка рисует
 * место под кадр — пропорция та же, поэтому раскладка проверяема
 * до того, как придут мастера.
 */
$photos = $quality ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/quality.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $filterPhotosHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $filterPhotosTitle ?? ['Контроль', 'качества'];
$lead  = $filterPhotosLead ?? '';

$filterItems = array_map(static fn($g) => ['label' => $g['group']], $photos);
$filterLabel = 'Что проверяем';
?>
<section class="kf-filter-photos" data-anim="kf-filter">
	<div class="kf-filter-photos__head">
		<<?= $hTag ?> class="kf-filter-photos__title rs-anim"><?= $te($title[0]) ?><?php
			if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>
		<?php if ($lead !== ''): ?>
		<p class="kf-filter-photos__lead rs-anim"><?= $te($lead) ?></p>
		<?php endif; ?>
	</div>

	<div class="kf-filter-photos__filter rs-anim">
		<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/filter/filter.php'; ?>
	</div>

	<?php foreach ($photos as $i => $group): ?>
	<div class="kf-filter-photos__grid" data-panel="<?= (int)$i ?>">
		<?php foreach ($group['items'] as $item): ?>
		<div class="kf-filter-photos__frame rs-anim">
			<?php if (!empty($item['image'])): ?>
			<img src="<?= $te($item['image']) ?>" alt="" loading="lazy">
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>
</section>
