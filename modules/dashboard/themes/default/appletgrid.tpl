{if $news_enabled}
<div class="iss-news-ticker" id="iss-news-ticker"
     data-endpoint="index.php?menu={$module_name}&amp;rawmode=yes&amp;action=newsFeed">
    <span class="iss-news-ticker__icon"><i class="ti ti-news"></i> {$LABEL_NEWS}</span>
    <span class="iss-news-ticker__body" id="iss-news-ticker-body">{$LABEL_NEWS_LOADING}</span>
    <span class="iss-news-ticker__nav" id="iss-news-ticker-nav"></span>
</div>
{/if}
<div class="iss-appletgrid-full">
{if $healthcenter}
    <div class='appletwindow iss-appletwindow--fixed' id='portlet-{$healthcenter.code}'>
        <div class='appletwindow_topbar'>
            <div class='appletwindow_title'>{$healthcenter.name}</div>
            <div class='appletwindow_widgets'>
                <a class='appletrefresh' title='{$healthcenter.name}'><i class="ti ti-refresh"></i></a>
            </div>
        </div>
        <div class='appletwindow_content' id='{$healthcenter.code}'>
            <div class='appletwindow_wait'><img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo_pattern.png" class="imageSpin" alt=""></div>
            <div class='appletwindow_fullcontent'></div>
        </div>
    </div>
{/if}
</div>
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
