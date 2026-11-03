<?php
/**
 * Факты о производстве. Данные — content/facts.php.
 *
 * Карточки лежат рядами, а не одной сеткой: на утверждённом сайте
 * ряды разной ширины и разного числа колонок (1 — 3 — 2), и середина
 * строится центрированием ряда, а не позицией каждой карточки.
 * Поэтому ряд здесь единица раскладки, а число карточек в нём
 * объявлено атрибутом — по нему CSS и выбирает сетку.
 *
 * Уровень заголовка приходит снаружи в $factsHeadingTag.
 */
$facts = $factsData ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/facts.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $factsHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $factsTitle ?? $facts['title'];
?>
<section class="sds kf-facts">
	<<?= $hTag ?> class="kf-facts__title rs-anim"><?= $te($title[0]) ?><?php
		if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>

	<div class="kf-facts__plate">
		<?php foreach ($facts['rows'] as $row): ?>
		<div class="kf-facts__row" data-cards="<?= count($row) ?>">
			<?php foreach ($row as $card): ?>
			<div class="kf-facts__card rs-anim">
				<?php if (array_key_exists('image', $card)): ?>
				<div class="kf-facts__pic"<?= empty($card['image']) ? ' aria-hidden="true"' : '' ?>>
					<?php if (!empty($card['image'])): ?>
					<img src="<?= $te($card['image']) ?>" alt="" loading="lazy">
					<?php endif; ?>
				</div>
				<?php else: ?>
				<div class="kf-facts__figure"><?= $te($card['value']) ?></div>
				<?php endif; ?>
				<p class="kf-facts__text"><?= $te($card['text']) ?></p>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>
</section>
