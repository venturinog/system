<form method="POST">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save_route" value="{$SAVE}">
            <input class="button" type="submit" name="cancel_route" value="{$CANCEL}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$route_dev.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$route_dev.INPUT}</span>
                {if $ROUTE_ERRORS.route_dev}<div class="invalid-feedback d-block">{$ROUTE_ERRORS.route_dev}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$route_dest.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$route_dest.INPUT}</span>
                {if $ROUTE_ERRORS.route_dest}<div class="invalid-feedback d-block">{$ROUTE_ERRORS.route_dest}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$route_gw.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$route_gw.INPUT}</span>
                {if $ROUTE_ERRORS.route_gw}<div class="invalid-feedback d-block">{$ROUTE_ERRORS.route_gw}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$route_metric.LABEL}</span>
                <span class="iss-field__control">{$route_metric.INPUT}</span>
                {if $ROUTE_ERRORS.route_metric}<div class="invalid-feedback d-block">{$ROUTE_ERRORS.route_metric}</div>{/if}
            </div>
        </div>
    </section>
</div>
{$route_orig.INPUT}
</form>
<script>
{literal}
(function () {
    'use strict';

    function validDest(v) {
        v = (v || '').trim();
        if (v === '') return false;
        if (v.indexOf('/') === -1) v += '/32';
        var m = v.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        if (parseInt(m[5], 10) > 32) return false;
        if (v === '0.0.0.0/0' || v === '0.0.0.0') return false;
        return true;
    }

    function validGw(v) {
        v = (v || '').trim();
        if (v === '0.0.0.0') return true; // on-link
        var m = v.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        return true;
    }

    function validMetric(v) {
        v = (v || '').trim();
        if (v === '') return true; // vacío: NM decide
        return /^\d+$/.test(v) && parseInt(v, 10) <= 4294967295;
    }

    function mark(el, ok) {
        if (!el) return;
        el.classList.remove('is-invalid', 'is-valid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }

    function check(el, fn) {
        if (!el) return true;
        var ok = fn(el.value);
        mark(el, ok);
        return ok;
    }

    var f = document.querySelector('input[name=route_dest]');
    var form = f ? f.form : null;
    if (!form) return;

    var dest = form.querySelector('input[name=route_dest]');
    var gw = form.querySelector('input[name=route_gw]');
    var metric = form.querySelector('input[name=route_metric]');

    [[dest, validDest], [gw, validGw], [metric, validMetric]].forEach(function (p) {
        if (!p[0]) return;
        p[0].addEventListener('input', function () { check(p[0], p[1]); });
        p[0].addEventListener('blur', function () { check(p[0], p[1]); });
    });

    form.addEventListener('submit', function (e) {
        // Cancelar no valida.
        if (e.submitter && e.submitter.name === 'cancel_route') return;
        var ok = true;
        ok = check(dest, validDest) && ok;
        ok = check(gw, validGw) && ok;
        ok = check(metric, validMetric) && ok;
        if (!ok) {
            e.preventDefault();
            return false;
        }
    });
})();
{/literal}
</script>
