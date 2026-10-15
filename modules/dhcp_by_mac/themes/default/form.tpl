<form method="post" action="?menu={$MODULE_NAME}">
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $mode ne 'view'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_dhcp" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'view'}
            <input class="button" type="submit" name="edit_dhcpconf" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'edit'}
            <input class="button" type="submit" name="update_dhacp" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid{if $mode eq 'view'} iss-formgrid--readonly{/if}">
            <div class="iss-field">
                <span class="iss-field__label">{$hostname.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                {if $mode eq 'view'}
                <span class="iss-field__control iss-field__value{if $VALUES.hostname eq ''} iss-field__value--empty{/if}">{if $VALUES.hostname eq ''}&mdash;{else}{$VALUES.hostname|escape}{/if}</span>
                {else}
                <span class="iss-field__control">{$hostname.INPUT}</span>
                {if $ERRORS.hostname}<div class="invalid-feedback d-block">{$ERRORS.hostname}</div>{/if}
                {/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ipaddress.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                {if $mode eq 'view'}
                <span class="iss-field__control iss-field__value{if $VALUES.ipaddress eq ''} iss-field__value--empty{/if}">{if $VALUES.ipaddress eq ''}&mdash;{else}{$VALUES.ipaddress|escape}{/if}</span>
                {else}
                <span class="iss-field__control">{$ipaddress.INPUT}</span>
                {if $ERRORS.ipaddress}<div class="invalid-feedback d-block">{$ERRORS.ipaddress}</div>{/if}
                {/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$macaddress.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                {if $mode eq 'view'}
                <span class="iss-field__control iss-field__value{if $VALUES.macaddress eq ''} iss-field__value--empty{/if}">{if $VALUES.macaddress eq ''}&mdash;{else}{$VALUES.macaddress|escape}{/if}</span>
                {else}
                <span class="iss-field__control">{$macaddress.INPUT}</span>
                {if $ERRORS.macaddress}<div class="invalid-feedback d-block">{$ERRORS.macaddress}</div>{/if}
                {/if}
            </div>
        </div>
    </section>
</div>
<input type="hidden" name="id" value="{$ID}" />
</form>
<script>
{literal}
(function () {
    'use strict';

    function q(name) { return document.querySelector('[name="' + name + '"]'); }
    function validV4(v) {
        var m = (v || '').trim().match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
        if (!m) return false;
        for (var i = 1; i <= 4; i++) if (parseInt(m[i], 10) > 255) return false;
        return true;
    }
    function validMac(v) { return /^[0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5}$/.test((v || '').trim()); }
    function validHost(v) { return /^[A-Za-z0-9-]+$/.test((v || '').trim()); }
    function mark(el, ok) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }

    var host = q('hostname'), ip = q('ipaddress'), mac = q('macaddress');
    var first = host || ip || mac;
    if (!first) return;
    var form = first.form;
    if (!form) return;

    function checkAll(touched) {
        var ok = true;

        var vh = !!host && validHost(host.value);
        if (touched || (host && host.value.trim() !== '')) mark(host, vh);
        ok = vh && ok;

        var vi = !!ip && validV4(ip.value);
        if (touched || (ip && ip.value.trim() !== '')) mark(ip, vi);
        ok = vi && ok;

        var vm = !!mac && validMac(mac.value);
        if (touched || (mac && mac.value.trim() !== '')) mark(mac, vm);
        ok = vm && ok;

        return ok;
    }

    [host, ip, mac].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', function () { checkAll(true); });
        el.addEventListener('blur', function () { checkAll(true); });
    });

    form.addEventListener('submit', function (e) {
        // Cancel y el paso a edicion no validan
        if (e.submitter && (e.submitter.name === 'cancel' || e.submitter.name === 'edit_dhcpconf')) return;
        if (!checkAll(true)) { e.preventDefault(); return false; }
    });

    checkAll(false);
})();
{/literal}
</script>
