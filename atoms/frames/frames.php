<?php
/**
 * Дорожка кадров: широкий снимок две трети ширины, за ним узкий одна
 * треть, дальше чередование повторяется. На узком экране кадры идут
 * по одному во всю ширину.
 *
 * Листание даёт атом slider, приехавший из библиотеки без изменений —
 * отсюда классы sds-slider__track и sds-slider__card. Корень с классом
 * sds-slider ставит тот, кто включает дорожку: у блока их может быть
 * несколько (по одной на группу), и каждая листается сама.
 *
 * Данные в $frames: [['image' => '…', 'source' => '…'], …].
 * Пустой image означает «место под снимок» — раскладка проверяема до
 * того, как придут мастера.
 */
$frames = $frames ?? [];
$framesLabel = $framesLabel ?? 'Кадры';
$fe = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<ul class="kf-frames sds-slider__track" role="group" tabindex="0" aria-label="<?= $fe($framesLabel) ?>">
	<?php foreach ($frames as $item): ?>
	<li class="kf-frames__frame sds-slider__card rs-anim">
		<?php if (!empty($item['image'])): ?>
		<img src="<?= $fe($item['image']) ?>" alt="" loading="lazy">
		<?php endif; ?>
	</li>
	<?php endforeach; ?>
</ul>
