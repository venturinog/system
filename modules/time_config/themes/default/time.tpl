<form action="?menu={$module_name}" method="POST">
<div class="iss-form" data-server-time="{$SERVER_TIME_ISO}">
    <div class="iss-form__toolbar">
        {if $mode eq 'input'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="Actualizar" value="{$INDEX_ACTUALIZAR}" onClick="return confirm('{$TIME_MSG_1}');" />
            <input class="button" type="submit" name="cancel" value="{$CANCEL}" />
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT}" />
            {/if}
        </div>
    </div>
    {if $mode eq 'input'}<div class="iss-reveal">{/if}
    <section class="iss-card">
        {if $mode eq 'view'}
        <div class="iss-card__body iss-formgrid iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$INDEX_HORA_SERVIDOR}</span>
                <span class="iss-field__control iss-field__value" id="SERVER_TIME">{$TIME_VIEW.datetime}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$TIME_NUEVA_ZONA}</span>
                <span class="iss-field__control iss-field__value{if $TIME_VIEW.timezone eq ''} iss-field__value--empty{/if}">{if $TIME_VIEW.timezone eq ''}&mdash;{else}{$TIME_VIEW.timezone|escape}{/if}</span>
            </div>
        </div>
        {else}
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$INDEX_HORA_SERVIDOR}</span>
                <span class="iss-field__control"><span id="SERVER_TIME"></span></span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$TIME_NUEVA_FECHA}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$date.INPUT}</span>
                {if $ERRORS.date}<div class="invalid-feedback d-block">{$ERRORS.date}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ServerDate_Hour.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$ServerDate_Hour.INPUT}</span>
                {if $ERRORS.ServerDate_Hour}<div class="invalid-feedback d-block">{$ERRORS.ServerDate_Hour}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ServerDate_Minute.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$ServerDate_Minute.INPUT}</span>
                {if $ERRORS.ServerDate_Minute}<div class="invalid-feedback d-block">{$ERRORS.ServerDate_Minute}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ServerDate_Second.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$ServerDate_Second.INPUT}</span>
                {if $ERRORS.ServerDate_Second}<div class="invalid-feedback d-block">{$ERRORS.ServerDate_Second}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$TIME_NUEVA_ZONA}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$TimeZone.INPUT}</span>
                {if $ERRORS.TimeZone}<div class="invalid-feedback d-block">{$ERRORS.TimeZone}</div>{/if}
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

    var cont = document.querySelector('.iss-form[data-server-time]');
    if (!cont) return;

    // La hora del servidor llega en un atributo de datos: el mes va en base
    // 0, como lo espera el constructor de Date.
    var partes = (cont.getAttribute('data-server-time') || '').split(',');
    if (partes.length !== 6) return;
    var serv_date2 = new Date(
        parseInt(partes[0], 10), parseInt(partes[1], 10), parseInt(partes[2], 10),
        parseInt(partes[3], 10), parseInt(partes[4], 10), parseInt(partes[5], 10)
    );
    if (isNaN(serv_date2.getTime())) return;

    var serv_msecdiff = new Date().getTime() - serv_date2.getTime();

    function pintar() {
        var server_date = new Date();
        server_date.setTime(server_date.getTime() - serv_msecdiff);
        var destino = document.getElementById('SERVER_TIME');
        if (destino) destino.textContent = server_date.toLocaleString();
    }

    pintar();
    setInterval(pintar, 500);
})();
{/literal}
</script>
