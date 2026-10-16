<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2026 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2. You may obtain a copy of the License at             |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  +----------------------------------------------------------------------+
  | Applet Health Center (dashboard). Reemplaza a SystemResources,       |
  | CommunicationActivity, HardDrives, PerformanceGraphic y              |
  | ProcessesStatus. Ver DASHBOARDtodo.md §4 y §6.                       |
  +----------------------------------------------------------------------+
*/
global $arrConf;

function _issabelHealthLib()
{
    foreach (array('modules/health/libs/issabelHealth.class.php',
                   '/var/www/html/modules/health/libs/issabelHealth.class.php') as $f) {
        if (file_exists($f)) { require_once $f; return TRUE; }
    }
    return FALSE;
}

function _issabelHealthFmtLib()
{
    foreach (array('modules/dashboard/libs/issabelDashboardFormat.class.php',
                   '/var/www/html/modules/dashboard/libs/issabelDashboardFormat.class.php') as $f) {
        if (file_exists($f)) { require_once $f; return TRUE; }
    }
    return FALSE;
}

/**
 * Los mensajes de los chequeos y las etiquetas de las acciones viven en el
 * módulo `health`: se carga su idioma para poder traducir `detail_key`, el
 * `value` y los avisos de impacto (DASHBOARDtodo §17).
 */
function _issabelHealthLang()
{
    if (function_exists('load_language_module')) load_language_module('health');
}

class Applet_HealthCenter
{
    function handleJSON_getContent($smarty, $module_name, $appletlist)
    {
        global $arrConf;

        $respuesta = array('status' => 'success', 'message' => '(no message)');

        // Defensa en profundidad: el applet es solo para administradores.
        $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : '';
        session_commit();
        if (empty($user) || !_issabelHealthIsAdmin($arrConf, $user)) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('You do not have permission to view this applet');
            return _issabelHealthJson($respuesta);
        }

