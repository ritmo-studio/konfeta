<?php
/**
 * Упаковка по назначению: ровная сетка кадров с ярлыком направления.
 *
 * Ярлык висит на верхнем крае кадра и скруглён только снизу — так на
 * утверждённом сайте; это не подпись под картинкой, а язычок поверх
 * неё, поэтому кадр и ярлык лежат в одном позиционированном узле.
 *
 * Карточка становится ссылкой только при заданном href: на сайте
 * нажатие открывает модалку заказа, формы отложены, а ссылка в
 * никуда обманывает и клавиатуру, и читалку.
 *
 * Знак «смотри ниже» справа от заголовка — atoms/arrow-down, он же в
 * kf-video и kf-filter-photos.
 *
 * Уровень заголовка приходит снаружи в $cardsGridHeadingTag.
 */
$data = $pack ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/pack.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $cardsGridHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $data['title'];
?>
<section class="sds kf-cards-grid">
	<div class="kf-cards-grid__head rs-anim">
		<<?= $hTag ?> class="kf-cards-grid__title"><?= $te($title[0]) ?><?php
			if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>
		<?php include dirname($_SERVER['DOCUMENT_ROOT']) . '/atoms/arrow-down/arrow-down.php'; ?>
	</div>
	<ul class="kf-cards-grid__grid">
		<?php foreach ($data['items'] as $item):
			$href = (string)($item['href'] ?? '');
			$tag = $href !== '' ? 'a' : 'div';
		?>
		<li class="kf-cards-grid__item rs-anim">
			<<?= $tag ?> class="kf-cards-grid__card"<?php if ($href !== ''): ?> href="<?= $te($href) ?>"<?php endif; ?>>
				<?php if (!empty($item['image'])): ?>
				<img src="<?= $te($item['image']) ?>" alt="">
				<?php endif; ?>
				<p class="kf-cards-grid__label"><?= $te($item['label']) ?></p>
			</<?= $tag ?>>
		</li>
		<?php endforeach; ?>
	</ul>
</section>
