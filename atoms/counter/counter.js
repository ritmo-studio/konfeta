/* Номер позиции в счётчике слайдера.

   Всего позиций — не число кадров, а число тех, что могут встать
   первыми: дорожка упирается в правый край, и последние кадры первыми
   уже не станут. На утверждённом сайте так и есть — в группе из шести
   кадров на широком экране счётчик показывает пять, на узком шесть.

   Считает по положению прокрутки, а не по кликам: свайп, стрелки и
   клавиатура обновляют номер одинаково. */
(function () {
	function pad(n) { return n < 10 ? '0' + n : String(n); }

	function setup(root) {
		var track = root.querySelector('.sds-slider__track');
		var box = root.querySelector('[data-counter]');
		if (!track || !box) return;
		var cur = box.querySelector('[data-counter-current]');
		var tot = box.querySelector('[data-counter-total]');

		function cards() { return track.querySelectorAll('.sds-slider__card'); }
		function maxScroll() { return track.scrollWidth - track.clientWidth; }

		function stops() {
			var list = cards(), out = [];
			for (var i = 0; i < list.length; i++) {
				var x = list[i].offsetLeft - track.offsetLeft;
				if (x <= maxScroll() + 1) out.push(x);
			}
			return out.length ? out : [0];
		}

		function sync() {
			var s = stops(), x = track.scrollLeft, i = 0;
			for (var k = 0; k < s.length; k++) if (Math.abs(s[k] - x) < Math.abs(s[i] - x)) i = k;
			cur.textContent = pad(i + 1);
			tot.textContent = pad(s.length);
		}

		var ticking = false;
		track.addEventListener('scroll', function () {
			if (ticking) return;
			ticking = true;
			requestAnimationFrame(function () { ticking = false; sync(); });
		}, { passive: true });
		window.addEventListener('resize', function () { setTimeout(sync, 160); });
		/* Пересчёт после смены группы: панель была скрыта, у скрытой
		   дорожки нет ни ширины, ни прокрутки. */
		root.addEventListener('kf:shown', sync);
		sync();
	}

	function start() {
		var roots = document.querySelectorAll('.sds-slider');
		for (var i = 0; i < roots.length; i++) setup(roots[i]);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
