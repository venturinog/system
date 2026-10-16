<link rel="stylesheet" href="modules/{$module_name}/applets/Calendar/tpl/css/styles.css" />

{if $EVENTOS_DIAS}
<ul class="iss-events">
{foreach from=$EVENTOS_DIAS item=evento}
    <li class="iss-events__item">
        <span class="iss-events__date"><strong>{$evento.day}</strong><small>{$evento.month}</small></span>
        <span class="iss-events__main">
            <a class="iss-events__title" href="?menu=calendar&amp;action=display&amp;id={$evento.id}&amp;event_date={$evento.dateshort}">{$evento.subject|escape:'html'}</a>
            <small class="iss-events__meta">
                {$evento.time}
                {if $evento.call}&nbsp;<span class="badge bg-azure-lt">{$LABEL_CALL}</span>{/if}
            </small>
        </span>
    </li>
{/foreach}
</ul>
<div class="iss-applet-more"><a href="?menu=calendar">{$LABEL_VIEW_CAL}</a></div>
{else}
<div class="iss-empty">{$NO_EVENTOS}</div>
{/if}
