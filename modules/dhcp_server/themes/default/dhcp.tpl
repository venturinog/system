<form method="post">
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $mode eq 'input'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" name="in_actualizar_conf_red" value="{$CONFIGURATION_UPDATE}" type="submit" />
            <input class="button" name="cancel" value="{$CANCEL}" type="submit" />
            {else}
            {if $SERVICE_STARING}
            <input class="button" name="in_finalizar" value="{$SERVICE_STOP}" type="submit" />
            {else}
            <input class="button" name="in_iniciar" value="{$SERVICE_START}" type="submit" />
            {/if}
            <input class="button" name="edit" value="{$EDIT_CONFIGURATION}" type="submit" />
            {/if}
        </div>
    </div>
    {if $mode eq 'input'}<div class="iss-reveal">{/if}
    <section class="iss-card">
        {if $mode eq 'view'}
        <div class="iss-card__body iss-formgrid iss-formgrid--3col iss-formgrid--readonly">
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$STATUS}</span>
                <span class="iss-field__control iss-field__value{if !$DHCP_STATUS_ACTIVE} iss-field__value--empty{/if}">{$DHCP_STATUS}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_ip_ini.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_ip_ini eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_ip_ini eq ''}&mdash;{else}{$DHCP_VIEW.in_ip_ini|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_ip_fin.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_ip_fin eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_ip_fin eq ''}&mdash;{else}{$DHCP_VIEW.in_ip_fin|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_lease_time.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_lease_time eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_lease_time eq ''}&mdash;{else}{$DHCP_VIEW.in_lease_time|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_dns1.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_dns1 eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_dns1 eq ''}&mdash;{else}{$DHCP_VIEW.in_dns1|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_dns2.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_dns2 eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_dns2 eq ''}&mdash;{else}{$DHCP_VIEW.in_dns2|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_wins.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_wins eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_wins eq ''}&mdash;{else}{$DHCP_VIEW.in_wins|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_gw.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_gw eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_gw eq ''}&mdash;{else}{$DHCP_VIEW.in_gw|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_next.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.in_next eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.in_next eq ''}&mdash;{else}{$DHCP_VIEW.in_next|escape}{/if}</span>
            </div>
        </div>
        {else}
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$STATUS}</span>
                <span class="iss-field__control iss-field__value{if !$DHCP_STATUS_ACTIVE} iss-field__value--empty{/if}">{$DHCP_STATUS}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_ip_ini.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$in_ip_ini.INPUT}</span>
                {if $ERRORS.in_ip_ini}<div class="invalid-feedback d-block">{$ERRORS.in_ip_ini}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_ip_fin.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$in_ip_fin.INPUT}</span>
                {if $ERRORS.in_ip_fin}<div class="invalid-feedback d-block">{$ERRORS.in_ip_fin}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_lease_time.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$in_lease_time.INPUT}</span>
                <div class="iss-field__help">{$OF_1_TO_50000_SECONDS}</div>
                {if $ERRORS.in_lease_time}<div class="invalid-feedback d-block">{$ERRORS.in_lease_time}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_dns1.LABEL}</span>
                <span class="iss-field__control">{$in_dns1.INPUT}</span>
                {if $ERRORS.in_dns1}<div class="invalid-feedback d-block">{$ERRORS.in_dns1}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_dns2.LABEL}</span>
                <span class="iss-field__control">{$in_dns2.INPUT}</span>
                {if $ERRORS.in_dns2}<div class="invalid-feedback d-block">{$ERRORS.in_dns2}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_wins.LABEL}</span>
                <span class="iss-field__control">{$in_wins.INPUT}</span>
                {if $ERRORS.in_wins}<div class="invalid-feedback d-block">{$ERRORS.in_wins}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_gw.LABEL}</span>
                <span class="iss-field__control">{$in_gw.INPUT}</span>
                {if $ERRORS.in_gw}<div class="invalid-feedback d-block">{$ERRORS.in_gw}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_next.LABEL}</span>
                <span class="iss-field__control">{$in_next.INPUT}</span>
                {if $ERRORS.in_next}<div class="invalid-feedback d-block">{$ERRORS.in_next}</div>{/if}
            </div>
        </div>
        {/if}
    </section>
    {if $mode eq 'input'}</div>{/if}
</div>
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
    function mark(el, ok) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }

    var ini = q('in_ip_ini'), fin = q('in_ip_fin'), lease = q('in_lease_time');
    var optional = [q('in_dns1'), q('in_dns2'), q('in_wins'), q('in_gw'), q('in_next')];
    var first = ini || fin;
    if (!first) return;
    var form = first.form;
    if (!form) return;

    function checkAll(touched) {
        var ok = true;

        var vIni = !!ini && ini.value.trim() !== '' && validV4(ini.value.trim());
        if (touched || (ini && ini.value.trim() !== '')) mark(ini, vIni);
        ok = vIni && ok;

        var vFin = !!fin && fin.value.trim() !== '' && validV4(fin.value.trim());
        if (touched || (fin && fin.value.trim() !== '')) mark(fin, vFin);
        ok = vFin && ok;

        var lv = lease ? (lease.value || '').trim() : '';
        var vLease = !!lease && /^\d+$/.test(lv) && parseInt(lv, 10) >= 1 && parseInt(lv, 10) <= 50000;
        if (touched || lv !== '') mark(lease, vLease);
        ok = vLease && ok;

        optional.forEach(function (el) {
            if (!el) return;
            if (el.value.trim() === '') { el.classList.remove('is-valid', 'is-invalid'); return; }
            var v = validV4(el.value.trim());
            mark(el, v);
            ok = v && ok;
        });

        return ok;
    }

    [ini, fin, lease].concat(optional).forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', function () { checkAll(true); });
        el.addEventListener('blur', function () { checkAll(true); });
    });

    form.addEventListener('submit', function (e) {
        // Solo el boton de guardado valida; Start/Stop del servicio y
        // Cancel no validan.
        if (e.submitter && e.submitter.name !== 'in_actualizar_conf_red') return;
        if (!checkAll(true)) { e.preventDefault(); return false; }
    });

    checkAll(false);
})();
{/literal}
</script>
