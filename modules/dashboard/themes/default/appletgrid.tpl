<div class="iss-appletgrid">
    <div class="appletcolumn" id="applet_col_1">
        {foreach from=$applet_col_1 item=applet}
        <div class='appletwindow' id='portlet-{$applet.code}'>
            <div class='appletwindow_topbar'>
                <div class='appletwindow_title'>{$applet.name}</div>
                <div class='appletwindow_widgets'>
                    <a class='appletrefresh' title='{$applet.name}'><i class="ti ti-refresh"></i></a>
                </div>
            </div>
            <div class='appletwindow_content' id='{$applet.code}'>
                <div class='appletwindow_wait'><img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo_pattern.png" class="imageSpin" alt=""></div>
                <div class='appletwindow_fullcontent'></div>
            </div>
        </div>
        {/foreach}
    </div>
    <div class="appletcolumn" id="applet_col_2">
        {foreach from=$applet_col_2 item=applet}
        <div class='appletwindow' id='portlet-{$applet.code}'>
            <div class='appletwindow_topbar'>
                <div class='appletwindow_title'>{$applet.name}</div>
                <div class='appletwindow_widgets'>
                    <a class='appletrefresh' title='{$applet.name}'><i class="ti ti-refresh"></i></a>
                </div>
            </div>
            <div class='appletwindow_content' id='{$applet.code}'>
                <div class='appletwindow_wait'><img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo_pattern.png" class="imageSpin" alt=""></div>
                <div class='appletwindow_fullcontent'></div>
            </div>
        </div>
        {/foreach}
    </div>
</div>
