<link rel="stylesheet" href="modules/{$module_name}/applets/Calls/tpl/css/styles.css" />

{if $rows}
<table class="iss-calls-table">
    <thead>
        <tr>
            <th>{$LABEL_WHEN}</th>
            <th></th>
            <th>{$LABEL_PEER}</th>
            <th>{$LABEL_STATUS}</th>
            <th class="text-end">{$LABEL_DURATION}</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$rows item=r}
        <tr>
            <td class="iss-calls-table__when">{$r.when}</td>
            <td class="iss-calls-table__dir" title="{if $r.dir == 'out'}{$LABEL_OUT}{else}{$LABEL_IN}{/if}">
                <i class="ti {if $r.dir == 'out'}ti-arrow-up-right{else}ti-arrow-down-left{/if}"></i>
            </td>
            <td class="iss-calls-table__peer">{$r.peer|escape:'html'}</td>
            <td><span class="badge {$r.badge}">{$r.status_label}</span></td>
            <td class="iss-calls-table__dur">{$r.duration}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
<div class="iss-applet-more"><a href="?menu=cdrreport">{$LABEL_VIEW_ALL}</a></div>
{else}
<div class="iss-empty">{$LABEL_NO_CALLS}</div>
{/if}
