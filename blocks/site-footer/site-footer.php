<?php
/**
 * Подвал: приглашение приехать, четыре группы контактов, карта и
 * служебная строка.
 *
 * Префикс сайтовый по той же причине, что у шапки: вид приведён к
 * утверждённому сайту, а под библиотечным именем подогнанный блок
 * сделал бы diff с библиотекой лживым.
 *
 * Группы контактов однотипны: подпись и дальше либо строки текста,
 * либо ссылки. На сайте почта лежала дважды — внутри «Контактов» для
 * узких экранов и отдельной группой с невидимой подписью для
 * широких, — здесь она одна группа с видимой подписью: дубль ссылки
 * читалка объявляет дважды, а пустая подпись нужна была только для
 * выравнивания сетки.
 *
 * Карта: пока файла или ключа нет, рисуется место под неё той же
 * пропорции.
 *
 * Уровень заголовка приходит снаружи в $footerHeadingTag.
 */
$data = $footer ?? include dirname($_SERVER['DOCUMENT_ROOT']) . '/content/footer.php';
$te = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$hTag = $footerHeadingTag ?? 'h2';
$hTag = in_array($hTag, ['h1', 'h2', 'h3'], true) ? $hTag : 'h2';
$title = $data['title'];
$note = $data['note'] ?? [];
$studio = $note['studio'] ?? [];
?>
<footer class="kf-site-footer">
	<div class="kf-site-footer__content">
		<<?= $hTag ?> class="kf-site-footer__title rs-anim"><?= $te($title[0]) ?><?php
			if (!empty($title[1])): ?><span><?= $te($title[1]) ?></span><?php endif; ?><?php
			if (!empty($title[2])): ?> <?= $te($title[2]) ?><?php endif; ?></<?= $hTag ?>>

		<div class="kf-site-footer__contacts">
			<?php foreach ($data['groups'] as $group): ?>
			<div class="kf-site-footer__group rs-anim">
				<p class="kf-site-footer__label"><?= $te($group['label']) ?></p>
				<div class="kf-site-footer__lines">
					<?php foreach ($group['lines'] ?? [] as $line): ?>
					<span><?= $te($line) ?></span>
					<?php endforeach; ?>
					<?php foreach ($group['links'] ?? [] as $link): ?>
					<a class="kf-site-footer__link" href="<?= $te($link['href']) ?>"><?= $te($link['label']) ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="kf-site-footer__map">
		<?php if (!empty($data['map']['src'])): ?>
		<iframe src="<?= $te($data['map']['src']) ?>" title="Как нас найти" loading="lazy"></iframe>
		<?php endif; ?>
	</div>

	<div class="kf-site-footer__note">
		<div class="kf-site-footer__note-links">
			<p><?= $te($note['copy'] ?? '') ?></p>
			<?php foreach ($note['links'] ?? [] as $link): ?>
			<a href="<?= $te($link['href']) ?>"><?= $te($link['label']) ?></a>
			<?php endforeach; ?>
		</div>
		<?php if (!empty($studio)): ?>
		<p class="kf-site-footer__studio">
			<a href="<?= $te($studio['href']) ?>" rel="noopener">
				<?php if (!empty($studio['logo'])): ?>
				<img src="<?= $te($studio['logo']) ?>" alt="<?= $te($studio['label']) ?>" width="46" height="22" loading="lazy">
				<?php else: ?>
				<?= $te($studio['label']) ?>
				<?php endif; ?>
			</a>
		</p>
		<?php endif; ?>
	</div>
</footer>
