<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.8                                                  |
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
  $Id: index.php, Mon 12 Nov 2018 09:09:17 PM EST, nicolas@issabel.com
*/
function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoNetwork.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoDHCP.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $arrFormDHCP = createFieldForm();

    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CONFIGURATION_UPDATE", _tr("Update"));
    $smarty->assign("SERVICE_START", _tr("Service Start"));
    $smarty->assign("SERVICE_STOP", _tr("Service Stop"));
    $smarty->assign("STATUS", _tr("Status"));
    $smarty->assign("OF_1_TO_50000_SECONDS", _tr("Of 1 to 50000 Seconds"));
    $smarty->assign("icon", "modules/$module_name/images/system_network_dhcp_server.png");

    if(isset($_POST["in_iniciar"])) $accion ="service_start";
    else if(isset($_POST["in_finalizar"])) $accion ="service_stop";
    else if(isset($_POST["in_actualizar_conf_red"])) $accion ="service_update";
    else $accion ="service_show";
    $content = "";
    switch($accion){
        case "service_start":
            $content = serviceStartDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP);
            break;
        case "service_stop":
            $content = serviceStopDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP);
            break;
        case "service_update":
            $content = serviceUpdateDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP);
            break;
        default: //service_show
            $content = serviceShowDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP);
            break;
    }
    return $content;
}

