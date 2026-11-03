<?php
/**
 * Кадры под фильтром: группы снимков, внутри группы — дорожка с
 * листанием. Данные — content/quality.php.
 *
 * Листание даёт атом slider, приехавший из библиотеки без изменений:
 * отсюда классы sds-slider__track и sds-slider__card и класс sds на
 * корне секции — без него не сработают селекторы атома. Вид органов
 * управления свой (atoms/counter): в библиотеке пагинация полосками,
 * на утверждённом сайте — счётчик между стрелками.
 *
 * Уровень заголовка приходит снаружи в $filterPhotosHeadingTag.
 *
 * Фотографий в репозитории пока нет: на сайте лежат уже нарезанные под
 * слот файлы, апскейлить их нельзя. Кадр рисует место под снимок —
 * пропорция та же, поэтому раскладка проверяема до прихода мастеров.
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
<section class="sds kf-filter-photos" data-anim="kf-filter">
	<div class="kf-filter-photos__head">
		<div class="kf-filter-photos__line rs-anim">
			<<?= $hTag ?> class="kf-filter-photos__title"><?= $te($title[0]) ?><?php
				if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>
			<div class="kf-filter-photos__hint" aria-hidden="true">
				<svg viewBox="0 0 36 52" fill="none"><path d="M18 0v50m0 0 15-15M18 50 3 35" stroke="currentColor" stroke-width="1.5"/></svg>
			</div>
		</div>
		<?php if ($lead !== ''): ?>
		<p class="kf-filter-photos__lead rs-anim"><?= $te($lead) ?></p>
		<?php endif; ?>
	</div>

	<div class="kf-filter-photos__filter rs-anim">
		<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/filter/filter.php'; ?>
	</div>

	<?php foreach ($photos as $i => $group): ?>
	<div class="kf-filter-photos__group sds-slider" data-panel="<?= (int)$i ?>">
		<?php
		$frames = $group['items'];
		$framesLabel = 'Кадры: ' . $group['group'];
		include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/frames/frames.php';
		include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/counter/counter.php';
		?>
	</div>
	<?php endforeach; ?>
</section>
