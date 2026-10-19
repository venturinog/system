<form method="POST" action="?menu={$module_name}">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_dhcpclient" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'view'}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'edit'}
            <input class="button" type="submit" name="save_edit" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$iphost.LABEL}</span>
                <span class="iss-field__control">{$iphost.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$date_starts.LABEL}</span>
                <span class="iss-field__control">{$date_starts.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$date_ends.LABEL}</span>
                <span class="iss-field__control">{$date_ends.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$macaddress.LABEL}</span>
                <span class="iss-field__control">{$macaddress.INPUT}</span>
            </div>
        </div>
    </section>
</div>
<input class="button" type="hidden" name="id" value="{$ID}" />
</form>
