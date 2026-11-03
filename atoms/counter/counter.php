<?php
/**
 * Счётчик слайдера: стрелка, номер позиции, стрелка.
 *
 * Кнопки помечены data-sds-prev / data-sds-next — их ищет атом slider,
 * ему всё равно, как они выглядят и где лежат. Поэтому вид здесь свой,
 * а листание остаётся библиотечным.
 *
 * Номер заполняет counter.js. Без скрипта счётчика не видно: листать
 * свайпом можно и так, а неподвижное «01 / 05» только врёт.
 */
?>
<div class="kf-counter">
	<button class="kf-counter__arrow" type="button" data-sds-prev aria-label="Предыдущий кадр">
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12H4m0 0 6-6m-6 6 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>
	<p class="kf-counter__value" data-counter aria-live="off"><span data-counter-current>01</span> / <span data-counter-total>01</span></p>
	<button class="kf-counter__arrow" type="button" data-sds-next aria-label="Следующий кадр">
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12h16m0 0-6-6m6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</button>
</div>
