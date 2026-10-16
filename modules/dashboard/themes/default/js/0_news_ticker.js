/* Ticker de noticias del dashboard (DASHBOARDtodo.md §9).
   Carga el feed por AJAX; si falla, oculta el ticker sin bloquear la página.
   Rotación lenta con pausa al pasar el ratón y respeto por
   prefers-reduced-motion. Sin dependencias. */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        var bar = document.getElementById('iss-news-ticker');
        if (!bar) return;
        var body = document.getElementById('iss-news-ticker-body');
        var nav  = document.getElementById('iss-news-ticker-nav');
        var endpoint = bar.getAttribute('data-endpoint');
        if (!endpoint || !body) return;

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var items = [], idx = 0, timer = null;

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = (s == null) ? '' : String(s);
            return d.innerHTML;
        }
        function render() {
            if (!items.length) return;
            var it = items[idx % items.length];
            body.innerHTML = '<a class="iss-news-ticker__title" target="_blank" rel="noopener noreferrer" href="'
                + esc(it.link) + '">' + esc(it.title) + '</a>'
                + (it.date_format ? ' <span class="iss-news-ticker__date">' + esc(it.date_format) + '</span>' : '');
            if (nav) nav.textContent = ((idx % items.length) + 1) + '/' + items.length;
        }
        function next() { idx++; render(); }
        function start() { if (!reduce && items.length > 1 && !timer) timer = setInterval(next, 8000); }
        function stop()  { if (timer) { clearInterval(timer); timer = null; } }

        bar.addEventListener('mouseenter', stop);
        bar.addEventListener('mouseleave', start);
        bar.addEventListener('focusin', stop);
        bar.addEventListener('focusout', start);

        var meta = document.querySelector('meta[name="issabel-csrf-token"]');
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (meta && meta.getAttribute('content')) {
            headers['X-Issabel-CSRF-Token'] = meta.getAttribute('content');
        }

        fetch(endpoint, { credentials: 'same-origin', headers: headers })
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (!r || r.status !== 'success' || !r.items || !r.items.length) {
                    bar.style.display = 'none';
                    return;
                }
                items = r.items; idx = 0; render(); start();
            })
            .catch(function () { bar.style.display = 'none'; });
    });
})();
