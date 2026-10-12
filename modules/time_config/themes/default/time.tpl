<script type="text/javascript">

var serv_date2 = new Date({$CURRENT_DATETIME});
var browser_date = new Date();
var serv_msecdiff = browser_date.getTime() - serv_date2.getTime();
</script>


<form action="#" method="POST">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="Actualizar" value="{$INDEX_ACTUALIZAR}" onClick="return confirm('{$TIME_MSG_1}');" />
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$INDEX_HORA_SERVIDOR}</span>
                <span class="iss-field__control"><span id="SERVER_TIME" align='right'></span></span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$TIME_NUEVA_FECHA}</span>
                <span class="iss-field__control"><input type="text" name="date" id="datepicker" value="{$CURRENT_DATE}" class="form-control form-control-sm" style="max-width: 12em; text-align: center;" readonly></span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$TIME_NUEVA_HORA}</span>
                <span class="iss-field__control">{html_select_time prefix="ServerDate_" }</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$TIME_NUEVA_ZONA}</span>
                <span class="iss-field__control">{html_options name="TimeZone" selected=$ZONA_ACTUAL values=$LISTA_ZONAS output=$LISTA_ZONAS }</span>
            </div>
        </div>
    </section>
    <input type='hidden' name='configurar_hora' id='configurar_hora' value='0' />
</div>
</form>
