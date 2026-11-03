/* Текущая карточка «Видов упаковки».

   Дорожка листается прокруткой, и активной считается та карточка, что
   стоит у левого края: именно её на утверждённом сайте заливает
   акцентом. Атом slider такого класса не ставит — подсветка нужна
   одному этому блоку, и держать её в общем механизме незачем.

   Счёт по положению прокрутки, а не по кликам: свайп, стрелки и
   клавиатура дают один и тот же результат. */
(function () {
	function setup(root) {
		var track = root.querySelector('.sds-slider__track');
		if (!track) return;

		function sync() {
			var cards = track.querySelectorAll('.sds-slider__card');
			var best = -1, bestDist = Infinity;
			for (var i = 0; i < cards.length; i++) {
				var dist = Math.abs(cards[i].offsetLeft - track.offsetLeft - track.scrollLeft);
				if (dist < bestDist) { bestDist = dist; best = i; }
			}
			for (var k = 0; k < cards.length; k++) {
				cards[k].classList.toggle('is-current', k === best);
			}
		}

		var ticking = false;
		track.addEventListener('scroll', function () {
			if (ticking) return;
			ticking = true;
			requestAnimationFrame(function () { ticking = false; sync(); });
		}, { passive: true });
		window.addEventListener('resize', function () { setTimeout(sync, 160); });
		sync();
	}

	function start() {
		var roots = document.querySelectorAll('.kf-cards-slider');
		for (var i = 0; i < roots.length; i++) setup(roots[i]);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
