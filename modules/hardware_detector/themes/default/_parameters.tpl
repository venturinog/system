<input type="hidden" name="idCard" id="idCard" value="" />
<div class="iss-form">
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field iss-field--full">
                <span class="iss-field__control"><label id="port_desc_span">{$CARD} # {$ID}: {$TIPO} {$ADICIONAL}</label></span>
            </div>
            <div id="config_span_div" class="iss-field--full">
                <div class="iss-formgrid">
                    <div class="iss-field">
                        <span class="iss-field__label">{$Timing_source}</span>
                        <span class="iss-field__control"><input type="text" size="3" class="input" id="tmsource" name="tmsource" title="{$Timing_source_title|escape:html}" /></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$Line_build_out}</span>
                        <span class="iss-field__control"><select id='lnbuildout' name='lnbuildout' class="form-control">
                        {html_options options=$type_lnbuildout}
                        </select></span>
                    </div>
                    <div class="iss-field" id="switch_pri_media">
                        <span class="iss-field__label">{$Media}</span>
                        <span class="iss-field__control"><select id='media_pri' name='media_pri' class="form-control">
                        {html_options options=$type_media}
                        </select></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$Framing}</span>
                        <span class="iss-field__control"><select id='framing' name='framing' class="form-control">
                        {html_options options=$type_framing}
                        </select></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$Coding}</span>
                        <span class="iss-field__control"><select id='coding' name='coding' class="form-control">
                        {html_options options=$type_coding}
                        </select></span>
                    </div>
                    <div class="iss-field" id="switch_crc">
                        <span class="iss-field__label">{$CRC}</span>
                        <span class="iss-field__control"><select id='crc' name='crc' class="form-control">
                        {html_options options=$type_crc}
                        </select></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<div class="letra12 viewButton" style="display: none;">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="button" id="save_span" name="save_span" value="{$SAVE}" onclick="saveSpan();" style="cursor:pointer" />
            <input class="button" type="button" id="cancel" name="cancel" value="{$CANCEL}" onclick="hideModalPopUP();" style="cursor:pointer"/>
        </div>
    </div>
    <div class="loading" style="float: right; position: relative; top: 0px; left:-70px"></div>
    <div class="message" style="text-align:left; font-size:14px; font-weight:bold; float: right; position: relative; left: 5px; top:5px; width:65%"></div>
</div>