        if (!_issabelHealthLib()) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Health module is not installed');
            return _issabelHealthJson($respuesta);
        }
        _issabelHealthFmtLib();
        _issabelHealthLang();

        $smarty->assign(array(
            'LABEL_ATTENTION' => _tr('Requires attention'),
            'LABEL_CRITICAL'  => _tr('critical'),
            'LABEL_WARNING'   => _tr('warning'),
            'LABEL_OK'        => _tr('ok'),
            'LABEL_UNKNOWN'   => _tr('unknown'),
            'LABEL_LASTCHECK' => _tr('Last check'),
            'LABEL_UPTIME'    => _tr('Uptime'),
            'LABEL_VIEW'      => _tr('View'),
            'LABEL_ALLGOOD'   => _tr('Everything looks fine'),
            'LABEL_RESOURCES' => _tr('Resources'),
            'LABEL_STORAGE'   => _tr('Storage'),
            'LABEL_TELEPHONY' => _tr('Telephony'),
            'LABEL_ASTERISK'  => _tr('Asterisk'),
            'LABEL_TRUNKS'    => _tr('Trunks'),
            'LABEL_EXTENSIONS'=> _tr('Extensions'),
            'LABEL_TREND'     => _tr('Performance trend'),
            'LABEL_CPU'       => _tr('CPU'),
            'LABEL_RAM'       => _tr('RAM'),
            'LABEL_SWAP'      => _tr('SWAP'),
            'LABEL_LOAD'      => _tr('Load'),
            'LABEL_STATE'     => _tr('state'),
            'LABEL_OPEN'      => _tr('Open health page'),
            'LABEL_ND'        => _tr('N/D'),
            'LABEL_NO_DATA'   => _tr('No data yet'),
            'LABEL_CANCEL'    => _tr('Cancel'),
            'LABEL_CONFIRM'   => _tr('Confirm'),
        ));

        $h = new issabelHealth();

        // Rango de la tendencia (1 h / 8 h / 24 h crudo; 7 d / 30 d agregados).
        $range = isset($_REQUEST['range']) ? (string)$_REQUEST['range'] : '8h';
        if (!array_key_exists($range, _issabelHealthRanges())) $range = '8h';

        // --- Estado global (health.db) ---
        $rows  = $h->readChecks();
        $score = $h->computeScore($rows);
        $run   = $h->lastRun();

        // --- Métricas vivas (no cacheadas) ---
        $m = $h->fastMetrics();

        // --- Hallazgos que requieren atención (críticos primero) ---
        $attention = array();
        foreach ($rows as $r) {
            if ($r['severity'] !== 'critical' && $r['severity'] !== 'warning') continue;
            $action = $h->actionInfo($r['action_id']);
            if (is_array($action)) {
                $action['btnlabel'] = ($action['type'] === 'service') ? _tr('Start') : _tr('Renew');
            }
            $detail = issabelHealthMsg::render($r);
            $attention[] = array(
                'severity' => $r['severity'],
                'detail'   => $detail !== '' ? $detail : $r['check_id'],
                'value'    => issabelHealthMsg::valueText($r),
                'menu'     => _issabelHealthMenuFromLink($r['link']),
                'action'   => $action,
            );
        }
        usort($attention, function ($a, $b) {
            $rank = array('critical' => 0, 'warning' => 1);
            return $rank[$a['severity']] - $rank[$b['severity']];
        });
        $attention = array_slice($attention, 0, 6);

        // --- Almacenamiento: una fila por montaje ---
        $disks = array();
        foreach ($rows as $id => $r) {
            if (strpos($id, 'storage.disk:') !== 0) continue;
            $disks[] = array(
                'mount'   => substr($id, strlen('storage.disk:')),
                'percent' => (float)rtrim($r['value'], '%'),
                'value'   => $r['value'],
                'detail'  => issabelHealthMsg::render($r),
                'severity'=> $r['severity'],
            );
        }
        usort($disks, function ($a, $b) { return $b['percent'] <=> $a['percent']; });

        // --- Comunicación (de los chequeos de telefonía) ---
        $tel = array('asterisk' => '–', 'trunks' => '–', 'extensions' => '–');
        if (isset($rows['telephony.asterisk']))   $tel['asterisk']   = issabelHealthMsg::valueText($rows['telephony.asterisk']);
        if (isset($rows['telephony.trunks']))     $tel['trunks']     = $rows['telephony.trunks']['value'];
        if (isset($rows['telephony.extensions'])) $tel['extensions'] = $rows['telephony.extensions']['value'];

        $checkedAt = 0;
        foreach ($rows as $r) if ($r['checked_at'] > $checkedAt) $checkedAt = $r['checked_at'];

        // Frescura por tarjeta: Resources es en vivo; Storage y Telephony salen
        // de sus grupos en health.db; la tendencia, del punto más nuevo.
        $storageAt   = _issabelHealthGroupTs($rows, array('storage.disk'));
        $telephonyAt = _issabelHealthGroupTs($rows, array('telephony.'));
        $nowTs       = time();

        $live = $h->liveTelephony();

        $chartConfig = _issabelHealthTrendConfig($range);
        $trendTs     = _issabelHealthTrendLastTs($chartConfig);

        $smarty->assign(array(
            'state'          => $score['state'],
            'score'          => is_null($score['score']) ? '–' : $score['score'],
            'critical'       => $score['critical'],
            'warning'        => $score['warning'],
            'ok'             => $score['ok'],
            'unknown'        => $score['unknown'],
            'hostname'       => $m['hostname'],
            'uptime_human'   => _issabelHealthHumanUptime($m['uptime']),
            'checked_human'  => $checkedAt ? _issabelHealthHumanAgo(time() - $checkedAt) : _tr('No data yet'),
            'attention'      => $attention,
            'attention_count'=> count($attention),
            'disks'          => $disks,
            'tel_asterisk'   => $tel['asterisk'],
            'tel_trunks'     => $tel['trunks'],
            'tel_extensions' => $tel['extensions'],
            // Llamadas/canales activos AHORA (en vivo)
            'live_calls'     => $live['calls'],
            'live_channels'  => $live['channels'],
            'live_available' => $live['available'],
            // Frescura por tarjeta (relativa a la vista) + absoluta para el tooltip
            'resources_ago'  => _issabelHealthAgo($nowTs),
            'resources_at'   => date('d/m H:i', $nowTs),
            'storage_ago'    => _issabelHealthAgo($storageAt),
            'storage_at'     => $storageAt ? date('d/m H:i', $storageAt) : '',
            'telephony_ago'  => _issabelHealthAgo($telephonyAt),
            'telephony_at'   => $telephonyAt ? date('d/m H:i', $telephonyAt) : '',
            'trend_ago'      => _issabelHealthAgo($trendTs),
            'trend_at'       => $trendTs ? date('d/m H:i', $trendTs) : '',
            'LABEL_LAST_UPDATE' => _tr('Last update'),
            'LABEL_ACTIVE_CALLS'=> _tr('Active calls'),
            'LABEL_CHANNELS'  => _tr('Channels'),
            'cpu_percent'    => $m['cpu_percent'],
            'mem_percent'    => $m['mem_percent'],
            'mem_used_gb'    => round($m['mem_used'] / 1024 / 1024, 1),
            'mem_total_gb'   => round($m['mem_total'] / 1024 / 1024, 1),
            'swap_percent'   => $m['swap_percent'],
            'load'           => number_format($m['load'][0], 2).' / '.$m['cores'],
            'ranges'         => _issabelHealthRangesForTemplate($range),
            'range_current'  => $range,
            'CHART_CONFIG'   => $chartConfig,
        ));

        $local_templates_dir = dirname($_SERVER['SCRIPT_FILENAME'])."/modules/$module_name/applets/HealthCenter/tpl";
        $respuesta['html'] = $smarty->fetch("$local_templates_dir/health_center.tpl");

        return _issabelHealthJson($respuesta);
    }

    /** POST: iniciar/reiniciar un servicio de la allowlist. */
    function handleJSON_serviceAction($smarty, $module_name, $appletlist)
    {
        return $this->_runAction('service');
    }

    /** POST: renovar el certificado TLS. */
    function handleJSON_certRenew($smarty, $module_name, $appletlist)
    {
        return $this->_runAction('cert');
    }

    /** AJAX: métricas vivas para el auto-refresh (CPU/memoria y llamadas activas). */
    function handleJSON_liveMetrics($smarty, $module_name, $appletlist)
    {
        $resp = array('status' => 'success', 'message' => '(no message)');
        session_commit();
        if (!_issabelHealthLib()) {
            $resp['status'] = 'error'; $resp['message'] = _tr('Health module is not installed');
            return _issabelHealthJson($resp);
        }
        _issabelHealthFmtLib();
        $h = new issabelHealth();
        $m = $h->fastMetrics();
        $live = $h->liveTelephony();
        $resp['metrics'] = array(
            'cpu_percent'  => $m['cpu_percent'],
            'mem_percent'  => $m['mem_percent'],
            'mem_used_gb'  => round($m['mem_used'] / 1024 / 1024, 1),
            'mem_total_gb' => round($m['mem_total'] / 1024 / 1024, 1),
            'swap_percent' => $m['swap_percent'],
            'load'         => number_format($m['load'][0], 2).' / '.$m['cores'],
            'calls'        => $live['calls'],
            'channels'     => $live['channels'],
            'telephony_available' => $live['available'],
            'ago'          => _issabelHealthAgo(time()),
            'at'           => date('d/m H:i'),
        );
        return _issabelHealthJson($resp);
    }

    /** AJAX: configuración de la tendencia (para refrescar la gráfica en vivo). */
    function handleJSON_trend($smarty, $module_name, $appletlist)
    {
        $resp = array('status' => 'success', 'message' => '(no message)');
        session_commit();
        if (!_issabelHealthLib()) {
            $resp['status'] = 'error'; $resp['message'] = _tr('Health module is not installed');
            return _issabelHealthJson($resp);
        }
        _issabelHealthFmtLib();
        $range = isset($_REQUEST['range']) ? (string)$_REQUEST['range'] : '8h';
        if (!array_key_exists($range, _issabelHealthRanges())) $range = '8h';
        $cfg = _issabelHealthTrendConfig($range);
        $ts  = _issabelHealthTrendLastTs($cfg);
        $resp['range']  = $range;
        $resp['config'] = json_decode($cfg, TRUE);
        $resp['ago']    = _issabelHealthAgo($ts);
        $resp['at']     = $ts ? date('d/m H:i', $ts) : '';
        return _issabelHealthJson($resp);
    }

    private function _runAction($kind)
    {
        global $arrConf;

        $respuesta = array('status' => 'success', 'message' => '(no message)');

        $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : '';
        session_commit();

        if (!_issabelHealthLib()) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Health module is not installed');
            return _issabelHealthJson($respuesta);
        }
        _issabelHealthLang();

        $h = new issabelHealth();
        if ($kind === 'service') {
            $service = isset($_REQUEST['service']) ? $_REQUEST['service'] : '';
            $action  = isset($_REQUEST['svcaction']) ? $_REQUEST['svcaction'] : '';
            $r = $h->runServiceAction($service, $action, $user);
        } else {
            $r = $h->runCertRenew($user);
        }

        $respuesta['status']  = $r['ok'] ? 'success' : 'error';
        $respuesta['message'] = $r['message'];
        if (!empty($r['detail'])) $respuesta['detail'] = $r['detail'];
        return _issabelHealthJson($respuesta);
    }
}

