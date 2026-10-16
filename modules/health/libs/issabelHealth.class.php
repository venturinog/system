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
*/

/**
 * issabelHealth — modelo y recolector del Health Center (Issabel 6).
 *
 * Ver DASHBOARDtodo.md §6. Este archivo lo usan dos consumidores:
 *   - el helper privileged `healthcollect` (corre como root desde cron) para
 *     recolectar los chequeos "lentos" y escribir /var/www/db/health.db;
 *   - el applet del dashboard y la página "Salud" (httpd) para LEER los
 *     resultados, calcular el score y mostrar el estado de frescura.
 *
 * Reglas clave:
 *   - Los chequeos lentos no se ejecutan en cada carga del navegador: viven en
 *     health.db con un TTL por grupo (§6.3, §6.6).
 *   - Un fallo de recolección NUNCA se convierte en "ok": se reporta `unknown`.
 *   - El score es secundario: un `critical` fuerza el estado global en rojo.
 */

if (!class_exists('paloDB')) {
    foreach (array('libs/paloSantoDB.class.php', '/var/www/html/libs/paloSantoDB.class.php') as $f) {
        if (file_exists($f)) { require_once $f; break; }
    }
}
class issabelHealth
{
    const SEV_CRITICAL = 'critical';
    const SEV_WARNING  = 'warning';
    const SEV_OK       = 'ok';
    const SEV_UNKNOWN  = 'unknown';

    var $errMsg = NULL;

    private $_dbFile   = NULL;
    private $_db       = NULL;   // paloDB sobre health.db (lectura para la web,
                                 // escritura cuando corre como root)
    private $_settings = NULL;   // paloDB sobre settings.db
    private $_cfg      = NULL;

    // Cachés en memoria de una misma corrida (evita repetir llamadas a Asterisk).
    private $_peerCache        = NULL;
    private $_trunkCache       = NULL;
    private $_trunkCacheLoaded = FALSE;
    private $_stateRows        = NULL;   // endpoint_state tras actualizarlo
    private $_observed         = NULL;   // foto de endpoints de esta corrida

    function __construct()
    {
        global $arrConf;

        $dbdir = isset($arrConf['issabel_dbdir']) ? $arrConf['issabel_dbdir'] : '/var/www/db';
        $this->_dbFile = "$dbdir/health.db";

        global $arrConf;
        if (!empty($arrConf['issabel_dsn']['settings'])) {
            $this->_settings = new paloDB($arrConf['issabel_dsn']['settings']);
            if (!empty($this->_settings->errMsg)) { $this->errMsg = $this->_settings->errMsg; $this->_settings = NULL; }
        }
    }

    // =====================================================================
    //  Configuración (settings.db) + defaults
    // =====================================================================

    function getDefaults()
    {
        return array(
            'thresholds' => array(
                'disk_warn_percent'  => 80,
                'disk_crit_percent'  => 90,
                'swap_warn_percent'  => 50,
                'load_warn_factor'   => 1.5,
                'cert_warn_days'     => 30,
                'cert_crit_days'     => 7,
                'channels_capacity'  => 0,   // 0 = desactivado
                'backup_warn_hours'  => 48,  // aviso si el respaldo es más viejo
            ),
            'backup_dir'           => '/var/www/backup',
            'critical_trunks'      => array('mode' => 'all',  'ids' => array()),
            'critical_extensions'  => array('mode' => 'list', 'ids' => array()),
            'endpoint_debounce_minutes' => 10,
            'endpoint_recent_days'      => 7,
            // TTL por grupo, en segundos (§6.3).
            'ttl' => array(
                'platform'  => 300,
                'storage'   => 900,
                'dirspace'  => 86400,
                'security'  => 300,
                'cert'      => 86400,
                'updates'   => 43200,
                'telephony' => 300,
                'backup'    => 3600,
            ),
        );
    }

    /**
     * Lee la configuración desde settings.db, completando con los defaults.
     * No escribe: sembrar los defaults es tarea del recolector (root), no de la
     * web.
     */
    function getConfig($useCache = TRUE)
    {
        if ($useCache && is_array($this->_cfg)) return $this->_cfg;

        $cfg = $this->getDefaults();
        if (!is_null($this->_settings)) {
            $t = $this->_getJson('health_thresholds');
            if (is_array($t)) $cfg['thresholds'] = array_merge($cfg['thresholds'], $t);

            $ct = $this->_getJson('health_critical_trunks');
            if (is_array($ct)) $cfg['critical_trunks'] = $ct;

            $ce = $this->_getJson('health_critical_extensions');
            if (is_array($ce)) $cfg['critical_extensions'] = $ce;

            $ttl = $this->_getJson('health_ttl');
            if (is_array($ttl)) $cfg['ttl'] = array_merge($cfg['ttl'], $ttl);

            $d = $this->_getKey('health_endpoint_debounce_minutes');
            if (is_numeric($d)) $cfg['endpoint_debounce_minutes'] = (int)$d;

            $r = $this->_getKey('health_endpoint_recent_days');
            if (is_numeric($r)) $cfg['endpoint_recent_days'] = (int)$r;

            $bd = $this->_getKey('health_backup_dir');
            if (is_string($bd) && trim($bd) !== '') $cfg['backup_dir'] = trim($bd);
        }
        $this->_cfg = $cfg;
        return $cfg;
    }

    /** Siembra en settings.db las claves que falten (idempotente, root). */
    function seedDefaults()
    {
        if (is_null($this->_settings)) return FALSE;
        $d = $this->getDefaults();
        $map = array(
            'health_thresholds'            => $d['thresholds'],
            'health_critical_trunks'       => $d['critical_trunks'],
            'health_critical_extensions'   => $d['critical_extensions'],
            'health_endpoint_debounce_minutes' => (string)$d['endpoint_debounce_minutes'],
            'health_endpoint_recent_days'  => (string)$d['endpoint_recent_days'],
            'health_backup_dir'            => $d['backup_dir'],
            'health_api_enabled'           => 'no',
        );
        foreach ($map as $key => $value) {
            if ($this->_getKey($key) === '') {
                $json = is_scalar($value) ? $value : json_encode($value);
                $this->_setKey($key, $json);
            }
        }
        // El token de la API se genera una sola vez, en la instalación.
        if ($this->_getKey('health_api_token') === '') {
            $this->_setKey('health_api_token', $this->_generateToken());
        }
        return TRUE;
    }

    private function _generateToken()
    {
        return bin2hex(random_bytes(24));
    }

    private function _getJson($key)
    {
        $raw = $this->_getKey($key);
        if (!is_string($raw) || trim($raw) === '') return NULL;
        $v = json_decode($raw, TRUE);
        return is_array($v) ? $v : NULL;
    }

    /** Lee una clave de settings.db (usa misc.lib.php si está disponible). */
    private function _getKey($key)
    {
        if (is_null($this->_settings)) return '';
        if (function_exists('get_key_settings')) {
            return get_key_settings($this->_settings, $key);
        }
        $r = $this->_settings->getFirstRowQuery(
            'SELECT `value` FROM settings WHERE `key` = ?', FALSE, array($key));
        return ($r && count($r) > 0) ? $r[0] : '';
    }

    private function _setKey($key, $value)
    {
        if (is_null($this->_settings)) return FALSE;
        if (function_exists('set_key_settings')) {
            return set_key_settings($this->_settings, $key, $value);
        }
        $r = $this->_settings->getFirstRowQuery(
            'SELECT COUNT(*) FROM settings WHERE `key` = ?', FALSE, array($key));
        if (!$r) return FALSE;
        return $this->_settings->genQuery(
            ($r[0] > 0) ? 'UPDATE settings SET `value` = ? WHERE `key` = ?'
                        : 'INSERT INTO settings (`value`, `key`) VALUES (?, ?)',
            array($value, $key)) ? TRUE : FALSE;
    }

    // =====================================================================
    //  Recolección
    // =====================================================================

