<form method="POST">
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
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$host.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$host.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns1.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$dns1.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$gateway.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$gateway.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns2.LABEL}</span>
                <span class="iss-field__control">{$dns2.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$gateway_dev.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$gateway_dev.INPUT}</span>
            </div>
        </div>
    </section>
</div>
</form>
{$ETHERNET_INTERFASES_LIST}
