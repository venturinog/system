<form method="POST" enctype="multipart/form-data" id="submit_decrypt">
{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="dodecrypt" value="{$PROCESS}">
            <input class="button" type="submit" name="back" value="{$BACK}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            {if $ERROR_MSG}
            <div class="iss-field iss-field--full">
                <span class="iss-field__control">{$ERROR_MSG}</span>
            </div>
            {/if}
            <div class="iss-field">
                <span class="iss-field__label">{$PASSPHRASE}</span>
                <span class="iss-field__control"><input type="password" name="passphrase" id="passphraase" class="form-control"></span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__help">{$DECRYPT_HELP}</span>
                <input type="hidden" id="filename" name="filename" value="{$BACKUP_FILE}">
            </div>
        </div>
    </section>
</div>
</form>
