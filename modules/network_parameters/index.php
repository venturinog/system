<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2018 Issabel Foundation                                |
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
  $Id: index.php, Thu 15 Nov 2018 09:16:29 AM EST, nicolas@issabel.com
*/
function _moduleContent(&$smarty, $module_name)
{
    include_once "libs/paloSantoForm.class.php";
    include_once "libs/paloSantoNetwork.class.php";
    include_once "libs/paloSantoGrid.class.php";
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

    // Dictionary names for UCS Micro VLAN interfaces
    $vlan_dictionary = array();
    $vlan_dictionary['eth0.100']='WAN';
    $vlan_dictionary['eth0.200']='LAN';

    $pNet = new paloNetwork();
    $smarty->assign("PENDING_CHANGE", "no");
    $smarty->assign("DRAFT_COUNT", 0);
    $smarty->assign("DRAFT_CHANGES", array());
    $arrEths = $pNet->obtener_interfases_red_fisicas();
    $nics = array();
    foreach($arrEths as $iface=>$idata) {
        $nicname = isset($vlan_dictionary[$idata['Name']])?$vlan_dictionary[$idata['Name']]:$idata['Name'];
        $nics[$iface]=$nicname;
    }

    $arrFormNetwork  = array("host"         => array("LABEL"                  => ""._tr('Host')." (Ex. host.example.com)",
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "domain",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dns1"         => array("LABEL"                  => _tr("Primary DNS"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dns2"         => array("LABEL"                  => _tr("Secondary DNS"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dns6"         => array("LABEL"                  => _tr("IPv6 DNS"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ipv6",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "gateway"      => array("LABEL"                  => _tr("Default Gateway"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),

);

    $arrFormInterfase = array("ip"          => array("LABEL"                  => _tr("IP Address"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ip",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "mask"         => array("LABEL"                  => _tr("Network Mask"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "mask",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "type"         => array("LABEL"                  => _tr("Interface Type"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "RADIO",
                                                     "INPUT_EXTRA_PARAM"      => array("static" => "Static", "dhcp" => "DHCP"),
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "ip6method"    => array("LABEL"                  => _tr("IPv6 Method"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "SELECT",
                                                     "INPUT_EXTRA_PARAM"      => array("" => _tr("No change"), "auto" => "auto (SLAAC)", "dhcp" => "dhcp", "manual" => "manual", "disabled" => "disabled"),
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "ip6addr"      => array("LABEL"                  => _tr("IPv6 Address"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                     "VALIDATION_TYPE"        => "ipv6/mask",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "ip6gw"        => array("LABEL"                  => _tr("IPv6 Gateway"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:200px"),
                                                     "VALIDATION_TYPE"        => "ipv6",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "dev_id"       => array("LABEL"                  => _tr("Device"),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "HIDDEN",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "ereg",
                                                     "VALIDATION_EXTRA_PARAM" => "^[[:alnum:]\.-]+$"));

    $arrFormRoute = array(
        "route_dev"    => array("LABEL"                  => _tr("Interface"),
                                "REQUIRED"               => "yes",
                                "INPUT_TYPE"             => "SELECT",
                                "INPUT_EXTRA_PARAM"      => $nics,
                                "VALIDATION_TYPE"        => "text",
                                "VALIDATION_EXTRA_PARAM" => ""),
        "route_dest"   => array("LABEL"                  => _tr("Destination"),
                                "REQUIRED"               => "yes",
                                "INPUT_TYPE"             => "TEXT",
                                "INPUT_EXTRA_PARAM"      => array("style" => "width:160px", "placeholder" => "10.0.0.0/8"),
                                "VALIDATION_TYPE"        => "text",
                                "VALIDATION_EXTRA_PARAM" => ""),
        "route_gw"     => array("LABEL"                  => _tr("Gateway"),
                                "REQUIRED"               => "yes",
                                "INPUT_TYPE"             => "TEXT",
                                "INPUT_EXTRA_PARAM"      => array("style" => "width:130px", "placeholder" => "192.168.1.1"),
                                "VALIDATION_TYPE"        => "text",
                                "VALIDATION_EXTRA_PARAM" => ""),
        "route_metric" => array("LABEL"                  => _tr("Metric"),
                                "REQUIRED"               => "no",
                                "INPUT_TYPE"             => "TEXT",
                                "INPUT_EXTRA_PARAM"      => array("style" => "width:60px", "placeholder" => "100"),
                                "VALIDATION_TYPE"        => "numeric",
                                "VALIDATION_EXTRA_PARAM" => ""),
        "route_orig"   => array("LABEL"                  => "route_orig",
                                "REQUIRED"               => "no",
                                "INPUT_TYPE"             => "HIDDEN",
                                "INPUT_EXTRA_PARAM"      => "",
                                "VALIDATION_TYPE"        => "text",
                                "VALIDATION_EXTRA_PARAM" => ""),
    );

    $strReturn ="";


    // MANEJO DE ACCIONES

    if(isset($_POST['confirm_network_change']) || isset($_POST['revert_network_change'])) {
        // Confirma o revierte el cambio de red pendiente (apply seguro).
        $pending = $pNet->obtenerCambioRedPendiente();
        $ok = TRUE;
        if (!is_null($pending)) {
            $ok = isset($_POST['confirm_network_change'])
                ? $pNet->confirmarCambioRed($pending['token'])
                : $pNet->revertirCambioRed($pending['token']);
        }
        if (!$ok) {
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message", $pNet->errMsg);
        } else {
            header("Location: index.php?menu=$module_name");
            return NULL;
        }
    }

    if(isset($_POST['apply_changes'])) {
        // Aplica el borrador completo como un único change set atómico.
        $draft = networkDraftGet();
        if (networkDraftCount($draft) > 0) {
            $changeJson = json_encode(networkDraftToChange($draft));
            $clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            if (!$pNet->aplicarCambioRed($changeJson, $clientIp)) {
                $smarty->assign("mb_title", _tr("ERROR"));
                $smarty->assign("mb_message", $pNet->errMsg);
            } else {
                networkDraftClear();
                header("Location: index.php?menu=$module_name");
                return NULL;
            }
        } else {
            header("Location: index.php?menu=$module_name");
            return NULL;
        }
    } else if(isset($_POST['discard_changes'])) {
        networkDraftClear();
        header("Location: index.php?menu=$module_name");
        return NULL;
    } else if(isset($_POST['save_route'])) {
        $routeResult = saveStaticRoute($pNet);
        if ($routeResult === TRUE) {
            header("Location: index.php?menu=$module_name");
            return NULL;
        }
        // Errores por campo: re-render del formulario marcando los campos.
        $routeOrig = (string)getParameter('route_orig');
        return constructRouteForm($smarty, $module_name, $local_templates_dir, $arrFormRoute,
            array(
                'route_dev'    => (string)getParameter('route_dev'),
                'route_dest'   => (string)getParameter('route_dest'),
                'route_gw'     => (string)getParameter('route_gw'),
                'route_metric' => (string)getParameter('route_metric'),
                'route_orig'   => $routeOrig,
            ),
            ($routeOrig === '') ? 'new' : 'edit',
            isset($routeResult['errors']) ? $routeResult['errors'] : array());
    } else if(isset($_POST['delete_route'])) {
        $routeResult = deleteStaticRoute($pNet);
        if ($routeResult !== TRUE) {
            $smarty->assign("mb_title", _tr("ERROR"));
            $smarty->assign("mb_message", $routeResult);
        } else {
            header("Location: index.php?menu=$module_name");
            return NULL;
        }
    } else if(isset($_POST['cancel_route'])) {
        header("Location: index.php?menu=$module_name");
        return NULL;
    } else if(isset($_GET['action']) && $_GET['action'] == "route_new") {
        return constructRouteForm($smarty, $module_name, $local_templates_dir, $arrFormRoute, array(), "new");
    } else if(isset($_GET['action']) && $_GET['action'] == "route_edit") {
        $routeDev  = getParameter('dev');
        $routeSpec = getParameter('spec');
        $parts = preg_split('/\s+/', trim((string)$routeSpec));
        $routeData = array(
            'route_dev'    => $routeDev,
            'route_orig'   => $routeSpec,
            'route_dest'   => isset($parts[0]) ? $parts[0] : '',
            'route_gw'     => isset($parts[1]) ? $parts[1] : '',
            'route_metric' => isset($parts[2]) ? $parts[2] : '',
        );
        return constructRouteForm($smarty, $module_name, $local_templates_dir, $arrFormRoute, $routeData, "edit");
    }

    if(isset($_POST['edit'])) {

        $arrNetwork = $pNet->obtener_configuracion_red();

        if(is_array($arrNetwork)) {
            $arrNetworkData['dns1'] = isset($arrNetwork['dns'][0])?$arrNetwork['dns'][0]:'';
            $arrNetworkData['dns2'] = isset($arrNetwork['dns'][1])?$arrNetwork['dns'][1]:'';
            $arrNetworkData['dns6'] = isset($arrNetwork['dns6'][0])?$arrNetwork['dns6'][0]:'';
            $arrNetworkData['host'] = isset($arrNetwork['host'])?$arrNetwork['host']:'';
            $arrNetworkData['gateway'] = isset($arrNetwork['gateway'])?$arrNetwork['gateway']:'';
        }

        $oForm = new paloForm($smarty, $arrFormNetwork);
        $smarty->assign("ETHERNET_INTERFASES_LIST", "");
        $smarty->assign("CANCEL", _tr("Cancel"));
        $smarty->assign("SAVE", _tr("Save"));
        $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
        $strReturn = $oForm->fetchForm("$local_templates_dir/network.tpl", _tr("Network Parameters"), $arrNetworkData);

    } else if(isset($_POST['save_network_changes'])) {

        $oForm = new paloForm($smarty, $arrFormNetwork);

        if($oForm->validateForm($_POST)) {
            // Encola el cambio (no lo aplica): se aplicará con "Aplicar cambios".
            networkDraftAddGenconf(array(
                'host'    => $_POST['host'],
                'gateway' => $_POST['gateway'],
                'dns1'    => $_POST['dns1'],
                'dns2'    => $_POST['dns2'],
                'dns6'    => (string)getParameter('dns6'),
            ));
            header("Location: index.php?menu=$module_name");
            return NULL;
        } else {
            // Error
            $smarty->assign("mb_title", _tr("Validation Error"));
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $smarty->assign("CANCEL", _tr("Cancel"));
            $smarty->assign("SAVE", _tr("Save"));
            $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
            $strReturn=$oForm->fetchForm("$local_templates_dir/network.tpl", _tr("Network Parameters"), $_POST);
        }
	$smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
        // Se aplasto el boton de grabar los cambios en la red

    } else if(isset($_POST['cancel_interfase_edit'])) {

        header("Location: index.php?menu=$module_name");

    } else if(isset($_POST['save_interfase_changes'])) {

        $oForm = new paloForm($smarty, $arrFormInterfase);

        if($oForm->validateForm($_POST)) {
            // Encola el cambio de interfaz (no lo aplica).
            networkDraftAddIfconf(array(
                'device'    => $_POST['dev_id'],
                'bootproto' => $_POST['type'],
                'ipaddr'    => $_POST['ip'],
                'netmask'   => $_POST['mask'],
                'ip6method' => (string)getParameter('ip6method'),
                'ip6addr'   => (string)getParameter('ip6addr'),
                'ip6gw'     => (string)getParameter('ip6gw'),
            ));
            header("Location: index.php?menu=$module_name");
            return NULL;
        } else {
            // Error
            $smarty->assign("mb_title", _tr("Validation Error"));
            $arrErrores=$oForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br>";
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
            $strErrorMsg .= "";
            $smarty->assign("mb_message", $strErrorMsg);
            $smarty->assign("CANCEL", _tr("Cancel"));
            $smarty->assign("APPLY_CHANGES", _tr("Apply Changes"));
            $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
            $smarty->assign("EDIT_PARAMETERS", _tr("Edit Network Parameters"));
	    $smarty->assign("icon","/modules/$module_name/images/system_hardware_detector.png");
            $smarty->assign("CONFIRM_EDIT", _tr("Are you sure you want to edit network parameters?"));
            $strReturn=$oForm->fetchForm("$local_templates_dir/network_edit_interfase.tpl", ""._tr('Edit Interface')." \"Ethernet ??\"", $_POST);
        }
    } else if(isset($_GET['action']) && $_GET['action'] == "editInterfase") {

        // TODO: Revisar si el $_GET['id'] contiene un id valido
        $arrEths = $pNet->obtener_interfases_red_fisicas();
        $arrEth = $arrEths[$_GET['id']];

        if(is_array($arrEth)) {
            $arrInterfaseData['ip'] = $arrEth['Inet Addr'];
            $arrInterfaseData['mask'] = $arrEth['Mask'];
            $arrInterfaseData['type'] = $arrEth['Type'];
            $arrInterfaseData['dev_id'] = $_GET['id'];
            $arrInterfaseData['ip6method'] = '';
            $arrInterfaseData['ip6addr'] = isset($arrEth['Inet6 Addr'], $arrEth['Mask6']) ? $arrEth['Inet6 Addr'].'/'.$arrEth['Mask6'] : '';
            $arrInterfaseData['ip6gw'] = '';
        }

        $oForm = new paloForm($smarty, $arrFormInterfase);
        $smarty->assign("CANCEL", _tr("Cancel"));
        $smarty->assign("APPLY_CHANGES", _tr("Apply Changes"));
        $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
        $smarty->assign("EDIT_PARAMETERS", _tr("Edit Network Parameters"));
	$smarty->assign("icon","/modules/$module_name/images/system_hardware_detector.png");
        $smarty->assign("CONFIRM_EDIT", _tr("Are you sure you want to edit network parameters?"));
        $strReturn = $oForm->fetchForm("$local_templates_dir/network_edit_interfase.tpl", ""._tr('Edit Interface')." \"" . $arrEth['Name'] . "\"", $arrInterfaseData);
    } else {
        // SECCION NETWORK PARAMETERS
        $arrNetwork = $pNet->obtener_configuracion_red();
        if(is_array($arrNetwork)) {
            $arrNetworkData['dns1'] = isset($arrNetwork['dns'][0])?$arrNetwork['dns'][0]:'';
            $arrNetworkData['dns2'] = isset($arrNetwork['dns'][1])?$arrNetwork['dns'][1]:'';
            $arrNetworkData['dns6'] = isset($arrNetwork['dns6'][0])?$arrNetwork['dns6'][0]:'';
            $arrNetworkData['host'] = isset($arrNetwork['host'])?$arrNetwork['host']:'';
            $arrNetworkData['gateway'] = isset($arrNetwork['gateway'])?$arrNetwork['gateway']:'';
        }

        $oForm = new paloForm($smarty, $arrFormNetwork);
        $oForm->setViewMode();

        // Cambio de red pendiente de confirmar (apply seguro)
        $pendingChange = $pNet->obtenerCambioRedPendiente();
        if (!is_null($pendingChange)) {
            $smarty->assign("PENDING_CHANGE", "yes");
            $smarty->assign("PENDING_SUMMARY", $pendingChange['summary']);
            $smarty->assign("PENDING_REMAINING", $pendingChange['remaining']);
            $smarty->assign("PENDING_WARNING", _tr("Network changes are pending confirmation. If not confirmed in time, they will be reverted automatically."));
            $smarty->assign("CONFIRM_CHANGE", _tr("Confirm Changes"));
            $smarty->assign("REVERT_CHANGE", _tr("Revert Now"));
        } else {
            $smarty->assign("PENDING_CHANGE", "no");
        }

        // Cambios sin aplicar (borrador en sesión)
        $draftLines = networkDraftSummary();
        $smarty->assign("DRAFT_CHANGES", $draftLines);
        $smarty->assign("DRAFT_COUNT", count($draftLines));
        $smarty->assign("PENDING_CHANGES_TITLE", _tr("Pending changes (not applied)"));
        $smarty->assign("APPLY_CHANGES", _tr("Apply Changes"));
        $smarty->assign("DISCARD_CHANGES", _tr("Discard Changes"));

        // SECCION ETHERNET LIST
        $arrData = array();
        $arrEths = $pNet->obtener_interfases_red_fisicas();
        $end = count($arrEths);

        foreach($arrEths as $idEth=>$arrEth) {

            $nicname = isset($vlan_dictionary[$arrEth['Name']])?$vlan_dictionary[$arrEth['Name']]:$arrEth['Name'];
            $arrTmp    = array();
            $arrTmp[0] = "&nbsp;<a href='?menu=$module_name&action=editInterfase&id=$idEth'>" . $nicname . "</a>";
            $arrTmp[1] = strtoupper($arrEth['Type']);
            $arrTmp[2] = $arrEth['Inet Addr'];
            $arrTmp[3] = $arrEth['Mask'];
            $arrTmp[4] = isset($arrEth['Inet6 Addr'], $arrEth['Mask6']) ? htmlspecialchars($arrEth['Inet6 Addr'].'/'.$arrEth['Mask6'], ENT_COMPAT, 'UTF-8') : '';
            $arrTmp[5] = $arrEth['HWaddr'];
            $arrTmp[6] = isset($arrEth['HW_info'])?$arrEth['HW_info']:''; //- Deberia acotar este campo pues puede ser muy largo
            $arrTmp[7] = ($arrEth['Running']=="Yes" ? "<font color=green>"._tr("Connected")."</font>" : "<font color=red>"._tr("Not Connected")."</font>");
            $arrData[] = $arrTmp;
        }

        $oGrid = new paloSantoGrid($smarty);
        $oGrid->pagingShow(false);

        $arrGrid = array("title"    => _tr("Ethernet Interfaces List"),
                         "icon"     => "/modules/$module_name/images/system_hardware_detector.png",
                         "width"    => "99%",
                         "start"    => "1",
                         "end"      => $end,
                         "total"    => $end,
                         "columns"  => array(0 => array("name"      => _tr("Device"),
                                                        "property1" => ""),
                                             1 => array("name"      => _tr("Type"),
                                                        "property1" => ""),
                                             2 => array("name"      => _tr("IP"),
                                                        "property1" => ""),
                                             3 => array("name"      => _tr("Mask"),
                                                        "property1" => ""),
                                             4 => array("name"      => _tr("IPv6"),
                                                        "property1" => ""),
                                             5 => array("name"      => _tr("MAC Address"),
                                                        "property1" => ""),
                                             6 => array("name"      => _tr("HW Info"),
                                                        "property1" => ""),
                                             7 => array("name"      => _tr("Status"),
                                                        "property1" => "")
                                            ));

        $htmlGrid = $oGrid->fetchGrid($arrGrid, $arrData);
        $smarty->assign("ETHERNET_INTERFASES_LIST", $htmlGrid);
        $smarty->assign("ROUTES_LIST", renderRoutesGrid($smarty, $module_name, $pNet));
        $smarty->assign("EDIT_PARAMETERS", _tr("Edit Network Parameters"));
        $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
	$smarty->assign("icon","modules/network_parameters/images/system_network_network_parameters.png");
        // DISPLAY
        $strReturn = $oForm->fetchForm("$local_templates_dir/network.tpl", _tr("Network Parameters"), $arrNetworkData);
    }

    return $strReturn;
}

function constructRouteForm($smarty, $module_name, $local_templates_dir, $arrFormRoute, $data, $action, $errors = array())
{
    // Marca los campos de texto con error (recuadro rojo).
    foreach (array('route_dest', 'route_gw', 'route_metric') as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormRoute[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormRoute[$f]['INPUT_EXTRA_PARAM']))
            $arrFormRoute[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormRoute[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormRoute[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormRoute[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }

    $oForm = new paloForm($smarty, $arrFormRoute);
    $smarty->assign("ROUTE_ERRORS", $errors);
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "modules/$module_name/images/system_network_network_parameters.png");
    $title = ($action == "new") ? _tr("New Static Route") : _tr("Edit Static Route");
    return $oForm->fetchForm("$local_templates_dir/route_edit.tpl", $title, $data);
}

// Separa una especificación de ruta de nmcli en destino / gateway / métrica.
function parseRouteSpec($spec)
{
    $parts = preg_split('/\s+/', trim((string)$spec));
    return array(
        'dest'   => isset($parts[0]) ? $parts[0] : '',
        'gw'     => isset($parts[1]) ? $parts[1] : '',
        'metric' => isset($parts[2]) ? $parts[2] : '',
    );
}

// Construye la grilla de rutas estáticas (debajo de las interfaces).
function renderRoutesGrid($smarty, $module_name, &$pNet)
{
    $routes = $pNet->obtenerRutasRed();
    $data = array();
    if (is_array($routes)) {
        foreach ($routes as $dev => $list) {
            if (!is_array($list)) continue;
            foreach ($list as $spec) {
                $r = parseRouteSpec($spec);
                $data[] = array(
                    "<input type='checkbox' name='del_route[]' value='".htmlspecialchars($dev.'|'.$spec, ENT_COMPAT, 'UTF-8')."' />",
                    htmlspecialchars($dev, ENT_COMPAT, 'UTF-8'),
                    htmlspecialchars($r['dest'], ENT_COMPAT, 'UTF-8'),
                    htmlspecialchars($r['gw'], ENT_COMPAT, 'UTF-8'),
                    htmlspecialchars($r['metric'], ENT_COMPAT, 'UTF-8'),
                    "<a href='?menu=".$module_name."&amp;action=route_edit&amp;dev=".urlencode($dev)."&amp;spec=".urlencode($spec)."'>["._tr('Edit')."]</a>",
                );
            }
        }
    }

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr('Static Routes'));
    $oGrid->setIcon("modules/$module_name/images/system_network_network_parameters.png");
    $oGrid->setURL(array('menu' => $module_name));
    $oGrid->setColumns(array('', _tr('Interface'), _tr('Destination'), _tr('Gateway'), _tr('Metric'), _tr('Options')));
    $oGrid->addNew("?menu=$module_name&amp;action=route_new", _tr('New Static Route'), true);
    $oGrid->deleteList(_tr('Are you sure you wish to delete this route?'), 'delete_route', _tr('Delete Route'));
    $oGrid->pagingShow(false);
    $oGrid->setTotal(count($data));
    $oGrid->setLimit(count($data) > 0 ? count($data) : 1);
    $oGrid->setData($data);
    return $oGrid->fetchGrid();
}

// Da de alta (o reemplaza) una ruta estática. Devuelve TRUE, o un arreglo con
// los errores por campo: array('errors' => array(campo => mensaje)).
function saveStaticRoute(&$pNet)
{
    $dev    = (string)getParameter('route_dev');
    $dest   = trim((string)getParameter('route_dest'));
    $gw     = trim((string)getParameter('route_gw'));
    $metric = trim((string)getParameter('route_metric'));
    $orig   = (string)getParameter('route_orig');

    $errors = array();

    if ($dev === '') $errors['route_dev'] = _tr("Invalid interface");

    // Destino: IPv4 CIDR. Si no se escribe máscara, se asume /32.
    if ($dest === '') {
        $errors['route_dest'] = _tr("Invalid destination (use CIDR, e.g. 10.0.0.0/8)");
    } else {
        if (strpos($dest, '/') === FALSE) $dest .= '/32';
        $m = NULL;
        if (!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/', $dest, $m)) {
            $errors['route_dest'] = _tr("Invalid destination (use CIDR, e.g. 10.0.0.0/8)");
        } else if ($m[1] > 255 || $m[2] > 255 || $m[3] > 255 || $m[4] > 255 || $m[5] > 32) {
            $errors['route_dest'] = _tr("Invalid destination (use CIDR, e.g. 10.0.0.0/8)");
        } else if ($dest === '0.0.0.0/0' || $dest === '0.0.0.0') {
            $errors['route_dest'] = _tr("The default route is set with the Default Gateway field");
        }
    }

    // Gateway obligatorio. 0.0.0.0 significa ruta on-link (sin next-hop).
    $onlink = FALSE;
    if ($gw === '') {
        $errors['route_gw'] = _tr("Gateway is required (use 0.0.0.0 for an on-link route)");
    } else if ($gw === '0.0.0.0') {
        $onlink = TRUE;
    } else {
        $m = NULL;
        if (!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $gw, $m)
            || $m[1] > 255 || $m[2] > 255 || $m[3] > 255 || $m[4] > 255) {
            $errors['route_gw'] = _tr("Invalid gateway");
        }
    }

    // Métrica opcional. Vacío -> NM decide (no se envía).
    if ($metric !== '' && (!ctype_digit($metric) || (float)$metric > 4294967295)) {
        $errors['route_metric'] = _tr("Invalid metric");
    }

    if (count($errors) > 0) return array('errors' => $errors);

    // Spec: "dest [gw] [metric]". On-link -> solo dest (la métrica se omite).
    $spec = $dest;
    if (!$onlink) {
        $spec .= ' '.$gw;
        if ($metric !== '') $spec .= ' '.$metric;
    }

    $list = routeBaseList($pNet, $dev);
    if ($orig !== '') {
        $list = array_values(array_diff($list, array($orig)));
    }
    $list = array_values(array_diff($list, array($spec)));
    $list[] = $spec;

    networkDraftAddRoutes($dev, implode(', ', $list));
    return TRUE;
}

// Borra una o varias rutas estáticas. Devuelve TRUE o el mensaje de error.
function deleteStaticRoute(&$pNet)
{
    $sel = getParameter('del_route');
    if (is_null($sel) || $sel === '') return _tr("No route selected");
    if (!is_array($sel)) $sel = array($sel);

    // Agrupa por interfaz para recomponer la lista de rutas de cada una.
    $byDev = array();
    foreach ($sel as $item) {
        if (!is_string($item) || strpos($item, '|') === FALSE) continue;
        list($dev, $spec) = explode('|', $item, 2);
        $byDev[$dev][] = $spec;
    }
    if (count($byDev) == 0) return _tr("No route selected");

    foreach ($byDev as $dev => $specs) {
        $list = routeBaseList($pNet, $dev);
        $list = array_values(array_diff($list, $specs));
        networkDraftAddRoutes($dev, implode(', ', $list));
    }
    return TRUE;
}

/* ------------------------------------------------------------------------- *
 *  Borrador de cambios de red (staged, en $_SESSION)
 * ------------------------------------------------------------------------- */

function networkDraftGet()
{
    if (isset($_SESSION['network_draft']) && is_array($_SESSION['network_draft'])) {
        $d = $_SESSION['network_draft'];
        if (!isset($d['genconf'])) $d['genconf'] = array();
        if (!isset($d['ifconf']))  $d['ifconf']  = array();
        if (!isset($d['routes']))  $d['routes']  = array();
        return $d;
    }
    return array('genconf' => array(), 'ifconf' => array(), 'routes' => array());
}

function networkDraftSet($d)   { $_SESSION['network_draft'] = $d; }
function networkDraftClear()   { unset($_SESSION['network_draft']); }

function networkDraftAddGenconf($g)
{
    $d = networkDraftGet();
    $d['genconf'] = $g;
    networkDraftSet($d);
}

function networkDraftAddIfconf($op)
{
    $d = networkDraftGet();
    $d['ifconf'][$op['device']] = $op;
    networkDraftSet($d);
}

function networkDraftAddRoutes($dev, $spec)
{
    $d = networkDraftGet();
    $d['routes'][$dev] = $spec;
    networkDraftSet($d);
}

function networkDraftCount($d = NULL)
{
    if (is_null($d)) $d = networkDraftGet();
    return (empty($d['genconf']) ? 0 : 1) + count($d['ifconf']) + count($d['routes']);
}

function networkDraftToChange($d)
{
    $change = array();
    if (!empty($d['genconf'])) $change['genconf'] = $d['genconf'];
    if (!empty($d['ifconf']))  $change['ifconf']  = array_values($d['ifconf']);
    if (!empty($d['routes'])) {
        $change['routes'] = array();
        foreach ($d['routes'] as $dev => $spec) $change['routes'][] = array('device' => $dev, 'routespec' => $spec);
    }
    return $change;
}

// Lista base de rutas de una interfaz: el borrador si existe, o lo aplicado.
function routeBaseList(&$pNet, $dev)
{
    $d = networkDraftGet();
    if (isset($d['routes'][$dev])) {
        $spec = trim($d['routes'][$dev]);
        return ($spec === '') ? array() : array_map('trim', explode(',', $spec));
    }
    $routes = $pNet->obtenerRutasRed();
    if (is_array($routes) && isset($routes[$dev]) && is_array($routes[$dev])) return $routes[$dev];
    return array();
}

// Texto de resumen del borrador, para el panel "cambios sin aplicar".
function networkDraftSummary()
{
    $d = networkDraftGet();
    $lines = array();
    if (!empty($d['genconf'])) {
        $g = $d['genconf'];
        $parts = array();
        foreach (array('host', 'gateway', 'dns1', 'dns2', 'dns6') as $k) {
            if (!empty($g[$k])) $parts[] = $k.'='.htmlspecialchars($g[$k], ENT_COMPAT, 'UTF-8');
        }
        $lines[] = _tr('General').': '.implode(', ', $parts);
    }
    foreach ($d['ifconf'] as $dev => $op) {
        $txt = $dev.': '.$op['bootproto'];
        if ($op['bootproto'] == 'static') $txt .= ' '.$op['ipaddr'].'/'.$op['netmask'];
        if (!empty($op['ip6method'])) $txt .= ' ipv6='.$op['ip6method'].(empty($op['ip6addr']) ? '' : ' '.$op['ip6addr']);
        $lines[] = _tr('Interface').' '.htmlspecialchars($txt, ENT_COMPAT, 'UTF-8');
    }
    foreach ($d['routes'] as $dev => $spec) {
        $lines[] = _tr('Routes').' '.htmlspecialchars($dev, ENT_COMPAT, 'UTF-8').': '.htmlspecialchars($spec, ENT_COMPAT, 'UTF-8');
    }
    return $lines;
}
?>
