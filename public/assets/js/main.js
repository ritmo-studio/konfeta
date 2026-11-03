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
