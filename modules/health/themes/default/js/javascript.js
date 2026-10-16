/* Página Salud — filtro de listas de endpoints y conmutador "todas/solo".
   Sin dependencias; ver system/modules/health/index.php. */
(function () {
    'use strict';

    window.issHealthToggleList = function (el) {
        var form = el.form || el.closest('form');
        if (!form) return;
        var list = form.querySelector('[data-endpoints]');
        if (!list) return;
        var checked = form.querySelector('input[name="cmode"]:checked');
        var all = checked && checked.value === 'all';
        list.style.display = all ? 'none' : '';
    };

    window.issHealthFilterList = function (input) {
        var q = (input.value || '').toLowerCase();
        var box = input.closest('[data-endpoints]');
        if (!box) return;
        box.querySelectorAll('.form-check').forEach(function (label) {
            var match = label.textContent.toLowerCase().indexOf(q) >= 0;
            label.style.display = match ? '' : 'none';
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[name="cmode"]:checked').forEach(function (r) {
            window.issHealthToggleList(r);
        });
    });
})();

/* API de salud: revelar y copiar el token (campo tipo password). */
(function () {
    'use strict';

    function feedback(ok) {
        var btn = document.getElementById('iss-hc-copy');
        if (!btn) return;
        var old = btn.innerHTML;
        btn.innerHTML = ok ? '<i class="ti ti-check"></i>' : '<i class="ti ti-x"></i>';
        setTimeout(function () { btn.innerHTML = old; }, 1500);
    }

    window.issHealthRevealToken = function () {
        var inp = document.getElementById('iss-hc-token');
        if (!inp) return;
        inp.type = (inp.type === 'password') ? 'text' : 'password';
    };

    window.issHealthCopyToken = function () {
        var inp = document.getElementById('iss-hc-token');
        if (!inp) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(inp.value).then(function () { feedback(true); },
                                                          function () { feedback(false); });
            return;
        }
        // Fallback para contextos sin Clipboard API.
        var prev = inp.type;
        inp.type = 'text'; inp.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        inp.type = prev;
        feedback(ok);
    };
})();

