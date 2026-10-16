<link rel="stylesheet" href="modules/{$module_name}/applets/HealthCenter/tpl/css/styles.css" />

<div class="iss-hc">

    <div class="iss-hc__head">
        <div class="iss-hc__score iss-hc__score--{$state}">
            <div class="iss-hc__scoreval">{$score}</div>
            <div class="iss-hc__scorelbl">{$LABEL_STATE}</div>
        </div>
        <div class="iss-hc__summary">
            <div class="iss-hc__counts">
                <span class="badge bg-red-lt">{$critical} {$LABEL_CRITICAL}</span>
                <span class="badge bg-yellow-lt">{$warning} {$LABEL_WARNING}</span>
                <span class="badge bg-green-lt">{$ok} {$LABEL_OK}</span>
                <span class="badge bg-secondary-lt">{$unknown} {$LABEL_UNKNOWN}</span>
            </div>
            <div class="iss-hc__meta">
                <i class="ti ti-clock"></i> {$LABEL_LASTCHECK}: {$checked_human}
                &nbsp;·&nbsp; <i class="ti ti-server"></i> {$hostname}
                &nbsp;·&nbsp; <i class="ti ti-history"></i> {$LABEL_UPTIME}: {$uptime_human}
            </div>
            <div class="mt-2">
                <a class="btn btn-sm btn-outline-secondary" href="?menu=health">
                    <i class="ti ti-heartbeat"></i> {$LABEL_OPEN}
                </a>
            </div>
        </div>
    </div>

    <div class="iss-hc__attention">
        <div class="iss-hc__sectiontitle">
            <i class="ti ti-alert-triangle"></i> {$LABEL_ATTENTION}
            {if $attention_count}({$attention_count}){/if}
        </div>
        {if $attention}
        <ul class="iss-hc__findings">
            {foreach from=$attention item=f}
            <li class="iss-hc__finding iss-hc__finding--{$f.severity}">
                <span class="iss-hc__sev"></span>
                <span class="iss-hc__fdetail">{$f.detail}</span>
                <span class="iss-hc__fvalue">{$f.value}</span>
                {if $f.menu}<a class="btn btn-sm btn-outline-secondary" href="?menu={$f.menu}">{$LABEL_VIEW}</a>{/if}
                {if $f.action}
                <button type="button" class="btn btn-sm btn-outline-danger iss-hc-act"
                        data-type="{$f.action.type}"
                        data-service="{if $f.action.service}{$f.action.service}{/if}"
                        data-action="{$f.action.action}"
                        data-label="{$f.action.label|escape:'html'}"
                        data-impact="{$f.action.impact|escape:'html'}">{$f.action.btnlabel}</button>
                {/if}
            </li>
            {/foreach}
        </ul>
        {else}
        <div class="iss-hc__allgood"><i class="ti ti-circle-check"></i> {$LABEL_ALLGOOD}</div>
        {/if}
    </div>

    <div class="iss-hc__grid">

        <div class="iss-hc__card">
            <div class="iss-hc__cardtitle"><i class="ti ti-cpu"></i> {$LABEL_RESOURCES}</div>
            <div class="iss-hc__gauges">
                <div class="iss-hc__gauge">
                    <div class="iss-hc__gaugeval">{$cpu_percent}%</div>
                    <div class="iss-hc__gaugelbl">{$LABEL_CPU}</div>
                </div>
                <div class="iss-hc__gauge">
                    <div class="iss-hc__gaugeval">{$mem_percent}%</div>
                    <div class="iss-hc__gaugelbl">{$LABEL_RAM}</div>
                </div>
                <div class="iss-hc__gauge">
                    <div class="iss-hc__gaugeval">{$swap_percent}%</div>
                    <div class="iss-hc__gaugelbl">{$LABEL_SWAP}</div>
                </div>
            </div>
            <ul class="iss-hc__specs">
                <li><span>{$LABEL_RAM}</span><strong>{$mem_used_gb} / {$mem_total_gb} GB</strong></li>
                <li><span>{$LABEL_LOAD}</span><strong>{$load}</strong></li>
            </ul>
        </div>

        <div class="iss-hc__card">
            <div class="iss-hc__cardtitle"><i class="ti ti-hdd"></i> {$LABEL_STORAGE}</div>
            {foreach from=$disks item=d}
            <div class="iss-hc__disk">
                <div class="iss-hc__disktop">
                    <span><i class="ti ti-folder"></i> {$d.mount}</span>
                    <span>{$d.value}</span>
                </div>
                <div class="progress">
                    <div class="progress-bar {if $d.severity == 'critical'}bg-red{elseif $d.severity == 'warning'}bg-yellow{else}bg-azure{/if}"
                         style="width:{$d.percent}%"></div>
                </div>
            </div>
            {foreachelse}
            <div class="iss-hc__nodata">{$LABEL_NO_DATA}</div>
            {/foreach}
        </div>

        <div class="iss-hc__card iss-hc__card--chart">
            <div class="iss-hc__cardtitle d-flex justify-content-between align-items-center gap-2">
                <span><i class="ti ti-activity"></i> {$LABEL_TREND}</span>
                <span class="iss-hc__range">
                    {foreach from=$ranges item=r}
                    <button type="button" class="btn btn-sm {if $r.active}btn-primary{else}btn-outline-secondary{/if} iss-hc-range" data-range="{$r.key}">{$r.label}</button>
                    {/foreach}
                </span>
            </div>
            <div class="iss-hc__chartwrap"><canvas id="iss-hc-chart"></canvas></div>
            <div class="iss-hc__nodata" id="iss-hc-chart-nodata" style="display:none">{$LABEL_NO_DATA}</div>
        </div>

        <div class="iss-hc__card">
            <div class="iss-hc__cardtitle"><i class="ti ti-phone"></i> {$LABEL_TELEPHONY}</div>
            <ul class="iss-hc__specs">
                <li><span>{$LABEL_ASTERISK}</span><strong>{$tel_asterisk}</strong></li>
                <li><span>{$LABEL_TRUNKS}</span><strong>{$tel_trunks}</strong></li>
                <li><span>{$LABEL_EXTENSIONS}</span><strong>{$tel_extensions}</strong></li>
            </ul>
        </div>

    </div>
