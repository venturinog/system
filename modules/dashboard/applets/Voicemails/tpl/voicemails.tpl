<link rel="stylesheet" href="modules/{$module_name}/applets/Voicemails/tpl/css/styles.css" />

{if $rows}
<div class="iss-vm-head">
    <span class="badge bg-purple-lt">{$total} {$LABEL_NEW}</span>
</div>
<ul class="iss-vm">
{foreach from=$rows item=v}
    <li class="iss-vm__item">
        <a class="iss-vm__link" href="?menu=voicemail">
            <span class="iss-vm__icon"><i class="ti ti-microphone"></i></span>
            <span class="iss-vm__main">
                <strong>{$v.sender|escape:'html'}</strong>
                <small>{$v.when} &middot; {$v.duration} &middot; {$LABEL_MAILBOX} {$v.mailbox}</small>
            </span>
            <i class="ti ti-player-play iss-vm__play"></i>
        </a>
    </li>
{/foreach}
</ul>
<div class="iss-applet-more"><a href="?menu=voicemail">{$LABEL_VIEW_ALL}</a></div>
{else}
<div class="iss-empty">{$LABEL_NO_VM}</div>
{/if}
