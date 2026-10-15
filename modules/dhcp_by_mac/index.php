<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.6-12                                               |
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
  $Id: index.php,v 1.1 2009-11-12 04:11:04 Oscar Navarrete onavarrete.palosanto.com Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoDHCP_Configuration.class.php";

    load_language_module($module_name);
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "new_dhcpconft":
            $content = viewFormDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $edit="false");
            break;
        case "view_dhcpconf":
            $content = viewFormDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $edit="false");
            break;
        case "edit_dhcpconf":
            $content = viewFormDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $edit="true");
            break;
        case "update_dhacp":
            $content = saveDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, true);
            break;
        case "save_dhcp":
            $content = saveDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "delete_dhcpConf":
            $content = deleteDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = reportDHCP_Configuration($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportDHCP_Configuration($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pDHCP_Configuration = new paloSantoDHCP_Configuration($pDB);
    $filter_field = getParameter("filter_field");
    $filter_value = getParameter("filter_value");
    $action = getParameter("nav");
    $start  = getParameter("start");
    
    //begin grid parameters
    $oGrid  = new paloSantoGrid($smarty);
        $nameOpt = array(
        "hostname" => _tr('Host Name'),
        "ipaddress" => _tr('IP Address'),
        "macaddress" => _tr('MAC Address'),
                    );

    if(isset($nameOpt[$filter_field])){
        $valorFiltro = $nameOpt[$filter_field];
    }else
        $valorFiltro = "";

    $oGrid->addFilterControl(_tr("Filter applied ")." ".$valorFiltro." = $filter_value", $_POST, array("filter_field" => "hostname","filter_value" => ""));

    $totalDHCP_Configuration = $pDHCP_Configuration->contarIpFijas($filter_field, $filter_value);

    $oGrid->addNew("new_dhcpconft",_tr("Assign IP Address"));
    $oGrid->deleteList("Are you sure you wish to delete the DHCP configuration.","delete_dhcpConf",_tr("Delete"));

    $limit  = 20;
    $total  = $totalDHCP_Configuration;
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

    $oGrid->calculatePagination($action,$start);
    $offset = $oGrid->getOffsetValue();
    $end    = $oGrid->getEnd();

    $arrData = null;
    $arrResult = $pDHCP_Configuration->leerIPsFijas($limit, $offset, $filter_field, $filter_value);

    if(is_array($arrResult) && $total>0){
        foreach($arrResult as $key => $value){ 
        $arrTmp[0]  = "<input type='checkbox' name='DhcpConfID_{$value['id']}' />";
        $arrTmp[1] = "<a href='?menu=$module_name&action=view_dhcpconf&id=".$value['id']."'>".$value['hostname']."</a>";;
        $arrTmp[2] = $value['ipaddress'];
        $arrTmp[3] = $value['macaddress'];
            $arrData[] = $arrTmp;
        }
    }

    $buttonDelete = "";

    $arrGrid = array("title"    => _tr('Assign IP Address to Host'),
                        "icon"     => "modules/$module_name/images/system_network_assign_ip_address.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        "url"      => array('menu' => $module_name, 'filter_field' => $filter_field, 'filter_value' => $filter_value),
                        "columns"  => array(
                0 => array("name"      => $buttonDelete,
                                    "property1" => ""),
                1 => array("name"      => _tr('Host Name'),
                                    "property1" => ""),
                2 => array("name"      => _tr('IP Address'),
                                    "property1" => ""),
                3 => array("name"      => _tr('MAC Address'),
                                    "property1" => ""),
                            )
                    );

    //begin section filter
    $arrFormFilterDHCP_Configuration = createFieldFilter();
    $oFilterForm = new paloForm($smarty, $arrFormFilterDHCP_Configuration);
    $smarty->assign("SHOW", _tr('Show'));

    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);
    //end grid parameters

    return $contenidoModulo;
}


