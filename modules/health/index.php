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
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  +----------------------------------------------------------------------+
  | Página "Salud": vista completa del Health Center (DASHBOARDtodo.md    |
  | §9). Reutiliza el modelo issabelHealth. Solo administradores.         |
  +----------------------------------------------------------------------+
*/

function _healthLib()
{
    foreach (array('modules/health/libs/issabelHealth.class.php',
                   '/var/www/html/modules/health/libs/issabelHealth.class.php') as $f) {
        if (file_exists($f)) { require_once $f; return TRUE; }
    }
    return FALSE;
}

function _moduleContent(&$smarty, $module_name)
{
    global $arrConf, $arrConfModule;
    include_once "modules/$module_name/configs/default.conf.php";
    $arrConf = array_merge($arrConf, $arrConfModule);

    load_language_module($module_name);

    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = isset($arrConf['templates_dir']) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/$templates_dir/".$arrConf['theme'];

    if (!_healthLib()) {
        return _healthDenied($smarty, $local_templates_dir, _tr('Health module is not installed'));
    }

    $h = new issabelHealth();
    $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : '';
    if (!$h->isAdminUser($user)) {
        return _healthDenied($smarty, $local_templates_dir, _tr('You do not have permission to view this page'));
    }

    // Exportación CSV (rawmode=yes).
    if (isset($_REQUEST['exportcsv']) && $_REQUEST['exportcsv'] === 'yes') {
        return _healthExportCSV($h);
    }

    // Acciones (POST).
    $msg = NULL; $msgType = 'success';
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    if ($action === 'saveThresholds') {
        $in = array();
        foreach (array('disk_warn_percent','disk_crit_percent','swap_warn_percent',
                       'load_warn_factor','cert_warn_days','cert_crit_days',
                       'channels_capacity','backup_warn_hours') as $k)
            if (isset($_POST[$k])) $in[$k] = $_POST[$k];
        $r = $h->saveThresholds($in, $user);
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    } elseif ($action === 'saveCritical') {
        $type = isset($_POST['ctype']) ? $_POST['ctype'] : '';
        $mode = isset($_POST['cmode']) ? $_POST['cmode'] : 'list';
        $ids  = (isset($_POST['cids']) && is_array($_POST['cids'])) ? $_POST['cids'] : array();
        $r = $h->saveCriticalEndpoints($type, $mode, $ids, $user);
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    } elseif ($action === 'saveEndpointPolicy') {
        $dm = isset($_POST['ep_minutes']) ? $_POST['ep_minutes'] : 10;
        $rd = isset($_POST['ep_days']) ? $_POST['ep_days'] : 7;
        $r = $h->saveEndpointPolicy($dm, $rd, $user);
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    } elseif ($action === 'saveApi') {
        if (isset($_POST['do']) && $_POST['do'] === 'regenerate') {
            $r = $h->regenerateApiToken($user);
        } else {
            $en = (isset($_POST['api_enabled']) && $_POST['api_enabled'] === '1');
            $r = $h->setApiEnabled($en, $user);
        }
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    }

    return _healthRenderPage($smarty, $h, $local_templates_dir, $msg, $msgType);
}

function _healthDenied($smarty, $dir, $message)
{
    $smarty->assign(array('title' => _tr('System health'), 'MESSAGE' => $message));
    return $smarty->fetch("$dir/denied.tpl");
}

