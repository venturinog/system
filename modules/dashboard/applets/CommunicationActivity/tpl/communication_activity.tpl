<link rel="stylesheet" media="screen" type="text/css" href="modules/{$module_name}/applets/CommunicationActivity/tpl/css/styles.css" />
<script type='text/javascript' src='modules/{$module_name}/applets/CommunicationActivity/js/javascript.js'></script>

<div class="iss-ca">
    <div class="iss-ca__item">
        <span class="iss-ca__label">{$LABEL_TOTAL_CALLS}</span>
        <span class="iss-ca__value">
            {$LABEL_CALLS} <strong>({$total})</strong>
            <span class="badge bg-green-lt">{$internal} {$LABEL_INTERNAL_CALLS}</span>
            <span class="badge bg-red-lt">{$external} {$LABEL_EXTERNAL_CALLS}</span>
        </span>
    </div>

    <div class="iss-ca__item">
        <span class="iss-ca__label">{$LABEL_TOTAL_CHANNELS}</span>
        <span class="iss-ca__value">{$channel}</span>
    </div>

    <div class="iss-ca__item">
        <span class="iss-ca__label">{$LABEL_QUEUES_WAITING}</span>
        <span class="iss-ca__value">{$totalQueues} {$LABEL_WAITING}</span>
    </div>

    <div class="iss-ca__item">
        <span class="iss-ca__label">{$LABEL_EXTENSIONS}</span>
        <span class="iss-ca__value">
            {$LABEL_SIP_EXTENSIONS} <strong>({$total_sip_Ext})</strong>
            <span class="badge bg-green-lt">{$sip_Ext_ok} {$LABEL_STATUS_OK}</span>
            <span class="badge bg-red-lt">{$sip_Ext_nok} {$LABEL_STATUS_NO_OK}</span>
        </span>
    </div>
    <div class="iss-ca__item">
        <span class="iss-ca__label"></span>
        <span class="iss-ca__value">
            {$LABEL_PJSIP_EXTENSIONS} <strong>({$total_pjsip_Ext})</strong>
            <span class="badge bg-green-lt">{$pjsip_Ext_ok} {$LABEL_STATUS_OK}</span>
            <span class="badge bg-red-lt">{$pjsip_Ext_nok} {$LABEL_STATUS_NO_OK}</span>
        </span>
    </div>
    <div class="iss-ca__item">
        <span class="iss-ca__label"></span>
        <span class="iss-ca__value">
            {$LABEL_IAX_EXTENSIONS} <strong>({$total_iax_Ext})</strong>
            <span class="badge bg-green-lt">{$iax_Ext_ok} {$LABEL_STATUS_OK}</span>
            <span class="badge bg-red-lt">{$iax_Ext_nok} {$LABEL_STATUS_NO_OK}</span>
        </span>
    </div>

    <div class="iss-ca__item">
        <span class="iss-ca__label">{$LABEL_TRUNKS} (SIP/IAX)</span>
        <span class="iss-ca__value">
            <strong>({$total_trunks})</strong>
            <span class="badge bg-green-lt">{$total_trunks_ok} {$LABEL_STATUS_OK}</span>
            <span class="badge bg-red-lt">{$total_trunks_nok} {$LABEL_STATUS_NO_OK}</span>
            <span class="badge bg-secondary-lt">{$total_trunks_unk} {$LABEL_UNKNOWN}</span>
        </span>
    </div>

    <div class="iss-ca__item">
        <span class="iss-ca__label">{$LABEL_NETWORK_TRAFFIC}</span>
        <span class="iss-ca__value">
            {$LABEL_BYTES}
            RX <strong><span id='communication_activity_rx_bytes'>{$rx_bytes}</span> kB/s</strong>
            &nbsp;|&nbsp;
            TX <strong><span id='communication_activity_tx_bytes'>{$tx_bytes}</span> kB/s</strong>
        </span>
    </div>
</div>
