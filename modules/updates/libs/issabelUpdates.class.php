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
  | Modelo del módulo Updates (UPDATESmodule.md §6).                     |
  |                                                                      |
  | La lista de paquetes NO se consulta aquí: la produce el recolector   |
  | de health (una sola ejecución de `dnf check-update` por corrida) y   |
  | se lee de health.db con issabelHealth::readUpdatePackages().         |
  | Este modelo solo lee el estado del job y dispara las acciones        |
  | privilegiadas por el helper `pkgupdate`.                             |
  +----------------------------------------------------------------------+
*/

if (!class_exists('issabelHealth')) {
    foreach (array('modules/health/libs/issabelHealth.class.php',
                   '/var/www/html/modules/health/libs/issabelHealth.class.php') as $f) {
        if (file_exists($f)) { require_once $f; break; }
    }
}

class issabelUpdates
{
    const STATE_DIR  = '/var/lib/issabel/updates';
    const STATE_FILE = '/var/lib/issabel/updates/state.json';
    const LOG_FILE   = '/var/lib/issabel/updates/job.log';

    var $errMsg = NULL;

    private $_health = NULL;

    /** Instancia de issabelHealth (de donde sale el snapshot de paquetes). */
    private function _health()
    {
        if (is_null($this->_health) && class_exists('issabelHealth')) {
            $this->_health = new issabelHealth();
        }
        return $this->_health;
    }

    /**
     * Lista de paquetes con actualización (solo lectura).
     * @return array {checked_at:int, packages:array}
     */
    function readPackages()
    {
        $h = $this->_health();
        if (!is_object($h)) return array('checked_at' => 0, 'packages' => array());
        return $h->readUpdatePackages();
    }

    /** ¿El usuario pertenece al grupo administrador? */
    function isAdminUser($user)
    {
        $h = $this->_health();
        if (!is_object($h)) return FALSE;
        return $h->isAdminUser($user);
    }

    // -----------------------------------------------------------------------
    //  Estado del job (en disco: sobrevive a la sesión)
    // -----------------------------------------------------------------------

    function jobDefaults()
    {
        return array(
            'state' => 'idle', 'pid' => 0, 'started' => 0, 'finished' => 0,
            'rc' => 0, 'reboot_needed' => FALSE, 'packages' => array(), 'message' => '',
        );
    }

    function jobStatus()
    {
        $default = $this->jobDefaults();
        if (!is_readable(self::STATE_FILE)) return $default;
        $j = json_decode((string)@file_get_contents(self::STATE_FILE), TRUE);
        return is_array($j) ? array_merge($default, $j) : $default;
    }

    /**
     * Salida del job. Si el helper todavía no escribió nada pero el proceso
     * sigue vivo, se devuelve la última salida parcial disponible.
     */
    function jobLog($lines = 200)
    {
        if (!is_readable(self::LOG_FILE)) return '';
        $all = file(self::LOG_FILE, FILE_IGNORE_NEW_LINES);
        if (!is_array($all)) return '';
        return implode("\n", array_slice($all, -max(1, (int)$lines)));
    }

    // -----------------------------------------------------------------------
    //  Acciones privilegiadas (helper pkgupdate)
    // -----------------------------------------------------------------------

    /**
     * Arranca el job de actualización. La lista llega del navegador pero el
     * helper la revalida contra el snapshot de health.db (allowlist cerrada).
     * @return array {ok, message}
     */
    function start($pkgs, $user)
    {
        if (!$this->isAdminUser($user)) return $this->_result(FALSE, $this->_t('Not authorized'));

        $args = array();
        foreach ((array)$pkgs as $p) {
            $p = trim((string)$p);
            if ($p !== '') $args[] = $p;
        }
        if (count($args) === 0) return $this->_result(FALSE, $this->_t('Select at least one package'));

        if ($this->_jobRunning()) return $this->_result(FALSE, $this->_t('An update is already in progress'));

        $j = $this->_runHelper('start', $args);
        $ok = ($j['rc'] === 0 && is_array($j['json']) && !empty($j['json']['ok']));
        $this->_audit($user, 'updates.start', implode(',', $args), $ok ? 'ok' : 'error');
        return $this->_result($ok, $ok ? $this->_t('Update started')
                                       : $this->_t('The update could not be started'));
    }

    /** Fuerza el refresco del snapshot de paquetes (grupo updates de health). */
    function refresh($user)
    {
        if (!$this->isAdminUser($user)) return $this->_result(FALSE, $this->_t('Not authorized'));
        $out = array(); $rc = 0;
        exec('/usr/bin/issabel-helper healthcollect --only=updates --force 2>&1', $out, $rc);
        $this->_audit($user, 'updates.refresh', 'updates', $rc === 0 ? 'ok' : 'error');
        return $this->_result($rc === 0,
            $rc === 0 ? $this->_t('Update list refreshed')
                      : $this->_t('The update list could not be refreshed'));
    }

    /** Libera un job colgado (p. ej. tras un reinicio del host). */
    function reset($user)
    {
        if (!$this->isAdminUser($user)) return $this->_result(FALSE, $this->_t('Not authorized'));
        $j = $this->_runHelper('reset', array());
        $ok = ($j['rc'] === 0 && is_array($j['json']) && !empty($j['json']['ok']));
        $this->_audit($user, 'updates.reset', 'job', $ok ? 'ok' : 'error');
        return $this->_result($ok, $ok ? $this->_t('Update status cleared')
                                       : $this->_t('The status could not be cleared'));
    }

    private function _jobRunning()
    {
        $st = $this->jobStatus();
        return ($st['state'] === 'running');
    }

    /** Ejecuta `/usr/bin/issabel-helper pkgupdate <cmd> [args…]`. */
    private function _runHelper($cmd, $args)
    {
        $line = '/usr/bin/issabel-helper pkgupdate '.escapeshellarg($cmd);
        foreach ((array)$args as $a) $line .= ' '.escapeshellarg($a);
        $out = array(); $rc = 0;
        exec($line.' 2>&1', $out, $rc);
        return array('rc' => $rc, 'json' => json_decode(implode("\n", $out), TRUE), 'out' => $out);
    }

    private function _result($ok, $message)
    {
        return array('ok' => (bool)$ok, 'message' => $message);
    }

    private function _t($s)
    {
        return function_exists('_tr') ? _tr($s) : $s;
    }

    /** Registra la acción en /var/log/issabel/audit.log. */
    private function _audit($user, $action, $target, $result)
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
        $line = sprintf('UPDATES %s: User %s performed "%s" on %s from %s.',
            strtoupper($result), $user, $action, $target, $remote);
        if (function_exists('writeLOG')) writeLOG('audit.log', $line);
        else error_log($line);
    }
}
?>