function _healthRenderPage($smarty, $h, $dir, $msg, $msgType)
{
    $rows  = $h->readChecks();
    $score = $h->computeScore($rows);
    $run   = $h->lastRun();
    $cfg   = $h->getConfig(TRUE);
    $api   = $h->getApiConfig();

    $fcat = isset($_POST['fcat']) ? $_POST['fcat'] : '';
    $fsev = isset($_POST['fsev']) ? $_POST['fsev'] : '';

    $checks = array(); $cats = array();
    foreach ($rows as $id => $r) {
        if ($r['category'] !== '' && !in_array($r['category'], $cats)) $cats[] = $r['category'];
        if ($fcat !== '' && $r['category'] !== $fcat) continue;
        if ($fsev !== '' && $r['severity'] !== $fsev) continue;
        $checks[] = array(
            'id'        => $id,
            'category'  => $r['category'],
            'severity'  => $r['severity'],
            'value'     => $r['value'],
            'threshold' => $r['threshold'],
            'detail'    => $r['detail'],
            'menu'      => _healthMenu($r['link']),
            'checked'   => _healthAgo($r['checked_at']),
        );
    }

    // Ordenar: críticos, avisos, desconocidos, correctos.
    $rank = array('critical' => 0, 'warning' => 1, 'unknown' => 2, 'ok' => 3);
    usort($checks, function ($a, $b) use ($rank) {
        if ($rank[$a['severity']] !== $rank[$b['severity']])
            return $rank[$a['severity']] - $rank[$b['severity']];
        return strcmp($a['id'], $b['id']);
    });
    sort($cats);

    $ct = $cfg['critical_trunks']; $ce = $cfg['critical_extensions'];

    $smarty->assign(array(
        'title'          => _tr('System health'),
        'LABEL_SCORE'    => _tr('score'),
        'LABEL_CRITICAL' => _tr('critical'),
        'LABEL_WARNING'  => _tr('warning'),
        'LABEL_OK'       => _tr('ok'),
        'LABEL_UNKNOWN'  => _tr('unknown'),
        'LABEL_LASTCHECK'=> _tr('Last check'),
        'LABEL_EXPORT'   => _tr('Export CSV'),
        'LABEL_CATEGORY' => _tr('Category'),
        'LABEL_SEVERITY' => _tr('Severity'),
        'LABEL_ALL'      => _tr('All'),
        'LABEL_FILTER'   => _tr('Filter'),
        'LABEL_CHECK'    => _tr('Check'),
        'LABEL_VALUE'    => _tr('Value'),
        'LABEL_THRESHOLD'=> _tr('Threshold'),
        'LABEL_DETAIL'   => _tr('Detail'),
        'LABEL_WHEN'     => _tr('Updated'),
        'LABEL_VIEW'     => _tr('View'),
        'LABEL_THRESHOLDS' => _tr('Thresholds'),
        'LABEL_SAVE'     => _tr('Save'),
        'LABEL_WARN_DISK'=> _tr('Disk warning'),
        'LABEL_CRIT_DISK'=> _tr('Disk critical'),
        'LABEL_WARN_SWAP'=> _tr('Swap warning'),
        'LABEL_LOAD_FACTOR' => _tr('Load warning factor'),
        'LABEL_WARN_CERT'=> _tr('Certificate warning'),
        'LABEL_CRIT_CERT'=> _tr('Certificate critical'),
        'LABEL_DAYS'     => _tr('days'),
        'LABEL_CHANNELS_CAP' => _tr('Channel capacity (0 = off)'),
        'LABEL_BACKUP_WARN'  => _tr('Backup warning'),
        'LABEL_CRITICAL_ENDPOINTS' => _tr('Critical endpoints'),
        'LABEL_TRUNKS'   => _tr('Trunks'),
        'LABEL_EXTENSIONS' => _tr('Extensions'),
        'LABEL_ALL_CRITICAL' => _tr('All are critical'),
        'LABEL_SELECTED' => _tr('Only the selected ones'),
        'LABEL_NO_DATA'  => _tr('No data yet'),
        'LABEL_HINT_EP'  => _tr('An endpoint that is down is reported as critical only if it is in this list.'),
        'LABEL_EP_POLICY' => _tr('Endpoint policy'),
        'LABEL_DEBOUNCE' => _tr('Debounce (minutes)'),
        'LABEL_RECENT'   => _tr('Consider registered within (days)'),
        'LABEL_HINT_DEBOUNCE' => _tr('A down endpoint is ignored until it has been down for this long, so reboots and re-registrations do not raise alarms.'),
        'ep_minutes'     => $cfg['endpoint_debounce_minutes'],
        'ep_days'        => $cfg['endpoint_recent_days'],
        'state'          => $score['state'],
        'score'          => is_null($score['score']) ? '–' : $score['score'],
        'critical'       => $score['critical'],
        'warning'        => $score['warning'],
        'ok'             => $score['ok'],
        'unknown'        => $score['unknown'],
        'run_time'       => $run ? _healthAgo($run['started_at']) : _tr('No data yet'),
        'checks'         => $checks,
        'categories'     => $cats,
        'fcat'           => $fcat,
        'fsev'           => $fsev,
        'msg'            => $msg,
        'msgType'        => $msgType,
        'th'             => $cfg['thresholds'],
        'trunk_mode'     => $ct['mode'],
        'ext_mode'       => $ce['mode'],
        'trunk_options'  => _healthOptions($h->availableTrunks(), $ct['ids']),
        'ext_options'    => _healthOptions($h->availableExtensions(), $ce['ids']),
        // API de salud (token)
        'LABEL_API_TITLE'   => _tr('Health API'),
        'LABEL_API_DESC'    => _tr('Read-only JSON with the health score and every check, for external monitoring (Zabbix, Nagios, curl). Off by default.'),
        'LABEL_API_CONSUME' => _tr('Consume it with this URL and the token header (recommended over ?token=, which may be logged):'),
        'LABEL_API_TOKEN'   => _tr('Token'),
        'LABEL_API_OFF'     => _tr('Disabled'),
        'LABEL_API_ON'      => _tr('Enabled'),
        'LABEL_COPY'        => _tr('Copy'),
        'LABEL_REGENERATE'  => _tr('Regenerate token'),
        'LABEL_HINT_RETOKEN'=> _tr('Regenerating invalidates the previous token immediately.'),
        'api_url'           => $api['url'],
        'api_token'         => $api['token'],
        'api_enabled'       => $api['enabled'],
        'api_curl'          => 'curl -k -H "X-Health-Token: '.$api['token'].'" '.$api['url'],
    ));
    return $smarty->fetch("$dir/health.tpl");
}

