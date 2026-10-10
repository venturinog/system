<link rel="stylesheet" media="screen" type="text/css" href="modules/{$module_name}/applets/News/tpl/css/styles.css" />

<div class="iss-news">
{foreach from=$NEWS_LIST item=NEWS_ITEM}
    <div class="iss-news__item">
        <span class="iss-news__date">{$NEWS_ITEM.date_format}</span>
        <a class="iss-news__title" href="{$NEWS_ITEM.link}" target="_blank">{$NEWS_ITEM.title|escape:"html"}</a>
        <a class="iss-news__tweet" title="Tweet" target="_blank"
           href="https://twitter.com/share?original_referer={$WEBSITE|escape:"url"}&related=&source=tweetbutton&text={$NEWS_ITEM.title|escape:"url"}&url={$NEWS_ITEM.link|escape:"url"}&via=issabelGui">
            <i class="ti ti-brand-twitter"></i>
        </a>
    </div>
{foreachelse}
    <div class="iss-news__item">{$NO_NEWS}</div>
{/foreach}
</div>
