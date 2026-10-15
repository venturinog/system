<form method="POST">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_interfase_changes" value="{$SAVE}"
                   onClick="return confirmSubmit('{$CONFIRM_EDIT}')">
            <input class="button" type="submit" name="cancel_interfase_edit" value="{$CANCEL}">
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT_PARAMETERS}">
            {/if}
        </div>
    </div>

    <section class="iss-card">
        <div class="iss-card__header"><i class="ti ti-network"></i> IPv4</div>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$type.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$type.INPUT}</span>
            </div>
            <div class="iss-field" id="iss-field-ip">
                <span class="iss-field__label">{$ip.LABEL}&nbsp;<span class="iss-required" id="iss-req-ip" style="display:none">*</span></span>
                <span class="iss-field__control">{$ip.INPUT}</span>
                {if $INTERFASE_ERRORS.ip}<div class="invalid-feedback d-block">{$INTERFASE_ERRORS.ip}</div>{/if}
            </div>
            <div class="iss-field" id="iss-field-mask">
                <span class="iss-field__label">{$mask.LABEL}&nbsp;<span class="iss-required" id="iss-req-mask" style="display:none">*</span></span>
                <span class="iss-field__control">{$mask.INPUT}</span>
                {if $INTERFASE_ERRORS.mask}<div class="invalid-feedback d-block">{$INTERFASE_ERRORS.mask}</div>{/if}
            </div>
        </div>
    </section>

    <section class="iss-card">
        <div class="iss-card__header"><i class="ti ti-world"></i> IPv6</div>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$ip6method.LABEL}</span>
                <span class="iss-field__control">{$ip6method.INPUT}</span>
            </div>
            <div class="iss-field" id="iss-field-ip6addr">
                <span class="iss-field__label">{$ip6addr.LABEL}&nbsp;<span class="iss-required" id="iss-req-ip6addr" style="display:none">*</span></span>
                <span class="iss-field__control">{$ip6addr.INPUT}</span>
                {if $INTERFASE_ERRORS.ip6addr}<div class="invalid-feedback d-block">{$INTERFASE_ERRORS.ip6addr}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ip6gw.LABEL}</span>
                <span class="iss-field__control">{$ip6gw.INPUT}</span>
                {if $INTERFASE_ERRORS.ip6gw}<div class="invalid-feedback d-block">{$INTERFASE_ERRORS.ip6gw}</div>{/if}
            </div>
        </div>
    </section>

    {$dev_id.INPUT}
</div>
</form>
<script>
{literal}
(function () {
    'use strict';

    function q(name) { return document.querySelector('[name="' + name + '"]'); }
    function radioVal(name) {
        var r = document.querySelector('input[name="' + name + '"]:checked');
        return r ? r.value : '';
    }
    function validV4(v) {
        var m = (v || '').trim().match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        return true;
    }
    function validMask(v) {
        if (!validV4(v)) return false;
        var o = v.trim().split('.').map(Number);
        var n = ((o[0] << 24) | (o[1] << 16) | (o[2] << 8) | o[3]) >>> 0;
        var inv = (~n) >>> 0;
        return (((inv + 1) & inv) >>> 0) === 0;
    }
    function validV6(v) {
        v = (v || '').trim();
        return v.indexOf(':') !== -1 && /^[0-9a-fA-F:]+$/.test(v);
    }
    function validV6Cidr(v) {
        var p = (v || '').trim().split('/');
        if (p.length !== 2) return false;
        if (!/^\d+$/.test(p[1]) || parseInt(p[1], 10) > 128) return false;
        return validV6(p[0]);
    }
    function mark(el, ok) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }

    var ip = q('ip'), mask = q('mask'), ip6addr = q('ip6addr'), ip6gw = q('ip6gw');
    var first = ip || mask || ip6addr || ip6gw;
    if (!first) return;
    var form = first.form;
    if (!form) return;

    function isStatic() { return radioVal('type') === 'static'; }
    function isManual6() { var m = q('ip6method'); return m && m.value === 'manual'; }

    function syncMode() {
        var st = isStatic();
        if (ip) ip.disabled = !st;
        if (mask) mask.disabled = !st;
        var rq = document.getElementById('iss-req-ip');
        var rm = document.getElementById('iss-req-mask');
        if (rq) rq.style.display = st ? '' : 'none';
        if (rm) rm.style.display = st ? '' : 'none';
        var r6 = document.getElementById('iss-req-ip6addr');
        if (r6) r6.style.display = isManual6() ? '' : 'none';
        [ip, mask, ip6addr, ip6gw].forEach(function (el) {
            if (el) el.classList.remove('is-valid', 'is-invalid');
        });
    }

    function checkAll(touched) {
        var ok = true;
        if (isStatic()) {
            var vi = ip && ip.value.trim() !== '' && validV4(ip.value);
            if (touched || (ip && ip.value.trim() !== '')) mark(ip, vi);
            ok = vi && ok;
            var vm = mask && mask.value.trim() !== '' && validMask(mask.value);
            if (touched || (mask && mask.value.trim() !== '')) mark(mask, vm);
            ok = vm && ok;
        }
        if (isManual6()) {
            var v6 = ip6addr && ip6addr.value.trim() !== '' && validV6Cidr(ip6addr.value);
            if (touched || (ip6addr && ip6addr.value.trim() !== '')) mark(ip6addr, v6);
            ok = v6 && ok;
        } else if (ip6addr && ip6addr.value.trim() !== '') {
            var v6o = validV6Cidr(ip6addr.value);
            mark(ip6addr, v6o);
            ok = v6o && ok;
        }
        if (ip6gw && ip6gw.value.trim() !== '') {
            var vg = validV6(ip6gw.value);
            mark(ip6gw, vg);
            ok = vg && ok;
        }
        return ok;
    }

    form.addEventListener('change', function () { syncMode(); checkAll(false); });
    [ip, mask, ip6addr, ip6gw].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', function () { checkAll(true); });
        el.addEventListener('blur', function () { checkAll(true); });
    });
    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.name === 'cancel_interfase_edit') return;
        if (!checkAll(true)) { e.preventDefault(); return false; }
    });

    syncMode();
    checkAll(false);
})();
{/literal}
</script>
