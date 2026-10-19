<link rel="stylesheet" href="modules/{$module_name}/themes/default/css/health.css" />
<script src="modules/{$module_name}/themes/default/js/javascript.js"></script>

<div class="iss-health">

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <h2 class="mb-0">{$title}</h2>
        <a class="btn btn-outline-secondary btn-sm" href="?menu=health&amp;rawmode=yes&amp;exportcsv=yes">
            <i class="ti ti-file-download"></i> {$LABEL_EXPORT}
        </a>
    </div>

    {if $msg}
    <div class="iss-alert iss-alert--{$msgType}">{$msg|escape:'html'}</div>
    {/if}

    <section class="iss-card">
        <div class="iss-card__body d-flex align-items-center gap-4 flex-wrap">
            <div class="iss-health__score iss-health__score--{$state}">
                <span class="iss-health__scoreval">{$score}</span>
                <span class="iss-health__scorelbl">{$LABEL_SCORE}</span>
            </div>
            <div>
                <div class="mb-1">
                    <span class="badge bg-red-lt">{$critical} {$LABEL_CRITICAL}</span>
                    <span class="badge bg-yellow-lt">{$warning} {$LABEL_WARNING}</span>
                    <span class="badge bg-green-lt">{$ok} {$LABEL_OK}</span>
                    <span class="badge bg-secondary-lt">{$unknown} {$LABEL_UNKNOWN}</span>
                </div>
                <div class="text-secondary">{$LABEL_LASTCHECK}: {$run_time}</div>
            </div>
        </div>
    </section>

    <form method="post" action="">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="filter">
        <section class="iss-card">
            <div class="iss-card__body iss-formgrid">
                <div class="iss-field">
                    <span class="iss-field__label">{$LABEL_CATEGORY}</span>
                    <span class="iss-field__control">
                        <select class="form-select" name="fcat">
                            <option value="">{$LABEL_ALL}</option>
                            {foreach from=$categories item=c}
                            <option value="{$c|escape:'html'}" {if $fcat == $c}selected{/if}>{$c|escape:'html'}</option>
                            {/foreach}
                        </select>
                    </span>
                </div>
                <div class="iss-field">
                    <span class="iss-field__label">{$LABEL_SEVERITY}</span>
                    <span class="iss-field__control">
                        <select class="form-select" name="fsev">
                            <option value="">{$LABEL_ALL}</option>
                            <option value="critical" {if $fsev == 'critical'}selected{/if}>{$LABEL_CRITICAL}</option>
                            <option value="warning" {if $fsev == 'warning'}selected{/if}>{$LABEL_WARNING}</option>
                            <option value="unknown" {if $fsev == 'unknown'}selected{/if}>{$LABEL_UNKNOWN}</option>
                            <option value="ok" {if $fsev == 'ok'}selected{/if}>{$LABEL_OK}</option>
                        </select>
                    </span>
                </div>
                <div class="iss-field">
                    <span class="iss-field__control"><button class="btn btn-primary">{$LABEL_FILTER}</button></span>
                </div>
            </div>
        </section>
    </form>

    <section class="iss-card">
        <div class="table-responsive">
            <table class="iss-table">
                <thead>
                    <tr>
                        <th>{$LABEL_SEVERITY}</th>
                        <th>{$LABEL_CHECK}</th>
                        <th>{$LABEL_CATEGORY}</th>
                        <th>{$LABEL_VALUE}</th>
                        <th>{$LABEL_THRESHOLD}</th>
                        <th>{$LABEL_DETAIL}</th>
                        <th>{$LABEL_WHEN}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$checks item=c}
                    <tr>
                        <td><span class="badge {if $c.severity == 'critical'}bg-red-lt{elseif $c.severity == 'warning'}bg-yellow-lt{elseif $c.severity == 'ok'}bg-green-lt{else}bg-secondary-lt{/if}">{$c.severity}</span></td>
                        <td class="text-nowrap"><code>{$c.id|escape:'html'}</code></td>
                        <td>{$c.category|escape:'html'}</td>
                        <td>{$c.value|escape:'html'}</td>
                        <td class="text-secondary">{$c.threshold|escape:'html'}</td>
                        <td>{$c.detail|escape:'html'}</td>
                        <td class="text-nowrap text-secondary">{$c.checked}</td>
                        <td>{if $c.menu}<a class="btn btn-sm btn-outline-secondary" href="?menu={$c.menu}">{$LABEL_VIEW}</a>{/if}</td>
                    </tr>
                {foreachelse}
                    <tr><td colspan="8" class="text-secondary">{$LABEL_NO_DATA}</td></tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </section>

    <form method="post" action="">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveThresholds">
        <section class="iss-card">
            <div class="iss-card__header">{$LABEL_THRESHOLDS}</div>
            <div class="iss-card__body">
                <div class="iss-formgrid iss-formgrid--3col">
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_WARN_DISK} (%)</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="disk_warn_percent" value="{$th.disk_warn_percent|escape:'html'}" min="0" max="100"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_CRIT_DISK} (%)</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="disk_crit_percent" value="{$th.disk_crit_percent|escape:'html'}" min="0" max="100"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_WARN_SWAP} (%)</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="swap_warn_percent" value="{$th.swap_warn_percent|escape:'html'}" min="0" max="100"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_LOAD_FACTOR}</span>
                        <span class="iss-field__control"><input class="form-control" type="number" step="0.1" name="load_warn_factor" value="{$th.load_warn_factor|escape:'html'}" min="0"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_WARN_CERT} ({$LABEL_DAYS})</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="cert_warn_days" value="{$th.cert_warn_days|escape:'html'}" min="0"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_CRIT_CERT} ({$LABEL_DAYS})</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="cert_crit_days" value="{$th.cert_crit_days|escape:'html'}" min="0"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_CHANNELS_CAP}</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="channels_capacity" value="{$th.channels_capacity|escape:'html'}" min="0"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_BACKUP_WARN} (h)</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="backup_warn_hours" value="{$th.backup_warn_hours|escape:'html'}" min="1"></span>
                    </div>
                </div>
                <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
            </div>
        </section>
    </form>

    <form method="post" action="">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveEndpointPolicy">
        <section class="iss-card">
            <div class="iss-card__header">{$LABEL_EP_POLICY}</div>
            <div class="iss-card__body">
                <div class="iss-formgrid">
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_DEBOUNCE}</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="ep_minutes" value="{$ep_minutes|escape:'html'}" min="0"></span>
                    </div>
                    <div class="iss-field">
                        <span class="iss-field__label">{$LABEL_RECENT}</span>
                        <span class="iss-field__control"><input class="form-control" type="number" name="ep_days" value="{$ep_days|escape:'html'}" min="0"></span>
                    </div>
                </div>
                <div class="iss-field__help mt-2">{$LABEL_HINT_DEBOUNCE}</div>
                <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
            </div>
        </section>
    </form>

    <form method="post" action="">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveCritical">
        <input type="hidden" name="ctype" value="trunks">
        <section class="iss-card">
            <div class="iss-card__header">{$LABEL_CRITICAL_ENDPOINTS}: {$LABEL_TRUNKS}</div>
            <div class="iss-card__body">
                <div class="iss-field">
                    <span class="iss-field__control">
                        <label class="iss-inline-field"><input class="form-check-input" type="radio" name="cmode" value="all" {if $trunk_mode == 'all'}checked{/if} onclick="issHealthToggleList(this)"> {$LABEL_ALL_CRITICAL}</label>
                        <label class="iss-inline-field"><input class="form-check-input" type="radio" name="cmode" value="list" {if $trunk_mode != 'all'}checked{/if} onclick="issHealthToggleList(this)"> {$LABEL_SELECTED}</label>
                    </span>
                </div>
                <div data-endpoints>
                    <div class="iss-field">
                        <span class="iss-field__control"><input type="text" class="form-control" placeholder="{$LABEL_FILTER}..." oninput="issHealthFilterList(this)"></span>
                    </div>
                    <div class="iss-health__list">
                    {foreach from=$trunk_options item=o}
                        <label class="form-check" title="{$o.id|escape:'html'}">
                            <input class="form-check-input" type="checkbox" name="cids[]" value="{$o.id|escape:'html'}" {if $o.checked}checked{/if}>
                            <span class="form-check-label">{$o.label|escape:'html'}</span>
                        </label>
                    {foreachelse}
                        <div class="text-secondary">{$LABEL_NO_DATA}</div>
                    {/foreach}
                    </div>
                </div>
                <div class="iss-field__help mt-2">{$LABEL_HINT_EP}</div>
                <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
            </div>
        </section>
    </form>

    <form method="post" action="">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveCritical">
        <input type="hidden" name="ctype" value="extensions">
        <section class="iss-card">
            <div class="iss-card__header">{$LABEL_CRITICAL_ENDPOINTS}: {$LABEL_EXTENSIONS}</div>
            <div class="iss-card__body">
                <div class="iss-field">
                    <span class="iss-field__control">
                        <label class="iss-inline-field"><input class="form-check-input" type="radio" name="cmode" value="all" {if $ext_mode == 'all'}checked{/if} onclick="issHealthToggleList(this)"> {$LABEL_ALL_CRITICAL}</label>
                        <label class="iss-inline-field"><input class="form-check-input" type="radio" name="cmode" value="list" {if $ext_mode != 'all'}checked{/if} onclick="issHealthToggleList(this)"> {$LABEL_SELECTED}</label>
                    </span>
                </div>
                <div data-endpoints>
                    <div class="iss-field">
                        <span class="iss-field__control"><input type="text" class="form-control" placeholder="{$LABEL_FILTER}..." oninput="issHealthFilterList(this)"></span>
                    </div>
                    <div class="iss-health__list">
                    {foreach from=$ext_options item=o}
                        <label class="form-check" title="{$o.id|escape:'html'}">
                            <input class="form-check-input" type="checkbox" name="cids[]" value="{$o.id|escape:'html'}" {if $o.checked}checked{/if}>
                            <span class="form-check-label">{$o.label|escape:'html'}</span>
                        </label>
                    {foreachelse}
                        <div class="text-secondary">{$LABEL_NO_DATA}</div>
                    {/foreach}
                    </div>
                </div>
                <div class="iss-field__help mt-2">{$LABEL_HINT_EP}</div>
                <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
            </div>
        </section>
    </form>

    <form method="post" action="">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveApi">
        <section class="iss-card">
            <div class="iss-card__header">{$LABEL_API_TITLE}</div>
            <div class="iss-card__body">
                <div class="iss-field__help">{$LABEL_API_DESC}</div>

                <div class="iss-field mt-3">
                    <span class="iss-field__control">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="api_enabled" value="1" {if $api_enabled}checked{/if}>
                            <span class="form-check-label">{if $api_enabled}{$LABEL_API_ON}{else}{$LABEL_API_OFF}{/if}</span>
                        </label>
                    </span>
                </div>

                <div class="iss-field mt-3">
                    <span class="iss-field__label">{$LABEL_API_CONSUME}</span>
                    <span class="iss-field__control">
                        <input type="text" class="form-control font-monospace mb-1" readonly value="{$api_url|escape:'html'}">
                        <input type="text" class="form-control font-monospace" readonly value="{$api_curl|escape:'html'}">
                    </span>
                </div>

                <div class="iss-field mt-3">
                    <span class="iss-field__label">{$LABEL_API_TOKEN}</span>
                    <span class="iss-field__control">
                        <div class="input-group">
                            <input type="password" class="form-control font-monospace" id="iss-hc-token" readonly value="{$api_token|escape:'html'}">
                            <button type="button" class="btn btn-outline-secondary" onclick="issHealthRevealToken()" title="{$LABEL_API_TOKEN}"><i class="ti ti-eye"></i></button>
                            <button type="button" class="btn btn-outline-secondary" id="iss-hc-copy" onclick="issHealthCopyToken()" title="{$LABEL_COPY}"><i class="ti ti-copy"></i></button>
                        </div>
                        <div class="iss-field__help mt-1">{$LABEL_HINT_RETOKEN}</div>
                    </span>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" name="do" value="save">{$LABEL_SAVE}</button>
                    <button class="btn btn-outline-danger" name="do" value="regenerate">{$LABEL_REGENERATE}</button>
                </div>
            </div>
        </section>
    </form>

</div>
