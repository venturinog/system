<div class="iss-form">
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-git-pull-request"></i> {$subtittle1}</header>
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$beta_channel_status.LABEL}</span>
                <span class="iss-field__control"><input type="hidden" name="oldbeta_channel_status" id="oldbeta_channel_status" value="{if $value_beta_channel}1{else}0{/if}" /><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="beta_channel_status" id="beta_channel_status" {if $value_beta_channel}checked="checked"{/if} /></div></span>
            </div>
        </div>
    </section>
</div>
