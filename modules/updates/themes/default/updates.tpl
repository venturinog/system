<script src="modules/{$module_name}/themes/default/js/javascript.js?v={$ASSET_V}"></script>

<div class="iss-updates" id="iss-updates" data-job-state="{$job_state|escape:'html'}">

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <h2 class="mb-0">{$title}</h2>
        <form method="post" action="" class="d-inline">
            {$ISSABEL_CSRF_FIELD}
            <input type="hidden" name="action" value="refresh">
            <button class="btn btn-outline-secondary btn-sm"><i class="ti ti-refresh"></i> {$LABEL_REFRESH}</button>
        </form>
    </div>

    {if $msg}
    <div class="alert alert-{$msgType}">{$msg|escape:'html'}</div>
    {/if}

    {if $job_state eq 'running'}
    <div class="alert alert-info d-flex align-items-center gap-2" id="iss-updates-working">
        <span class="spinner-border spinner-border-sm" role="status"></span>
        <span>{$LABEL_WORKING}{if $job_started} ({$job_started}){/if}</span>
        <a href="#" id="iss-updates-viewlog" class="ms-auto">{$LABEL_VIEW_LOG}</a>
    </div>
    {/if}

    {if $reboot_needed}
    <div class="alert alert-warning">
        <i class="ti ti-alert-triangle"></i> {$LABEL_REBOOT}
    </div>
    {/if}

    <form method="post" action="" id="iss-updates-form"
          data-confirm="{$LABEL_CONFIRM_UPDATE|escape:'html'}"
          data-selectone="{$LABEL_SELECT_ONE|escape:'html'}">
        {$ISSABEL_CSRF_FIELD}
        <input type="hidden" name="action" value="update">

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0" for="iss-updates-filter">{$LABEL_FILTER}</label>
                    <select class="form-select form-select-sm" id="iss-updates-filter">
                        <option value="">{$LABEL_ALL}</option>
                        <option value="issabel">{$LABEL_ISSABEL}</option>
                        <option value="security">{$LABEL_SECURITY}</option>
                        <option value="other">{$LABEL_OTHER}</option>
                    </select>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="iss-updates-toggle"
                            data-selectall="{$LABEL_SELECT_ALL|escape:'html'}"
                            data-selectnone="{$LABEL_SELECT_NONE|escape:'html'}">{$LABEL_SELECT_ALL}</button>
                    <button type="submit" class="btn btn-primary">{$LABEL_UPDATE_SELECTED}</button>
                </div>
            </div>

            <div class="table-responsive" id="iss-updates-table">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th style="width: 2.5rem;"></th>
                            <th>{$LABEL_PACKAGE}</th>
                            <th>{$LABEL_INSTALLED}</th>
                            <th>{$LABEL_AVAILABLE}</th>
                            <th>{$LABEL_REPOSITORY}</th>
                            <th>{$LABEL_TYPE}</th>
                        </tr>
                    </thead>
                    <tbody>
                    {if !$has_rows}
                        <tr>
                            <td colspan="6" class="text-secondary">
                                {if $checked_at}{$LABEL_NO_UPDATES}{else}{$LABEL_NO_DATA} &mdash; {$LABEL_HINT_NODATA}{/if}
                            </td>
                        </tr>
                    {else}
                        {foreach from=$rows item=r}
                        <tr data-type="{if $r.is_issabel}issabel {/if}{if $r.is_security}security {/if}{if !$r.is_issabel && !$r.is_security}other{/if}">
                            <td><input class="form-check-input" type="checkbox" name="pkg_x[]" value="{$r.key|escape:'html'}"></td>
                            <td class="text-nowrap">{$r.pkg|escape:'html'}</td>
                            <td class="text-secondary text-nowrap">{$r.installed|escape:'html'}</td>
                            <td class="text-nowrap">{$r.available|escape:'html'}</td>
                            <td class="text-secondary">{$r.repo|escape:'html'}</td>
                            <td class="text-nowrap">
                                {if $r.is_issabel}<span class="badge bg-blue-lt">{$LABEL_ISSABEL}</span>{/if}
                                {if $r.is_security}<span class="badge bg-red-lt">{$LABEL_SECURITY}</span>{/if}
                                {if !$r.is_issabel && !$r.is_security}<span class="badge bg-secondary-lt">{$LABEL_OTHER}</span>{/if}
                            </td>
                        </tr>
                        {/foreach}
                    {/if}
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-secondary small mt-2">
            {$LABEL_LAST_CHECK}: {if $checked_at}{$checked_human} ({$checked_at}){else}{$LABEL_NO_DATA}{/if}
        </div>
    </form>

    <div class="card d-none mt-3" id="iss-updates-logcard">
        <div class="card-header">{$LABEL_VIEW_LOG}</div>
        <div class="card-body"><pre id="iss-updates-log" class="mb-0" style="max-height: 24rem; overflow: auto;"></pre></div>
    </div>

</div>
