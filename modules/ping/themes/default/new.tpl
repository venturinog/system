<form method="POST" id="ping_form">
<input type="hidden" value="" name="send" id="send">
{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$LBL_REQUIRED}</span>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$LBL_HOST}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">
                    <div class="d-flex gap-2">
                        <input type="text" id="ping_hostname" name="ping_hostname" value="{$ping_hostname|escape}" class="form-control{if $ERRORS.ping neq ''} is-invalid{/if}" placeholder="192.168.11.1" autocomplete="off" />
                        <input type="submit" id="ping_submit" class="button" value="{$LBL_PING}" onclick="document.getElementById('send').value='ping';">
                    </div>
                    {if $ERRORS.ping neq ''}<div class="invalid-feedback d-block">{$ERRORS.ping}</div>{/if}
                </span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$LBL_HOST}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">
                    <div class="d-flex gap-2">
                        <input type="text" id="tracert_hostname" name="tracert_hostname" value="{$tracert_hostname|escape}" class="form-control{if $ERRORS.tracepath neq ''} is-invalid{/if}" placeholder="192.168.11.1" autocomplete="off" />
                        <input type="submit" id="trace_submit" class="button" value="{$LBL_TRACE}" onclick="document.getElementById('send').value='tracepath';">
                    </div>
                    {if $ERRORS.tracepath neq ''}<div class="invalid-feedback d-block">{$ERRORS.tracepath}</div>{/if}
                </span>
            </div>
        </div>
    </section>
</div>
</form>
<div class="iss-form" id="ping_results"{if $frame_url eq ""} style="display:none;"{/if}>
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-terminal"></i> {$LBL_RESULTS}</header>
        <div class="iss-card__body">
            <div class="iss-console" id="ping_console">
                <div class="iss-console--pending" id="ping_pending" style="display:none;"><span class="iss-cursor"></span></div>
                <iframe name="ifm" id="ifm" title="{$LBL_RESULTS}" style="height:500px;{if $frame_url eq ""}display:none;{/if}"{if $frame_url neq ""} src="{$frame_url}"{/if}></iframe>
            </div>
        </div>
    </section>
</div>{literal}
<script>
(function () {
    var form = document.getElementById('ping_form');
    var sendField = document.getElementById('send');
    var ifm = document.getElementById('ifm');
    if (!form || !sendField) return;

    var pendingTimer = null;

    // Mismas reglas que issabelPingHost::valid() en el servidor (feedback).
    function validV4(v) {
        var m = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/.exec(v);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        return true;
    }
    function validV6(v) { return v.indexOf(':') !== -1 && /^[0-9A-Fa-f:]+$/.test(v); }
    function validHost(v) { return /^([A-Za-z0-9-]+\.)+[A-Za-z0-9]+$/.test(v); }
    function valid(v) { return v !== '' && (validV4(v) || validV6(v) || validHost(v)); }

    function mark(el, ok) {
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }

    var fields = {
        ping:      document.getElementById('ping_hostname'),
        tracepath: document.getElementById('tracert_hostname')
    };

    Object.keys(fields).forEach(function (k) {
        var el = fields[k];
        if (!el) return;
        var check = function () { mark(el, valid(el.value.trim())); };
        el.addEventListener('input', check);
        el.addEventListener('blur', check);
    });

    // El iframe de resultados ya pintó la consola: mostrar el panel real.
    function revealIframe() {
        var pending = document.getElementById('ping_pending');
        if (pending) pending.style.display = 'none';
        if (ifm) ifm.style.display = '';
        if (pendingTimer) { window.clearTimeout(pendingTimer); pendingTimer = null; }
    }

    function finishRun() {
        var b1 = document.getElementById('ping_submit');
        var b2 = document.getElementById('trace_submit');
        if (b1) b1.disabled = false;
        if (b2) b2.disabled = false;
        revealIframe();
    }

    // Borra el documento anterior del iframe (el resultado del comando previo)
    // para que nunca se vean restos mientras llega la salida nueva.
    function clearPreviousResult() {
        if (!ifm) return;
        try {
            var doc = ifm.contentDocument || (ifm.contentWindow && ifm.contentWindow.document);
            if (!doc) return;
            doc.open();
            doc.write('<!doctype html><meta charset="utf-8"><style>html,body{margin:0;height:100%;background:#0d1117;}</style>');
            doc.close();
        } catch (e) {}
    }

    // La consola avisa por postMessage: 'start' al pintarse y 'done' al
    // terminar el comando (no se usa el load del iframe: el borrado anterior
    // también lo dispararía).
    window.addEventListener('message', function (ev) {
        if (!ev || !ev.data) return;
        if (ev.data === 'issabel-ping-start') revealIframe();
        else if (ev.data === 'issabel-ping-done') finishRun();
    });

    form.addEventListener('submit', function (e) {
        // Sin recarga: el resultado se carga en el iframe de Results. El POST
        // del <form> queda solo como respaldo sin JavaScript.
        e.preventDefault();

        var btn = e.submitter || null;
        if (btn && btn.id) {
            if (btn.id === 'trace_submit') sendField.value = 'tracepath';
            else if (btn.id === 'ping_submit') sendField.value = 'ping';
        }
        var which = sendField.value === 'tracepath' ? 'tracepath' : 'ping';
        if (!btn && document.activeElement) {
            if (document.activeElement.id === 'tracert_hostname') which = 'tracepath';
            else if (document.activeElement.id === 'ping_hostname') which = 'ping';
            sendField.value = which;
        }
        if (!btn) btn = document.getElementById(which === 'tracepath' ? 'trace_submit' : 'ping_submit');

        var el = fields[which];
        if (el && !valid(el.value.trim())) {
            mark(el, false);
            el.focus();
            return false;
        }

        var results = document.getElementById('ping_results');
        var pending = document.getElementById('ping_pending');
        clearPreviousResult();
        if (ifm) ifm.style.display = 'none';
        if (pending) pending.style.display = '';
        if (results) results.style.display = '';
        // El botón pulsado queda deshabilitado hasta que termina el comando.
        if (btn) btn.disabled = true;

        if (pendingTimer) window.clearTimeout(pendingTimer);
        pendingTimer = window.setTimeout(finishRun, 8000);

        if (ifm) ifm.src = '/modules/ping/generate.php?send=' + encodeURIComponent(which)
            + '&hostname=' + encodeURIComponent(el.value.trim());
    });
})();
</script>
{/literal}
