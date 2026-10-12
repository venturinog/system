<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            {if $mode eq 'input'}
            <input class="button" type="submit" name="save_new" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'view'}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {elseif $mode eq 'edit'}
            <input class="button" type="submit" name="save_edit" value="{$EDIT}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
            {/if}
        </div>
    </div>
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <input type="hidden" name="idCard" value="{$DESC_ID}" />
            <div class="iss-field iss-field--full">
                <span class="iss-field__control"><label id="port_desc">{$CARD} # {$ID}: {$TIPO} {$ADICIONAL}</label></span>
            </div>
            <div id="form" class="iss-field iss-field--full" style='background-color: var(--tblr-warning-lt, #fff3cd); color: var(--iss-text)'>
                {foreach key=key item=echocancel name=arrPortsEchoInfo from=$arrPortsEcho}
                <div class="iss-field">
                    <span class="iss-field__label"><b>{$key}</b> {$echocancel.name_port}</span>
                    <span class="iss-field__control">
                        <select id='typeecho_{$key}' name='typeecho_{$key}' class="form-control">
                            {html_options options=$type_echo_names selected=$echocancel.type_echo}
                        </select>
                        <input type="hidden" value="{$echocancel.type_echo}" name="tmpTypeEcho{$key}" />
                    </span>
                </div>
                {/foreach}
            </div>
        </div>
    </section>
</div>
<input class="button" type="hidden" name="id" value="{$ID}" />
