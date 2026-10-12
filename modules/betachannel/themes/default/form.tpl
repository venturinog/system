<table class="tabForm" style="font-size: 16px;" width="99%" >
    <tr>
    <td  width="50%" valign='top'>
        <table>
        <tr class="letra12">
            <td align="left" colspan=2><legend>{$subtittle1}</legend></td>
        </tr>
        <tr class="letra12">
            <td align="left" >
                        <b>{$beta_channel_status.LABEL}:</b>
                    </td>
            <td align="left" ><input type="hidden" name="oldbeta_channel_status" id="oldbeta_channel_status" value="{if $value_beta_channel}1{else}0{/if}" /><div class="form-check form-switch"><input type="checkbox" class="form-check-input" role="switch" name="beta_channel_status" id="beta_channel_status" {if $value_beta_channel}checked="checked"{/if} /></div></td>
        </tr>
        </table>
    </td>
    </tr>
</table>