function createFieldForm()
{
    // Una sola caja de texto por direccion IPv4 (no los 4 octetos separados por
    // puntos que usaba el modulo).
    $arrFields =       array("in_ip_ini"    => array("LABEL"                  => _tr("Start range of IPs"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.100"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_ip_fin"    => array("LABEL"                  => _tr("End range of IPs"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.200"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_lease_time"=> array("LABEL"                  => _tr('Time of client refreshment'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "max-width:110px","maxlength" =>"5"),
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_dns1"      => array("LABEL"                  => _tr("DNS 1"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.1"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_dns2"      => array("LABEL"                  => _tr("DNS 2"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "8.8.8.8"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_wins"      => array("LABEL"                  => _tr("WINS"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.1"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_gw"        => array("LABEL"                  => _tr("Gateway"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.1"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "in_next"      => array("LABEL"                  => _tr("PXE Boot Server"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.1"),
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""));
    return $arrFields;
}

/*
 * Reune los valores del archivo de configuracion de Kea en un arreglo plano
 * con las mismas claves que los campos del formulario.
 */
function dhcpConfigValues()
{
    $paloDHCP = new PaloSantoDHCP();
    $arrTmp = array();
    $arrConfiguration = $paloDHCP->getConfigurationDHCP();
    if (is_array($arrConfiguration)) {
        foreach($arrConfiguration as $arrValues){
            if (is_array($arrValues)) $arrTmp = array_merge($arrTmp,$arrValues);
        }
    }
    return $arrTmp;
}

/*
 * Render unico de la pagina: marca los campos con error, resuelve el estado del
 * servicio y devuelve el formulario. Lo usan las cuatro acciones, de modo que un
 * error de validacion re-renderiza la pagina sin redirigir ni perder valores.
 */
function renderDHCPPage($smarty, $local_templates_dir, $arrFormDHCP, $data, $errors = array())
{
    $fields = array('in_ip_ini','in_ip_fin','in_lease_time','in_dns1','in_dns2','in_wins','in_gw','in_next');

    foreach ($fields as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormDHCP[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormDHCP[$f]['INPUT_EXTRA_PARAM']))
            $arrFormDHCP[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormDHCP[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormDHCP[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormDHCP[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }

    $oForm = new paloForm($smarty, $arrFormDHCP);

    $paloDHCP = new PaloSantoDHCP();
    $bActive = ($paloDHCP->getStatusServiceDHCP() == "active");
    $smarty->assign("DHCP_STATUS", _tr($bActive ? 'Active' : 'Inactive'));
    $smarty->assign("DHCP_STATUS_ACTIVE", $bActive);
    $smarty->assign("SERVICE_STARING", $bActive);

    $arrErrors = array();
    foreach ($fields as $f) $arrErrors[$f] = isset($errors[$f]) ? $errors[$f] : '';
    $smarty->assign("ERRORS", $arrErrors);

    return $oForm->fetchForm("$local_templates_dir/dhcp.tpl", _tr("DHCP Configuration"), $data);
}

function serviceStartDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP)
{
    $paloDHCP = new PaloSantoDHCP();
    if(!$paloDHCP->startServiceDHCP()){
        $smarty->assign("mb_title",_tr('Information'));
        $smarty->assign("mb_message",_tr("The DHCP service can't start"));
        return renderDHCPPage($smarty, $local_templates_dir, $arrFormDHCP, dhcpConfigValues());
    }
    header("Location: ?menu=$module_name");
}

function serviceStopDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP)
{
    // Intentar terminar el servicio
    $paloDHCP = new PaloSantoDHCP();
    if(!$paloDHCP->stopServiceDHCP()){
        $smarty->assign("mb_title",_tr('Information'));
        $smarty->assign("mb_message",_tr("The DHCP service can't stop"));
        return renderDHCPPage($smarty, $local_templates_dir, $arrFormDHCP, dhcpConfigValues());
    }
    header("Location: ?menu=$module_name");
}

function serviceShowDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP)
{
    return renderDHCPPage($smarty, $local_templates_dir, $arrFormDHCP, dhcpConfigValues());
}

function serviceUpdateDHCP($smarty, $module_name, $local_templates_dir, $arrFormDHCP)
{
    $ip_ini  = trim($_POST['in_ip_ini']);
    $ip_fin  = trim($_POST['in_ip_fin']);
    $ip_dns1 = trim($_POST['in_dns1']);
    $ip_dns2 = trim($_POST['in_dns2']);
    $ip_wins = trim($_POST['in_wins']);
    $ip_gw   = trim($_POST['in_gw']);
    $ip_next = trim($_POST['in_next']);
    //filename parameter is not in the form
    $in_filename = isset($_POST['in_filename']) ? trim($_POST['in_filename']) : '';

    $in_lease_time = trim($_POST['in_lease_time']);

    $errors = array();

    // Rango de IPs (obligatorio)
    if ($ip_ini === '')            $errors['in_ip_ini'] = sprintf(_tr('%s is required'), _tr('Start range of IPs'));
    else if (!validIpv4($ip_ini))  $errors['in_ip_ini'] = sprintf(_tr('%s is invalid'), _tr('Start range of IPs'));
    if ($ip_fin === '')            $errors['in_ip_fin'] = sprintf(_tr('%s is required'), _tr('End range of IPs'));
    else if (!validIpv4($ip_fin))  $errors['in_ip_fin'] = sprintf(_tr('%s is invalid'), _tr('End range of IPs'));

    // Opciones de red (opcionales; solo se validan si tienen valor)
    $optional = array('in_dns1' => array($ip_dns1, 'DNS 1'),
                      'in_dns2' => array($ip_dns2, 'DNS 2'),
                      'in_wins' => array($ip_wins, 'WINS'),
                      'in_gw'   => array($ip_gw,   'Gateway'),
                      'in_next' => array($ip_next, 'PXE Boot Server'));
    foreach ($optional as $f => $pair) {
        if ($pair[0] !== '' && !validIpv4($pair[0]))
            $errors[$f] = sprintf(_tr('%s is invalid'), _tr($pair[1]));
    }

    // Tiempo de arrendamiento
    if ($in_lease_time === '')
        $errors['in_lease_time'] = sprintf(_tr('%s is required'), _tr('Time of client refreshment'));
    else if (!ctype_digit($in_lease_time))
        $errors['in_lease_time'] = sprintf(_tr('%s is invalid'), _tr('Time of client refreshment'));
    else if ($in_lease_time > 50000 || $in_lease_time < 1)
        $errors['in_lease_time'] = _tr("Value outside the range (1-50000)");

    // Las IPs del rango deben pertenecer a alguna interfaz de la LAN
    $paloNet = new paloNetwork();
    $paloDHCP = new PaloSantoDHCP();
    $interfazEncontrada = FALSE;
    $configuracion_de_red_actual = array();
    $IPSubnet = "";

    if (!isset($errors['in_ip_ini']) && !isset($errors['in_ip_fin'])) {
        $ifacesConIp = 0;
        $empiezaEnAlguna = FALSE;
        foreach ($paloNet->obtener_interfases_red() as $dev => $datos) {
            if (preg_match("/^dummy/", $dev)) continue;
            if ($dev == "lo") continue;
            $valorIP = $datos["Inet Addr"];
            if ($valorIP == '') continue;
            $valorMask = $datos["Mask"];
            $ifacesConIp++;

            $IPSubnet = $paloDHCP->calcularIpSubred($valorIP, $valorMask);
            if ($paloDHCP->calcularIpSubred($ip_ini, $valorMask) != $IPSubnet) continue;

            $empiezaEnAlguna = TRUE;
            if ($paloDHCP->calcularIpSubred($ip_fin, $valorMask) != $IPSubnet) {
                $errors['in_ip_fin'] = _tr("The final IP is outside the LAN network");
                continue;
            }

            // La IP inicial pertenece a esa interfaz, se adopta
            unset($errors['in_ip_fin']);
            $configuracion_de_red_actual["lan_mask"] = $valorMask;
            $configuracion_de_red_actual["lan_ip"]   = $valorIP;
            $interfazEncontrada = TRUE;
            break;
        }
        if (!$interfazEncontrada) {
            if ($ifacesConIp == 0)
                $errors['in_ip_ini'] = _tr("IP Range is invalid for available devices");
            else if (!$empiezaEnAlguna)
                $errors['in_ip_ini'] = _tr("The start IP is outside the LAN network");
            else if (!isset($errors['in_ip_fin']))
                $errors['in_ip_fin'] = _tr("The final IP is outside the LAN network");
        }
    }

    // El gateway, si se indica, debe pertenecer a la misma LAN
    if ($ip_gw !== '' && !isset($errors['in_gw']) && $interfazEncontrada) {
        if ($paloDHCP->calcularIpSubred($ip_gw, $configuracion_de_red_actual["lan_mask"]) !=
                $paloDHCP->calcularIpSubred($configuracion_de_red_actual["lan_ip"], $configuracion_de_red_actual["lan_mask"]))
            $errors['in_gw'] = _tr("Gateway is outside the LAN network");
    }

    if (count($errors) > 0) {
        return renderDHCPPage($smarty, $local_templates_dir, $arrFormDHCP, $_POST, $errors);
    }

    if ($ip_next !== '' && $in_filename === '') {
        $in_filename = "syslinux/pxelinux.0";
    }

    // Pase la validacion, empiezo a generar la data que constituira el archivo de configuracion nuevo
    if(!$paloDHCP->updateFileConfDHCP($ip_gw,'',$ip_wins,$ip_dns1,$ip_dns2,
                                      $IPSubnet,$configuracion_de_red_actual,
                                      $ip_ini,$ip_fin,$in_lease_time,$ip_next,$in_filename)){
        $smarty->assign("mb_title",_tr("Update Error").":");
        $smarty->assign("mb_message",$paloDHCP->errMsg);
        return renderDHCPPage($smarty, $local_templates_dir, $arrFormDHCP, $_POST);
    }
    header("Location: ?menu=$module_name");
}

/*
 * Validacion pura de una direccion IPv4 en formato a.b.c.d. Vacia no es valida;
 * los campos opcionales deben comprobar antes que no esten vacios.
 */
function validIpv4($s)
{
    if (!is_string($s) || $s === '') return FALSE;
    $octets = explode('.', $s);
    if (count($octets) != 4) return FALSE;
    foreach ($octets as $octet) {
        if ($octet === '' || !ctype_digit($octet) || (int)$octet > 255) return FALSE;
    }
    return TRUE;
}
?>
