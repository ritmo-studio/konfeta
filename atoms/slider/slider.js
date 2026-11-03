/* atom slider v1.3 · общее листание дорожки для всех блоков-слайдеров (views-slider,
   photo-slider, ...). Ищет на странице любой .sds-slider с .sds-slider__track внутри —
   блок помечает себя этими классами, а разметку track/card даёт сам. Прокрутка нативная:
   свайп работает и без этого файла. Скрипт добавляет стрелки, пагинацию, перетаскивание
   мышью, клавиатуру и зацикливание. Клонов карточек нет: «перемотка» — это прокрутка
   к началу или к концу дорожки. */
(function () {
  var smooth = !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);

  // Файл подключается в <head>, разметки на этот момент ещё нет.
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();

  function start() {
    var sliders = document.querySelectorAll('.sds.sds-slider, .sds .sds-slider');
    for (var s = 0; s < sliders.length; s++) init(sliders[s]);
  }

  function init(root) {
    var track = root.querySelector('.sds-slider__track');
    if (!track) return;
    var cards = track.querySelectorAll('.sds-slider__card');
    if (cards.length < 2) return;

    var prev = root.querySelector('[data-sds-prev]');
    var next = root.querySelector('[data-sds-next]');
    var dots = root.querySelectorAll('.sds-pager__dot');
    root.classList.add('sds-slider--js');

    // Карточек мало, и на этой ширине дорожка не упирается в край — стрелкам
    // и точкам листать нечего, оставлять их кликабельными означает ответ
    // без эффекта. Пересчитывается при ресайзе: брейкпоинт меняет ширину карточек.
    function updateOverflow() {
      root.classList.toggle('sds-slider--no-overflow', maxScroll() <= 1);
    }

    // Позиции карточек берём из вёрстки, а не умножением шага на индекс.
    // Последняя «страница» дорожки короче карточки: прокрутка упирается в край,
    // и кратные шагу цели туда просто не попадают — отсюда прыжки через карточку.
    function maxScroll() { return track.scrollWidth - track.clientWidth; }
    function offsets() {
      var base = cards[0].offsetLeft, arr = [], i;
      for (i = 0; i < cards.length; i++) arr.push(Math.min(cards[i].offsetLeft - base, maxScroll()));
      return arr;
    }
    function nearest(x) {
      var o = offsets(), best = 0, dist = Infinity, i, d;
      for (i = 0; i < o.length; i++) {
        d = Math.abs(o[i] - x);
        if (d < dist) { dist = d; best = i; }
      }
      return best;
    }
    function atStart() { return track.scrollLeft <= 2; }
    function atEnd() { return track.scrollLeft + track.clientWidth >= track.scrollWidth - 2; }
    function go(left) { track.scrollTo({ left: left, behavior: smooth ? 'smooth' : 'auto' }); }
    function goTo(i) {
      var o = offsets();
      if (i < 0) i = 0;
      if (i > o.length - 1) i = o.length - 1;
      go(o[i]);
    }

    if (next) next.addEventListener('click', function () {
      if (atEnd()) goTo(0);                                 // с конца — к началу
      else goTo(nearest(track.scrollLeft) + 1);
    });
    if (prev) prev.addEventListener('click', function () {
      if (atStart()) go(maxScroll());                       // с начала — к концу
      else goTo(nearest(track.scrollLeft) - 1);
    });

    // Перетаскивание мышью: курсор-рука, зажал и тянешь. Только мышь — на тач-экранах
    // работает родная прокрутка, перехватывать её нечем и незачем.
    // Указатель захватываем не сразу на pointerdown, а только когда сдвиг перешёл
    // порог (DRAG_THRESHOLD): ранний захват в части браузеров переадресует следом
    // идущий click на сам track — обычный клик по ссылке/кнопке внутри карточки
    // (например, lightbox-триггер в review-card) просто переставал доходить до цели.
    // На время захвата отключаем scroll-snap: иначе привязка тянет дорожку обратно.
    // После отпускания доводим до ближайшей карточки, возвращаем привязку и — если
    // сдвиг всё-таки был — гасим идущий следом click, чтобы отпускание после
    // протаскивания не срабатывало как клик по карточке под курсором.
    var DRAG_THRESHOLD = 5;
    var dragging = false, moved = false, pointerId = null, startX = 0, startScroll = 0, snapBack = null;
    track.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      dragging = false;
      moved = false;
      pointerId = e.pointerId;
      startX = e.clientX;
      track.scrollLeft = track.scrollLeft;   // гасим незаконченную плавную доводку
      startScroll = track.scrollLeft;
      if (snapBack) { clearTimeout(snapBack); snapBack = null; }
    });
    track.addEventListener('pointermove', function (e) {
      if (pointerId === null || e.pointerId !== pointerId) return;
      var dx = e.clientX - startX;
      if (!dragging) {
        if (Math.abs(dx) < DRAG_THRESHOLD) return;
        dragging = true;
        moved = true;
        track.classList.add('is-dragging', 'is-free');
        track.setPointerCapture(pointerId);
      }
      e.preventDefault();
      track.scrollLeft = startScroll - dx;
    });
    function endDrag(e) {
      if (pointerId === null || (e.pointerId !== undefined && e.pointerId !== pointerId)) return;
      if (dragging) {
        track.classList.remove('is-dragging');
        try { track.releasePointerCapture(pointerId); } catch (err) {}
        goTo(nearest(track.scrollLeft));
        // привязку возвращаем, когда доводка закончилась, иначе она дёрнет дорожку рывком
        snapBack = setTimeout(function () { track.classList.remove('is-free'); snapBack = null; }, 360);
      }
      dragging = false;
      pointerId = null;
    }
    track.addEventListener('pointerup', endDrag);
    track.addEventListener('pointercancel', endDrag);
    track.addEventListener('click', function (e) {
      if (!moved) return;
      moved = false;
      e.preventDefault();
      e.stopPropagation();
    });
    // Картинки и текст не должны «отрываться» при протаскивании.
    track.addEventListener('dragstart', function (e) { e.preventDefault(); });

    // Клавиатура: дорожка в фокусе по tabindex, стрелки листают по карточке.
    // Зацикливание такое же, как у кнопок, чтобы поведение не расходилось.
    track.addEventListener('keydown', function (e) {
      var k = e.key;
      if (k === 'ArrowRight') { e.preventDefault(); if (atEnd()) goTo(0); else goTo(nearest(track.scrollLeft) + 1); }
      else if (k === 'ArrowLeft') { e.preventDefault(); if (atStart()) go(maxScroll()); else goTo(nearest(track.scrollLeft) - 1); }
      else if (k === 'Home') { e.preventDefault(); goTo(0); }
      else if (k === 'End') { e.preventDefault(); go(maxScroll()); }
    });

    for (var i = 0; i < dots.length; i++) bindDot(dots[i], i);
    function bindDot(dot, i) {
      dot.addEventListener('click', function () { goTo(i); });
    }

    // Активная полоска: считаем по положению прокрутки, а не по кликам —
    // тогда свайп и стрелки обновляют её одинаково.
    var ticking = false;
    function sync() {
      ticking = false;
      var i = atEnd() ? dots.length - 1 : nearest(track.scrollLeft);
      if (i < 0) i = 0;
      if (i > dots.length - 1) i = dots.length - 1;
      // Пока справа есть что листать — правый край растворяется в фоне.
      root.classList.toggle('sds-slider--more', !atEnd());
      for (var k = 0; k < dots.length; k++) {
        if (k === i) dots[k].setAttribute('aria-current', 'true');
        else dots[k].removeAttribute('aria-current');
      }
    }
    track.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(sync);
    }, { passive: true });
    sync();
    updateOverflow();
    var resizeTimer = null;
    window.addEventListener('resize', function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(updateOverflow, 150);
    });
  }
})();
