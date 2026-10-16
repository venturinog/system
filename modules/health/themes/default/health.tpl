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
    <div class="alert alert-{$msgType}">{$msg|escape:'html'}</div>
    {/if}

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center gap-4 flex-wrap">
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
    </div>

    <form method="post" action="" class="card mb-3">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="filter">
        <div class="card-body d-flex gap-3 flex-wrap align-items-end">
            <div>
                <label class="form-label">{$LABEL_CATEGORY}</label>
                <select class="form-select" name="fcat">
                    <option value="">{$LABEL_ALL}</option>
                    {foreach from=$categories item=c}
                    <option value="{$c|escape:'html'}" {if $fcat == $c}selected{/if}>{$c|escape:'html'}</option>
                    {/foreach}
                </select>
            </div>
            <div>
                <label class="form-label">{$LABEL_SEVERITY}</label>
                <select class="form-select" name="fsev">
                    <option value="">{$LABEL_ALL}</option>
                    <option value="critical" {if $fsev == 'critical'}selected{/if}>{$LABEL_CRITICAL}</option>
                    <option value="warning" {if $fsev == 'warning'}selected{/if}>{$LABEL_WARNING}</option>
                    <option value="unknown" {if $fsev == 'unknown'}selected{/if}>{$LABEL_UNKNOWN}</option>
                    <option value="ok" {if $fsev == 'ok'}selected{/if}>{$LABEL_OK}</option>
                </select>
            </div>
            <div><button class="btn btn-primary">{$LABEL_FILTER}</button></div>
        </div>
    </form>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
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
    </div>

    <form method="post" action="" class="card mb-3">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveThresholds">
        <div class="card-header"><h3 class="card-title">{$LABEL_THRESHOLDS}</h3></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_WARN_DISK} (%)</label>
                    <input class="form-control" type="number" name="disk_warn_percent" value="{$th.disk_warn_percent|escape:'html'}" min="0" max="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_CRIT_DISK} (%)</label>
                    <input class="form-control" type="number" name="disk_crit_percent" value="{$th.disk_crit_percent|escape:'html'}" min="0" max="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_WARN_SWAP} (%)</label>
                    <input class="form-control" type="number" name="swap_warn_percent" value="{$th.swap_warn_percent|escape:'html'}" min="0" max="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_LOAD_FACTOR}</label>
                    <input class="form-control" type="number" step="0.1" name="load_warn_factor" value="{$th.load_warn_factor|escape:'html'}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_WARN_CERT} ({$LABEL_DAYS})</label>
                    <input class="form-control" type="number" name="cert_warn_days" value="{$th.cert_warn_days|escape:'html'}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_CRIT_CERT} ({$LABEL_DAYS})</label>
                    <input class="form-control" type="number" name="cert_crit_days" value="{$th.cert_crit_days|escape:'html'}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_CHANNELS_CAP}</label>
                    <input class="form-control" type="number" name="channels_capacity" value="{$th.channels_capacity|escape:'html'}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_BACKUP_WARN} (h)</label>
                    <input class="form-control" type="number" name="backup_warn_hours" value="{$th.backup_warn_hours|escape:'html'}" min="1">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
        </div>
    </form>

    <form method="post" action="" class="card mb-3">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveEndpointPolicy">
        <div class="card-header"><h3 class="card-title">{$LABEL_EP_POLICY}</h3></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_DEBOUNCE}</label>
                    <input class="form-control" type="number" name="ep_minutes" value="{$ep_minutes|escape:'html'}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{$LABEL_RECENT}</label>
                    <input class="form-control" type="number" name="ep_days" value="{$ep_days|escape:'html'}" min="0">
                </div>
            </div>
            <div class="text-secondary small mt-2">{$LABEL_HINT_DEBOUNCE}</div>
            <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
        </div>
    </form>

    <form method="post" action="" class="card mb-3">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveCritical">
        <input type="hidden" name="ctype" value="trunks">
        <div class="card-header"><h3 class="card-title">{$LABEL_CRITICAL_ENDPOINTS}: {$LABEL_TRUNKS}</h3></div>
        <div class="card-body">
            <div class="mb-2">
                <label class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="cmode" value="all" {if $trunk_mode == 'all'}checked{/if} onclick="issHealthToggleList(this)">
                    <span class="form-check-label">{$LABEL_ALL_CRITICAL}</span>
                </label>
                <label class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="cmode" value="list" {if $trunk_mode != 'all'}checked{/if} onclick="issHealthToggleList(this)">
                    <span class="form-check-label">{$LABEL_SELECTED}</span>
                </label>
            </div>
            <div data-endpoints>
                <input type="text" class="form-control mb-2" placeholder="{$LABEL_FILTER}..." oninput="issHealthFilterList(this)">
                <div class="iss-health__list">
                {foreach from=$trunk_options item=o}
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="cids[]" value="{$o.id|escape:'html'}" {if $o.checked}checked{/if}>
                        <span class="form-check-label">{$o.id|escape:'html'}</span>
                    </label>
                {foreachelse}
                    <div class="text-secondary">{$LABEL_NO_DATA}</div>
                {/foreach}
                </div>
            </div>
            <div class="text-secondary mt-2 small">{$LABEL_HINT_EP}</div>
            <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
        </div>
    </form>

    <form method="post" action="" class="card mb-3">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveCritical">
        <input type="hidden" name="ctype" value="extensions">
        <div class="card-header"><h3 class="card-title">{$LABEL_CRITICAL_ENDPOINTS}: {$LABEL_EXTENSIONS}</h3></div>
        <div class="card-body">
            <div class="mb-2">
                <label class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="cmode" value="all" {if $ext_mode == 'all'}checked{/if} onclick="issHealthToggleList(this)">
                    <span class="form-check-label">{$LABEL_ALL_CRITICAL}</span>
                </label>
                <label class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="cmode" value="list" {if $ext_mode != 'all'}checked{/if} onclick="issHealthToggleList(this)">
                    <span class="form-check-label">{$LABEL_SELECTED}</span>
                </label>
            </div>
            <div data-endpoints>
                <input type="text" class="form-control mb-2" placeholder="{$LABEL_FILTER}..." oninput="issHealthFilterList(this)">
                <div class="iss-health__list">
                {foreach from=$ext_options item=o}
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="cids[]" value="{$o.id|escape:'html'}" {if $o.checked}checked{/if}>
                        <span class="form-check-label">{$o.id|escape:'html'}</span>
                    </label>
                {foreachelse}
                    <div class="text-secondary">{$LABEL_NO_DATA}</div>
                {/foreach}
                </div>
            </div>
            <div class="text-secondary mt-2 small">{$LABEL_HINT_EP}</div>
            <div class="mt-3"><button class="btn btn-primary">{$LABEL_SAVE}</button></div>
        </div>
    </form>

    <form method="post" action="" class="card mb-3">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="saveApi">
        <div class="card-header"><h3 class="card-title">{$LABEL_API_TITLE}</h3></div>
        <div class="card-body">
            <p class="text-secondary">{$LABEL_API_DESC}</p>

            <label class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="api_enabled" value="1" {if $api_enabled}checked{/if}>
                <span class="form-check-label">{if $api_enabled}{$LABEL_API_ON}{else}{$LABEL_API_OFF}{/if}</span>
            </label>

            <div class="mb-2">
                <label class="form-label">{$LABEL_API_CONSUME}</label>
                <input type="text" class="form-control font-monospace mb-1" readonly value="{$api_url|escape:'html'}">
                <input type="text" class="form-control font-monospace" readonly value="{$api_curl|escape:'html'}">
            </div>

            <div class="mb-2">
                <label class="form-label">{$LABEL_API_TOKEN}</label>
                <div class="input-group">
                    <input type="password" class="form-control font-monospace" id="iss-hc-token" readonly value="{$api_token|escape:'html'}">
                    <button type="button" class="btn btn-outline-secondary" onclick="issHealthRevealToken()" title="{$LABEL_API_TOKEN}"><i class="ti ti-eye"></i></button>
                    <button type="button" class="btn btn-outline-secondary" id="iss-hc-copy" onclick="issHealthCopyToken()" title="{$LABEL_COPY}"><i class="ti ti-copy"></i></button>
                </div>
                <div class="text-secondary small mt-1">{$LABEL_HINT_RETOKEN}</div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary" name="do" value="save">{$LABEL_SAVE}</button>
                <button class="btn btn-outline-danger" name="do" value="regenerate">{$LABEL_REGENERATE}</button>
            </div>
        </div>
    </form>

</div>
