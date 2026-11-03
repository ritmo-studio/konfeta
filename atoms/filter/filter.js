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
		}

		tabs.forEach(function (t, i) {
			t.addEventListener('click', function () { show(i); });
		});
		if (select) {
			select.addEventListener('change', function () { show(select.selectedIndex); });
		}

		root.classList.add('kf-filter-ready');
		show(0);
	});
})();
