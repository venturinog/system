<link rel="stylesheet" media="screen" type="text/css" href="modules/{$module_name}/applets/HardDrives/tpl/css/styles.css" />
<script type='text/javascript' src='modules/{$module_name}/applets/HardDrives/js/javascript.js'></script>

{foreach from=$part item=particion name=part}
<div class="iss-hd">
    <div class="iss-hd__gauge" id="dashboard-applet-hd-usage-{$smarty.foreach.part.index}" style="width:150px; height:150px;"></div>
    <script>
        {literal}
        var ram = new JustGage({
          id: "dashboard-applet-hd-usage-{/literal}{$smarty.foreach.part.index}{literal}",
          value: {/literal}{$particion.porcentaje_usado}{literal},
          min: 0,
          max: 100,
          donut: true,
          startAnimationType : 'bounce',
          shadowSize: 0,
          shadowVerticalOffset: 0,
          valueFontColor: '#666666',
          levelColors : ['#3184d5'],
          gaugeColor : '#6e407e',
          label: "%"
        }); {/literal}
    </script>
    <div class="iss-hd__info">
        <div class="iss-hd__legend">
            <span class="iss-hd__dot used"></span> {$particion.formato_porcentaje_usado}% {$LABEL_PERCENT_USED}
            &nbsp;&nbsp;
            <span class="iss-hd__dot free"></span> {$particion.formato_porcentaje_libre}% {$LABEL_PERCENT_AVAILABLE}
        </div>
        <ul class="iss-hd__specs">
            <li><span>{$LABEL_DISK_CAPACITY}</span><strong>{$particion.sTotalGB} GB</strong></li>
            <li><span>{$LABEL_MOUNTPOINT}</span><strong>{$particion.punto_montaje}</strong></li>
            <li><span>{$LABEL_DISK_VENDOR}</span><strong>{$particion.sModelo}</strong></li>
        </ul>
    </div>
</div>
{/foreach}

<div class="neo-divisor"></div>
<div id="harddrives_dirspacereport">
    <p class="iss-hd__warn">{$TEXT_WARNING_DIRSPACEREPORT}</p>
    <button class="btn btn-sm btn-outline-secondary" id="harddrives_dirspacereport_fetch">{$FETCH_DIRSPACEREPORT}</button>
</div>
