<form method="POST" action="?menu={$module_name}">
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $mode eq 'input'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT}">
            {/if}
        </div>
    </div>
    {if $mode eq 'input'}<div class="iss-reveal">{/if}
    <section class="iss-card">
        {if $mode eq 'view'}
        <div class="iss-card__body iss-formgrid iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$currency.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $CURRENCY_VIEW.symbol eq ''} iss-field__value--empty{/if}">{if $CURRENCY_VIEW.symbol eq ''}&mdash;{else}{$CURRENCY_VIEW.symbol|escape} &mdash; {$CURRENCY_VIEW.name|escape}{/if}</span>
            </div>
        </div>
        {else}
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$currency.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$currency.INPUT}</span>
                {if $CURRENCY_ERROR}<div class="invalid-feedback d-block">{$CURRENCY_ERROR}</div>{/if}
            </div>
        </div>
        {/if}
    </section>
    {if $mode eq 'input'}</div>{/if}
</div>
</form>
