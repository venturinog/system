<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.4-1                                               |
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
  $Id: index.php,v 1.1 2008-08-25 05:08:01 jvega jvega@palosanto.com Exp $ */
//include issabel framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoDB.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoCurrency.class.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['issabel_dsn']['settings']);

    //actions
    $accion = getAction();
    $content = "";

    switch($accion){
        case "save":
            $content = saveCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        case "edit":
            $content = formCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, 'input');
            break;
        default:
            $content = formCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, 'view');
            break;
    }
    return $content;
}

/*
 * Render unico de la pagina. En modo vista la moneda aparece como texto plano
 * en una tarjeta compacta; el modo edicion es un paso aparte (POST).
 */
function formCurrency($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $mode = 'view', $errors = array())
{
    $pCurrency = new paloSantoCurrency($pDB);
    $arrFormCurrency = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormCurrency);
    if ($mode !== 'input') $oForm->setViewMode();

    //CARGAR CURRENCY GUARDADO
    $curr = loadCurrentCurrency($pDB);

    if( $curr == false ) $curr = "$";

    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "modules/$module_name/images/system_preferences_currency.png");
    $smarty->assign("module_name", $module_name);

    // Vista compacta: el simbolo y el nombre de la moneda como texto plano.
    $arrCurrencies = getCurrencys();
    $smarty->assign("CURRENCY_VIEW", array(
        'symbol' => $curr,
        'name'   => isset($arrCurrencies[$curr]) ? $arrCurrencies[$curr] : $curr,
    ));

    $smarty->assign("CURRENCY_ERROR", isset($errors['currency']) ? $errors['currency'] : '');

    $arrData = array('currency' => $curr);
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Currency"), $arrData);

    return $htmlForm;
}

function saveCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf)
{
    $curr = getParameter("currency");
    $oPalo = new paloSantoCurrency($pDB);

    // La moneda debe ser una de la lista: el valor se guarda en la base de
    // datos y se usa al formatear los importes de la facturacion.
    $errors = array();
    if ($curr === '' || $curr === NULL)
        $errors['currency'] = sprintf(_tr('%s is required'), _tr('Currency'));
    else if (!array_key_exists($curr, getCurrencys()))
        $errors['currency'] = sprintf(_tr('%s is invalid'), _tr('Currency'));

    if (count($errors) > 0)
        return formCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, 'input', $errors);

    $bandera = $oPalo->SaveOrUpdateCurrency($curr);

    if($bandera == true ){
        $smarty->assign("mb_message", _tr("Successfully saved"));
    }
    else{
        $smarty->assign("mb_title", _tr("Error"));
        $smarty->assign("mb_message", $oPalo->errMsg);
    }

    return formCurrency($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, 'view');
}

function createFieldForm()
{
    $arrOptions = array('val1' => 'Value 1', 'val2' => 'Value 2', 'val3' => 'Value 3');

    $arrFields = array(
            "currency"   => array(  "LABEL"                  => _tr("Currency"),
                                    "REQUIRED"               => "no",
                                    "INPUT_TYPE"             => "SELECT",
                                    "INPUT_EXTRA_PARAM"      => getCurrencys(),
                                    "VALIDATION_TYPE"        => "text",
                                    "VALIDATION_EXTRA_PARAM" => "",
                                    "EDITABLE"               => "si",
                                            ),
            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if(getParameter("save"))
        return "save";
    else if(getParameter("edit"))
        return "edit";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}

function loadCurrentCurrency($pDB)
{
    $oPalo = new paloSantoCurrency($pDB);
    return $oPalo->loadCurrency();
}

function getCurrencys()
{
    return array(
            "AR$" => "AR$ - "._tr("Argentinian Peso"),
            "฿" => "฿ - "._tr("Thai Baht / Panamanian Balboa"),
            "Bs" => "Bs - "._tr("Venezuelan Bolívar"),
            "Bs.F." => "Bs.F. - "._tr("Venezuelan Strong Bolívar"),
            "₡" => "₡ - "._tr("Costa Rican Colón"),
            "C$" => "C$ - "._tr("Nicaraguan Córdoba / Canadian Dollar"),
            "₫" => "₫ - "._tr("Vietnamese Dong"),
            "EC$" => "EC$ - "._tr("East Caribbean Dollar"),
            "Kr" => "Kr - "._tr("Danish Krone / Swedish Krona"),
            "£" => "£ - "._tr("Turkish Lira"),
            "L$" => "L$ - "._tr("Honduran Lempira"),
            "Q" => "Q - "._tr("Guatemalan Quetzal"),
            "€" => "€ - "._tr("Euro"),
            "£GBP" => "£GBP - "._tr("Pound Sterling"),
            "R" => "R - "._tr("South African Rand"),
            "Rp" => "Rp - "._tr("Indonesian Rupiah"),
            "Rs" => "Rs - "._tr("Indian Rupee"),
            "R$" => "R$ - "._tr("Brazilian Real"),
            "руб" => "руб - "._tr("Russian Ruble"),
            "A$" => "A$ - "._tr("Australian Dollar"),
            "$" => "$ - "._tr("Dollar/Peso"),
            "¥" => "¥ - "._tr("Japanese Yen"),
            "₪" => "₪ - "._tr("Israeli Shekel"),
            "¢" => "¢ - "._tr("Salvadoran Colón"),
            "元" => "元 - "._tr("Chinese Yuan"),
            "৳" => "৳ - "._tr("Bangladeshi Taka"),
            "S$" => "S$ - "._tr("Singapore Dollar"),
            "CHF" => "CHF - "._tr("Swiss Franc"),
            "UAH" => "UAH - "._tr("Ukrainian Hryvnia"),
    );
}

?>
