/**
 * Панель шапки: фокус, замок прокрутки, плавность, закрытие по ссылке.
 *
 * Открытие нативное — details уже переключился, скрипту остаётся
 * четыре вещи:
 *
 * 1. inert соседям шапки по body, пока панель открыта: панель fixed
 *    поверх страницы, но содержимое под ней остаётся в табуляции, и
 *    Tab уводил курсор в невидимую страницу. CSS этого не умеет.
 * 2. Класс is-shown на следующем кадре, чтобы переход сыграл от
 *    скрытого состояния.
 * 3. Закрытие: сначала снять класс, дать перейти, и только потом
 *    open=false — иначе содержимое исчезает (display:none у details)
 *    раньше перехода.
 * 4. Клик по любой ссылке в панели закрывает её: переход к якорю
 *    делает сам браузер.
 *
 * inert снимается и по событию toggle, и прямо в closePanel: в WebKit
 * после клика с preventDefault событие toggle не доставляется
 * (проверено — атрибут open снимается, события нет), и страница
 * оставалась inert после закрытия меню. Та же правка нужна
 * библиотечному site-header, она записана в README.
 *
 * Зеркало aria-expanded на summary не ставим: у details это состояние
 * уже выражено атрибутом open, и читалки его объявляют сами.
 */
(() => {
	const toggle = document.querySelector('.kf-site-header__toggle');
	if (!toggle) return;

	const header = toggle.closest('.kf-site-header') || toggle;
	const panel = toggle.querySelector('.kf-site-header__panel');
	const burger = toggle.querySelector('summary.kf-site-header__burger');
	const still = window.matchMedia('(prefers-reduced-motion:reduce)').matches;

	const setInert = (on) => {
		Array.from(document.body.children).forEach((el) => {
			if (el === header || el.contains(header)) return;
			if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE') return;
			if (on) el.setAttribute('inert', '');
			else el.removeAttribute('inert');
		});
	};

	const closePanel = () => {
		if (!toggle.open) return;
		panel.classList.remove('is-shown');
		if (still) { toggle.open = false; setInert(false); return; }
		let done = false;
		const finish = () => {
			if (done) return;
			done = true;
			panel.removeEventListener('transitionend', finish);
			toggle.open = false;
			setInert(false);
		};
		panel.addEventListener('transitionend', finish);
		setTimeout(finish, 300);
	};

	toggle.addEventListener('toggle', () => {
		setInert(toggle.open);
		if (toggle.open) requestAnimationFrame(() => requestAnimationFrame(() => panel.classList.add('is-shown')));
		else panel.classList.remove('is-shown');
	});

	burger.addEventListener('click', (e) => {
		if (toggle.open) { e.preventDefault(); closePanel(); }
	});

	panel.addEventListener('click', (e) => {
		if (e.target.closest('a')) closePanel();
	});
})();
