/* Фильтр группами: кнопки и список переключают панели внутри блока.
   Обработчик вешается на корень блока — он видит и фильтр, и панели,
   поэтому одна и та же механика работает в двух разных блоках.

   Панели в разметке видимы: пока JS не инициализировал блок, человек
   видит все группы подряд, а не пустое место. Прячет их этот код. */
(function () {
	if (!window.RS) return;

	window.RS.anim('kf-filter', function (root) {
		var tabs = [].slice.call(root.querySelectorAll('[data-group]'));
		var panels = [].slice.call(root.querySelectorAll('[data-panel]'));
		var select = root.querySelector('[data-filter-select]');
		if (!panels.length) return;

		function show(index) {
			panels.forEach(function (p, i) { p.hidden = (i !== index); });
			tabs.forEach(function (t, i) {
				var on = (i === index);
				t.classList.toggle('is-active', on);
				t.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			if (select && select.selectedIndex !== index) select.selectedIndex = index;

			/* Панель была скрыта: у скрытой дорожки нет ни ширины, ни
			   прокрутки, поэтому слайдер внутри пересчитывается только
			   сейчас. resize — для атома slider, своего события он не
			   слушает; kf:shown — для счётчика. */
			var shown = panels[index];
			if (shown) {
				shown.dispatchEvent(new Event('kf:shown'));
				window.dispatchEvent(new Event('resize'));
			}
		}

		tabs.forEach(function (t, i) {
			t.addEventListener('click', function () { show(i); });
		});
		if (select) {
			select.addEventListener('change', function () { show(select.selectedIndex); });
		}

		/* Порции внутри панели: сетка с data-limited показывает по N
		   плиток, остальное открывает кнопка. Число в подписи — сколько
		   порций осталось, а не сколько плиток: так в макете. */
		function limit(panel) {
			var grid = panel.querySelector('[data-limited]');
			var more = panel.querySelector('[data-more]');
			if (!grid || !more) return;
			var step = parseInt(grid.getAttribute('data-limited'), 10) || 6;
			var tiles = [].slice.call(grid.children);
			var shownCount = Math.min(step, tiles.length);

			function render() {
				tiles.forEach(function (t, i) { t.hidden = (i >= shownCount); });
				var rest = tiles.length - shownCount;
				if (rest > 0) {
					more.setAttribute('data-more-rest', String(rest));
					var box = more.querySelector('[data-more-count]');
					if (box) box.textContent = '(' + Math.ceil(rest / step) + ')';
				} else {
					more.removeAttribute('data-more-rest');
				}
			}
			if (!more.dataset.bound) {
				more.dataset.bound = '1';
				more.addEventListener('click', function () { shownCount += step; render(); });
			}
			render();
		}
		panels.forEach(limit);

		root.classList.add('kf-filter-ready');
		show(0);
	});
})();
