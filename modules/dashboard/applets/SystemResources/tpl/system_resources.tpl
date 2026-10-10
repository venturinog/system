<link rel="stylesheet" media="screen" type="text/css" href="modules/{$module_name}/applets/SystemResources/tpl/css/styles.css" />
<script type='text/javascript' src='modules/{$module_name}/applets/SystemResources/js/javascript.js'></script>

<div id="lastlogin" class="iss-applet-note">{$lastlogin}</div>

<div class="iss-gauges">
    <div class="iss-gauge" id="cpugauge">
        <div id="dashboard-applet-cpugauge" style="width:140px; height:140px"></div>
        <input type="hidden" name="cpugauge_value" id="cpugauge_value" value="{$cpugauge.fraction}" />
        <input type="hidden" name="cpugauge_label" id="cpugauge_label" value="{$LABEL_CPU|escape:html}" />
    </div>
    <div class="iss-gauge" id="memgauge">
        <div id="dashboard-applet-memgauge" style="width:140px; height:140px"></div>
        <input type="hidden" name="memgauge_value" id="memgauge_value" value="{$memgauge.fraction}" />
        <input type="hidden" name="memgauge_label" id="memgauge_label" value="{$LABEL_RAM|escape:html}" />
    </div>
    <div class="iss-gauge" id="swapgauge">
        <div id="dashboard-applet-swapgauge" style="width:140px; height:140px"></div>
        <input type="hidden" name="swapgauge_value" id="swapgauge_value" value="{$swapgauge.fraction}" />
        <input type="hidden" name="swapgauge_label" id="swapgauge_label" value="{$LABEL_SWAP|escape:html}" />
    </div>
</div>

<ul class="iss-applet-specs">
    <li><span>{$LABEL_CPUINFO}</span><strong>{$cpu_info}</strong></li>
    <li><span>{$LABEL_UPTIME}</span><strong>{$uptime}</strong></li>
    <li><span>{$LABEL_CPUSPEED}</span><strong>{$speed}</strong></li>
    <li><span>{$LABEL_MEMORYUSE}</span><strong>RAM: {$memtotal} &middot; SWAP: {$swaptotal}</strong></li>
</ul>
