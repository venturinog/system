/*
 * Módulo Updates (Issabel 6). Ver UPDATESmodule.md §6.5.
 * Select all/none, filtro por tipo, confirmación y sondeo del job.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    /*
     * Lista de casillas de paquetes. Se consulta en cada uso (no se cachea al
     * cargar): el módulo puede montarse de forma diferida y una lista capturada
     * al inicio puede quedar vacía. Ver UPDATESmodule.md §6.4.
     */
    function getBoxes() {
        var form = document.getElementById('iss-updates-form');
        if (!form) return [];
        var list = form.elements['pkg_x[]'];   // input único o RadioNodeList
        if (!list) return [];
        if (list.length === undefined) return [list];
        var out = [];
        for (var i = 0; i < list.length; i++) out.push(list[i]);
        return out;
    }

    function pollStatus() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '?menu=updates&ajax=status&rawmode=yes', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var st = {};
            try { st = JSON.parse(xhr.responseText); } catch (e) { st = {}; }
            if (st.state && st.state !== 'running') window.location.reload();
        };
        xhr.send();
    }

    ready(function () {
        var root = document.getElementById('iss-updates');
        var form = document.getElementById('iss-updates-form');

        // Confirmación + validación de selección.
        if (form) {
            form.addEventListener('submit', function (e) {
                var boxes = getBoxes();
                var n = 0;
                for (var i = 0; i < boxes.length; i++) if (boxes[i].checked) n++;
                if (n === 0) {
                    e.preventDefault();
                    window.alert(form.getAttribute('data-selectone') || '');
                    return false;
                }
                if (!window.confirm(form.getAttribute('data-confirm') || '')) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // Select all / none (alterna).
        var toggle = document.getElementById('iss-updates-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var boxes = getBoxes();
                var allChecked = boxes.length > 0;
                for (var i = 0; i < boxes.length; i++) if (!boxes[i].checked) allChecked = false;
                for (var j = 0; j < boxes.length; j++) boxes[j].checked = !allChecked;
                toggle.textContent = allChecked
                    ? (toggle.getAttribute('data-selectnone') || '')
                    : (toggle.getAttribute('data-selectall') || '');
            });
        }

        // Filtro por tipo (en cliente, para no perder la selección).
        var filter = document.getElementById('iss-updates-filter');
        if (filter) {
            filter.addEventListener('change', function () {
                var v = filter.value;
                var rows = document.querySelectorAll('#iss-updates-table tbody tr[data-type]');
                for (var i = 0; i < rows.length; i++) {
                    var t = ' ' + (rows[i].getAttribute('data-type') || '') + ' ';
                    var show = (v === '' || t.indexOf(' ' + v + ' ') >= 0);
                    rows[i].style.display = show ? '' : 'none';
                }
            });
        }

        // Sondeo mientras el job corre (el estado vive en disco, así que
        // sobrevive a cerrar sesión o recargar).
        if (root && root.getAttribute('data-job-state') === 'running') {
            var timer = setInterval(pollStatus, 3000);
            pollStatus();
        }

        // Ver el log del job bajo demanda.
        var logLink = document.getElementById('iss-updates-viewlog');
        if (logLink) {
            logLink.addEventListener('click', function (e) {
                e.preventDefault();
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '?menu=updates&ajax=log&rawmode=yes', true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState !== 4) return;
                    var data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (err) { data = {}; }
                    var card = document.getElementById('iss-updates-logcard');
                    var pre = document.getElementById('iss-updates-log');
                    if (card && pre) {
                        card.classList.remove('d-none');
                        pre.textContent = data.log || '';
                    }
                };
                xhr.send();
            });
        }
    });
})();
