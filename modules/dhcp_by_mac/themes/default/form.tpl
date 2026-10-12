<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $mode ne 'view'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_dhcp" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'view'}
            <input class="button" type="submit" name="edit_dhcpconf" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'edit'}
            <input class="button" type="submit" name="update_dhacp" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$hostname.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$hostname.INPUT}&nbsp;&nbsp;{$HOST_NAME}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$ipaddress.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$ipaddress.INPUT}&nbsp;&nbsp;{$IP_ADDRESS}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$macaddress.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$macaddress.INPUT}&nbsp;&nbsp;{$MAC_ADDRESS}</span>
            </div>
        </div>
    </section>
</div>
<input class="button" type="hidden" name="id" value="{$ID}" />
