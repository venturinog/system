<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  | Copyright (c) 2021 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You must obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"   |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  $Id: index.php, Thu 08 Jul 2021 05:40:56 PM EDT, nicolas@issabel.com Exp $ */
require_once "libs/misc.lib.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "libs/paloSantoForm.class.php" ;
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

    $listaZonas = readTimeZones();
    $sZonaActual = get_default_timezone();  // <-- requiere framework >= 2.5.0-6

    $errors = array();
    $accion = getParameter("Actualizar") ? 'save' : (getParameter("edit") ? 'edit' : 'show');

    if ($accion === 'save') {
        $errors = validateTimeForm($_POST, $listaZonas);
        if (count($errors) === 0) {
            $resultado = applyTimeSettings($_POST, $listaZonas, $sZonaActual);
            if ($resultado['error'] !== '') {
                $smarty->assign('mb_title', _tr('Error'));
                $smarty->assign('mb_message', $resultado['error']);
            } else {
                $smarty->assign('mb_message', $resultado['message']);
                $sZonaActual = $resultado['timezone'];
                // Tras cambiar la hora, la vista muestra la nueva.
                $accion = 'show';
            }
        }
    }

    if ($accion === 'show')
        return renderTimePage($smarty, $module_name, $local_templates_dir, $listaZonas, $sZonaActual, 'view', $errors);

    return renderTimePage($smarty, $module_name, $local_templates_dir, $listaZonas, $sZonaActual, 'input', $errors);
}

/*
 * Render unico de la pagina. En modo vista se muestran la hora actual y la
 * zona horaria como texto plano; el formulario de cambio es un paso aparte.
 */
