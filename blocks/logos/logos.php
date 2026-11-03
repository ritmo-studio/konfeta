<?php
/**
 * Нам доверяют: сетка плашек с логотипами. При наведении из-под
 * курсора выезжает фотография заказа этого партнёра.
 *
 * Фотография лежит в разметке, а не подгружается по наведению: так на
 * утверждённом сайте, и так она успевает показаться без задержки.
 * Узел с ней помечен aria-hidden — это украшение к логотипу, читалке
 * хватает названия партнёра.
 *
 * Узел рисуется и без файла: пока фотографий нет, по наведению
 * выезжает место под неё. Иначе механику нельзя было бы проверить до
 * переезда картинок — а проверять надо именно её, не картинку.
 *
 * Уровень заголовка приходит снаружи в $logosHeadingTag.
 */
$data = $partners ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/partners.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $logosHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $data['title'];
?>
<section class="sds kf-logos">
	<<?= $hTag ?> class="kf-logos__title rs-anim"><?= $te($title[0]) ?><?php
		if (!empty($title[1])): ?> <span><?= $te($title[1]) ?></span><?php endif; ?></<?= $hTag ?>>
	<ul class="kf-logos__grid">
		<?php foreach ($data['items'] as $item): ?>
		<li class="kf-logos__item">
			<div class="kf-logos__logo">
				<?php if (!empty($item['logo'])): ?>
				<img src="<?= $te($item['logo']) ?>" alt="<?= $te($item['name'] ?? '') ?>" loading="lazy">
				<?php endif; ?>
			</div>
			<div class="kf-logos__shot" aria-hidden="true">
				<div class="kf-logos__frame">
					<?php if (!empty($item['shot'])): ?>
					<img src="<?= $te($item['shot']) ?>" alt="" loading="lazy">
					<?php endif; ?>
				</div>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</section>
