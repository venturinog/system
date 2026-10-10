<link rel="stylesheet" media="screen" type="text/css" href="modules/{$module_name}/applets/ProcessesStatus/tpl/css/styles.css" />
<script type='text/javascript' src='modules/{$module_name}/applets/ProcessesStatus/js/javascript.js'></script>

{* Menú flotante de control (lo posiciona el JS) *}
<div class="neo-applet-processes-menu">
    <input type="hidden" id="neo_applet_selected_process" value="" />
    <div id="neo-applet-processes-controles">
        <input type="button" class="neo_applet_process btn btn-sm btn-outline-danger"  name="processcontrol_stop"       id="neo_applet_process_stop"       value="{$sMsgStop}" />
        <input type="button" class="neo_applet_process btn btn-sm btn-outline-success" name="processcontrol_start"      id="neo_applet_process_start"      value="{$sMsgStart}" />
        <input type="button" class="neo_applet_process btn btn-sm btn-outline-secondary" name="processcontrol_restart"  id="neo_applet_process_restart"    value="{$sMsgRestart}" />
        <input type="button" class="neo_applet_process btn btn-sm btn-outline-secondary" name="processcontrol_activate" id="neo_applet_process_activate"   value="{$sMsgActivate}" />
        <input type="button" class="neo_applet_process btn btn-sm btn-outline-secondary" name="processcontrol_deactivate" id="neo_applet_process_deactivate" value="{$sMsgDeactivate}" />
    </div>
    <img id="neo-applet-processes-processing" src="modules/{$module_name}/applets/ProcessesStatus/images/loading.gif" style="display: none;" alt="" />
</div>

<div class="iss-proclist">
{foreach from=$services item=infoServicio key=sServicio}
    <div class="neo-applet-processes-row iss-proc">
        <span class="iss-proc__dot {if $infoServicio.status_service eq 'OK'}on{else}off{/if}"></span>
        <div class="neo-applet-processes-row-icon iss-proc__icon"><img src="modules/{$module_name}/applets/ProcessesStatus/images/{$infoServicio.icon}" width="24" height="24" alt="{$sServicio}" /></div>
        <div class="neo-applet-processes-row-name iss-proc__name">{$infoServicio.name_service}</div>
        <div class="neo-applet-processes-row-status-msg iss-proc__status" style="color: {$infoServicio.status_color}">{$infoServicio.status_desc}</div>
        <div class="neo-applet-processes-row-menu iss-proc__menu" title="{$sMsgRestart}">
            <input type="hidden" name="key-servicio" id="key-servicio" value="{$sServicio}" />
            <input type="hidden" name="status-servicio" id="status-servicio" value="{$infoServicio.status_service}" />
            <input type="hidden" name="activate-process" id="activate-process" value="{$infoServicio.activate}" />
            <img src="modules/{$module_name}/applets/ProcessesStatus/images/{$infoServicio.status_service_icon}" style="cursor:{$infoServicio.pointer_style};" width="15" height="15" alt="menu" />
        </div>
    </div>
{/foreach}
</div>