function renderTimePage($smarty, $module_name, $local_templates_dir, $listaZonas, $sZonaActual, $mode = 'view', $errors = array())
{
    $arrForm = createTimeForm($listaZonas, $sZonaActual);

    $campos = array('date','ServerDate_Hour','ServerDate_Minute','ServerDate_Second','TimeZone');
    foreach ($campos as $f) {
        if (!isset($errors[$f])) continue;
        if ($arrForm[$f]['INPUT_TYPE'] === 'SELECT') {
            // El <select> no usa INPUT_EXTRA_PARAM para los atributos.
            $arrForm[$f]['CLASS'] = 'is-invalid';
        } else {
            if (!isset($arrForm[$f]['INPUT_EXTRA_PARAM']) || !is_array($arrForm[$f]['INPUT_EXTRA_PARAM']))
                $arrForm[$f]['INPUT_EXTRA_PARAM'] = array();
            $cls = isset($arrForm[$f]['INPUT_EXTRA_PARAM']['class'])
                ? $arrForm[$f]['INPUT_EXTRA_PARAM']['class'].' ' : 'form-control ';
            $arrForm[$f]['INPUT_EXTRA_PARAM']['class'] = $cls.'is-invalid';
        }
    }

    $oForm = new paloForm($smarty, $arrForm);
    if ($mode !== 'input') $oForm->setViewMode();

    $arrErrors = array();
    foreach ($campos as $f) $arrErrors[$f] = isset($errors[$f]) ? $errors[$f] : '';
    $smarty->assign("ERRORS", $arrErrors);

    $smarty->assign("TIME_TITULO",_tr("Date and Time Configuration"));
    $smarty->assign("INDEX_HORA_SERVIDOR",_tr("Current Datetime"));
    $smarty->assign("TIME_NUEVA_FECHA",_tr("New Date"));
    $smarty->assign("TIME_NUEVA_HORA",_tr("New Time"));
    $smarty->assign("TIME_NUEVA_ZONA",_tr("New Timezone"));
    $smarty->assign("INDEX_ACTUALIZAR",_tr("Apply changes"));
    $smarty->assign("TIME_MSG_1", _tr("The change of date and time can concern important  system processes.").'  '._tr("Are you sure you wish to continue?"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("EDIT", _tr("Edit"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon","modules/$module_name/images/system_preferences_datetime.png");
    $smarty->assign("module_name", $module_name);
    $smarty->assign('LISTA_ZONAS', $listaZonas);
    $smarty->assign('ZONA_ACTUAL', $sZonaActual);

    // La hora del servidor viaja en un atributo de datos, no dentro del
    // script: asi el bloque puede ir entero dentro de {literal}.
    $mes = (int)date("m", time()) - 1;
    $smarty->assign("SERVER_TIME_ISO", sprintf("%d,%d,%d,%d,%d,%d",
        (int)date("Y", time()), $mes, (int)date("d", time()),
        (int)date("H", time()), (int)date("i", time()), (int)date("s", time())));
    $smarty->assign("CURRENT_DATE", date("d M Y", time()));

    // Vista compacta: la hora actual y la zona como texto plano.
    $smarty->assign("TIME_VIEW", array(
        'datetime' => date("d M Y H:i:s", time()),
        'timezone' => $sZonaActual,
    ));

    $arrData = array();
    if ($mode === 'input' && isset($_POST['date'])) {
        $arrData = $_POST;
    } else {
        // Al abrir el formulario se propone la hora actual del servidor.
        $arrData = array(
            'date'              => date("d M Y", time()),
            'ServerDate_Hour'   => date("H", time()),
            'ServerDate_Minute' => date("i", time()),
            'ServerDate_Second' => date("s", time()),
            'TimeZone'          => $sZonaActual,
        );
    }

    return $oForm->fetchForm("$local_templates_dir/time.tpl", _tr('Date and Time Configuration'), $arrData);
}

/*
 * Abre el archivo /usr/share/zoneinfo/zone.tab y carga la columna 3, que
 * lista todas las zonas horarias. Se ignoran las lineas que empiezan por #.
 */
function readTimeZones()
{
    $listaZonas = NULL;
    $hArchivo = fopen('/usr/share/zoneinfo/zone.tab', 'r');
    if ($hArchivo) {
        $listaZonas = array();
        while ($tupla = fgetcsv($hArchivo, 2048, "\t")) {
            if (count($tupla) >= 3 && $tupla[0][0] != '#') $listaZonas[] = $tupla[2];
        }
        fclose($hArchivo);
        sort($listaZonas);
    }
    return $listaZonas;
}

/*
 * Campos del formulario. La hora va en formato de 24 horas: el selector
 * anterior mostraba un reloj de 12 y el meridiano no se guardaba, con lo
 * que cualquier hora de la tarde se guardaba como la de la mañana.
 */
function createTimeForm($listaZonas, $sZonaActual)
{
    $arrHoras = array();
    for ($i = 0; $i < 24; $i++)    $arrHoras[sprintf("%02d", $i)] = sprintf("%02d", $i);
    $arrMinutos = array();
    for ($i = 0; $i < 60; $i++)    $arrMinutos[sprintf("%02d", $i)] = sprintf("%02d", $i);

    return array(
        "date" => array(
            "LABEL"                  => _tr("New Date"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => array("class" => "form-control", "id" => "datepicker", "readonly" => "readonly", "maxlength" => "20"),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
        "ServerDate_Hour" => array(
            "LABEL"                  => _tr("Hour"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrHoras,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
        "ServerDate_Minute" => array(
            "LABEL"                  => _tr("Minute"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrMinutos,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
        "ServerDate_Second" => array(
            "LABEL"                  => _tr("Second"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrMinutos,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
        "TimeZone" => array(
            "LABEL"                  => _tr("New Timezone"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => is_array($listaZonas) ? $listaZonas : array(),
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
        ),
    );
}

/*
 * Validacion por campo. La fecha se recibe en el formato del selector
 * (dd M yyyy) y se traduce a yyyy-mm-dd; la zona tiene que estar en la
 * lista de zone.tab.
 */
function validateTimeForm($post, $listaZonas)
{
    $errors = array();

    $date = isset($post['date']) ? trim($post['date']) : '';
    $sTraducida = translateDate($date);
    if ($date === '')
        $errors['date'] = sprintf(_tr('%s is required'), _tr('New Date'));
    else if ($sTraducida === false)
        $errors['date'] = sprintf(_tr('%s is invalid'), _tr('New Date'));
    else {
        $partes = explode('-', $sTraducida);
        if (count($partes) !== 3 || !checkdate((int)$partes[1], (int)$partes[2], (int)$partes[0]))
            $errors['date'] = sprintf(_tr('%s is invalid'), _tr('New Date'));
    }

    $rango = array(
        'ServerDate_Hour'   => array(0, 23, _tr('Hour')),
        'ServerDate_Minute' => array(0, 59, _tr('Minute')),
        'ServerDate_Second' => array(0, 59, _tr('Second')),
    );
    foreach ($rango as $campo => $def) {
        $valor = isset($post[$campo]) ? trim($post[$campo]) : '';
        if ($valor === '')
            $errors[$campo] = sprintf(_tr('%s is required'), $def[2]);
        else if (!ctype_digit($valor) || (int)$valor < $def[0] || (int)$valor > $def[1])
            $errors[$campo] = sprintf(_tr('%s is invalid'), $def[2]);
    }

    $zona = isset($post['TimeZone']) ? trim($post['TimeZone']) : '';
    if ($zona === '')
        $errors['TimeZone'] = sprintf(_tr('%s is required'), _tr('New Timezone'));
    else if (!is_array($listaZonas) || !in_array($zona, $listaZonas))
        $errors['TimeZone'] = sprintf(_tr('%s is invalid'), _tr('New Timezone'));

    return $errors;
}

/*
 * Aplica la hora y la zona con el helper privilegiado. La zona se cambia
 * despues de la hora, porque el usuario espera que la hora mostrada luego
 * del cambio salte por la cantidad adecuada segun la zona.
 */
function applyTimeSettings($post, $listaZonas, $sZonaActual)
{
    $sTraducida = translateDate(trim($post['date']));
    $partes = explode('-', $sTraducida);
    $year = $partes[0];
    $month = $partes[1];
    $day = $partes[2];

    $fecha = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
        $year, $month, $day, $post['ServerDate_Hour'],
        $post['ServerDate_Minute'], $post['ServerDate_Second']);
    $cmd = "/usr/bin/issabel-helper dateconfig --datetime '$fecha' 2>&1";
    $output = $ret_val = "";
    exec($cmd, $output, $ret_val);

    if ($ret_val != 0) {
        return array('error' => _tr('System time can not be changed')." - <br/>".implode('<br/>', $output),
                     'message' => '', 'timezone' => $sZonaActual);
    }

    $sZonaNueva = $post['TimeZone'];
    if ($sZonaNueva != $sZonaActual) {
        $sComando = '/usr/bin/issabel-helper dateconfig --timezone '.escapeshellarg($sZonaNueva).' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            return array('error' => _tr('Failed to change timezone').' - '.implode('<br/>', $output),
                         'message' => '', 'timezone' => $sZonaActual);
        }
        // Actualizar zona horaria para date()
        date_default_timezone_set($sZonaNueva);
        $sZonaActual = $sZonaNueva;
    }

    return array('error' => '', 'message' => _tr('System time changed successfully'), 'timezone' => $sZonaActual);
}
?>
