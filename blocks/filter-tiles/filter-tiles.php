<?php
/**
 * Плитки под фильтром: группы конструкций, в каждой — плитка с
 * чертежом и подписью. Данные — content/constructions.php.
 *
 * Блок не знает своей позиции: уровень заголовка приходит снаружи
 * в $filterTilesHeadingTag, по умолчанию h2. Имя переменной с
 * префиксом блока намеренно — страница включает блоки подряд в
 * одной области видимости, короткое $headingTag утекло бы в соседей.
 *
 * Плитки на утверждённом сайте открывали модалку заявки с WordPress.
 * Формы вырезаны до своего приёмника POST, поэтому плитка — div:
 * ссылка с href="#" осталась бы мёртвой разметкой, которую потом
 * выковыривать.
 *
 * Подложка здесь тёмная: на сайте под ней фотография производства,
 * до картиночной итерации — плоский цвет, текст остаётся белым.
 */
$tiles = $constructions ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/constructions.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $filterTilesHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $filterTilesTitle ?? ['Конструкции', 'упаковки'];

$filterItems = array_map(static fn($g) => [
	'label' => $g['group'],
	'count' => count($g['items']),
], $tiles);
$filterLabel = 'Группа конструкций';
?>
<section class="kf-filter-tiles" data-anim="kf-filter">
	<div class="kf-filter-tiles__inner">
		<<?= $hTag ?> class="kf-filter-tiles__title rs-anim"><?= $te($title[0]) ?><?php
			if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>

		<div class="kf-filter-tiles__filter rs-anim">
			<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/filter/filter.php'; ?>
		</div>

		<?php foreach ($tiles as $i => $group): ?>
		<div class="kf-filter-tiles__grid" data-panel="<?= (int)$i ?>">
			<?php foreach ($group['items'] as $item): ?>
			<div class="kf-filter-tiles__tile rs-anim">
				<div class="kf-filter-tiles__drawing"<?= empty($item['image']) ? ' aria-hidden="true"' : '' ?>>
					<?php if (!empty($item['image'])): ?>
					<img src="<?= $te($item['image']) ?>" alt="" loading="lazy">
					<?php endif; ?>
				</div>
				<p class="kf-filter-tiles__caption"><?= $te($item['title']) ?></p>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>
</section>