    /**
     * Ejecuta los chequeos cuyo TTL venció y escribe los resultados.
     * @param bool $force  Ignora el TTL y corre todo.
     * @return array       Resumen de la corrida.
     */
    function collect($force = FALSE)
    {
        $started = microtime(TRUE);
        $cfg  = $this->getConfig(FALSE);
        $now  = time();

        $this->seedDefaults();
        $this->_ensureSchema();

        $existing = $this->_readRows(NULL);   // indexado por check_id
        $groupLast = array();
        foreach ($existing as $row) {
            $g = $row['group_id'];
            if (!isset($groupLast[$g]) || $row['checked_at'] > $groupLast[$g]) $groupLast[$g] = $row['checked_at'];
        }

        $ran = 0; $skipped = 0; $errors = 0;
        $fresh = array();       // group_id => filas a persistir

        foreach ($this->_checks() as $def) {
            $group = $def['group'];
            $ttl   = isset($cfg['ttl'][$def['group']]) ? $cfg['ttl'][$def['group']] : 300;
            if (!$force && isset($groupLast[$group]) && ($now - $groupLast[$group]) < $ttl) {
                $skipped++;
                continue;
            }

            $args = isset($def['args']) ? $def['args'] : array();
            try {
                $res = call_user_func_array(array($this, $def['call']), array_merge(array($cfg), $args));
            } catch (Throwable $e) {
                $res = array('severity' => self::SEV_UNKNOWN, 'error' => 'excepción: '.$e->getMessage());
            }
            if (!is_array($res)) {
                $res = array('severity' => self::SEV_UNKNOWN, 'error' => 'resultado inválido');
            }
            if (!isset($res['severity'])) $res['severity'] = self::SEV_UNKNOWN;
            if (!empty($res['error'])) $errors++;

            $rows = $this->_normalizeRows($def, $res, $now);
            foreach ($rows as $r) {
                if (!isset($fresh[$group])) $fresh[$group] = array();
                $fresh[$group][] = $r;
            }
            $ran++;
        }

        $this->_writeGroups($fresh);

        $duration = (int)round((microtime(TRUE) - $started) * 1000);
        $summary = array(
            'started_at'  => (int)$started,
            'duration_ms' => $duration,
            'ran'         => $ran,
            'skipped'     => $skipped,
            'errors'      => $errors,
            'message'     => sprintf('ran=%d skipped=%d errors=%d', $ran, $skipped, $errors),
        );
        $this->_writeRun($summary);
        $this->_enforceOwnership();
        return $summary;
    }

    /**
     * Catálogo de chequeos. `call` es un método de esta clase; `group` define el
     * TTL y el conjunto de filas que se reemplazan juntas.
     */
    private function _checks()
    {
        $svc = function ($unit, $required, $label) {
            $base = preg_replace('/\.service$/', '', $unit);
            return array('group' => 'platform', 'category' => 'platform',
                         'call' => 'checkService', 'id' => 'platform.service.'.$base,
                         'args' => array($unit, $required, $label));
        };
        return array(
            $svc('asterisk.service', TRUE,  'Asterisk PBX'),
            $svc('httpd.service',    TRUE,  'Web server'),
            $svc('mariadb.service',  TRUE,  'Database'),
            $svc('crond.service',    FALSE, 'Cron'),
            $svc('chronyd.service',  FALSE, 'NTP (chrony)'),
            $svc('fail2ban.service', FALSE, 'Fail2ban'),
            $svc('postfix.service',  FALSE, 'SMTP relay'),
            array('group' => 'platform',  'call' => 'checkSystemdFailed'),
            array('group' => 'platform',  'call' => 'checkClock'),
            array('group' => 'backup',    'category' => 'platform', 'id' => 'platform.backup', 'call' => 'checkBackups'),
            array('group' => 'storage',   'call' => 'checkDisks'),
            array('group' => 'dirspace',  'call' => 'checkDirSpace'),
            array('group' => 'security',  'call' => 'checkFirewall'),
            array('group' => 'security',  'call' => 'checkFail2ban'),
            array('group' => 'security',  'call' => 'checkPacketbl'),
            array('group' => 'cert',      'call' => 'checkCertificate'),
            array('group' => 'updates',   'call' => 'checkUpdatesIssabel'),
            array('group' => 'updates',   'call' => 'checkUpdatesSecurity'),
            array('group' => 'updates',   'call' => 'checkRepos'),
            array('group' => 'updates',   'call' => 'checkReboot'),
            array('group' => 'telephony', 'call' => 'checkAsterisk'),
            array('group' => 'telephony', 'call' => 'checkTrunks'),
            array('group' => 'telephony', 'call' => 'checkExtensions'),
        );
    }

    /**
     * Convierte el resultado de un chequeo en una o más filas.
     * Un chequeo puede devolver `rows` (varias filas, p.ej. un disco por
     * montaje) o los campos sueltos (una sola fila).
     */
    private function _normalizeRows($def, $res, $now)
    {
        $base = array(
            'group_id'  => $def['group'],
            'category'  => isset($def['category']) ? $def['category'] : $def['group'],
            'checked_at'=> $now,
            'severity'  => isset($res['severity']) ? $res['severity'] : self::SEV_UNKNOWN,
            'source'    => isset($res['source']) ? $res['source'] : $def['call'],
        );
        if (isset($res['rows']) && is_array($res['rows'])) {
            $out = array();
            foreach ($res['rows'] as $id => $row) {
                $out[] = array_merge($base, array('check_id' => $id), $row);
            }
            return $out;
        }
        $id = isset($def['id']) ? $def['id'] : $this->_checkIdFromCall($def['call']);
        return array(array_merge($base, array('check_id' => $id), $res));
    }

    private function _checkIdFromCall($call)
    {
        $map = array(
            'checkSystemdFailed'     => 'platform.systemd_failed',
            'checkClock'             => 'platform.clock',
            'checkDisks'             => 'storage.disk',
            'checkDirSpace'          => 'storage.dirspace',
            'checkFirewall'          => 'security.firewall',
            'checkFail2ban'          => 'security.fail2ban',
            'checkPacketbl'          => 'security.packetbl',
            'checkCertificate'       => 'security.certificate',
            'checkUpdatesIssabel'    => 'updates.issabel',
            'checkUpdatesSecurity'   => 'updates.security',
            'checkRepos'             => 'updates.repos',
            'checkReboot'            => 'updates.reboot',
            'checkAsterisk'          => 'telephony.asterisk',
            'checkTrunks'            => 'telephony.trunks',
            'checkExtensions'        => 'telephony.extensions',
        );
        return isset($map[$call]) ? $map[$call] : $call;
    }

    // =====================================================================
    //  Chequeos: plataforma
    // =====================================================================

    function checkService($cfg, $unit, $required, $label)
    {
        $r = $this->_run("systemctl show -p ActiveState -p LoadState -- $unit", 10);
        $out = implode("\n", $r['out']);
        if (!preg_match('/ActiveState=(\S+)/', $out, $m)) {
            return array('severity' => self::SEV_UNKNOWN, 'value' => '?',
                         'detail' => "$label: no se pudo consultar systemd", 'error' => 'systemctl');
        }
        $state = $m[1];
        $load = preg_match('/LoadState=(\S+)/', $out, $m2) ? $m2[1] : 'loaded';
        $value = ($load === 'not-found') ? 'not installed' : $state;

        if ($load === 'not-found') {
            $sev = self::SEV_OK;
            $detail = "$label: no instalado";
        } elseif ($state === 'active') {
            $sev = self::SEV_OK;
            $detail = "$label: activo";
        } else {
            $sev = $required ? self::SEV_CRITICAL : self::SEV_WARNING;
            $detail = "$label: $state";
        }
        return array('severity' => $sev, 'value' => $value, 'detail' => $detail,
                     'source' => 'systemctl', 'action_id' => 'service_start:'.$unit);
    }

