<form method="post">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" name="in_actualizar_conf_red" value="{$CONFIGURATION_UPDATE}" type="submit" />
            {if $SERVICE_STARING}
            <input class="button" name="in_finalizar" value="{$SERVICE_STOP}" type="submit" />
            {else}
            <input class="button" name="in_iniciar" value="{$SERVICE_START}" type="submit" />
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$STATUS}</span>
                <span class="iss-field__control">{$DHCP_STATUS}</span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$START_RANGE_OF_IPS}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_ip_ini_1.LABEL}{$in_ip_ini_1.INPUT}
                    {$in_ip_ini_2.LABEL}{$in_ip_ini_2.INPUT}
                    {$in_ip_ini_3.LABEL}{$in_ip_ini_3.INPUT}
                    {$in_ip_ini_4.LABEL}{$in_ip_ini_4.INPUT}
                </span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$END_RANGE_OF_IPS}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_ip_fin_1.LABEL}{$in_ip_fin_1.INPUT}
                    {$in_ip_fin_2.LABEL}{$in_ip_fin_2.INPUT}
                    {$in_ip_fin_3.LABEL}{$in_ip_fin_3.INPUT}
                    {$in_ip_fin_4.LABEL}{$in_ip_fin_4.INPUT}
                </span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$in_lease_time.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$in_lease_time.INPUT}&nbsp;&nbsp;({$OF_1_TO_50000_SECONDS})</span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$DNS_1}</span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_dns1_1.LABEL}{$in_dns1_1.INPUT}
                    {$in_dns1_2.LABEL}{$in_dns1_2.INPUT}
                    {$in_dns1_3.LABEL}{$in_dns1_3.INPUT}
                    {$in_dns1_4.LABEL}{$in_dns1_4.INPUT}&nbsp;&nbsp;({$OPTIONAL})
                </span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$DNS_2}</span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_dns2_1.LABEL}{$in_dns2_1.INPUT}
                    {$in_dns2_2.LABEL}{$in_dns2_2.INPUT}
                    {$in_dns2_3.LABEL}{$in_dns2_3.INPUT}
                    {$in_dns2_4.LABEL}{$in_dns2_4.INPUT}&nbsp;&nbsp;({$OPTIONAL})
                </span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$WINS}</span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_wins_1.LABEL}{$in_wins_1.INPUT}
                    {$in_wins_2.LABEL}{$in_wins_2.INPUT}
                    {$in_wins_3.LABEL}{$in_wins_3.INPUT}
                    {$in_wins_4.LABEL}{$in_wins_4.INPUT}&nbsp;&nbsp;({$OPTIONAL})
                </span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$GATEWAY}</span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_gw_1.LABEL}{$in_gw_1.INPUT}
                    {$in_gw_2.LABEL}{$in_gw_2.INPUT}
                    {$in_gw_3.LABEL}{$in_gw_3.INPUT}
                    {$in_gw_4.LABEL}{$in_gw_4.INPUT}&nbsp;&nbsp;({$OPTIONAL})
                </span>
            </div>
            <div class="iss-field iss-field--full">
                <span class="iss-field__label">{$NEXT_SERVER}</span>
                <span class="iss-field__control iss-ip-octets">
                    {$in_next_1.LABEL}{$in_next_1.INPUT}
                    {$in_next_2.LABEL}{$in_next_2.INPUT}
                    {$in_next_3.LABEL}{$in_next_3.INPUT}
                    {$in_next_4.LABEL}{$in_next_4.INPUT}&nbsp;&nbsp;({$OPTIONAL})
                </span>
            </div>
        </div>
    </section>
</div>
</form>
