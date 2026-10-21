<form method="POST" action="?menu={$module_name}" id="ftp_form">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save_new_FTP" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}" formnovalidate>
        </div>
    </div>

    {if !$FTP_CONFIGURED}
    <div class="iss-alert iss-alert--info">
        <div class="iss-alert__content">{$FTP_NOT_CONFIGURED}</div>
    </div>
    {/if}

    <div id="ftp-message"></div>

    <div class="row g-3">
        <div class="col-md-6">
            <section class="iss-card h-100">
                <header class="iss-card__header"><i class="ti ti-folder"></i> {$LOCAL_LABEL}</header>
                <div class="iss-card__body">
                    <ul id="sortable1" class="list-unstyled border rounded p-2 mb-0{if $FTP_CONFIGURED} droptrue{/if}" style="min-height:140px">
                        {foreach from=$local_files item=file}
                        <li id="inn_{$file|escape}">{$file|escape}</li>
                        {/foreach}
                    </ul>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="iss-card h-100">
                <header class="iss-card__header"><i class="ti ti-server"></i> {$REMOTE_LABEL}</header>
                <div class="iss-card__body">
                    <ul id="sortable2" class="list-unstyled border rounded p-2 mb-0{if $FTP_CONFIGURED} droptrue2{/if}" style="min-height:140px">
                        {foreach from=$remote_files item=file}
                        <li id="out_{$file|escape}">{$file|escape}</li>
                        {/foreach}
                    </ul>
                </div>
            </section>
        </div>
    </div>

    <div class="iss-field__help mb-3">{$TITLE}</div>

    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-settings"></i> {$CREDENTIALS_TITLE}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$server.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$server.INPUT}</span>
                {if $ERRORS.server}<div class="invalid-feedback d-block">{$ERRORS.server}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$port.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$port.INPUT}</span>
                {if $ERRORS.port}<div class="invalid-feedback d-block">{$ERRORS.port}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$user.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$user.INPUT}</span>
                {if $ERRORS.user}<div class="invalid-feedback d-block">{$ERRORS.user}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$password.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$password.INPUT}</span>
                {if $ERRORS.password}<div class="invalid-feedback d-block">{$ERRORS.password}</div>{/if}
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$pathServer.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$pathServer.INPUT}</span>
                {if $ERRORS.pathServer}<div class="invalid-feedback d-block">{$ERRORS.pathServer}</div>{/if}
            </div>
        </div>
    </section>
</div>
</form>
<script>
{literal}
(function () {
    var form = document.getElementById('ftp_form');
    if (!form) return;

    // El aviso nativo del navegador no pinta el recuadro del tema: se marca
    // el campo con is-invalid, que se quita en cuanto el usuario lo corrige.
    form.addEventListener('invalid', function (e) {
        if (e.target && e.target.tagName === 'INPUT') e.target.classList.add('is-invalid');
    }, true);

    form.addEventListener('input', function (e) {
        var campo = e.target;
        if (campo && campo.classList && campo.classList.contains('is-invalid') && campo.value.trim() !== '')
            campo.classList.remove('is-invalid');
    });
})();
{/literal}
</script>
