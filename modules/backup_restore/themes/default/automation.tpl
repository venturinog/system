<form method="POST" action="?menu={$module_name}">
{$ISSABEL_CSRF_FIELD}
<section class="iss-card">
    <div class="iss-card__header"><i class="ti ti-clock"></i> {$AUTOMATION_TITLE}</div>
    <div class="iss-card__body">
        <div class="iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__label">{$AUTOMATIC_LABEL}</span>
                <span class="iss-field__control">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <select name="time" id="time" class="form-select form-select-sm" style="flex:1 1 0;min-width:140px;max-width:430px">
                            {html_options options=$backupIntervals selected=$selectedBackupTime}
                        </select>
                        <button type="submit" name="automatic" value="{$AUTOMATIC_LABEL}" class="btn btn-sm btn-outline-primary" style="flex-shrink:0;max-width:100%;white-space:normal;text-align:left"><i class="ti ti-check"></i> {$APPLY}</button>
                    </div>
                </span>
            </div>
            <div class="iss-field">
                <span class="iss-field__label">{$DELETEB_LABEL}</span>
                <span class="iss-field__control">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <select name="deltime" id="deltime" class="form-select form-select-sm" style="flex:1 1 0;min-width:140px;max-width:430px">
                            {html_options options=$deleteIntervals selected=$selectedDeleteTime}
                        </select>
                        <button type="submit" name="automatic_del" value="{$DELETEB_LABEL}" class="btn btn-sm btn-outline-primary" style="flex-shrink:0;max-width:100%;white-space:normal;text-align:left"><i class="ti ti-check"></i> {$APPLY}</button>
                    </div>
                </span>
            </div>
        </div>
    </div>
</section>
</form>
