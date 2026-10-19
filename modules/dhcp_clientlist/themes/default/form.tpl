<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <a class="button" href="?menu={$module_name}">{$CANCEL}</a>
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid iss-formgrid--readonly">
            <div class="iss-field">
                <span class="iss-field__label">{$iphost.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.iphost eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.iphost eq ''}&mdash;{else}{$DHCP_VIEW.iphost|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$macaddress.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.macaddress eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.macaddress eq ''}&mdash;{else}{$DHCP_VIEW.macaddress|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$date_starts.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.date_starts eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.date_starts eq ''}&mdash;{else}{$DHCP_VIEW.date_starts|escape}{/if}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$date_ends.LABEL}</span>
                <span class="iss-field__control iss-field__value{if $DHCP_VIEW.date_ends eq ''} iss-field__value--empty{/if}">{if $DHCP_VIEW.date_ends eq ''}&mdash;{else}{$DHCP_VIEW.date_ends|escape}{/if}</span>
            </div>
        </div>
    </section>
</div>
