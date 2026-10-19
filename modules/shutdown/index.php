<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php, Mon 06 May 2024 02:01:22 PM EDT, nicolas@issabel.com
*/

function _moduleContent(&$smarty, $module_name) {
//include module files
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $smarty->assign("icon","modules/$module_name/images/system_shutdown.png");
    $smarty->assign("title",_tr("Shutdown"));
    $smarty->assign("ACCEPT", _tr("Accept"));
    $smarty->assign("CONFIRM_CONTINUE", _tr("Are you sure you wish to continue?"));
    $smarty->assign("HALT", _tr("Halt"));
    $smarty->assign("REBOOT", _tr("Reboot"));
    $smarty->assign("module_name", $module_name);

    if(isset($_POST['submit_accept'])) {
        $ipaddr = $_SERVER['REMOTE_ADDR'];
        $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : 'unknown';
        $smarty->assign("SHUTDOWN_PROGRESS", _tr("Shutdown in progress"));
        $smarty->assign("MSG_LINK", _tr("Continue"));
        $modo = isset($_POST['shutdown_mode']) ? $_POST['shutdown_mode'] : '';
        if($modo=='1' || $modo=='2') {
            $esApagado = ($modo == '1');
            if($esApagado) {
                writeLOG("audit.log", "SHUTDOWN $user: User $user requested SHUTDOWN from $ipaddr.");
                $cmd = "echo 'sudo -u root /usr/sbin/shutdown -h now' | at now +1 minute";
            } else {
                writeLOG("audit.log", "SHUTDOWN $user: User $user requested RESTART from $ipaddr.");
                $cmd = "echo 'sudo -u root /usr/sbin/shutdown -r now' | at now +1 minute";
            }
            $output = system($cmd,$return);
            if($return!==0) {
                if($esApagado)
                    $smarty->assign("SHUTDOWN_MSG", _tr("There was a problem sending the shutdown signal."));
                else
                    $smarty->assign("SHUTDOWN_MSG", _tr("There was a problem sending the reboot signal."));
            } else {
                if($esApagado)
                    $smarty->assign("SHUTDOWN_MSG", _tr("Your system in shutting down now. Please, try again later."));
                else
                    $smarty->assign("SHUTDOWN_MSG", _tr("The reboot signal has been sent correctly."));
            }
            $salida = $smarty->fetch("file:$local_templates_dir/shutdown_in_progress.tpl");
        } else {
            // Aviso dentro de la pagina: bajo el layout de pestanas el
            // mb_message solo lo pinta la shell.
            $smarty->assign("SHUTDOWN_ALERT",
                '<div class="iss-alert iss-alert--error">'
                .'<div class="iss-alert__content">'.htmlspecialchars(_tr("Invalid shutdown mode"), ENT_COMPAT, 'UTF-8').'</div>'
                .'</div>');
            $salida = $smarty->fetch("file:$local_templates_dir/shutdown.tpl");
        }
    } else {
        $salida = $smarty->fetch("file:$local_templates_dir/shutdown.tpl");
    }

    return $salida;
}
?>