// ---------------------------------------------------------------------------
//  Utilidades del applet
// ---------------------------------------------------------------------------

function _issabelHealthIsAdmin($arrConf, $user)
{
    if (!class_exists('paloDB')) {
        foreach (array('libs/paloSantoDB.class.php', '/var/www/html/libs/paloSantoDB.class.php') as $f)
            if (file_exists($f)) { require_once $f; break; }
    }
    if (!class_exists('paloACL')) {
        foreach (array('libs/paloSantoACL.class.php', '/var/www/html/libs/paloSantoACL.class.php') as $f)
            if (file_exists($f)) { require_once $f; break; }
    }
    if (!class_exists('paloDB') || !class_exists('paloACL')) return FALSE;
    $pDB = new paloDB($arrConf['issabel_dsn']['acl']);
    if (!empty($pDB->errMsg)) return FALSE;
    $pACL = new paloACL($pDB);
    return (bool)$pACL->isUserAdministratorGroup($user);
}

function _issabelHealthJson($payload)
{
    $json = new Services_JSON();
    Header('Content-Type: application/json');
    return $json->encode($payload);
}

/** Extrae el `menu` de un link "menu=xxx" (o devuelve vacío). */
function _issabelHealthMenuFromLink($link)
{
    if (preg_match('/menu=([\w]+)/', (string)$link, $m)) return $m[1];
    return '';
}

