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
