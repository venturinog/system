<ul class="iss-events">
{foreach from=$EVENTOS_DIAS item=evento}
    <li class="iss-events__item">
        <a class="iss-events__title" href='?menu=calendar&amp;action=display&amp;id={$evento.id}&amp;event_date={$evento.dateshort}'>{$evento.subject|escape:html}</a>
        <span class="iss-events__meta">{$tag_date}: {$evento.date} &middot; {$tag_call}: {$evento.call}</span>
    </li>
{foreachelse}
    <li class="iss-events__item">{$NO_EVENTOS}</li>
{/foreach}
</ul>