function _issabelHealthHumanUptime($seconds)
{
    $d = (int)($seconds / 86400);
    $h = (int)(($seconds % 86400) / 3600);
    $m = (int)(($seconds % 3600) / 60);
    if ($d > 0) return $d.' '._tr('day(s)').' '.$h.' '._tr('hour(s)');
    if ($h > 0) return $h.' '._tr('hour(s)').' '.$m.' '._tr('minute(s)');
    return $m.' '._tr('minute(s)');
}

function _issabelHealthHumanAgo($seconds)
{
    if ($seconds < 60)   return _tr('just now');
    if ($seconds < 3600) return floor($seconds / 60).' '._tr('minute(s)');
    if ($seconds < 86400) return floor($seconds / 3600).' '._tr('hour(s)');
    return floor($seconds / 86400).' '._tr('day(s)');
}

/**
 * Rangos disponibles para la tendencia. `resolution` 0 = muestras crudas
 * (~26 h); 3600/86400 = agregados del sampler (samples_rollup).
 */
function _issabelHealthRanges()
{
    return array(
        '1h'  => array('label' => '1 h',  'secs' => 3600,      'resolution' => 0),
        '8h'  => array('label' => '8 h',  'secs' => 8 * 3600,  'resolution' => 0),
        '24h' => array('label' => '24 h', 'secs' => 24 * 3600, 'resolution' => 0),
        '7d'  => array('label' => '7 d',  'secs' => 7 * 86400, 'resolution' => 3600),
        '30d' => array('label' => '30 d', 'secs' => 30 * 86400,'resolution' => 86400),
    );
}