function createFieldFilter(){
    $arrFilter = array(
        "hostname" => _tr('Host Name'),
        "ipaddress" => _tr('IP Address'),
        "macaddress" => _tr('MAC Address'),
                    );

    $arrFormElements = array(
            "filter_field" => array("LABEL"                  => _tr('Search'),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => $arrFilter,
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
            "filter_value" => array("LABEL"                  => "",
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "TEXT",
                                    "INPUT_EXTRA_PARAM"      => array("id" => "filter_value"),
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => ""),
                    );
    return $arrFormElements;
}


function viewFormDHCP_Configuration($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $edit="true")
{
    $pDHCP_Configuration = new paloSantoDHCP_Configuration($pDB);
    $arrFormDHCP_Configuration = createFieldForm();

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");

    $mode = 'input';
    if($action=="view_dhcpconf") $mode = 'view';
    else if($edit=="true")       $mode = 'edit';
    //end, Form data persistence to errors and other events.

    if($action=="view_dhcpconf" || $edit=="true"){ // the action is to view or view_edit.
        $dataDhcpConfig = $pDHCP_Configuration->leerInfoIPFija($id);
        if(is_array($dataDhcpConfig) && count($dataDhcpConfig)>0)
            $_DATA = $dataDhcpConfig;
        else{
            $smarty->assign("mb_title", _tr('Error get Data'));
            $smarty->assign("mb_message", $pDHCP_Configuration->errMsg);
        }
    }

    return renderDHCPForm($smarty, $module_name, $local_templates_dir, $arrFormDHCP_Configuration, $mode, $_DATA);
}


function saveDHCP_Configuration($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $update=FALSE){
    $pDHCP_Configuration = new paloSantoDHCP_Configuration($pDB);
    $arrFormDHCP_Configuration = createFieldForm();

    // Un "nombre apellido" se guarda como "nombre_apellido" (comportamiento previo)
    $hostname = getParameter("hostname");
    if(preg_match("/^([a-zA-Z]+)[[:space:]]+([a-zA-Z]+)$/", $hostname, $arrReg))
        $hostname = $arrReg[1]."_".$arrReg[2];

    $ipaddress  = getParameter("ipaddress");
    $macaddress = getParameter("macaddress");

    // Validacion por campo (el servidor es la fuente de verdad)
    $errors = array();
    if ($hostname === '')
        $errors['hostname'] = sprintf(_tr('%s is required'), _tr('Host Name'));
    else if (!preg_match('/^([[:alnum:]-]+)$/', $hostname))
        $errors['hostname'] = _tr('Invalid hostname');

    if ($ipaddress === '')
        $errors['ipaddress'] = sprintf(_tr('%s is required'), _tr('IP Address'));
    else if (!validIpv4($ipaddress))
        $errors['ipaddress'] = _tr('Invalid IP address');

    if ($macaddress === '')
        $errors['macaddress'] = sprintf(_tr('%s is required'), _tr('MAC Address'));
    else if (!preg_match('/^[[:xdigit:]]{2}(:[[:xdigit:]]{2}){5}$/', $macaddress))
        $errors['macaddress'] = _tr('Invalid MAC address');

    if (count($errors) > 0) {
        return renderDHCPForm($smarty, $module_name, $local_templates_dir, $arrFormDHCP_Configuration,
            $update ? 'edit' : 'input', $_POST, $errors);
    }

    if($update){
        $id = getParameter("id");
        $r = $pDHCP_Configuration->actualizarIpFija($id, $hostname, $ipaddress, $macaddress);
    }else{
        $r = $pDHCP_Configuration->insertarIpFija($hostname, $ipaddress, $macaddress);
    }

    if (!$r) {
        // El fallo de la capa de datos se atribuye al campo correspondiente cuando se puede
        $msg = $pDHCP_Configuration->errMsg;
        $dataErrors = array();
        if ($msg == _tr('Invalid hostname')) {
            $dataErrors['hostname'] = $msg;
        } else if ($msg == _tr('Invalid IP address')) {
            $dataErrors['ipaddress'] = $msg;
        } else if ($msg == _tr('Invalid MAC address')) {
            $dataErrors['macaddress'] = $msg;
        } else if ($msg == _tr('Duplicate IP or MAC')) {
            $dataErrors['ipaddress']  = $msg;
            $dataErrors['macaddress'] = $msg;
        } else {
            $smarty->assign("mb_message", $msg);
        }
        return renderDHCPForm($smarty, $module_name, $local_templates_dir, $arrFormDHCP_Configuration,
            $update ? 'edit' : 'input', $_POST, $dataErrors);
    }

    header("Location: ?menu=$module_name&action=show");
}

function deleteDHCP_Configuration($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf){
    $pDHCP_Configuration = new paloSantoDHCP_Configuration($pDB);
    foreach($_POST as $key => $values){
        if(substr($key,0,11) == "DhcpConfID_")
        {
            $dhcpConfId = substr($key, 11);
            $pDHCP_Configuration->borrarIpFija($dhcpConfId);
        }
    }

    header("Location: ?menu=$module_name&action=show");
}

/*
 * Render unico del formulario ABM (nuevo, ver y editar). Marca los campos con
 * error, resuelve el modo del paloForm y devuelve la plantilla. Lo usan
 * viewFormDHCP_Configuration() y saveDHCP_Configuration(), de modo que un error
 * re-renderiza la pagina sin redirigir ni perder los valores enviados.
 */
function renderDHCPForm($smarty, $module_name, $local_templates_dir, $arrFormDHCP_Configuration, $mode, $data, $errors = array())
{
    $fields = array('hostname', 'ipaddress', 'macaddress');

    foreach ($fields as $f) {
        if (!isset($errors[$f])) continue;
        if (!isset($arrFormDHCP_Configuration[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrFormDHCP_Configuration[$f]['INPUT_EXTRA_PARAM']))
            $arrFormDHCP_Configuration[$f]['INPUT_EXTRA_PARAM'] = array();
        $cls = isset($arrFormDHCP_Configuration[$f]['INPUT_EXTRA_PARAM']['class'])
            ? $arrFormDHCP_Configuration[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
        $arrFormDHCP_Configuration[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
    }

    $oForm = new paloForm($smarty, $arrFormDHCP_Configuration);
    if ($mode === 'view')      $oForm->setViewMode();
    else if ($mode === 'edit') $oForm->setEditMode();

    $smarty->assign("SAVE", _tr('Save'));
    $smarty->assign("EDIT", _tr('Edit'));
    $smarty->assign("CANCEL", _tr('Cancel'));
    $smarty->assign("REQUIRED_FIELD", _tr('Required field'));
    $smarty->assign("icon", "modules/$module_name/images/system_network_assign_ip_address.png");
    $smarty->assign("MODULE_NAME", $module_name);

    $smarty->assign("ID", isset($data['id']) ? $data['id'] : getParameter('id'));

    $arrErrors = array();
    $arrValues = array();
    foreach ($fields as $f) {
        $arrErrors[$f] = isset($errors[$f]) ? $errors[$f] : '';
        $arrValues[$f] = isset($data[$f]) ? $data[$f] : '';
    }
    $smarty->assign("ERRORS", $arrErrors);
    $smarty->assign("VALUES", $arrValues);

    return $oForm->fetchForm("$local_templates_dir/form.tpl", _tr('Assign IP Address to Host'), $data);
}

function createFieldForm()
{
    $arrFields = array(
            "hostname"   => array(      "LABEL"                  => _tr('Host Name'),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("placeholder" => "myhostname","maxlength" =>"200"),
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "ipaddress"   => array(      "LABEL"                  => _tr('IP Address'),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("placeholder" => "192.168.1.10"),
                                            "VALIDATION_TYPE"        => "ip",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ),
            "macaddress"   => array(      "LABEL"                  => _tr('MAC Address'),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXT",
                                            "INPUT_EXTRA_PARAM"      => array("placeholder" => "AA:BB:CC:DD:EE:FF"),
                                            "VALIDATION_TYPE"        => "ereg",
                                            "VALIDATION_EXTRA_PARAM" => "([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$"
                                            ),
            );
    return $arrFields;
}

/*
 * Validacion pura de una direccion IPv4 en formato a.b.c.d, con octetos 0-255.
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

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    else if(getParameter("new_dhcpconft"))
        return "new_dhcpconft";
    else if(getParameter("edit_dhcpconf"))
        return "edit_dhcpconf";
    else if(getParameter("delete_dhcpConf"))
        return "delete_dhcpConf";
    else if(getParameter("action")=="view_dhcpconf")
        return "view_dhcpconf";
    else if(getParameter("update_dhacp"))
        return "update_dhacp";
    else if(getParameter("save_dhcp"))
        return "save_dhcp";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}

?>
