<div class="iss-form">
    <div class="iss-form__toolbar">
        <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="save_new_FTP" value="{$SAVE}">
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </div>
    </div>
    <div id="table_center" class="iss-card">
        <div class="iss-card__body letra12">
            <div class="divs">
                <span class="iss-field__label"><b>{$local.LABEL}</b></span>
                <span class="iss-field__label"><b>{$server_ftp.LABEL}</b></span>
            </div>
            <div id="content">
                <div id="home">
                    <div id="lef">
                        <ul id="sortable1" class='droptrue'>
                        {* {$LOCAL_LI} *}
                        {foreach from=$local_files item=file}
                        <li class='ui-state-default' id='inn_{$file}'><b class='item'>{$file}</b></li>
                        {/foreach}
                        </ul>
                    </div>
                    <div id="med">
                    </div>
                    <div id="cen">
                        <ul id="sortable2" class='droptrue2'>
                        {* {$REMOTE_LI} *}
                        {foreach from=$remote_files item=file}
                        <li class='ui-state-default' id='out_{$file}'><b class='item'>{$file}</b></li>
                        {/foreach}
                        </ul>
                    </div>
                    <div id="rig">
                        <div class="iss-formgrid">
                            <div class="iss-field">
                                <span class="iss-field__label">{$server.LABEL}&nbsp;<span class="iss-required">*</span></span>
                                <span class="iss-field__control">{$server.INPUT}</span>
                            </div>
                            <div class="iss-field">
                                <span class="iss-field__label">{$port.LABEL}&nbsp;<span class="iss-required">*</span></span>
                                <span class="iss-field__control">{$port.INPUT}</span>
                            </div>
                            <div class="iss-field">
                                <span class="iss-field__label">{$user.LABEL}&nbsp;<span class="iss-required">*</span></span>
                                <span class="iss-field__control">{$user.INPUT}</span>
                            </div>
                            <div class="iss-field">
                                <span class="iss-field__label">{$password.LABEL}&nbsp;<span class="iss-required">*</span></span>
                                <span class="iss-field__control">{$password.INPUT}</span>
                            </div>
                            <div class="iss-field">
                                <span class="iss-field__label">{$pathServer.LABEL}&nbsp;<span class="iss-required">*</span></span>
                                <span class="iss-field__control">{$pathServer.INPUT}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
