<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.0-7                                               |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
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
  $Id: index.php,v 1.1 2009-12-28 06:12:49 Bruno bomv.27 Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoAppletAdmin.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "save_new":
            $content = saveApplets_Admin($module_name);
            break;
        default: // view_form
            $content = showApplets_Admin($module_name);
            break;
    }
    return $content;
}

function showApplets_Admin($module_name)
{
    global $smarty;
    global $arrConf;

    $pAppletAdmin = new paloSantoAppletAdmin();
    $oForm = new paloForm($smarty,array());

    $arrApplets = $pAppletAdmin->getApplets_User($_SESSION["issabel_user"]);

    //Codigo para tomar en cuenta el nombre de applets para los archivos de idioma
    foreach($arrApplets as &$applet){
      $applet['name']=_tr($applet['name']);
    }
    unset($applet);
    //

    $smarty->assign("applets",$arrApplets);
    $smarty->assign("module_name", $module_name);
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("Applet", _tr("Applet"));
    $smarty->assign("Activated", _tr("Activated"));
    $smarty->assign("checkall", _tr("Check All"));
    $smarty->assign("icon", "modules/$module_name/images/system_dashboard_applet_admin.png");

    // Opciones del dashboard: solo para administradores.
    $isAdmin = _appletAdminIsAdmin();
    $smarty->assign("dashboard_admin", $isAdmin);
    if ($isAdmin) {
        $pDBset = new paloDB($arrConf['issabel_dsn']['settings']);
        $news = (!empty($pDBset->errMsg)) ? 'yes' : get_key_settings($pDBset, 'issabel_dashboard_news');
        $smarty->assign("news_enabled", ($news !== 'no'));
        $smarty->assign("LABEL_DASH_OPTIONS", _tr("Dashboard options"));
        $smarty->assign("LABEL_NEWS_TICKER", _tr("Show the news ticker"));
    }

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];
    $htmlForm = $oForm->fetchForm("$local_templates_dir/applet_admin.tpl",_tr("Dashboard Applet Admin"), $_POST);

    return $htmlForm;
}

function saveApplets_Admin($module_name)
{
    global $smarty;
    global $arrConf;
    $arrIDs_DAU = array();

    if(is_array($_POST) && count($_POST)>0){
        foreach($_POST as $key => $value){
            if(substr($key,0,7) == "chkdau_")
                $arrIDs_DAU[] = substr($key,7);
        }
    }

    // Opción del ticker de noticias (solo administradores).
    if (_appletAdminIsAdmin() && !empty($arrConf['issabel_dsn']['settings'])) {
        $pDBset = new paloDB($arrConf['issabel_dsn']['settings']);
        if (empty($pDBset->errMsg)) {
            set_key_settings($pDBset, 'issabel_dashboard_news',
                (isset($_POST['news_ticker']) ? 'yes' : 'no'));
        }
    }

    $pAppletAdmin = new paloSantoAppletAdmin();
    if(count($arrIDs_DAU)==0){
        // Aviso dentro de la pagina: bajo el layout de pestanas el
        // mb_message solo lo pinta la shell.
        appletAdminSetAlert($smarty, _tr("You must have at least one applet activated"), _tr("ERROR"));
    }
    else{
        $ok = $pAppletAdmin->setApplets_User($arrIDs_DAU, $_SESSION["issabel_user"]);
        if(!$ok){
            appletAdminSetAlert($smarty, $pAppletAdmin->errMsg, _tr("Validation Error"));
        }
    }
    return showApplets_Admin($module_name);
}

/*
 * Aviso visible dentro de la pagina del modulo, con la familia de avisos
 * del tema.
 */
function appletAdminSetAlert(&$smarty, $sMessage, $sTitle = NULL)
{
    if (trim((string)$sMessage) === '') $sMessage = _tr('The operation could not be completed.');
    if ($sTitle === NULL) $sTitle = _tr('ERROR');
    $html = '<div class="iss-alert iss-alert--error">'
        .'<div class="iss-alert__title">&nbsp;'.htmlspecialchars($sTitle, ENT_COMPAT, 'UTF-8').'</div>'
        .'<div class="iss-alert__content">'.nl2br(htmlspecialchars((string)$sMessage, ENT_COMPAT, 'UTF-8')).'</div>'
        .'</div>';
    $smarty->assign("APPLET_ALERT", $html);
}

function _appletAdminIsAdmin()
{
    $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : '';
    if ($user === '') return FALSE;
    if (!class_exists('paloACL')) {
        foreach (array('libs/paloSantoACL.class.php', '/var/www/html/libs/paloSantoACL.class.php') as $f)
            if (file_exists($f)) { require_once $f; break; }
    }
    if (!class_exists('paloACL')) return FALSE;
    global $arrConf;
    $pACL = new paloACL(new paloDB($arrConf['issabel_dsn']['acl']));
    return (bool)$pACL->isUserAdministratorGroup($user);
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else
        return "report"; //cancel
}
?>
