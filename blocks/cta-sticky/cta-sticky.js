/**
 * Показ прилипшей кнопки на узком экране.
 *
 * Порог — нижняя кромка секции, заданной в data-show-after: пока она
 * не прошла низ экрана, кнопки нет. Условие снято с утверждённого
 * сайта (main.js: bottom <= innerHeight у блока фактов). Селектор
 * приходит из контента, поэтому блок не знает, что стоит на странице.
 *
 * Нет такой секции или атрибута — порог в один экран: кнопка
 * появляется, как только первый экран уехал вверх.
 *
 * На широком экране скрипт не нужен: там кнопка висит всегда, и класс
 * ничего не решает.
 */
(() => {
	const root = document.querySelector('.kf-cta-sticky');
	if (!root) return;

	const narrow = window.matchMedia('(max-width:640px)');
	const after = root.dataset.showAfter ? document.querySelector(root.dataset.showAfter) : null;

	const sync = () => {
		if (!narrow.matches) { root.classList.remove('is-shown'); return; }
		const passed = after
			? after.getBoundingClientRect().bottom <= window.innerHeight
			: window.scrollY >= window.innerHeight;
		root.classList.toggle('is-shown', passed);
	};

	sync();
	window.addEventListener('scroll', sync, { passive: true });
	window.addEventListener('resize', sync);
	narrow.addEventListener('change', sync);
})();
