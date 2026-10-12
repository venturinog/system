<!--<form method="POST" enctype="multipart/form-data">

Comentario:  He agregado variables para que se muestre la misma vista de la 160

-->

<form method="POST" enctype="multipart/form-data">
<input type="hidden" value="" name="send" id="send">
<div class="iss-form">
    <section class="iss-card">
        <div class="iss-card__body iss-formgrid">
            <div class="iss-field">
                <span class="iss-field__control">
                    <input type="text" id="ping_hostname" name="ping_hostname" value='{$ping_hostname}' class="form-control" />
                    <input type="submit" class="button" value="{php}echo _tr('Ping');{/php}" onclick="document.getElementById('send').value='ping';">
                </span>
            </div>
            <div class="iss-field">
                <span class="iss-field__control">
                    <input type="text" id="tracert_hostname" name="tracert_hostname" value='{$tracert_hostname}' class="form-control" />
                    <input type="submit" class="button" value="{php}echo _tr('Trace');{/php}" onclick="document.getElementById('send').value='tracepath';">
                </span>
            </div>
        </div>
    </section>
</div>
</form>
{if $frame_url neq ""}
<div class="iss-form">
    <section class="iss-card">
        <header class="iss-card__header"><i class="ti ti-terminal"></i> {php}echo _tr('Results');{/php}</header>
        <div class="iss-card__body">
            <pre style="font-family: monospace;">
<iframe name="ifm" id="ifm" frameborder=0 width="600px" height="500px" marginheight=0 marginwidth=0 scrolling=no src={$frame_url}></iframe>
</pre>
        </div>
    </section>
</div>
{/if}