    function checkSystemdFailed()
    {
        $r = $this->_run("systemctl --failed --no-legend --plain", 10);
        if ($r['rc'] === 127) return array('severity' => self::SEV_UNKNOWN, 'detail' => 'systemctl no disponible');
        $n = 0; $names = array();
        foreach ($r['out'] as $line) {
            $line = trim($line);
            if ($line !== '') { $n++; if (count($names) < 5) $names[] = strtok($line, ' '); }
        }
        return array(
            'severity' => $n > 0 ? self::SEV_WARNING : self::SEV_OK,
            'value'    => $n,
            'detail'   => $n > 0 ? ('unidades fallidas: '.implode(', ', $names)) : 'sin unidades fallidas',
            'source'   => 'systemctl',
        );
    }

    function checkClock()
    {
        if (trim((string)shell_exec('command -v chronyc 2>/dev/null')) === '') {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'chronyc no instalado');
        }
        $r = $this->_run("chronyc tracking", 10);
        $out = implode("\n", $r['out']);
        if (!preg_match('/Leap status\s*:\s*(\S+)/i', $out, $m)) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'sin respuesta de chrony', 'error' => 'chronyc');
        }
        $ok = (strcasecmp($m[1], 'Normal') === 0);
        return array(
            'severity' => $ok ? self::SEV_OK : self::SEV_CRITICAL,
            'value'    => $m[1],
            'detail'   => 'Reloj: '.$m[1],
            'source'   => 'chronyc',
        );
    }

    /**
     * Respaldo más reciente en el directorio de backups (DASHBOARDtodo.md §7.6).
     * "Correcto" = existe un .tar con tamaño > 0; se usa su mtime. No se simula
     * OK: si no hay ninguno, se avisa.
     */
    function checkBackups($cfg)
    {
        $dir = (isset($cfg['backup_dir']) && trim($cfg['backup_dir']) !== '')
            ? rtrim($cfg['backup_dir'], '/') : '/var/www/backup';
        $warnHours = isset($cfg['thresholds']['backup_warn_hours'])
            ? (int)$cfg['thresholds']['backup_warn_hours'] : 48;

        $newest = NULL; $newestTs = 0;
        foreach ((array)glob($dir.'/*.tar') as $f) {
            if (!is_file($f) || filesize($f) <= 0) continue;
            $ts = filemtime($f);
            if ($ts > $newestTs) { $newestTs = $ts; $newest = $f; }
        }

        if (is_null($newest)) {
            return array(
                'severity'  => self::SEV_WARNING,
                'value'     => 'none',
                'threshold' => "> {$warnHours} h warning",
                'detail'    => "no backups found in $dir",
                'source'    => 'backup',
                'link'      => 'menu=backup_restore',
            );
        }

        $ageH = (time() - $newestTs) / 3600.0;
        return array(
            'severity'  => ($ageH > $warnHours) ? self::SEV_WARNING : self::SEV_OK,
            'value'     => self::_ageStr($newestTs).' old',
            'threshold' => "> {$warnHours} h warning",
            'detail'    => basename($newest).' ('.self::_ageStr($newestTs).' old)',
            'source'    => 'backup',
            'link'      => 'menu=backup_restore',
        );
    }

    /** Edad legible y corta, en inglés ("6 h", "3 d", "20 min"). */
    private static function _ageStr($ts)
    {
        $s = max(0, time() - (int)$ts);
        if ($s < 3600)  return max(1, (int)floor($s / 60)).' min';
        if ($s < 86400) return (int)floor($s / 3600).' h';
        return (int)floor($s / 86400).' d';
    }

    // =====================================================================
    //  Chequeos: almacenamiento
    // =====================================================================
    private function _diskPaths()
    {
        return array('/', '/var', '/var/log', '/var/lib/mysql', '/var/www/backup',
                     '/var/spool/asterisk');
    }

    function checkDisks($cfg)
    {
        $warn = (float)$cfg['thresholds']['disk_warn_percent'];
        $crit = (float)$cfg['thresholds']['disk_crit_percent'];
        $seen = array(); $rows = array(); $worst = self::SEV_OK; $worstPct = 0;

        foreach ($this->_diskPaths() as $path) {
            if (!is_dir($path)) continue;
            $r = $this->_run("df -P -- ".escapeshellarg($path), 10);
            if ($r['rc'] !== 0 || count($r['out']) < 2) continue;
            $cols = preg_split('/\s+/', trim($r['out'][1]));
            if (count($cols) < 6) continue;
            list($dev, $total, $used, $avail, $pct) = $cols;
            $mount = $cols[5];
            if (isset($seen[$mount])) continue;
            $seen[$mount] = TRUE;

            $pctNum = (float)rtrim($pct, '%');
            $sev = $pctNum >= $crit ? self::SEV_CRITICAL : ($pctNum >= $warn ? self::SEV_WARNING : self::SEV_OK);
            if ($pctNum > $worstPct) { $worstPct = $pctNum; $worst = $sev; }

            $g = function ($kb) { return round($kb / 1024 / 1024, 1); };  // a GB
            $rows['storage.disk:'.$mount] = array(
                'severity'  => $sev,
                'value'     => number_format($pctNum, 0).'%',
                'threshold' => ">{$warn}% warn, >{$crit}% crit",
                'detail'    => "$mount (".$g($used)."/".$g($total)." GB usados, ".$g($avail)." GB libres)",
                'source'    => 'df',
            );
        }
        if (empty($rows)) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'sin datos de df');
        }
        return array('severity' => $worst, 'value' => number_format($worstPct, 0).'%',
                     'detail' => count($rows).' montajes', 'source' => 'df', 'rows' => $rows);
    }

    function checkDirSpace()
    {
        $helper = '/usr/bin/issabel-helper';
        if (!is_executable($helper)) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'issabel-helper no disponible');
        }
        $r = $this->_run("$helper dirspacereport", 60);
        if ($r['rc'] !== 0) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'dirspacereport falló', 'error' => 'dirspacereport');
        }
        $parts = array();
        foreach ($r['out'] as $line) {
            $l = preg_split('/\s+/', trim($line));
            if (count($l) >= 2) $parts[] = $l[0].' '.$l[1];
        }
        return array(
            'severity' => self::SEV_OK,
            'value'    => count($parts),
            'detail'   => implode(' · ', array_slice($parts, 0, 6)),
            'source'   => 'dirspacereport',
        );
    }

    // =====================================================================
    //  Chequeos: seguridad
    // =====================================================================

    function checkFirewall()
    {
        $fw = '/usr/share/issabel/privileged/fwconfig';
        if (!file_exists($fw)) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'módulo security no instalado');
        }
        $r = $this->_run("$fw --isactive", 15);
        // 0 = activo, !=0 = inactivo o error.
        $active = ($r['rc'] === 0);
        return array(
            'severity' => $active ? self::SEV_OK : self::SEV_CRITICAL,
            'value'    => $active ? 'active' : 'inactive',
            'detail'   => $active ? 'firewall activo' : 'firewall inactivo',
            'source'   => 'fwconfig', 'link' => 'menu=sec_rules',
        );
    }

    function checkFail2ban()
    {
        $svc = $this->_serviceState('fail2ban.service');
        if ($svc === 'not-found') {
            return array('severity' => self::SEV_OK, 'value' => 'not installed', 'detail' => 'fail2ban no instalado');
        }
        if ($svc !== 'active') {
            return array('severity' => self::SEV_CRITICAL, 'value' => $svc, 'detail' => 'fail2ban no activo', 'link' => 'menu=sec_fb_admin');
        }
        $r = $this->_run("fail2ban-client status", 10);
        $out = implode("\n", $r['out']);
        $jails = array();
        if (preg_match('/Jail list:\s*(.+)/', $out, $m)) {
            $jails = array_filter(array_map('trim', explode(',', $m[1])));
        }
        return array(
            'severity' => self::SEV_OK,
            'value'    => count($jails),
            'detail'   => count($jails).' jaulas: '.implode(', ', $jails),
            'source'   => 'fail2ban-client', 'link' => 'menu=sec_fb_admin',
        );
    }

    function checkPacketbl()
    {
        $state = $this->_serviceState('packetbl.service');
        if ($state === 'not-found') {
            return array('severity' => self::SEV_OK, 'value' => 'not installed', 'detail' => 'packetbl no instalado (opcional)');
        }
        $sev = ($state === 'active') ? self::SEV_OK : self::SEV_WARNING;
        return array('severity' => $sev, 'value' => $state, 'detail' => "packetbl: $state", 'source' => 'systemctl');
    }

    function checkCertificate($cfg)
    {
        $file = $this->_webCertFile();
        if (is_null($file)) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'no se ubicó el certificado web');
        }
        $r = $this->_run("openssl x509 -enddate -noout -in ".escapeshellarg($file), 10);
        $out = implode("\n", $r['out']);
        if (!preg_match('/notAfter=(.+)/', $out, $m)) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'no se pudo leer el certificado', 'error' => 'openssl');
        }
        $exp = strtotime(trim($m[1]));
        if ($exp === FALSE) return array('severity' => self::SEV_UNKNOWN, 'detail' => 'fecha de certificado inválida');
        $days = (int)floor(($exp - time()) / 86400);
        $warn = (int)$cfg['thresholds']['cert_warn_days'];
        $crit = (int)$cfg['thresholds']['cert_crit_days'];
        if ($days <= $crit)      $sev = self::SEV_CRITICAL;
        elseif ($days <= $warn)  $sev = self::SEV_WARNING;
        else                     $sev = self::SEV_OK;
        return array(
            'severity'  => $sev,
            'value'     => max($days, 0).'d',
            'threshold' => "<{$warn}d warn, <{$crit}d crit",
            'detail'    => basename($file).' expira en '.$days.' días',
            'source'    => 'openssl', 'action_id' => 'cert_renew',
            'link'      => 'menu=sec_letsencrypt',
        );
    }

    private function _webCertFile()
    {
        $ssl = '/etc/httpd/conf.d/ssl.conf';
        if (file_exists($ssl)) {
            foreach (file($ssl) as $line) {
                if (preg_match('/^\s*SSLCertificateFile\s+(\S+)/', $line, $m)) return $m[1];
            }
        }
        foreach (array('/etc/pki/tls/certs/localhost.crt') as $f) if (file_exists($f)) return $f;
        return NULL;
    }

    // =====================================================================
    //  Chequeos: actualizaciones
    // =====================================================================

    function checkUpdatesIssabel()
    {
        return $this->_dnfCount("dnf -q check-update", '/^issabel/i', 'paquetes issabel*');
    }

    function checkUpdatesSecurity()
    {
        return $this->_dnfCount("dnf -q --security check-update", NULL, 'parches de seguridad');
    }

    private function _dnfCount($cmd, $filterRe, $label)
    {
        if (trim((string)shell_exec('command -v dnf 2>/dev/null')) === '') {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'dnf no disponible');
        }
        $r = $this->_run($cmd, 120);
        if ($r['rc'] === 124) return array('severity' => self::SEV_UNKNOWN, 'detail' => "$label: dnf agotó el tiempo");
        // rc 100 = hay actualizaciones; 0 = no hay.
        if ($r['rc'] !== 0 && $r['rc'] !== 100) {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => "$label: dnf falló (rc={$r['rc']})", 'error' => 'dnf');
        }
        $n = 0;
        foreach ($r['out'] as $line) {
            if (trim($line) === '' || strpos($line, 'Obsoleting') === 0) continue;
            if ($filterRe === NULL || preg_match($filterRe, $line)) $n++;
        }
        return array(
            'severity' => $n > 0 ? self::SEV_WARNING : self::SEV_OK,
            'value'    => $n,
            'detail'   => "$label: $n pendientes",
            'source'   => 'dnf', 'link' => 'menu=betachannel',
        );
    }

    function checkRepos()
    {
        $r = $this->_run("dnf -q repolist", 60);
        if ($r['rc'] !== 0) {
            return array('severity' => self::SEV_CRITICAL, 'value' => 'error', 'detail' => 'no se pudo listar repositorios', 'error' => 'dnf');
        }
        $n = 0;
        foreach ($r['out'] as $line) if (preg_match('/^\S+\s+\S+/', $line) && strpos($line, 'repo id') === FALSE) $n++;
        return array(
            'severity' => $n > 0 ? self::SEV_OK : self::SEV_WARNING,
            'value'    => $n,
            'detail'   => $n.' repositorios',
            'source'   => 'dnf',
        );
    }

    function checkReboot()
    {
        if (trim((string)shell_exec('command -v dnf 2>/dev/null')) === '') {
            return array('severity' => self::SEV_UNKNOWN, 'detail' => 'dnf no disponible');
        }
        $r = $this->_run("dnf -q needs-restarting -r", 60);
        if ($r['rc'] === 0) return array('severity' => self::SEV_OK, 'value' => 'no', 'detail' => 'sin reinicio requerido');
        if ($r['rc'] === 1) {
            return array('severity' => self::SEV_WARNING, 'value' => 'yes', 'detail' => 'se recomienda reiniciar el sistema');
        }
        return array('severity' => self::SEV_UNKNOWN, 'detail' => 'needs-restarting no concluyente');
    }

    // =====================================================================
    //  Chequeos: telefonía
    // =====================================================================

    private function _asterisk($cmd, $timeout = 10)
    {
        if (trim((string)shell_exec('command -v asterisk 2>/dev/null')) === '') return NULL;
        return $this->_run("asterisk -r -x ".escapeshellarg($cmd), $timeout);
    }

    function checkAsterisk()
    {
        $r = $this->_asterisk('core show uptime');
        if (is_null($r)) return array('severity' => self::SEV_UNKNOWN, 'detail' => 'asterisk no instalado');
        if ($r['rc'] !== 0 || empty($r['out'])) {
            return array('severity' => self::SEV_CRITICAL, 'value' => 'down', 'detail' => 'sin respuesta de Asterisk (CLI)', 'error' => 'asterisk');
        }
        return array('severity' => self::SEV_OK, 'value' => 'up', 'detail' => 'Asterisk responde',
                     'source' => 'asterisk-cli', 'action_id' => 'service_start:asterisk.service');
    }

    private function _peerRows()
    {
        if (is_array($this->_peerCache)) return $this->_peerCache;
        $rows = array();
        $r = $this->_asterisk('sip show peers');
        if (is_array($r)) {
            foreach ($r['out'] as $line) {
                if (preg_match('/^\s*(\S+)\/\S+\s+(\S+)\s+\S*\s*\S*\s*\S*\s+\d+\s+(\D+)/', $line, $m)) {
                    $rows[] = array('name' => $m[1], 'state' => $m[3]);
                }
            }
        }
        $r = $this->_asterisk('pjsip show endpoints');
        if (is_array($r)) {
            $start = FALSE;
            foreach ($r['out'] as $line) {
                if (strpos($line, '====') === 0) { $start = TRUE; continue; }
                if (!$start || strpos($line, 'Endpoint:') === FALSE) continue;
                if (strpos($line, 'dummy_endpoint') !== FALSE) continue;
                $p = preg_split('/\s+/', trim($line));
                if (count($p) >= 3) $rows[] = array('name' => $p[1], 'state' => $p[2]);
            }
        }
        $this->_peerCache = $rows;
        return $rows;
    }

    private function _trunkNames()
    {
        if ($this->_trunkCacheLoaded) return $this->_trunkCache;
        $this->_trunkCacheLoaded = TRUE;
        $this->_trunkCache = $this->_trunkNamesCompute();
        return $this->_trunkCache;
    }

    private function _trunkNamesCompute()
    {
        if (!class_exists('paloSantoTrunk')) {
            $f = '/var/www/html/libs/paloSantoTrunk.class.php';
            if (file_exists($f)) require_once $f;
        }
        if (!class_exists('paloSantoTrunk') || !function_exists('generarDSNSistema')) return NULL;
        try {
            $pDB = new paloDB(generarDSNSistema('asteriskuser', 'asterisk'));
            if (!empty($pDB->errMsg)) return NULL;
            $arr = getTrunks($pDB);
            if (!is_array($arr)) return NULL;
            $names = array();
            foreach ($arr as $t) {
                if (!empty($t[1]) && strpos($t[1], '/') !== FALSE) {
                    $tmp = explode('/', $t[1]);
                    $names[] = $tmp[1];
                }
            }
            return $names;
        } catch (Throwable $e) {
            return NULL;
        }
    }

    private function _isCritical($endpoint, $policy)
    {
        if (!is_array($policy)) return FALSE;
        if ($policy['mode'] === 'all') return TRUE;
        return in_array($endpoint, isset($policy['ids']) ? $policy['ids'] : array());
    }

    /**
     * Foto de endpoints (troncales + extensiones) con su estado actual.
     * @return array  [{name, kind: trunk|extension, up: bool}]
     */
    private function _observedEndpoints()
    {
        if (is_array($this->_observed)) return $this->_observed;
        $trunks = $this->_trunkNames();
        $trunks = is_array($trunks) ? $trunks : array();
        $out = array();
        foreach ($this->_peerRows() as $p) {
            $out[] = array(
                'name' => $p['name'],
                'kind' => in_array($p['name'], $trunks) ? 'trunk' : 'extension',
                'up'   => (stripos($p['state'], 'OK') === 0),
            );
        }
        $this->_observed = $out;
        return $out;
    }

    /**
     * Actualiza `endpoint_state` y devuelve las filas resultantes. Guarda
     * first_seen / last_up / down_since para poder aplicar el debounce y el
     * filtro de "registrada recientemente" (§6.5); poda lo no visto en 30 días.
     */
    private function _endpointState($now)
    {
        if (is_array($this->_stateRows)) return $this->_stateRows;
        if (!$this->_openDb(TRUE)) { $this->_stateRows = array(); return $this->_stateRows; }

        $existing = array();
        $t = $this->_db->fetchTable('SELECT * FROM endpoint_state', TRUE, array());
        if (is_array($t)) foreach ($t as $row) $existing[$row['name']] = $row;

        $rows = array();
        foreach ($this->_observedEndpoints() as $o) {
            $name = $o['name']; $up = $o['up'];
            $cur = isset($existing[$name]) ? $existing[$name] : NULL;
            if (is_null($cur)) {
                $r = array('name' => $name, 'kind' => $o['kind'],
                    'first_seen' => $now, 'last_seen' => $now,
                    'last_up' => $up ? $now : 0, 'last_state' => $up ? 'up' : 'down',
                    'down_since' => $up ? 0 : $now, 'last_change' => $now, 'flap_count' => 0);
                $this->_db->genQuery(
                    'INSERT INTO endpoint_state (name,kind,first_seen,last_seen,last_up,last_state,down_since,last_change,flap_count)
                     VALUES (?,?,?,?,?,?,?,?,?)',
                    array($r['name'], $r['kind'], $r['first_seen'], $r['last_seen'],
                          $r['last_up'], $r['last_state'], $r['down_since'],
                          $r['last_change'], $r['flap_count']));
            } else {
                $r = $cur;
                $r['kind'] = $o['kind'];
                $r['last_seen'] = $now;
                $st = $up ? 'up' : 'down';
                if ($cur['last_state'] !== $st) {
                    $r['last_change'] = $now;
                    $r['flap_count']  = ((int)$cur['flap_count']) + 1;
                    $r['last_state']  = $st;
                    if ($up) { $r['last_up'] = $now; $r['down_since'] = 0; }
                    else     { $r['down_since'] = $now; }
                } elseif ($up) {
                    $r['last_up'] = $now; $r['down_since'] = 0;
                }
                $this->_db->genQuery(
                    'UPDATE endpoint_state SET kind=?,last_seen=?,last_up=?,last_state=?,down_since=?,last_change=?,flap_count=? WHERE name=?',
                    array($r['kind'], $r['last_seen'], $r['last_up'], $r['last_state'],
                          $r['down_since'], $r['last_change'], $r['flap_count'], $name));
            }
            $rows[$name] = $r;
        }
        $this->_db->genQuery('DELETE FROM endpoint_state WHERE last_seen < ?', array($now - 30 * 86400));
        $this->_stateRows = $rows;
        return $rows;
    }

    /**
     * Clasifica los endpoints observados aplicando debounce y (solo para
     * extensiones) el filtro de "registrada recientemente". Es una función
     * pura, para poder probarla sin Asterisk.
     *
     * @return array  ['trunk'=>['up'=>N,'down'=>[nombres]], 'extension'=>...]
     */
    function classifyEndpoints($observed, $stateRows, $cfg, $now)
    {
        $debounce = ((int)$cfg['endpoint_debounce_minutes']) * 60;
        $recent   = ((int)$cfg['endpoint_recent_days']) * 86400;

        $out = array('trunk' => array('up' => 0, 'down' => array()),
                     'extension' => array('up' => 0, 'down' => array()));
        foreach ($observed as $o) {
            $k = $o['kind'];
            if ($o['up']) { $out[$k]['up']++; continue; }
            $r  = isset($stateRows[$o['name']]) ? $stateRows[$o['name']] : NULL;
            $ds = $r ? (int)$r['down_since'] : 0;
            $lu = $r ? (int)$r['last_up'] : 0;
            $armed = ($ds > 0) && ($now - $ds >= $debounce);
            if ($armed && $k === 'extension') {
                // Una extensión configurada y nunca usada no alerta.
                $armed = ($lu > 0) && ($now - $lu <= $recent);
            }
            if ($armed) $out[$k]['down'][] = $o['name'];
        }
        return $out;
    }

    function checkTrunks($cfg)
    {
        if (is_null($this->_trunkNames())) {
            return array('severity' => self::SEV_UNKNOWN, 'value' => '?',
                         'detail' => 'no se pudo determinar la lista de troncales');
        }
        $observed = $this->_observedEndpoints();
        if (empty($observed)) return array('severity' => self::SEV_UNKNOWN, 'detail' => 'sin datos de troncales');

        $now   = time();
        $state = $this->_endpointState($now);
        $c     = $this->classifyEndpoints($observed, $state, $cfg, $now);
        $up    = $c['trunk']['up'];
        $down  = $c['trunk']['down'];

        $critDown = array();
        foreach ($down as $t) if ($this->_isCritical($t, $cfg['critical_trunks'])) $critDown[] = $t;
        $sev = $critDown ? self::SEV_CRITICAL : ($down ? self::SEV_WARNING : self::SEV_OK);
        $total = $up + count($down);
        return array(
            'severity' => $sev, 'value' => $up.'/'.$total,
            'threshold'=> $cfg['critical_trunks']['mode'],
            'detail'   => $down ? ('down: '.implode(', ', $down))
                                : ($total ? 'trunks registered' : 'no trunks'),
            'source'   => 'asterisk-cli', 'link' => 'menu=pbxconfig',
        );
    }

    function checkExtensions($cfg)
    {
        $observed = $this->_observedEndpoints();
        if (empty($observed)) return array('severity' => self::SEV_UNKNOWN, 'detail' => 'sin datos de extensiones');

        $now   = time();
        $state = $this->_endpointState($now);
        $c     = $this->classifyEndpoints($observed, $state, $cfg, $now);
        $reg   = $c['extension']['up'];
        $down  = $c['extension']['down'];

        // Default: ninguna extensión es crítica (§6.5), así que las caídas se
        // agrupan y no fuerzan el rojo.
        $critDown = array();
        foreach ($down as $e) if ($this->_isCritical($e, $cfg['critical_extensions'])) $critDown[] = $e;
        $total = $reg + count($down);
        $sev = $critDown ? self::SEV_CRITICAL : (count($down) ? self::SEV_WARNING : self::SEV_OK);
        return array(
            'severity' => $sev, 'value' => $reg.'/'.$total,
            'threshold'=> $cfg['critical_extensions']['mode'],
            'detail'   => count($down).' unregistered'.($critDown ? (' (critical: '.implode(', ', $critDown).')') : ''),
            'source'   => 'asterisk-cli', 'link' => 'menu=pbxconfig',
        );
    }

    // =====================================================================
    //  Métricas vivas (baratas, se calculan en la petición; no se cachean)
    // =====================================================================

    /**
     * CPU, memoria, swap, load y uptime, leídos de /proc en vivo.
     * No toca health.db: es lo que el applet/página muestran "ahora".
     */
    function fastMetrics()
    {
        $a = $this->_cpuSample();
        usleep(200000);
        $b = $this->_cpuSample();
        $cpu = $this->_cpuUsed($a, $b);

        $mem = $this->_memInfo();
        $memUsed = $mem['MemTotal'] - $mem['MemAvailable'];
        $swapUsed = $mem['SwapTotal'] - $mem['SwapFree'];

        $load = array(0, 0, 0);
        $la = @file_get_contents('/proc/loadavg');
        if ($la !== FALSE) {
            $p = preg_split('/\s+/', trim($la));
            $load = array((float)$p[0], isset($p[1]) ? (float)$p[1] : 0.0, isset($p[2]) ? (float)$p[2] : 0.0);
        }

        return array(
            'cpu_percent'  => round($cpu * 100.0, 1),
            'cores'        => $this->_cpuCores(),
            'load'         => $load,
            'mem_total'    => $mem['MemTotal'],
            'mem_used'     => $memUsed,
            'mem_percent'  => $mem['MemTotal'] > 0 ? round(100.0 * $memUsed / $mem['MemTotal'], 1) : 0,
            'swap_total'   => $mem['SwapTotal'],
            'swap_used'    => $swapUsed,
            'swap_percent' => $mem['SwapTotal'] > 0 ? round(100.0 * $swapUsed / $mem['SwapTotal'], 1) : 0,
            'uptime'       => $this->_uptime(),
            'hostname'     => gethostname(),
        );
    }

    private function _cpuSample()
    {
        $h = @fopen('/proc/stat', 'r');
        if (!$h) return array();
        $line = fgets($h);
        fclose($h);
        $p = preg_split('/\s+/', trim($line));
        array_shift($p); // quita 'cpu'
        return array_map('intval', $p);
    }

    private function _cpuUsed($a, $b)
    {
        if (empty($a) || empty($b) || count($a) < 4) return 0.0;
        $idleA = $a[3] + (isset($a[4]) ? $a[4] : 0);
        $idleB = $b[3] + (isset($b[4]) ? $b[4] : 0);
        $idle = $idleB - $idleA;
        $total = 0;
        foreach ($b as $i => $v) $total += $v - (isset($a[$i]) ? $a[$i] : $v);
        return ($total > 0) ? (1.0 - $idle / $total) : 0.0;
    }

    private function _memInfo()
    {
        $info = array('MemTotal' => 0, 'MemAvailable' => 0, 'MemFree' => 0, 'SwapTotal' => 0, 'SwapFree' => 0);
        foreach ((array)@file('/proc/meminfo') as $line) {
            if (preg_match('/^(\w+):\s+(\d+) kB/', $line, $m)) {
                if (array_key_exists($m[1], $info)) $info[$m[1]] = (int)$m[2];
            }
        }
        if ($info['MemAvailable'] === 0) {
            $info['MemAvailable'] = $info['MemFree'];
        }
        return $info;
    }

    private function _cpuCores()
    {
        return (int)trim((string)shell_exec('nproc 2>/dev/null')) ?: 1;
    }

    private function _uptime()
    {
        foreach ((array)@file('/proc/stat') as $line) {
            if (strpos($line, 'btime ') === 0) {
                $t = explode(' ', $line);
                return max(0, time() - (int)$t[1]);
            }
        }
        return 0;
    }

    // =====================================================================
    //  Lectura, score y estado
    // =====================================================================

    /** Devuelve las filas de health.db indexadas por check_id. */
    function readChecks()
    {
        return $this->_readRows(NULL);
    }

    private function _readRows($group)
    {
        if (!$this->_openDb(FALSE)) return array();
        $sql = 'SELECT * FROM health_check';
        $params = array();
        if (!is_null($group)) { $sql .= ' WHERE group_id = ?'; $params[] = $group; }
        $t = $this->_db->fetchTable($sql, TRUE, $params);
        $out = array();
        if (is_array($t)) {
            foreach ($t as $row) {
                if (is_null($group)) $out[$row['check_id']] = $row;
                else $out[] = $row;
            }
        }
        return $out;
    }

    /** Score y estado global a partir de las filas (modelo §6.4). */
    function computeScore($rows = NULL)
    {
        if (is_null($rows)) $rows = $this->readChecks();
        $ok = 0; $warn = 0; $crit = 0; $unknown = 0;
        foreach ($rows as $r) {
            switch ($r['severity']) {
            case self::SEV_OK:      $ok++; break;
            case self::SEV_WARNING: $warn++; break;
            case self::SEV_CRITICAL:$crit++; break;
            default:                $unknown++; break;
            }
        }
        $total = $ok + $warn + $crit;
        $score = ($total > 0) ? (int)round(100 * ($ok + 0.5 * $warn) / $total) : NULL;

        // El estado respeta la severidad, no el promedio.
        if ($crit > 0)      $state = 'critical';
        elseif ($warn > 0)  $state = 'warning';
        elseif ($total > 0) $state = 'ok';
        else                $state = 'unknown';

        // Cobertura: si hay demasiados unknown, el score no es fiable.
        $all = $total + $unknown;
        if ($all > 0 && $unknown / $all > 0.5) $score = NULL;

        return array(
            'score'   => $score,
            'state'   => $state,
            'ok'      => $ok,
            'warning' => $warn,
            'critical'=> $crit,
            'unknown' => $unknown,
            'total'   => $all,
        );
    }

    /** Última corrida del recolector, o NULL. */
    function lastRun()
    {
        if (!$this->_openDb(FALSE)) return NULL;
        $t = $this->_db->getFirstRowQuery('SELECT * FROM health_run ORDER BY id DESC LIMIT 1', TRUE, array());
        return is_array($t) ? $t : NULL;
    }

    // =====================================================================
    //  Acciones (web, solo admin) — allowlist cerrada
    // =====================================================================

    /**
     * Servicios que se permite iniciar/reiniciar desde la UI. Lista cerrada:
     * el navegador nunca elige el comando, solo una clave de este mapa.
     */
    function allowedServices()
    {
        return array(
            'asterisk' => array('label' => 'Asterisk PBX',  'impact' => 'Restarting Asterisk can drop active calls.'),
            'httpd'    => array('label' => 'Web server',    'impact' => 'The web panel will be briefly unavailable.'),
            'mariadb'  => array('label' => 'Database',      'impact' => 'The panel and reports may be briefly unavailable.'),
            'crond'    => array('label' => 'Cron',          'impact' => 'Scheduled tasks pause briefly.'),
            'chronyd'  => array('label' => 'NTP (chrony)',  'impact' => 'Clock synchronization pauses briefly.'),
            'fail2ban' => array('label' => 'Fail2ban',      'impact' => 'IP blocking pauses briefly.'),
            'postfix'  => array('label' => 'SMTP relay',    'impact' => 'Outgoing mail pauses briefly.'),
        );
    }

    /** ¿El usuario pertenece al grupo administrador? */
    function isAdminUser($user)
    {
        if (!class_exists('paloACL')) {
            foreach (array('libs/paloSantoACL.class.php', '/var/www/html/libs/paloSantoACL.class.php') as $f)
                if (file_exists($f)) { require_once $f; break; }
        }
        if (empty($user) || !class_exists('paloACL')) return FALSE;
        global $arrConf;
        if (empty($arrConf['issabel_dsn']['acl'])) return FALSE;
        $pDB = new paloDB($arrConf['issabel_dsn']['acl']);
        if (!empty($pDB->errMsg)) return FALSE;
        $pACL = new paloACL($pDB);
        return (bool)$pACL->isUserAdministratorGroup($user);
    }

    /**
     * Traduce el `action_id` de un chequeo en la acción disponible, o NULL.
     * @return array|null  type, service/action, label, impact
     */
    function actionInfo($actionId)
    {
        if (!is_string($actionId) || $actionId === '') return NULL;
        if (strpos($actionId, 'service_start:') === 0) {
            $svc = preg_replace('/\.service$/', '', substr($actionId, strlen('service_start:')));
            $svcs = $this->allowedServices();
            if (!isset($svcs[$svc])) return NULL;
            return array('type' => 'service', 'service' => $svc, 'action' => 'start',
                         'label' => $svcs[$svc]['label'], 'impact' => $svcs[$svc]['impact']);
        }
        if ($actionId === 'cert_renew') {
            return array('type' => 'cert', 'action' => 'renew',
                         'label' => 'TLS certificate',
                         'impact' => 'The web server is reloaded after the certificate is renewed.');
        }
        return NULL;
    }

    function runServiceAction($service, $action, $user)
    {
        $svcs = $this->allowedServices();
        $service = strtolower((string)$service);
        if (!isset($svcs[$service]))   return $this->_actionResult(FALSE, $this->_t('Invalid service'));
        if (!in_array($action, array('start', 'restart'), TRUE))
                                       return $this->_actionResult(FALSE, $this->_t('Invalid action'));
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));

        $out = array(); $rc = 0;
        exec('sudo -u root service generic-cloexec '.escapeshellarg($service).' '.escapeshellarg($action).
             ' 1>/dev/null 2>/dev/null', $out, $rc);
        $ok = ($rc === 0);
        $this->_audit($user, 'health.'.$action, 'service:'.$service, $ok ? 'ok' : 'error');
        $msg = $ok ? sprintf($this->_t('%s: action sent'), $svcs[$service]['label'])
                   : $this->_t('The action could not be completed');
        return $this->_actionResult($ok, $msg);
    }

    function runCertRenew($user)
    {
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));
        $out = array(); $rc = 0;
        exec('/usr/bin/issabel-helper ssl_certbot renewcertificate 2>&1', $out, $rc);
        $ok = ($rc === 0);
        $this->_audit($user, 'health.renew_cert', 'tls', $ok ? 'ok' : 'error');
        $msg = $ok ? $this->_t('Certificate renewal requested')
                   : $this->_t('The action could not be completed');
        return $this->_actionResult($ok, $msg, implode("\n", array_slice($out, -3)));
    }

    private function _actionResult($ok, $msg, $detail = '')
    {
        return array('ok' => $ok, 'message' => $msg, 'detail' => $detail);
    }

    private function _t($s)
    {
        return function_exists('_tr') ? _tr($s) : $s;
    }

    /** Registra la acción en /var/log/issabel/audit.log (ver §8). */
    private function _audit($user, $action, $target, $result)
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
        $line = sprintf('HEALTH %s: User %s performed "%s" on %s from %s.',
            strtoupper($result), $user, $action, $target, $remote);
        if (function_exists('writeLOG')) writeLOG('audit.log', $line);
        else error_log($line);
    }

    // =====================================================================
    //  Configuración editable desde la página Salud (§6.5 y §6.7)
    // =====================================================================

    /** Nombres de troncales conocidas, para el selector. */
    function availableTrunks()
    {
        $t = $this->_trunkNames();
        return is_array($t) ? array_values(array_unique($t)) : array();
    }

    /** Nombres de extensiones conocidas (todo lo que no es troncal). */
    function availableExtensions()
    {
        $peers = $this->_peerRows();
        $trunks = $this->_trunkNames();
        $trunks = is_array($trunks) ? $trunks : array();
        $out = array();
        foreach ($peers as $p) {
            if (in_array($p['name'], $trunks)) continue;
            $out[] = $p['name'];
        }
        return array_values(array_unique($out));
    }

    /**
     * Guarda los umbrales (§6.7) con validación (aviso < crítico).
     * @return array  {ok, message}
     */
    function saveThresholds(array $in, $user)
    {
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));

        $out = $this->getConfig(TRUE);
        $t = $out['thresholds'];
        foreach ($t as $k => $def) {
            if (!array_key_exists($k, $in)) continue;
            if (!is_numeric($in[$k])) return $this->_actionResult(FALSE, $this->_t('Invalid value'));
            $t[$k] = $in[$k] + 0;
        }
        if ($t['disk_warn_percent'] >= $t['disk_crit_percent'])
            return $this->_actionResult(FALSE, $this->_t('Warning must be lower than critical'));
        if ($t['cert_warn_days'] <= $t['cert_crit_days'])
            return $this->_actionResult(FALSE, $this->_t('Warning days must be greater than critical days'));
        if ($t['disk_warn_percent'] < 0 || $t['disk_crit_percent'] > 100)
            return $this->_actionResult(FALSE, $this->_t('Invalid value'));

        if ($this->_setKey('health_thresholds', json_encode($t)) === FALSE)
            return $this->_actionResult(FALSE, $this->_t('Could not save the configuration'));
        $this->cfgInvalidate();
        $this->_audit($user, 'health.save_thresholds', 'thresholds', 'ok');
        return $this->_actionResult(TRUE, $this->_t('Configuration saved'));
    }

    /**
     * Guarda la política de críticos de un tipo (§6.5).
     * @param string $type  'trunks' | 'extensions'
     * @param string $mode  'all' | 'list'
     */
    function saveCriticalEndpoints($type, $mode, array $ids, $user)
    {
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));
        if (!in_array($type, array('trunks', 'extensions'), TRUE)) return $this->_actionResult(FALSE, $this->_t('Invalid action'));
        if (!in_array($mode, array('all', 'list'), TRUE))            return $this->_actionResult(FALSE, $this->_t('Invalid action'));

        $clean = array();
        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id !== '' && preg_match('/^[\w.@\-]{1,64}$/', $id)) $clean[] = $id;
        }
        $clean = array_values(array_unique($clean));
        $key = ($type === 'trunks') ? 'health_critical_trunks' : 'health_critical_extensions';
        $val = array('mode' => $mode, 'ids' => ($mode === 'all' ? array() : $clean));

        if ($this->_setKey($key, json_encode($val)) === FALSE)
            return $this->_actionResult(FALSE, $this->_t('Could not save the configuration'));
        $this->cfgInvalidate();
        $this->_audit($user, 'health.save_critical', $type, 'ok');
        return $this->_actionResult(TRUE, $this->_t('Configuration saved'));
    }

    /** Invalida la caché de configuración en memoria. */
    function cfgInvalidate()
    {
        $this->_cfg = NULL;
    }

    /** Guarda la política de endpoints: debounce y "registrada recientemente". */
    function saveEndpointPolicy($debounceMinutes, $recentDays, $user)
    {
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));
        $dm = (int)$debounceMinutes;
        $rd = (int)$recentDays;
        if ($dm < 0 || $rd < 0) return $this->_actionResult(FALSE, $this->_t('Invalid value'));
        if ($this->_setKey('health_endpoint_debounce_minutes', (string)$dm) === FALSE ||
            $this->_setKey('health_endpoint_recent_days', (string)$rd) === FALSE)
            return $this->_actionResult(FALSE, $this->_t('Could not save the configuration'));
        $this->cfgInvalidate();
        $this->_audit($user, 'health.save_endpoint_policy', 'endpoints', 'ok');
        return $this->_actionResult(TRUE, $this->_t('Configuration saved'));
    }

    // =====================================================================
    //  API de salud (token) — DASHBOARDtodo.md §12 (endpoint /health)
    // =====================================================================

    /** Estado de la API: enabled, token y URL de consumo. */
    function getApiConfig()
    {
        $v = $this->_getKey('health_api_enabled');
        return array(
            'enabled' => ($v === 'yes' || $v === '1'),
            'token'   => $this->_getKey('health_api_token'),
            'url'     => $this->apiUrl(),
        );
    }

    /** URL absoluta del endpoint (mejor esfuerzo, a partir del host actual). */
    function apiUrl()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : gethostname();
        return 'https://'.$host.'/modules/health/health.php';
    }

    function apiEnabled()
    {
        $v = $this->_getKey('health_api_enabled');
        return ($v === 'yes' || $v === '1');
    }

    /** Compara el token recibido con el guardado (tiempo constante). */
    function apiTokenValid($token)
    {
        $cur = $this->_getKey('health_api_token');
        if (!is_string($token) || $token === '' || !is_string($cur) || $cur === '') return FALSE;
        return hash_equals($cur, $token);
    }

    function setApiEnabled($enabled, $user)
    {
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));
        if ($this->_setKey('health_api_enabled', $enabled ? 'yes' : 'no') === FALSE)
            return $this->_actionResult(FALSE, $this->_t('Could not save the configuration'));
        $this->_audit($user, 'health.api_'.($enabled ? 'enable' : 'disable'), 'api', 'ok');
        return $this->_actionResult(TRUE, $this->_t('Configuration saved'));
    }

    function regenerateApiToken($user)
    {
        if (!$this->isAdminUser($user)) return $this->_actionResult(FALSE, $this->_t('Not authorized'));
        $token = $this->_generateToken();
        if ($this->_setKey('health_api_token', $token) === FALSE)
            return $this->_actionResult(FALSE, $this->_t('Could not save the configuration'));
        $this->_audit($user, 'health.api_retoken', 'api', 'ok');
        $r = $this->_actionResult(TRUE, $this->_t('Token regenerated'));
        $r['token'] = $token;
        return $r;
    }

    /** Cuerpo JSON del endpoint (mismo modelo que el applet y la página). */
    function apiPayload()
    {
        $rows  = $this->readChecks();
        $score = $this->computeScore($rows);
        $run   = $this->lastRun();

        $checks = array();
        foreach ($rows as $id => $r) {
            $checks[] = array(
                'id'         => $id,
                'category'   => $r['category'],
                'severity'   => $r['severity'],
                'value'      => $r['value'],
                'threshold'  => $r['threshold'],
                'detail'     => $r['detail'],
                'checked_at' => (int)$r['checked_at'],
            );
        }
        return array(
            'status'     => 'success',
            'score'      => $score['score'],
            'state'      => $score['state'],
            'counts'     => array(
                'critical' => $score['critical'],
                'warning'  => $score['warning'],
                'ok'       => $score['ok'],
                'unknown'  => $score['unknown'],
            ),
            'checked_at' => $run ? (int)$run['started_at'] : 0,
            'checks'     => $checks,
        );
    }

    // =====================================================================
    //  Utilidades internas
    // =====================================================================

    private function _openDb($writable = FALSE)
    {
        if (!is_null($this->_db)) return TRUE;
        if (!file_exists($this->_dbFile)) { $this->errMsg = 'health.db no existe'; return FALSE; }
        $this->_db = new paloDB("sqlite3:///$this->_dbFile");
        if (!empty($this->_db->errMsg)) {
            $this->errMsg = $this->_db->errMsg;
            $this->_db = NULL;
            return FALSE;
        }
        return TRUE;
    }

    private function _serviceState($unit)
    {
        $r = $this->_run("systemctl show -p ActiveState -p LoadState -- $unit", 10);
        $out = implode("\n", $r['out']);
        if (strpos($out, 'LoadState=not-found') !== FALSE) return 'not-found';
        if (preg_match('/ActiveState=(\S+)/', $out, $m)) return $m[1];
        return 'unknown';
    }

    private function _run($cmd, $timeout = 15)
    {
        $full = 'timeout -k 2 '.escapeshellarg((string)(int)$timeout).' sh -c '.escapeshellarg($cmd).' 2>/dev/null';
        $out = array(); $rc = 0;
        exec($full, $out, $rc);
        return array('rc' => $rc, 'out' => $out);
    }

    private function _writeGroups($fresh)
    {
        if (empty($fresh)) return;
        if (!$this->_openDb(TRUE)) return;
        $this->_db->beginTransaction();
        foreach ($fresh as $group => $rows) {
            $this->_db->genQuery('DELETE FROM health_check WHERE group_id = ?', array($group));
            foreach ($rows as $r) {
                $this->_db->genQuery(
                    'INSERT OR REPLACE INTO health_check
                     (check_id, group_id, category, severity, value, threshold, detail, link, action_id, source, error, checked_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(
                        $r['check_id'], $r['group_id'], $r['category'],
                        $r['severity'],
                        isset($r['value']) ? $r['value'] : '',
                        isset($r['threshold']) ? $r['threshold'] : '',
                        isset($r['detail']) ? $r['detail'] : '',
                        isset($r['link']) ? $r['link'] : '',
                        isset($r['action_id']) ? $r['action_id'] : '',
                        $r['source'],
                        isset($r['error']) ? $r['error'] : '',
                        $r['checked_at'],
                    ));
            }
        }
        $this->_db->commit();
    }

    private function _writeRun($summary)
    {
        if (!$this->_openDb(TRUE)) return;
        $this->_db->genQuery(
            'INSERT INTO health_run (started_at, duration_ms, ran, skipped, errors, message) VALUES (?,?,?,?,?,?)',
            array($summary['started_at'], $summary['duration_ms'], $summary['ran'],
                  $summary['skipped'], $summary['errors'], $summary['message']));
    }

    /**
     * Crea el esquema si falta. El instalador ya lo aplica con issabel-dbprocess,
     * pero el recolector se auto-repara si el archivo no existe (p.ej. tras un
     * borrado manual).
     */
    private function _ensureSchema()
    {
        if (is_null($this->_db)) {
            // Fuerza la apertura aunque el archivo no exista.
            $this->_db = new paloDB("sqlite3:///$this->_dbFile");
            if (!empty($this->_db->errMsg)) { $this->errMsg = $this->_db->errMsg; $this->_db = NULL; return FALSE; }
        }
        $this->_db->genQuery(
            'CREATE TABLE IF NOT EXISTS health_check (
                check_id varchar(96) primary key, group_id varchar(48) not null,
                category varchar(32) not null, severity varchar(16) not null,
                value varchar(128), threshold varchar(64), detail varchar(255),
                link varchar(255), action_id varchar(64), source varchar(64),
                error varchar(255), checked_at integer not null)', array());
        $this->_db->genQuery(
            'CREATE TABLE IF NOT EXISTS health_run (
                id integer primary key, started_at integer not null,
                duration_ms integer not null default 0, ran integer not null default 0,
                skipped integer not null default 0, errors integer not null default 0,
                message varchar(255))', array());
        $this->_db->genQuery(
            'CREATE TABLE IF NOT EXISTS endpoint_state (
                name varchar(64) primary key, kind varchar(16),
                first_seen integer, last_seen integer, last_up integer,
                last_state varchar(8), down_since integer, last_change integer,
                flap_count integer)', array());
        return TRUE;
    }

    /**
     * health.db lo escribe el recolector (root) y lo lee la web (asterisk).
     * Propietario root, grupo asterisk, modo 0640 (DASHBOARDtodo.md §6.6).
     */
    private function _enforceOwnership()
    {
        if (!file_exists($this->_dbFile)) return;
        @chown($this->_dbFile, 'root');
        if (@chgrp($this->_dbFile, 'asterisk')) {
            @chmod($this->_dbFile, 0640);
        }
    }
}
