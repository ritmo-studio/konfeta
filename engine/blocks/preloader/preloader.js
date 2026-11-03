/* Занавес. Вся раскладка во времени — в CSS; здесь только то,
   чего CSS не умеет.

   Уход занавеса скрипт не запускает: он отыгрывает сам. Если JS
   выключен или не доехал, страница всё равно откроется — поэтому
   здесь нет ни одной строки, без которой сайт сломался бы.

   Задачи ровно три: придержать прокрутку, пока занавес висит;
   закрыть занавес перед переходом на другую страницу; вернуть
   страницу в рабочий вид, если браузер достал её из кеша
   кнопкой «назад» — там занавес остался закрытым.             */
(function () {
  'use strict';
  if (!window.RS) return;

  RS.anim('preloader', function (el, ctx) {
    var root = document.documentElement;
    var leaving = false;

    function lock() {
      root.classList.add('rs-preloader-lock');
      if (window.RS_lenis) { try { window.RS_lenis.stop(); } catch (e) {} }
    }

    function unlock() {
      root.classList.remove('rs-preloader-lock');
      if (window.RS_lenis) { try { window.RS_lenis.start(); } catch (e) {} }
    }

    /* При reduced-motion занавеса нет вовсе (он скрыт в CSS):
       прокрутку не трогаем, переходы оставляем браузеру.      */
    if (ctx.reduced) { unlock(); return; }

    /* Такты живут на детях — листе и волне, — поэтому subtree.
       Там, где опции не поддержаны, сработает страховка ниже. */
    function waitThenUnlock() {
      var running = [];
      if (typeof el.getAnimations === 'function') {
        try { running = el.getAnimations({ subtree: true }); } catch (e) { running = []; }
      }
      if (running.length) {
        Promise.all(running.map(function (a) { return a.finished; }))
               .then(unlock, unlock);
      }
      setTimeout(unlock, 5000);
    }

    lock();
    waitThenUnlock();

    /* ── Переход на другую страницу ─────────────────────────
       Настоящая навигация, не SPA: закрываем занавес и отдаём
       адрес браузеру. Новый документ рисует занавес закрытым
       с первой отрисовки, поэтому склейки не видно, а грузиться
       он начинает сразу после закрытия — логотипы отыгрывают
       уже на нём.                                             */

    function sameDoc(a) {
      return a.href.split('#')[0] === location.href.split('#')[0];
    }

    function internal(a) {
      var raw = a.getAttribute('href') || '';
      if (!raw || raw.charAt(0) === '#') return false;
      if (/^[a-z]+:/i.test(raw) && !/^https?:/i.test(raw)) return false;  // mailto, tel
      if (a.hasAttribute('download') || a.target === '_blank') return false;
      if (a.host !== location.host) return false;
      return !sameDoc(a);
    }

    document.addEventListener('click', function (e) {
      if (e.defaultPrevented || e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      if (!e.target || !e.target.closest) return;

      var a = e.target.closest('a[href]');
      if (!a || !internal(a)) return;

      e.preventDefault();
      if (leaving) return;
      leaving = true;

      var url = a.href;
      var gone = false;
      function go() { if (!gone) { gone = true; location.href = url; } }

      lock();
      el.classList.add('is-covering');

      /* Ждём лист: он накрывает экран последним. Страховка на
         случай, когда событий не будет вовсе — вкладка в фоне,
         анимации не стартовали.                               */
      el.addEventListener('animationend', function (ev) {
        if (ev.animationName === 'rs-wave-in') go();
      });
      setTimeout(go, 900);
    });

    /* Возврат кнопкой «назад»: страница приходит из кеша в том
       виде, в каком её оставили, то есть с закрытым занавесом.
       Снимаем класс — у правил по умолчанию другое имя анимации,
       и браузер проигрывает уход заново.                       */
    window.addEventListener('pageshow', function (e) {
      if (!e.persisted) return;
      leaving = false;
      el.classList.remove('is-covering');
      lock();
      waitThenUnlock();
    });
  });
})();
