/**
 * Гармошка шагов: на широком экране шаги сложены в одну карточку и
 * раскладываются по мере прокрутки, а при прокрутке вверх — складываются
 * обратно.
 *
 * Механика снята с утверждённого сайта (main.js, activateStepsCards):
 * шаг сдвинут вверх на сумму высот предыдущих, множитель сдвига идёт от
 * 1 к 0 на отрезке «верх списка дошёл до середины окна» → «низ списка
 * дошёл до низа окна». Там это GSAP ScrollTrigger со scrub, здесь —
 * обработчик scroll: тянуть GSAP ради пяти translateY незачем, а
 * обратимость даёт сам пересчёт от позиции окна, а не анимация.
 *
 * Считаем прямо в обработчике, без requestAnimationFrame: пять записей
 * transform — дешевле, чем кадровая очередь, и состояние всегда
 * соответствует настоящей позиции прокрутки (её доводит Lenis).
 *
 * Место в потоке у сложенных шагов остаётся — как на сайте: сдвиг
 * трансформом, высоту списка он не меняет, поэтому измерения не врут.
 *
 * Без JS и на узком экране шаги просто лежат списком: начального
 * состояния в CSS нет, его ставит только этот скрипт.
 */
(() => {
	const section = document.querySelector('.kf-steps');
	if (!section) return;

	const list = section.querySelector('.kf-steps__list');
	const steps = Array.from(section.querySelectorAll('.kf-steps__step'));
	if (!list || steps.length < 2) return;

	const wide = window.matchMedia('(min-width:641px)');
	const still = window.matchMedia('(prefers-reduced-motion:reduce)');

	let offsets = [];

	const measure = () => {
		let sum = 0;
		offsets = steps.map((step) => {
			const own = sum;
			sum += step.getBoundingClientRect().height;
			return own;
		});
	};

	const apply = () => {
		if (!wide.matches || still.matches) {
			steps.forEach((step) => { step.style.transform = ''; });
			return;
		}

		const rect = list.getBoundingClientRect();
		const top = rect.top + window.scrollY;
		const start = top - window.innerHeight / 2;
		const end = top + rect.height - window.innerHeight;
		const progress = end > start ? (window.scrollY - start) / (end - start) : 1;
		const folded = 1 - Math.min(1, Math.max(0, progress));

		steps.forEach((step, i) => {
			step.style.transform = folded ? `translateY(${-offsets[i] * folded}px)` : '';
		});
	};

	const sync = () => { measure(); apply(); };

	sync();
	/* Высоты шагов зависят от шрифта: до его загрузки они меньше, и
	   сложенная гармошка разъехалась бы на несколько пикселей. */
	if (document.fonts && document.fonts.ready) document.fonts.ready.then(sync);
	window.addEventListener('scroll', apply, { passive: true });
	window.addEventListener('resize', sync);
	wide.addEventListener('change', sync);
})();
