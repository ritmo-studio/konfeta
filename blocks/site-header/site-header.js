/* site-header v1.2 · мобильная панель забирает фокус себе.
   Панель — position:fixed поверх страницы, но содержимое под ней остаётся в табуляции:
   Tab уводил курсор в невидимую страницу. CSS этого не умеет, нативный <details> фокус
   не ловит — поэтому на время открытия соседям шапки по <body> ставится inert.
   Трогаем только прямых детей body и только пока панель открыта: ни одного состояния
   между открытиями не храним, несколько шапок на странице не предполагается (uniquePerPage).
   Файл подключается в <head>, разметки на этот момент ещё нет — ждём DOMContentLoaded.

   closePanel: открытие нативное (атрибут open уже стоит), а закрытие — сначала CSS-переход,
   потом open=false, иначе контент успевает исчезнуть (display:none у <details>) раньше
   анимации. Клик по любой ссылке внутри панели закрывает её тем же путём — переход
   по якорю или на страницу делает соседний скрипт (base-anchor.js), этому знать об этом
   не нужно (блоки независимые).

   Отличие снимка от библиотеки: inert снимается не только по событию toggle, но и
   в closePanel, сразу после open=false. Событие toggle у <details> в WebKit после
   клика с preventDefault не доставляется (проверено: атрибут open снимается, события
   нет), и соседи шапки оставались inert — вся страница после закрытия меню переставала
   принимать фокус и клики. setInert идемпотентна, поэтому там, где событие приходит,
   поведение прежнее. Правку надо вернуть в библиотеку.

   startSpy: подсвечивает в меню пункт, соответствующий блоку на экране — только для
   ссылок-якорей на этой же странице (лендинг). Ссылка на отдельную страницу (будущие
   проекты) не трогается: её aria-current расставляет контент. */
(function () {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();

  function start() {
    var toggles = document.querySelectorAll('.sds-header__toggle'), i;
    for (i = 0; i < toggles.length; i++) bind(toggles[i]);
    startSpy();
  }

  function bind(toggle) {
    var header = toggle.closest('.sds-header') || toggle;
    var panel = toggle.querySelector('.sds-header__panel');
    var summary = toggle.querySelector('summary.sds-header__burger');
    var reduced = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setInert(on) {
      var kids = document.body.children, i, el;
      for (i = 0; i < kids.length; i++) {
        el = kids[i];
        if (el === header || el.contains(header)) continue;
        if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE') continue;
        if (on) el.setAttribute('inert', '');
        else el.removeAttribute('inert');
      }
    }

    toggle.addEventListener('toggle', function () {
      setInert(toggle.open);
      // Открытие — нативное и мгновенное (атрибут уже стоит), класс добавляем
      // на следующий кадр, чтобы transition сыграл от скрытого состояния.
      if (toggle.open) requestAnimationFrame(function () { requestAnimationFrame(function () { panel.classList.add('is-shown'); }); });
      else panel.classList.remove('is-shown');
    });

    // Закрытие по бургеру — не мгновенный toggle, а сначала анимация, потом open=false.
    summary.addEventListener('click', function (e) {
      if (toggle.open) { e.preventDefault(); closePanel(); }
    });

    // Любая ссылка в панели (пункт меню, телефон, кнопка) закрывает её —
    // переход или скролл к якорю делает соседний обработчик (base-anchor.js),
    // этому достаточно знать, что по ссылке кликнули.
    panel.addEventListener('click', function (e) {
      if (e.target.closest('a')) closePanel();
    });

    function closePanel() {
      if (!toggle.open) return;
      panel.classList.remove('is-shown');
      if (reduced) { toggle.open = false; setInert(false); return; }
      var done = false;
      function finish() {
        if (done) return;
        done = true;
        panel.removeEventListener('transitionend', finish);
        toggle.open = false;
        setInert(false);
      }
      panel.addEventListener('transitionend', finish);
      setTimeout(finish, 300);
    }
  }

  // Активный пункт меню по положению скролла — только для якорей на этой же
  // странице (лендинг). Пункты с ссылкой на другую страницу (будущие проекты)
  // статичны, их aria-current расставляет контент, а не этот скрипт.
  function startSpy() {
    var links = document.querySelectorAll('.sds-header__link, .sds-header__mrow');
    var byId = {}, order = [], i, href, id, el;
    for (i = 0; i < links.length; i++) {
      href = links[i].getAttribute('href') || '';
      if (href.charAt(0) !== '#' || href.length < 2) continue;
      id = href.slice(1);
      el = document.getElementById(id);
      if (!el) continue;
      if (!byId[id]) { byId[id] = { el: el, links: [] }; order.push(id); }
      byId[id].links.push(links[i]);
      links[i].removeAttribute('aria-current');
    }
    if (!order.length || !('IntersectionObserver' in window)) return;
    order.sort(function (a, b) { return byId[a].el.compareDocumentPosition(byId[b].el) & 4 ? -1 : 1; });

    var active = {};
    function setActive(id) {
      for (i = 0; i < order.length; i++) {
        var on = order[i] === id, l = byId[order[i]].links, j;
        for (j = 0; j < l.length; j++) if (on) l[j].setAttribute('aria-current', 'page'); else l[j].removeAttribute('aria-current');
      }
    }
    var headerBar = document.querySelector('.sds-header__bar');
    var offset = (headerBar ? headerBar.getBoundingClientRect().height : 0) + 24;
    var io = new IntersectionObserver(function (entries) {
      var found = null, j;
      for (j = 0; j < entries.length; j++) active[entries[j].target.id] = entries[j].isIntersecting;
      // Ничего не пересекает зону — явно снимаем активность, а не оставляем прежний
      // пункт висеть (иначе он остаётся выбранным даже после возврата в самый верх).
      for (j = 0; j < order.length; j++) if (active[order[j]]) { found = order[j]; break; }
      setActive(found);
    }, { rootMargin: '-' + Math.round(offset) + 'px 0px -60% 0px', threshold: 0 });
    for (i = 0; i < order.length; i++) io.observe(byId[order[i]].el);

    // Последний пункт короче «зоны активности» (например, подвал) может никогда не
    // попасть в неё — страница физически не может прокрутиться настолько, чтобы его
    // верх прошёл линию срабатывания. У низа страницы подсвечиваем его принудительно.
    var last = order[order.length - 1];
    window.addEventListener('scroll', function () {
      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) setActive(last);
    }, { passive: true });
  }
})();
