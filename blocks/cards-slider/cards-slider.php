<?php
/**
 * Виды упаковки: дорожка широких карточек, половина кадр, половина
 * текст. Данные — content/kinds.php.
 *
 * Листание даёт атом slider из библиотеки (классы sds-slider__track и
 * sds-slider__card, корень sds-slider), органы управления — свой
 * atoms/counter, как в первом экране и галерее.
 *
 * Карточки на утверждённом сайте — ссылки с href="#": разделов под
 * них ещё нет. Поэтому здесь они статичны, как плитки конструкций:
 * появится адрес — карточка станет ссылкой, мёртвых якорей в разметке
 * не остаётся.
 *
 * Уровень заголовка приходит снаружи в $cardsSliderHeadingTag.
 */
$kinds = $kindsData ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/kinds.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $cardsSliderHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $cardsSliderTitle ?? $kinds['title'];
?>
<section class="sds kf-cards-slider sds-slider">
	<<?= $hTag ?> class="kf-cards-slider__title rs-anim"><?= $te($title[0]) ?><?php
		if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>

	<ul class="kf-cards-slider__track sds-slider__track" role="group" tabindex="0" aria-label="<?= $te(implode(' ', $title)) ?>">
		<?php foreach ($kinds['cards'] as $card): ?>
		<li class="kf-cards-slider__card sds-slider__card rs-anim">
			<div class="kf-cards-slider__pic"<?= empty($card['image']) ? ' aria-hidden="true"' : '' ?>>
				<?php if (!empty($card['image'])): ?>
				<img src="<?= $te($card['image']) ?>" alt="" loading="lazy">
				<?php endif; ?>
			</div>
			<div class="kf-cards-slider__body">
				<p class="kf-cards-slider__name"><?= $te($card['title']) ?></p>
				<p class="kf-cards-slider__text"><?= $te($card['text']) ?></p>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>

	<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/counter/counter.php'; ?>
</section>