/** Rangos con la marca de cuál está activo, para la plantilla. */
function _issabelHealthRangesForTemplate($current)
{
    $out = array();
    foreach (_issabelHealthRanges() as $key => $r) {
        $out[] = array('key' => $key, 'label' => $r['label'], 'active' => ($key === $current));
    }
    return $out;
}

/** checked_at más reciente de los chequeos cuyos ids empiezan por alguno de los prefijos. */
function _issabelHealthGroupTs($rows, $prefixes)
{
    $ts = 0;
    foreach ((array)$rows as $id => $r) {
        foreach ((array)$prefixes as $p) {
            if (strpos($id, $p) === 0) {
                if ($r['checked_at'] > $ts) $ts = (int)$r['checked_at'];
                break;
            }
        }
    }
    return $ts;
}

/** Marca de tiempo del punto más nuevo de la tendencia (0 si no hay datos). */
function _issabelHealthTrendLastTs($jsonConfig)
{
    $cfg = json_decode($jsonConfig, TRUE);
    if (!is_array($cfg) || empty($cfg['data']['datasets'])) return 0;
    $max = 0;
    foreach ($cfg['data']['datasets'] as $d) {
        foreach ((array)$d['data'] as $pt) if ($pt['x'] > $max) $max = (int)$pt['x'];
    }
    return (int)($max / 1000);
}

/** "hace N" para un timestamp (vacío si no hay dato). */
function _issabelHealthAgo($ts)
{
    $ts = (int)$ts;
    if ($ts <= 0) return '';
    return issabelDashboardFormat::ago($ts);
}

/**
 * Configuración de la gráfica de tendencia (Chart.js), a partir del sampler.
 * Devuelve un JSON (o "null" si no hay datos todavía).
 */
function _issabelHealthTrendConfig($range = '8h')
{
    global $arrConf;

    $ranges = _issabelHealthRanges();
    if (!isset($ranges[$range])) $range = '8h';
    $def    = $ranges[$range];
    $end    = time();
    $start  = $end - $def['secs'];
    $res    = (int)$def['resolution'];

    $cfg = array('type' => 'line', 'data' => array('datasets' => array()), 'options' => array(
        'responsive' => true,
        'maintainAspectRatio' => false,
        'animation' => false,
        'elements' => array('line' => array('borderWidth' => 1), 'point' => array('radius' => 0)),
        'scales' => array(
            'x' => array('type' => 'time', 'time' => array('unit' => ($res >= 86400 ? 'day' : 'minute'))),
            'y' => array('beginAtZero' => true),
        ),
        'plugins' => array('legend' => array('labels' => array('boxWidth' => 12))),
    ));

    $lib = "{$arrConf['basePath']}/libs/paloSantoSampler.class.php";
    if (!file_exists($lib)) return 'null';
    require_once $lib;
    if (!class_exists('paloSampler')) return 'null';

    try {
        $oSampler = new paloSampler();
        $lines = $oSampler->getGraphLinesById(1);
        if (!is_array($lines) || count($lines) === 0) return 'null';

        foreach ($lines as $line) {
            if ($res === 0) {
                $samples = $oSampler->getSamplesByLineId($line['id']);
                $data = array();
                foreach ((array)$samples as $s) {
                    $ts = (int)$s['timestamp'];
                    if ($ts < $start) continue;
                    $data[] = array('x' => $ts * 1000, 'y' => (float)$s['value']);
                }
            } else {
                // El bucket se alinea a su inicio, así que pedimos uno de más
                // para incluir el que contiene `$start`.
                $rows = $oSampler->getRollup($line['id'], $res, $start - $res, $end);
                $data = array();
                foreach ((array)$rows as $r) {
                    $data[] = array('x' => (int)$r['timestamp'] * 1000, 'y' => (float)$r['value']);
                }
            }
            if (count($data) === 0) continue;
            $cfg['data']['datasets'][] = array(
                'label'           => _tr($line['name']),
                'data'            => $data,
                'borderColor'     => $line['color'],
                'backgroundColor' => $line['color'],
                'fill'            => false,
                'tension'         => 0.2,
            );
        }
        if (count($cfg['data']['datasets']) === 0) return 'null';
        $json = new Services_JSON();
        return $json->encode($cfg);
    } catch (Throwable $e) {
        return 'null';
    }
}
