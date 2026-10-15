<form method="POST">
{if $PENDING_CHANGE eq 'yes'}
<div style="border:1px solid #d9a000;background:#fff6d9;padding:8px 12px;margin-bottom:10px;">
    <div style="margin-bottom:6px;"><strong>{$PENDING_WARNING}</strong><br>{$PENDING_SUMMARY} &mdash; {$PENDING_REMAINING}s</div>
    <input class="button" type="submit" name="confirm_network_change" value="{$CONFIRM_CHANGE}">
    <input class="button" type="submit" name="revert_network_change" value="{$REVERT_CHANGE}">
</div>
{/if}
{if $DRAFT_COUNT > 0}
<div id="issabel_draft" style="border:1px solid #6aa84f;background:#eef7e6;padding:8px 12px;margin-bottom:10px;">
    <strong>{$PENDING_CHANGES_TITLE}</strong>
    <ul style="margin:6px 0 6px 18px;">
    {foreach from=$DRAFT_CHANGES item=L}
        <li>{$L}</li>
    {/foreach}
    </ul>
    <input class="button" type="submit" name="apply_changes" value="{$APPLY_CHANGES}">
    <input class="button" type="submit" name="discard_changes" value="{$DISCARD_CHANGES}">
</div>
{/if}
<div class="iss-form">
    <div class="iss-form__toolbar">
        {if $mode eq 'input'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_network_changes" value="{$SAVE}" >
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {else}
            <input class="button" type="submit" name="edit" value="{$EDIT_PARAMETERS}">
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$host.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$host.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns1.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$dns1.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$gateway.LABEL}{if $mode eq 'input'}&nbsp;<span class="iss-required">*</span>{/if}</span>
                <span class="iss-field__control">{$gateway.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns2.LABEL}</span>
                <span class="iss-field__control">{$dns2.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$dns6.LABEL}</span>
                <span class="iss-field__control">{$dns6.INPUT}</span>
            </div>
        </div>
    </section>
</div>
</form>
{$ETHERNET_INTERFASES_LIST}
{$ROUTES_LIST}
