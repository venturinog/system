<form method="POST">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_interfase_changes" value="{$APPLY_CHANGES}"
                   onClick="return confirmSubmit('{$CONFIRM_EDIT}')">
            <input class="button" type="submit" name="cancel_interfase_edit" value="{$CANCEL}  ">
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT_PARAMETERS}">
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$type.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$type.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ip.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$ip.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$mask.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$mask.INPUT}</span>
            </div>
        </div>
    </section>
    {$dev_id.INPUT}
</div>
</form>
