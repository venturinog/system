<html>
<head>
</head>
<body style='background-color: var(--iss-surface-2)'>
<table id='version' width='750px' align='center' border='0' cellspacing='0' cellpadding='4' style='font-weight:normal;'>
    <tr>
        <td class='neo-module-name' align='left' valign='middle' colspan=5'>{$warning_details}</td>
    </tr>
    <tr style='font-size: 13px; color: var(--iss-text); background-color: var(--iss-surface-2);'>
	    <td align='center' style='font-size: 12px; font-family: verdana,arial,helvetica,sans-serif;'>{$programs}</td>
	    <td align='center' style='font-size: 12px; font-family: verdana,arial,helvetica,sans-serif;'>{$Package}</td>
	    <td colspan='2' style='font-size: 13px; color: var(--iss-text); background-color: var(--iss-surface-2);'>
	        <table class='neo-table-title-row' align='center'>
	            <tr align='center'>
	               <td width='130px' colspan='2' style='border-bottom: solid 1px var(--iss-border); font-size: 13px; color: var(--iss-text); font-family: verdana,arial,helvetica,sans-serif;'>{$Version}</td>
	            </tr>
	            <tr align='center' style='font-size: 13px; font-family: verdana,arial,helvetica,sans-serif;'>
	                <td style='color: var(--iss-text); font-size: 13px;'>{$local_version}</td>
	                <td style='color: var(--iss-text); font-size: 13px;'>{$external_version}</td>
	            </tr>
	        </table>
	    </td>
	    <td class='neo-module-name'>
	        <table align='center'>
	            <tr class='neo-module-name' align='center'>
	               <td colspan='6' style='border-bottom: solid 1px var(--iss-border); font-size: 13px; color: var(--iss-text); font-family: verdana,arial,helvetica,sans-serif;'>{$Options_Backup}</td>
	            </tr>
	            <tr align='center' style='font-size: 12px; color: var(--iss-text);'>
	                <td width='60px' >&nbsp;{$Endpoint}&nbsp;</td>
	                <td width='60px' >&nbsp;{$Fax}&nbsp;</td>
	                <td width='60px' >&nbsp;{$Email}&nbsp;</td>
	                <td width='60px' >&nbsp;{$Asterisk}&nbsp;</td>
	                <td width='60px' >&nbsp;{$Others}&nbsp;</td>
	                <td width='60px' >&nbsp;{$Others_new}&nbsp;</td>
	            </tr>
	        </table>
	    </td>
    </tr>
{foreach item=package from=$packagereport}
    <tr class='neo-table-data-row' onmouseout="this.style.backgroundColor='var(--iss-surface-2)';" onmouseover="this.style.backgroundColor='var(--iss-surface)';" style='color: var(--iss-text-muted); font-size: 11px; background-color: var(--iss-surface-2); font-family: verdana,arial,helvetica,sans-serif;'>
        <td class='tdStyle' align='center'>{$package.desc}</td>
        <td class='tdStyle' align='center'>{$package.name}</td>
        <td class='tdStyle' align='center'>{$package.version_current}</td>
        <td class='tdStyle' align='center'>{$package.version_backup}</td>
        <td class='tdStyle'>
            <table align='center'>
                <tr align='center' style='font-size: 11px;'>
                    <td width='60px' class='tdStyle'>{$package.endpoint}</td>
                    <td width='60px' class='tdStyle'>{$package.fax}</td>
                    <td width='60px' class='tdStyle'>{$package.email}</td>
                    <td width='60px' class='tdStyle'>{$package.asterisk}</td>
                    <td width='60px' class='tdStyle'>{$package.otros}</td>
                    <td width='60px' class='tdStyle'>{$package.otros_new}</td>
                </tr>
            </table>
        <td>
    </tr>
{/foreach}
</table>
</body>
</html>