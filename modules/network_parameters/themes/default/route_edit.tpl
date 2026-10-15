<form method="POST">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save_route" value="{$SAVE}">
            <input class="button" type="submit" name="cancel_route" value="{$CANCEL}">
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$route_dev.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$route_dev.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$route_dest.LABEL}&nbsp;<span class="iss-required">*</span></span>
                <span class="iss-field__control">{$route_dest.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$route_gw.LABEL}</span>
                <span class="iss-field__control">{$route_gw.INPUT}</span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$route_metric.LABEL}</span>
                <span class="iss-field__control">{$route_metric.INPUT}</span>
            </div>
        </div>
    </section>
</div>
{$route_orig.INPUT}
</form>
