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
  | Página "Updates": lista los paquetes con actualización reportados por  |
  | el Health Center y permite aplicarlos (UPDATESmodule.md). Solo admin.  |
  |                                                                      |
  | El listado sale de health.db (snapshot del recolector), por lo que   |
  | esta página no ejecuta dnf: el trabajo pesado lo hace el helper       |
  | privilegiado `pkgupdate` en segundo plano.                            |
  +----------------------------------------------------------------------+
*/

function _updatesLib()
{
    foreach (array('modules/updates/libs/issabelUpdates.class.php',
                   '/var/www/html/modules/updates/libs/issabelUpdates.class.php') as $f) {
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

    if (!_updatesLib()) {
        return _updatesDenied($smarty, $local_templates_dir, _tr('Updates module is not installed'));
    }

    $u = new issabelUpdates();
    $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : '';
    if (!$u->isAdminUser($user)) {
        return _updatesDenied($smarty, $local_templates_dir, _tr('You do not have permission to view this page'));
    }

    // AJAX de solo lectura. Se usa `ajax` (no `action`) a propósito: así el
    // requerimiento no exige token CSRF ni ensucia audit.log (STYLE.md §7).
    if (isset($_REQUEST['ajax'])) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        if ($_REQUEST['ajax'] === 'status') return json_encode($u->jobStatus());
        if ($_REQUEST['ajax'] === 'log')    return json_encode(array('log' => $u->jobLog(200)));
        return json_encode(array('error' => 'unknown'));
    }

    // Acciones (POST).
    $msg = NULL; $msgType = 'success';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'update') {
        $pkgs = (isset($_POST['pkg_x']) && is_array($_POST['pkg_x'])) ? $_POST['pkg_x'] : array();
        $r = $u->start($pkgs, $user);
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    } elseif ($action === 'refresh') {
        $r = $u->refresh($user);
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    } elseif ($action === 'reset') {
        $r = $u->reset($user);
        $msg = $r['message']; $msgType = $r['ok'] ? 'success' : 'danger';
    }

    return _updatesRenderPage($smarty, $u, $local_templates_dir, $msg, $msgType);
}

function _updatesDenied($smarty, $dir, $message)
{
    $smarty->assign(array('title' => _tr('Updates'), 'MESSAGE' => $message));
    return $smarty->fetch("$dir/denied.tpl");
}

function _updatesRenderPage($smarty, $u, $dir, $msg, $msgType)
{
    $snap = $u->readPackages();
    $job  = $u->jobStatus();

    $rows = array();
    foreach ($snap['packages'] as $p) {
        $rows[] = array(
            'key'         => $p['pkg'].'.'.$p['arch'],
            'pkg'         => $p['pkg'],
            'installed'   => $p['installed'],
            'available'   => $p['available'],
            'repo'        => $p['repo'],
            'is_issabel'  => !empty($p['is_issabel']),
            'is_security' => !empty($p['is_security']),
        );
    }

    $checkedAt = (int)$snap['checked_at'];

    // Cache-buster: la mtime del JS cambia con cada upgrade del RPM, así que la
    // URL del recurso cambia y el navegador no reutiliza el archivo viejo.
    $jsFile = dirname($_SERVER['SCRIPT_FILENAME']).'/modules/updates/themes/default/js/javascript.js';
    $assetV = is_file($jsFile) ? (string)@filemtime($jsFile) : '0';

    $smarty->assign(array(
        'title'             => _tr('Updates'),
        'module_name'       => 'updates',
        'ASSET_V'           => $assetV,

        'LABEL_PACKAGE'     => _tr('Package'),
        'LABEL_INSTALLED'   => _tr('Installed'),
        'LABEL_AVAILABLE'   => _tr('Available'),
        'LABEL_REPOSITORY'  => _tr('Repository'),
        'LABEL_TYPE'        => _tr('Type'),
        'LABEL_ISSABEL'      => _tr('Issabel'),
        'LABEL_SECURITY'    => _tr('Security'),
        'LABEL_OTHER'       => _tr('Other'),

        'LABEL_FILTER'      => _tr('Filter'),
        'LABEL_ALL'         => _tr('All'),
        'LABEL_SELECT_ALL'  => _tr('Select all'),
        'LABEL_SELECT_NONE' => _tr('Select none'),
        'LABEL_UPDATE_SELECTED' => _tr('Update selected'),
        'LABEL_REFRESH'     => _tr('Refresh'),
        'LABEL_LAST_CHECK'  => _tr('Last check'),

        'LABEL_NO_UPDATES'  => _tr('No updates available'),
        'LABEL_NO_DATA'     => _tr('No data yet'),
        'LABEL_HINT_NODATA' => _tr('Waiting for the first health check.'),

        'LABEL_WORKING'     => _tr('Update in progress'),
        'LABEL_VIEW_LOG'    => _tr('View log'),
        'LABEL_REBOOT'      => _tr('A reboot is recommended because the kernel or core libraries were updated.'),
        'LABEL_CONFIRM_UPDATE' => _tr('Update the selected packages now?'),
        'LABEL_SELECT_ONE'  => _tr('Select at least one package'),

        'rows'              => $rows,
        'has_rows'          => (count($rows) > 0),
        'checked_human'     => $checkedAt ? _updatesAgo($checkedAt) : '',
        'checked_at'        => $checkedAt ? date('Y-m-d H:i', $checkedAt) : '',
        'job_state'         => $job['state'],
        'job_started'       => !empty($job['started']) ? _updatesAgo((int)$job['started']) : '',
        'job_rc'            => (int)$job['rc'],
        'reboot_needed'     => !empty($job['reboot_needed']),
        'job_stale'         => ($job['state'] === 'running' && !empty($job['message']) && $job['message'] === 'stale'),
        'msg'               => $msg,
        'msgType'           => $msgType,
    ));
    return $smarty->fetch("$dir/updates.tpl");
}

/** "hace N" para un timestamp (mismo criterio que la página Salud). */
function _updatesAgo($ts)
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
