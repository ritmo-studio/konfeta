/* Базовый JS сайта «Ритм».
   Два правила, из которых всё следует:
   1. Блок не знает своей позиции — анимация идёт по входу в экран.
   2. Отсутствие элемента не должно ронять страницу. */

(function () {
  'use strict';

  var root = document.documentElement;
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Ставим флаг сразу: до него .rs-anim не прячется, поэтому
     при выключенном JS контент остаётся видимым. */
  if (!reduced) root.classList.add('rs-js');

  /* ── Появление по входу в экран ───────────────────────────── */
  function initReveal() {
    var items = document.querySelectorAll('.rs-anim');
    if (!items.length) return;

    if (reduced || !('IntersectionObserver' in window)) {
      for (var i = 0; i < items.length; i++) items[i].classList.add('is-in');
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        e.target.classList.add('is-in');
        io.unobserve(e.target);           /* одноразово: не моргает при обратном скролле */
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0 });

    items.forEach(function (el) { io.observe(el); });
  }

  /* ── Реестр анимаций блоков ───────────────────────────────────
     Блок объявляет себя разметкой: <div class="rs-section" data-anim="hero">
     и регистрирует обработчик: RS.anim('hero', function (el) { ... }).
     Инициализатор просто обходит DOM. Поэтому блок можно поставить
     первым, последним и трижды подряд — ничего не сломается.     */
  var registry = {};

  function anim(name, fn) {
    registry[name] = fn;
    return anim;
  }

  function initBlocks() {
    var nodes = document.querySelectorAll('[data-anim]');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      var name = el.getAttribute('data-anim');
      var fn = registry[name];
      if (typeof fn !== 'function') continue;        /* нет обработчика — молча пропускаем */
      if (el.dataset.animReady === '1') continue;    /* повторная инициализация безопасна */
      try {
        fn(el, { reduced: reduced });
        el.dataset.animReady = '1';
      } catch (err) {
        /* один упавший блок не должен уносить остальные */
        if (window.console) console.error('[rs] блок "' + name + '":', err);
      }
    }
  }

  /* ── Плавная прокрутка ────────────────────────────────────────
     Lenis доводит настоящую позицию окна, а не подменяет прокрутку
     трансформом, поэтому sticky, fixed и события scroll остаются
     рабочими. Нет библиотеки — страница просто скроллится обычно.

     При prefers-reduced-motion не включаем: доводка прокрутки —
     ровно то движение, от которого людям с вестибулярными
     расстройствами делается плохо.                             */
  function initSmoothScroll() {
    if (reduced || typeof window.Lenis !== 'function') return;
    try {
      var lenis = new window.Lenis({
        duration: 1,
        smoothWheel: true,
        /* Тач не трогаем: на мобильных у системы своя инерция,
           и перебивать её — верный способ сделать хуже.        */
        syncTouch: false
      });
      var tick = function (time) { lenis.raf(time); requestAnimationFrame(tick); };
      requestAnimationFrame(tick);
      window.RS_lenis = lenis;
    } catch (err) {
      if (window.console) console.error('[rs] плавная прокрутка:', err);
    }
  }

  function init() { initSmoothScroll(); initReveal(); initBlocks(); }

  /* CSS и JS склеиваются в один файл, и base.js в нём идёт первым.
     Скрипт подключён с defer, поэтому к моменту его выполнения
     разметка уже разобрана и readyState равен 'interactive' —
     init() побежал бы сразу, ДО того как файлы блоков ниже успеют
     зарегистрировать свои обработчики, и реестр остался бы пустым.
     Отложенный вызов даёт им выполниться: setTimeout ставит init
     в следующую задачу, когда весь склеенный файл уже отработал. */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    setTimeout(init, 0);
  }

  window.RS = { anim: anim, init: init, reduced: reduced };
})();
