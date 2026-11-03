/**
 * Фотография из-под курсора: при наведении на плашку логотипа узел с
 * фотографией встаёт в точку курсора и вырастает из нуля, при уходе
 * схлопывается обратно.
 *
 * Механика снята с утверждённого сайта (main.js, activatePartnersHover):
 * left и top ставятся по координатам курсора относительно плашки, а
 * сдвиг на половину и рост от нуля лежат в CSS. Условие включения
 * оттуда же — широкий экран или настоящий курсор: на пальце наведения
 * нет, и трогать разметку незачем.
 *
 * Плашек 18, поэтому слушатели вешаются на каждую: событие mousemove
 * нужно именно внутри своей плашки, а не на всей секции.
 */
(() => {
	const section = document.querySelector('.kf-logos');
	if (!section) return;
	if (!window.matchMedia('(min-width:641px),(pointer:fine)').matches) return;

	section.querySelectorAll('.kf-logos__item').forEach((item) => {
		const shot = item.querySelector('.kf-logos__shot');
		if (!shot) return;

		const place = (event) => {
			const rect = item.getBoundingClientRect();
			shot.style.left = `${event.clientX - rect.left}px`;
			shot.style.top = `${event.clientY - rect.top}px`;
		};

		item.addEventListener('mousemove', place);
		item.addEventListener('mouseenter', (event) => {
			place(event);
			shot.classList.add('is-visible');
		});
		item.addEventListener('mouseleave', () => {
			shot.classList.remove('is-visible');
		});
	});
})();
