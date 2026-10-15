<?php
require_once "libs/paloSantoForm.class.php";
require_once dirname(__FILE__)."/libs/issabelPingHost.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    require_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $default_host = "www.issabel.org";
    $lbl_host     = _tr('Host name or IP');
    $send         = '';
    $host         = '';
    $errors       = array();   // por campo: 'ping' / 'tracepath'
    $frame_url    = '';

    // El servidor es la fuente de verdad: no se lanza el comando si el destino
    // no es una IP o un nombre de host válido.
    if ($_POST) {
        if (isset($_POST['send']) && $_POST['send'] == 'ping') {
            $send = 'ping';
            $host = isset($_POST['ping_hostname']) ? trim($_POST['ping_hostname']) : '';
        } else if (isset($_POST['send']) && $_POST['send'] == 'tracepath') {
            $send = 'tracepath';
            $host = isset($_POST['tracert_hostname']) ? trim($_POST['tracert_hostname']) : '';
        }

        if ($send !== '') {
            if ($host === '')                      $errors[$send] = sprintf(_tr('%s is required'), $lbl_host);
            else if (!issabelPingHost::valid($host)) $errors[$send] = sprintf(_tr('%s is invalid'), $lbl_host);

            if (count($errors) == 0) {
                $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
                $frame_url = $scheme."://".$_SERVER['HTTP_HOST']
                    ."/modules/ping/generate.php?send=".rawurlencode($send)
                    ."&hostname=".rawurlencode($host);
            }
        }
    }

    // Se preserva lo escrito en cada campo, o el host por defecto.
    $ping_hostname    = isset($_POST['ping_hostname'])    ? trim($_POST['ping_hostname'])    : $default_host;
    $tracert_hostname = isset($_POST['tracert_hostname']) ? trim($_POST['tracert_hostname']) : $default_host;

    $smarty->assign("frame_url", $frame_url);
    $smarty->assign("ping_hostname", $ping_hostname);
    $smarty->assign("tracert_hostname", $tracert_hostname);
    $smarty->assign("ERRORS", array(
        'ping'      => isset($errors['ping'])      ? $errors['ping']      : '',
        'tracepath' => isset($errors['tracepath']) ? $errors['tracepath'] : '',
    ));

    $smarty->assign("LBL_PING",     _tr('Ping'));
    $smarty->assign("LBL_TRACE",    _tr('Trace'));
    $smarty->assign("LBL_RESULTS",  _tr('Results'));
    $smarty->assign("LBL_HOST",     $lbl_host);
    $smarty->assign("LBL_REQUIRED", _tr('Required field'));

    $content = $smarty->fetch("$local_templates_dir/new.tpl");

    return $content;

}
