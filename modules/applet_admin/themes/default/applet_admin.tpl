{literal}
<script type="text/javascript">
$(document).ready(function() {
 $('.checkall').click(function () {
     $(".letra12").find(':checkbox').prop('checked', $(this).is(':checked'));
 });

});
});
</script>
{/literal}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save_new" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body letra12">
            <div class="iss-field iss-field--full">
                <span class="iss-field__control"><label class="iss-inline-field"><input name="checkall" class="checkall" type="checkbox"> {$checkall}</label></span>
            </div>
            <br />
            {foreach from=$applets key=q item=applet name=appletrow}
            <div class="iss-field">
                <span class="iss-inline-field"><b>{$applet.name}:</b></span>
                <span class="iss-field__control">
                    <label class="iss-inline-field"><input name="chkdau_{$applet.id}" type="checkbox" {if $applet.activated} checked="checked" {/if}></label>
                </span>
            </div>
            {/foreach}
        </div>
    </section>
</div>
