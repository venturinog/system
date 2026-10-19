<form method="POST">
{if $NETWORK_ALERT}
{$NETWORK_ALERT}
{/if}
{if $PENDING_CHANGE eq 'yes'}
<div class="iss-alert iss-alert--warning">
    <div class="iss-alert__content"><strong>{$PENDING_WARNING}</strong><br>{$PENDING_SUMMARY} &mdash; {$PENDING_REMAINING}s</div>
    <input class="button" type="submit" name="confirm_network_change" value="{$CONFIRM_CHANGE}">
    <input class="button" type="submit" name="revert_network_change" value="{$REVERT_CHANGE}">
</div>
{/if}
{if $DRAFT_COUNT > 0}
<div id="issabel_draft" class="iss-alert iss-alert--success">
    <div class="iss-alert__title">{$PENDING_CHANGES_TITLE}</div>
    <ul class="iss-alert__list">
    {foreach from=$DRAFT_CHANGES item=L}
        <li>{$L}</li>
    {/foreach}
    </ul>
    <input class="button" type="submit" name="apply_changes" value="{$APPLY_CHANGES}">
    <input class="button" type="submit" name="discard_changes" value="{$DISCARD_CHANGES}">
</div>
{/if}
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $mode eq 'input'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_network_changes" value="{$SAVE}" >
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT_PARAMETERS}">
            {/if}
        </div>
    </div>

    {if $mode eq 'input'}
    <div class="iss-reveal">
    <section class="iss-card">
        <div class="iss-card__header"><i class="ti ti-settings"></i> {$GENERAL}</div>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$host.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$host.INPUT}</span>
                {if $NETWORK_ERRORS.host}<div class="invalid-feedback d-block">{$NETWORK_ERRORS.host}</div>{/if}
            </div>
        </div>
    </section>

    <section class="iss-card">
        <div class="iss-card__header"><i class="ti ti-network"></i> IPv4</div>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$dns1.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$dns1.INPUT}</span>
                {if $NETWORK_ERRORS.dns1}<div class="invalid-feedback d-block">{$NETWORK_ERRORS.dns1}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns2.LABEL}</span>
                <span class="iss-field__control">{$dns2.INPUT}</span>
                {if $NETWORK_ERRORS.dns2}<div class="invalid-feedback d-block">{$NETWORK_ERRORS.dns2}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$gateway.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$gateway.INPUT}</span>
                {if $NETWORK_ERRORS.gateway}<div class="invalid-feedback d-block">{$NETWORK_ERRORS.gateway}</div>{/if}
            </div>
        </div>
    </section>

    <section class="iss-card">
        <div class="iss-card__header"><i class="ti ti-world"></i> IPv6</div>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$dns6.LABEL}</span>
                <span class="iss-field__control">{$dns6.INPUT}</span>
                {if $NETWORK_ERRORS.dns6}<div class="invalid-feedback d-block">{$NETWORK_ERRORS.dns6}</div>{/if}
            </div>
        </div>
    </section>
    </div>
    {else}
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--3col iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$host.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $NETWORK_VIEW.host eq ''} iss-field__value--empty{/if}">{if $NETWORK_VIEW.host eq ''}&mdash;{else}{$NETWORK_VIEW.host|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns1.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $NETWORK_VIEW.dns1 eq ''} iss-field__value--empty{/if}">{if $NETWORK_VIEW.dns1 eq ''}&mdash;{else}{$NETWORK_VIEW.dns1|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns2.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $NETWORK_VIEW.dns2 eq ''} iss-field__value--empty{/if}">{if $NETWORK_VIEW.dns2 eq ''}&mdash;{else}{$NETWORK_VIEW.dns2|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$gateway.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $NETWORK_VIEW.gateway eq ''} iss-field__value--empty{/if}">{if $NETWORK_VIEW.gateway eq ''}&mdash;{else}{$NETWORK_VIEW.gateway|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns6.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $NETWORK_VIEW.dns6 eq ''} iss-field__value--empty{/if}">{if $NETWORK_VIEW.dns6 eq ''}&mdash;{else}{$NETWORK_VIEW.dns6|escape}{/if}</span>
            </div>
        </div>
    </section>
    {/if}
</div>
</form>
{$ETHERNET_INTERFASES_LIST}
{$ROUTES_LIST}
{if $mode eq 'input'}
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
    function validV6(v) {
        v = (v || '').trim();
        if (v.indexOf(':') === -1) return false;
        try { return !!new URL('http://[' + v + ']/'); }
        catch (e) { return /^[0-9a-fA-F:]+$/.test(v); }
    }
    function validHost(v) { return /^([A-Za-z0-9-]+\.)+[A-Za-z0-9]+$/.test(v); }
    function mark(el, ok) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }

    var host = q('host'), dns1 = q('dns1'), dns2 = q('dns2'), dns6 = q('dns6'), gw = q('gateway');
    var first = host || dns1 || dns2 || dns6 || gw;
    if (!first) return;
    var form = first.form;
    if (!form) return;

    function checkAll(touched) {
        var ok = true;

        var vh = !!host && host.value.trim() !== '' && validHost(host.value.trim());
        if (touched || (host && host.value.trim() !== '')) mark(host, vh);
        ok = vh && ok;

        var v1 = !!dns1 && dns1.value.trim() !== '' && validV4(dns1.value.trim());
        if (touched || (dns1 && dns1.value.trim() !== '')) mark(dns1, v1);
        ok = v1 && ok;

        if (dns2 && dns2.value.trim() !== '') {
            var v2 = validV4(dns2.value.trim());
            mark(dns2, v2);
            ok = v2 && ok;
        }

        var vg = !!gw && gw.value.trim() !== '' && validV4(gw.value.trim());
        if (touched || (gw && gw.value.trim() !== '')) mark(gw, vg);
        ok = vg && ok;

        if (dns6 && dns6.value.trim() !== '') {
            var v6 = validV6(dns6.value.trim());
            mark(dns6, v6);
            ok = v6 && ok;
        }

        return ok;
    }

    [host, dns1, dns2, dns6, gw].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', function () { checkAll(true); });
        el.addEventListener('blur', function () { checkAll(true); });
    });

    form.addEventListener('submit', function (e) {
        if (e.submitter && (e.submitter.name === 'cancel' || e.submitter.name === 'cancel_interfase_edit')) return;
        if (!checkAll(true)) { e.preventDefault(); return false; }
    });

    checkAll(false);
})();
{/literal}
</script>
{/if}