</div>

<div class="iss-hc-modal" id="iss-hc-modal" hidden>
    <div class="iss-hc-modal__backdrop" data-isshc-close="1"></div>
    <div class="iss-hc-modal__box" role="dialog" aria-modal="true" aria-labelledby="iss-hc-modal-title">
        <div class="iss-hc-modal__title" id="iss-hc-modal-title"></div>
        <div class="iss-hc-modal__text" id="iss-hc-modal-text"></div>
        <div class="iss-hc-modal__actions">
            <button type="button" class="btn btn-link link-secondary" data-isshc-close="1">{$LABEL_CANCEL}</button>
            <button type="button" class="btn btn-danger" id="iss-hc-modal-ok">{$LABEL_CONFIRM}</button>
        </div>
    </div>
</div>

<script>window.ISS_HC_CHART = {$CHART_CONFIG}; window.ISS_HC_BASE = '{$WEBPATH}themes/{$THEMENAME}/js/';</script>
{literal}
<script>
(function () {
    var base = window.ISS_HC_BASE;
    var files = ['moment.min.js', 'chart.umd.min.js', 'chartjs-adapter-moment.min.js', 'issabel-charts.js'];

    function init() {
        var canvas = document.getElementById('iss-hc-chart');
        var cfg = window.ISS_HC_CHART;
        if (!canvas || typeof Chart === 'undefined' || !cfg) {
            var nd = document.getElementById('iss-hc-chart-nodata');
            if (nd) nd.style.display = '';
            return;
        }
        if (window.__issHcChart) { window.__issHcChart.destroy(); }
        window.__issHcChart = new Chart(canvas.getContext('2d'), cfg);
    }

    var i = 0;
    (function next() {
        if (i >= files.length) { init(); return; }
        if (document.querySelector('script[data-iss-hc="' + files[i] + '"]')) { i++; next(); return; }
        var s = document.createElement('script');
        s.src = base + files[i];
        s.setAttribute('data-iss-hc', files[i]);
        s.onload = function () { i++; next(); };
        s.onerror = function () { i++; next(); };
        document.head.appendChild(s);
    })();
})();
</script>
<script>
/* Acciones (iniciar/reiniciar servicio, renovar certificado). Delegado, para
   sobrevivir a los refrescos del applet. La confirmación explica el impacto; el
   servidor revalida allowlist y permisos y registra la acción en auditoría. */
(function () {
    if (typeof window.jQuery === 'undefined') return;
    var $ = window.jQuery;

    function post(payload, done, fail) {
        $.post('index.php', $.extend({menu: 'dashboard', rawmode: 'yes', applet: 'HealthCenter'}, payload),
            done, 'json').fail(fail);
    }

    // --- Modal de confirmación propio (no hay Bootstrap JS en el iframe) ---
    function modalEl() { return document.getElementById('iss-hc-modal'); }
    function modalClose() {
        var m = modalEl();
        if (m) { m.hidden = true; }
        window.__issHcPending = null;
    }
    function modalOpen(btn, payload, title, text) {
        var m = modalEl();
        if (!m) return;
        document.getElementById('iss-hc-modal-title').textContent = title || '';
        document.getElementById('iss-hc-modal-text').textContent = text || '';
        window.__issHcPending = {btn: btn, payload: payload};
        m.hidden = false;
    }

    $(document).on('click', '[data-isshc-close]', function (e) {
        e.preventDefault();
        modalClose();
    });

    $(document).on('click', '#iss-hc-modal-ok', function () {
        var p = window.__issHcPending;
        modalClose();
        if (!p) return;
        var b = p.btn;
        $(b).prop('disabled', true);
        post(p.payload, function (r) {
            $(b).prop('disabled', false);
            window.alert((r && r.message) ? r.message : 'Error');
        }, function () {
            $(b).prop('disabled', false);
            window.alert('Error');
        });
    });

    $(document).off('click.issHc').on('click.issHc', '.iss-hc-act', function () {
        var b = $(this);
        var type = b.data('type');
        var title = (type === 'service')
            ? (b.data('label') + ' — ' + b.data('action'))
            : b.data('label');
        var payload = (type === 'service')
            ? {action: 'serviceAction', service: b.data('service'), svcaction: b.data('action')}
            : {action: 'certRenew'};
        modalOpen(this, payload, title, b.data('impact'));
    });

    // Selector de rango de la tendencia: re-consultamos el applet con `range`.
    $(document).off('click.issHcRange').on('click.issHcRange', '.iss-hc-range', function () {
        var range = $(this).data('range');
        var box = $(this).closest('.appletwindow_content');
        if (!box.length) return;
        var full = box.children('.appletwindow_fullcontent');
        $.get('index.php', {menu: 'dashboard', rawmode: 'yes', applet: 'HealthCenter',
                            action: 'getContent', range: range}, function (resp) {
            if (resp && resp.status === 'success' && resp.html) full.html(resp.html);
        }, 'json');
    });
})();
</script>
{/literal}
