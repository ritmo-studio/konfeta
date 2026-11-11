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
if (box) box.textContent ='('+ Math.ceil(rest / step) +')';
} else {
more.removeAttribute('data-more-rest');
}
}
if (!more.dataset.bound) {
more.dataset.bound ='1';
more.addEventListener('click', function () { shownCount += step; render(); });
}
render();
}
panels.forEach(limit);
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
if (document.readyState ==='loading') document.addEventListener('DOMContentLoaded', start);
else start();
})();
(() => {
const root = document.querySelector('.kf-cta-sticky');
if (!root) return;
const narrow = window.matchMedia('(max-width:640px)');
const after = root.dataset.showAfter ? document.querySelector(root.dataset.showAfter) : null;
const sync = () => {
if (!narrow.matches) { root.classList.remove('is-shown'); return; }
const passed = after
? after.getBoundingClientRect().bottom <= window.innerHeight
: window.scrollY >= window.innerHeight;
root.classList.toggle('is-shown', passed);
};
sync();
window.addEventListener('scroll', sync, { passive: true });
window.addEventListener('resize', sync);
narrow.addEventListener('change', sync);
})();
(() => {
const section = document.querySelector('.kf-logos');
if (!section) return;
if (!window.matchMedia('(min-width:641px),(pointer:fine)').matches) return;
section.querySelectorAll('.kf-logos__item').forEach((item) => {
const shot = item.querySelector('.kf-logos__shot');
if (!shot) return;
const place = (event) => {
const rect = item.getBoundingClientRect();
shot.style.left =`${event.clientX - rect.left}px`;
shot.style.top =`${event.clientY - rect.top}px`;
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
(function () {'use strict';
if (!window.RS) return;
RS.anim('kf-preloader', function (el, ctx) {
var root = document.documentElement;
var leaving = false;
function lock() {
root.classList.add('kf-preloader-lock');
if (window.RS_lenis) { try { window.RS_lenis.stop(); } catch (e) {} }
}
function unlock() {
root.classList.remove('kf-preloader-lock');
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
if (ev.animationName ==='kf-wave-in') go();
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
(() => {
const toggle = document.querySelector('.kf-site-header__toggle');
if (!toggle) return;
const header = toggle.closest('.kf-site-header') || toggle;
const panel = toggle.querySelector('.kf-site-header__panel');
const burger = toggle.querySelector('summary.kf-site-header__burger');
const still = window.matchMedia('(prefers-reduced-motion:reduce)').matches;
const setInert = (on) => {
Array.from(document.body.children).forEach((el) => {
if (el === header || el.contains(header)) return;
if (el.tagName ==='SCRIPT'|| el.tagName ==='STYLE') return;
if (on) el.setAttribute('inert','');
else el.removeAttribute('inert');
});
};
const closePanel = () => {
if (!toggle.open) return;
panel.classList.remove('is-shown');
if (still) { toggle.open = false; setInert(false); return; }
let done = false;
const finish = () => {
if (done) return;
done = true;
panel.removeEventListener('transitionend', finish);
toggle.open = false;
setInert(false);
};
panel.addEventListener('transitionend', finish);
setTimeout(finish, 300);
};
toggle.addEventListener('toggle', () => {
setInert(toggle.open);
if (toggle.open) requestAnimationFrame(() => requestAnimationFrame(() => panel.classList.add('is-shown')));
else panel.classList.remove('is-shown');
});
burger.addEventListener('click', (e) => {
if (toggle.open) { e.preventDefault(); closePanel(); }
});
panel.addEventListener('click', (e) => {
if (e.target.closest('a')) closePanel();
});
})();
(() => {
const section = document.querySelector('.kf-steps');
if (!section) return;
const list = section.querySelector('.kf-steps__list');
const steps = Array.from(section.querySelectorAll('.kf-steps__step'));
if (!list || steps.length < 2) return;
const wide = window.matchMedia('(min-width:641px)');
const still = window.matchMedia('(prefers-reduced-motion:reduce)');
let offsets = [];
const measure = () => {
let sum = 0;
offsets = steps.map((step) => {
const own = sum;
sum += step.getBoundingClientRect().height;
return own;
});
};
const apply = () => {
if (!wide.matches || still.matches) {
steps.forEach((step) => { step.style.transform =''; });
return;
}
const rect = list.getBoundingClientRect();
const top = rect.top + window.scrollY;
const start = top - window.innerHeight / 2;
const end = top + rect.height - window.innerHeight;
const progress = end > start ? (window.scrollY - start) / (end - start) : 1;
const folded = 1 - Math.min(1, Math.max(0, progress));
steps.forEach((step, i) => {
step.style.transform = folded ?`translateY(${-offsets[i] * folded}px)`:'';
});
};
const sync = () => { measure(); apply(); };
sync();
if (document.fonts && document.fonts.ready) document.fonts.ready.then(sync);
window.addEventListener('scroll', apply, { passive: true });
window.addEventListener('resize', sync);
wide.addEventListener('change', sync);
})();
