(function () {'use strict';
var root = document.documentElement;
var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (!reduced) root.classList.add('rs-js');
function initReveal() {
var items = document.querySelectorAll('.rs-anim');
if (!items.length) return;
if (reduced || !('IntersectionObserver'in window)) {
for (var i = 0; i < items.length; i++) items[i].classList.add('is-in');
return;
}
var io = new IntersectionObserver(function (entries) {
entries.forEach(function (e) {
if (!e.isIntersecting) return;
e.target.classList.add('is-in');
io.unobserve(e.target);
});
}, { rootMargin:'0px 0px -10% 0px', threshold: 0 });
items.forEach(function (el) { io.observe(el); });
}
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
if (typeof fn !=='function') continue;
if (el.dataset.animReady ==='1') continue;
try {
fn(el, { reduced: reduced });
el.dataset.animReady ='1';
} catch (err) {
if (window.console) console.error('[rs] блок "'+ name +'":', err);
}
}
}
function initSmoothScroll() {
if (reduced || typeof window.Lenis !=='function') return;
try {
var lenis = new window.Lenis({
duration: 1,
smoothWheel: true,
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
if (document.readyState ==='loading') {
document.addEventListener('DOMContentLoaded', init);
} else {
setTimeout(init, 0);
}
window.RS = { anim: anim, init: init, reduced: reduced };
})();
(function () {
function pad(n) { return n < 10 ?'0'+ n : String(n); }
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
root.addEventListener('kf:shown', sync);
sync();
}
function start() {
var roots = document.querySelectorAll('.sds-slider');
for (var i = 0; i < roots.length; i++) setup(roots[i]);
}
if (document.readyState ==='loading') document.addEventListener('DOMContentLoaded', start);
else start();
})();
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
t.setAttribute('aria-pressed', on ?'true':'false');
});
if (select && select.selectedIndex !== index) select.selectedIndex = index;
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
root.classList.add('kf-filter-ready');
show(0);
});
})();
(function () {
var smooth = !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
if (document.readyState ==='loading') document.addEventListener('DOMContentLoaded', start);
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
function updateOverflow() {
root.classList.toggle('sds-slider--no-overflow', maxScroll() <= 1);
}
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
function go(left) { track.scrollTo({ left: left, behavior: smooth ?'smooth':'auto'}); }
function goTo(i) {
var o = offsets();
if (i < 0) i = 0;
if (i > o.length - 1) i = o.length - 1;
go(o[i]);
}
if (next) next.addEventListener('click', function () {
if (atEnd()) goTo(0);
else goTo(nearest(track.scrollLeft) + 1);
});
if (prev) prev.addEventListener('click', function () {
if (atStart()) go(maxScroll());
else goTo(nearest(track.scrollLeft) - 1);
});
var DRAG_THRESHOLD = 5;
var dragging = false, moved = false, pointerId = null, startX = 0, startScroll = 0, snapBack = null;
track.addEventListener('pointerdown', function (e) {
if (e.pointerType !=='mouse'|| e.button !== 0) return;
dragging = false;
moved = false;
pointerId = e.pointerId;
startX = e.clientX;
track.scrollLeft = track.scrollLeft;
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
track.classList.add('is-dragging','is-free');
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
track.addEventListener('dragstart', function (e) { e.preventDefault(); });
track.addEventListener('keydown', function (e) {
var k = e.key;
if (k ==='ArrowRight') { e.preventDefault(); if (atEnd()) goTo(0); else goTo(nearest(track.scrollLeft) + 1); }
else if (k ==='ArrowLeft') { e.preventDefault(); if (atStart()) go(maxScroll()); else goTo(nearest(track.scrollLeft) - 1); }
else if (k ==='Home') { e.preventDefault(); goTo(0); }
else if (k ==='End') { e.preventDefault(); go(maxScroll()); }
});
for (var i = 0; i < dots.length; i++) bindDot(dots[i], i);
function bindDot(dot, i) {
dot.addEventListener('click', function () { goTo(i); });
}
var ticking = false;
function sync() {
ticking = false;
var i = atEnd() ? dots.length - 1 : nearest(track.scrollLeft);
if (i < 0) i = 0;
if (i > dots.length - 1) i = dots.length - 1;
root.classList.toggle('sds-slider--more', !atEnd());
for (var k = 0; k < dots.length; k++) {
if (k === i) dots[k].setAttribute('aria-current','true');
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
(function () {'use strict';
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
if (ctx.reduced) { unlock(); return; }
function waitThenUnlock() {
var running = [];
if (typeof el.getAnimations ==='function') {
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
function sameDoc(a) {
return a.href.split('#')[0] === location.href.split('#')[0];
}
function internal(a) {
var raw = a.getAttribute('href') ||'';
if (!raw || raw.charAt(0) ==='#') return false;
if (/^[a-z]+:/i.test(raw) && !/^https?:/i.test(raw)) return false;
if (a.hasAttribute('download') || a.target ==='_blank') return false;
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
el.addEventListener('animationend', function (ev) {
if (ev.animationName ==='rs-wave-in') go();
});
setTimeout(go, 900);
});
window.addEventListener('pageshow', function (e) {
if (!e.persisted) return;
leaving = false;
el.classList.remove('is-covering');
lock();
waitThenUnlock();
});
});
})();
