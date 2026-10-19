<form method="POST">
{$ISSABEL_CSRF_FIELD}
<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="submit_accept" value="{$ACCEPT}" onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
        </div>
    </div>
    {if $SHUTDOWN_ALERT}{$SHUTDOWN_ALERT}{/if}
    <section class="iss-card">
        <div class="iss-card__body">
            <div class="iss-field">
                <label class="iss-inline-field"><input type="radio" name="shutdown_mode" value="1"> {$HALT}</label>
            </div>
            <div class="iss-field">
                <label class="iss-inline-field"><input type="radio" name="shutdown_mode" value="2" checked> {$REBOOT}</label>
            </div>
        </div>
    </section>
</div>
</form>
