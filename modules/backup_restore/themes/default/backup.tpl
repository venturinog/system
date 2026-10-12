{literal}
<script type="text/javascript">

function ChequearTodos(chkbox)
{
    for (var i=0;i < document.getElementById("backup_form").elements.length;i++)
    {
        var elemento = document.getElementById("backup_form").elements[i];
        if (elemento.type == "checkbox")
        {
            if(!elemento.disabled)
                elemento.checked = chkbox.checked
        }
    }
}
function ChequearTabla(chkbox, id)
{
    //Si desmarco el checkbox desmarcar all options global
    if(!chkbox.checked)
        document.getElementById('backup_total').checked = chkbox.checked;

    var arr_chk = document.getElementById(id).getElementsByTagName("INPUT");
    for(var i=0; i<arr_chk.length; i++)
    {
        if(!arr_chk[i].disabled)
            arr_chk[i].checked = chkbox.checked;
    }
}
function VerificarCheck(chkbox, id)
{
    if(!chkbox.checked)
    {
        //Descarmar all options de la tabla
        document.getElementById(id).checked = chkbox.checked;
        //Descarmar all options global
        document.getElementById('backup_total').checked = chkbox.checked;
    }
}
function popup_dif(content_popup)
{
	var ancho = 800;
        var alto = 110;
	var winiz = (screen.width-ancho)/2;
	var winal = (screen.height-alto)/2;
	my_window = window.open(content_popup,"my_window","width="+ancho+",height="+alto+",top="+winal+",left="+winiz+",location=no,status=no,resizable=no,scrollbars=no,fullscreen=no,toolbar=no,directories=no");
        my_window.document.close();
}
</script>
{/literal}
<form method="POST" enctype="multipart/form-data" id="backup_form">
<div class="iss-form">
    <div class="iss-form__toolbar">
        <div class="iss-form__buttons">
            <input class="button" type="submit" name="process" value="{$PROCESS}">
            <input class="button" type="submit" name="back" value="{$BACK}">
        </div>
    </div>
    {$ERROR_MSG}
    <div class="iss-field iss-field--full">
        <label class="iss-inline-field"><INPUT type="checkbox" name="backup_total" id="backup_total" onClick=ChequearTodos(this);> <strong>{$LBL_TODOS}</strong></label>
    </div>
    <section class="iss-card" id="table_endpoint">
        <header class="iss-card__header"><i class="ti ti-device-desktop"></i> {$ENDPOINT}</header>
        <div class="iss-card__body">
            <div class="iss-field iss-field--full">
                <label class="iss-inline-field"><INPUT type="checkbox" name="backup_endpoint" id="backup_endpoint" onClick="ChequearTabla(this, 'table_endpoint');"> <strong>{$TODO_ENDPOINT}</strong></label>
            </div>
            {foreach key=key item=item from=$backup_endpoint}
            <div class="iss-field">
                <label class="iss-inline-field"><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_endpoint');" {$item.check}> <span {if !empty($item.disable)}style="text-decoration: line-through"{/if}>{$item.desc}&nbsp;{$item.msg}</span></label>
            </div>
            {/foreach}
        </div>
    </section>
    <section class="iss-card" id="table_fax">
        <header class="iss-card__header"><i class="ti ti-printer"></i> {$FAX}</header>
        <div class="iss-card__body">
            <div class="iss-field iss-field--full">
                <label class="iss-inline-field"><INPUT type="checkbox" name="backup_fax" id="backup_fax" onClick="ChequearTabla(this, 'table_fax');"> <strong>{$TODO_FAX}</strong></label>
            </div>
            {foreach key=key item=item from=$backup_fax}
            <div class="iss-field">
                <label class="iss-inline-field"><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_fax');" {$item.check}> <span {if !empty($item.disable)}style="text-decoration: line-through"{/if}>{$item.desc}&nbsp;{$item.msg}<span></span></label>
            </div>
            {/foreach}
        </div>
    </section>
    <section class="iss-card" id="table_email">
        <header class="iss-card__header"><i class="ti ti-mail"></i> {$EMAIL}</header>
        <div class="iss-card__body">
            <div class="iss-field iss-field--full">
                <label class="iss-inline-field"><INPUT type="checkbox" name="backup_email" id="backup_email" onClick="ChequearTabla(this, 'table_email');"> <strong>{$TODO_EMAIL}</strong></label>
            </div>
            {foreach key=key item=item from=$backup_email}
            <div class="iss-field">
                <label class="iss-inline-field"><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_email');" {$item.check}> <span {if !empty($item.disable)}style="text-decoration: line-through"{/if}>{$item.desc}&nbsp;{$item.msg}</span></label>
            </div>
            {/foreach}
        </div>
    </section>
    <section class="iss-card" id="table_asterisk">
        <header class="iss-card__header"><i class="ti ti-phone"></i> {$ASTERISK}</header>
        <div class="iss-card__body">
            <div class="iss-field iss-field--full">
                <label class="iss-inline-field"><INPUT type="checkbox" name="backup_asterisk" id="backup_asterisk" onClick="ChequearTabla(this, 'table_asterisk');"><strong> {$TODO_ASTERISK}</strong></label>
            </div>
            {foreach key=key item=item from=$backup_asterisk}
            <div class="iss-field">
                <label class="iss-inline-field"><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_asterisk');" {$item.check}> <span {if !empty($item.disable)}style="text-decoration: line-through"{/if}>{$item.desc}&nbsp;{$item.msg}</span></label>
            </div>
            {/foreach}
        </div>
    </section>
    <section class="iss-card" id="table_others">
        <header class="iss-card__header"><i class="ti ti-folder"></i> {$OTROS}</header>
        <div class="iss-card__body">
            <div class="iss-field iss-field--full">
                <label class="iss-inline-field"><INPUT type="checkbox" name="backup_others" id="backup_others" onClick="ChequearTabla(this, 'table_others');"> <strong>{$TODO_OTROS}</strong></label>
            </div>
            {foreach key=key item=item from=$backup_otros}
            <div class="iss-field">
                <label class="iss-inline-field"><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_others');" {$item.check}> <span {if !empty($item.disable)}style="text-decoration: line-through"{/if}>{$item.desc}&nbsp;{$item.msg}</span></label>
            </div>
            {/foreach}
        </div>
    </section>
    <section class="iss-card" id="table_others_new">
        <header class="iss-card__header"><i class="ti ti-folder-plus"></i> {$OTROS_NEW}</header>
        <div class="iss-card__body">
            <div class="iss-field iss-field--full">
                <label class="iss-inline-field"><INPUT type="checkbox" name="backup_others_new" id="backup_others_new" onClick="ChequearTabla(this, 'table_others_new');"> <strong>{$TODO_OTROS_NEW}</strong></label>
            </div>
            {foreach key=key item=item from=$backup_otros_new}
            <div class="iss-field">
                <label class="iss-inline-field"><INPUT type="checkbox" {$item.disable} name="{$key}" id="{$key}" value="{$key}" onClick="VerificarCheck(this, 'backup_others_new');" {$item.check}> <span {if !empty($item.disable)}style="text-decoration: line-through"{/if}>{$item.desc}&nbsp;{$item.msg}</span></label>
            </div>
            {/foreach}
        </div>
    </section>
    {$WARNING}
    <INPUT type="hidden" name="option_url" id="option_url" value="{$OPTION_URL}">
    <input type='hidden' name='backup_file' value='{$BACKUP_FILE}'>
</div>
</form>