function _healthExportCSV($h)
{
    $rows = $h->readChecks();
    header('Cache-Control: private');
    header('Content-Disposition: attachment; filename="issabel-health-'.date('Ymd-His').'.csv"');
    header('Content-Type: text/csv; charset=UTF-8');

    $buf = fopen('php://temp', 'r+');
    fputcsv($buf, array('check', 'category', 'severity', 'value', 'threshold', 'detail', 'checked_at'));
    foreach ($rows as $id => $r) {
        fputcsv($buf, array($id, $r['category'], $r['severity'], $r['value'],
                           $r['threshold'], $r['detail'], date('c', $r['checked_at'])));
    }
    rewind($buf);
    $csv = stream_get_contents($buf);
    fclose($buf);
    return $csv;
}

function _healthOptions($available, $selected)
{
    $all = array_values(array_unique(array_merge((array)$available, (array)$selected)));
    sort($all);
    $out = array();
    foreach ($all as $id) $out[] = array('id' => $id, 'checked' => in_array($id, (array)$selected));
    return $out;
}

function _healthMenu($link)
{
    if (preg_match('/menu=([\w]+)/', (string)$link, $m)) return $m[1];
    return '';
}

function _healthAgo($ts)
{
    $ts = (int)$ts;
    if ($ts <= 0) return _tr('No data yet');
    $s = time() - $ts;
    if ($s < 0) $s = 0;
    if ($s < 60)    return _tr('just now');
    if ($s < 3600)  return floor($s / 60).' '._tr('minute(s)');
    if ($s < 86400) return floor($s / 3600).' '._tr('hour(s)');
    return floor($s / 86400).' '._tr('day(s)');
}
?>
