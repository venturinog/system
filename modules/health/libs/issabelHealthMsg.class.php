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
  | Mensajes de los chequeos (Detail y Threshold) del Health Center.      |
  |                                                                      |
  | El recolector corre como root y SIN idioma, así que guarda la clave  |
  | (`detail_key`/`threshold_key`), los argumentos (JSON) y un respaldo  |
  | EN INGLÉS (`detail`/`threshold`). Applet y página traducen con la    |
  | clave; CSV y /health usan el inglés. Ver DASHBOARDtodo.md §17.       |
  +----------------------------------------------------------------------+
*/

class issabelHealthMsg
{
    /**
     * Texto en inglés (respaldo determinista, independiente del idioma).
     * DEBE coincidir con el lang/en.lang del módulo `health`.
     * Un argumento que empieza con "@" se traduce (p. ej. "@label.asterisk").
     * Los mensajes con plural usan las claves ".one" / ".other" (y ".few" /
     * ".many" donde el idioma lo requiera).
     */
    private static $EN = array(
        'svc.active' => '%s: active',
        'svc.notinstalled' => '%s: not installed',
        'svc.state' => '%s: %s',
        'svc.probe' => '%s: could not query systemd',
        'systemd.failed.one' => '%d failed unit: %s',
        'systemd.failed.other' => '%d failed units: %s',
        'systemd.ok' => 'no failed units',
        'systemd.unavailable' => 'systemctl is not available',
        'clock.ok' => 'clock: %s',
        'clock.nochrony' => 'chronyc is not installed',
        'clock.noresponse' => 'no response from chrony',
        'disk.mount' => '%s (%s/%s GB used, %s GB free)',
        'disk.mounts.one' => '%d mount point',
        'disk.mounts.other' => '%d mount points',
        'disk.nodata' => 'no df data',
        'dirspace.unavailable' => 'issabel-helper is not available',
        'dirspace.failed' => 'dirspacereport failed',
        'sec.missing' => 'security module is not installed',
        'fw.active' => 'firewall is active',
        'fw.inactive' => 'firewall is inactive',
        'f2b.notinstalled' => 'fail2ban is not installed',
        'f2b.inactive' => 'fail2ban is not active (%s)',
        'f2b.jails.one' => '%d jail: %s',
        'f2b.jails.other' => '%d jails: %s',
        'pbl.notinstalled' => 'packetbl is not installed (optional)',
        'pbl.state' => 'packetbl: %s',
        'cert.missing' => 'web certificate not found',
        'cert.unreadable' => 'could not read the certificate',
        'cert.baddate' => 'invalid certificate date',
        'cert.expires.one' => '%s expires in %d day',
        'cert.expires.other' => '%s expires in %d days',
        'updates.issabel.one' => '%d issabel* package pending',
        'updates.issabel.other' => '%d issabel* packages pending',
        'updates.security.one' => '%d security patch pending',
        'updates.security.other' => '%d security patches pending',
        'dnf.unavailable' => 'dnf is not available',
        'dnf.timeout' => 'dnf did not finish in time',
        'dnf.failed' => 'dnf failed (rc=%d)',
        'repos.count.one' => '%d repository',
        'repos.count.other' => '%d repositories',
        'repos.failed' => 'could not list the repositories',
        'reboot.none' => 'no reboot required',
        'reboot.needed' => 'a system reboot is recommended',
        'reboot.unknown' => 'needs-restarting was inconclusive',
        'ast.ok' => 'Asterisk is responding',
        'ast.down' => 'no response from Asterisk (CLI)',
        'ast.missing' => 'Asterisk is not installed',
        'tr.down.one' => 'trunk down: %s',
        'tr.down.other' => 'trunks down: %s',
        'tr.ok' => 'trunks registered',
        'tr.none' => 'no trunks',
        'tr.grace.one' => '%d trunk down (within grace)',
        'tr.grace.other' => '%d trunks down (within grace)',
        'tr.unknown' => 'no trunk data',
        'tr.nolist' => 'could not determine the trunk list',
        'ext.unreg.one' => '%d unregistered extension',
        'ext.unreg.other' => '%d unregistered extensions',
        'ext.unregcrit.one' => '%d unregistered extension, critical: %s',
        'ext.unregcrit.other' => '%d unregistered extensions, critical: %s',
        'ext.grace.one' => '%d extension down (within grace)',
        'ext.grace.other' => '%d extensions down (within grace)',
        'ext.ok' => 'extensions registered',
        'ext.unknown' => 'no endpoint data',
        'bk.none' => 'no backups found in %s',
        'bk.age' => '%s (%s old)',
        'label.asterisk' => 'Asterisk PBX',
        'label.httpd' => 'Web server',
        'label.mariadb' => 'Database',
        'label.crond' => 'Cron',
        'label.chronyd' => 'NTP (chrony)',
        'label.fail2ban' => 'Fail2ban',
        'label.postfix' => 'SMTP relay',
        'state.active' => 'active',
        'state.inactive' => 'inactive',
        'state.failed' => 'failed',
        'state.unknown' => 'unknown',
        'state.notinstalled' => 'not installed',
        'value.active' => 'active',
        'value.inactive' => 'inactive',
        'value.failed' => 'failed',
        'value.not_installed' => 'not installed',
        'value.none' => 'none',
        'value.error' => 'error',
        'value.yes' => 'yes',
        'value.no' => 'no',
        'value.up' => 'up',
        'value.down' => 'down',
        'thr.warncrit' => '%s warn, %s crit',
        'thr.backup' => '%s warning',
        'mode.all' => 'all',
        'mode.list' => 'selected',
    );

    /** Categoría plural. Cubre one/other y, para eslavas (ru/uk), one/few/many. */
    public static function pluralCat($n, $lang)
    {
        $n = (int)$n; $abs = abs($n);
        switch (substr((string)$lang, 0, 2)) {
        case 'ru':
        case 'uk':
            $m10 = $abs % 10; $m100 = $abs % 100;
            if ($m10 == 1 && $m100 != 11) return 'one';
            if ($m10 >= 2 && $m10 <= 4 && ($m100 < 12 || $m100 > 14)) return 'few';
            return 'many';
        default:
            return ($abs == 1) ? 'one' : 'other';
        }
    }

    /** Traduce una clave: usa `_tr` si el idioma cargado la tiene; si no, inglés. */
    private static function t($key, $forceEn = FALSE)
    {
        if (!$forceEn && function_exists('_tr')) {
            $t = _tr($key);
            if (is_string($t) && $t !== $key && $t !== '') return $t;
        }
        return array_key_exists($key, self::$EN) ? self::$EN[$key] : $key;
    }

    /** Plantilla para una clave, eligiendo la forma plural si corresponde. */
    private static function template($key, $count, $lang, $forceEn)
    {
        if ($count >= 0) {
            $cat = self::pluralCat($count, $forceEn ? 'en' : $lang);
            foreach (array($key.'.'.$cat, $key.'.other', $key.'.one') as $cand) {
                $tpl = self::t($cand, $forceEn);
                if ($tpl !== $cand) return $tpl;
            }
        }
        return self::t($key, $forceEn);
    }

    /** Resuelve los argumentos: los que empiezan con "@" se traducen. */
    private static function args($args, $forceEn, $lang)
    {
        $out = array();
        foreach ((array)$args as $a) {
            if (is_string($a) && strlen($a) > 1 && $a[0] === '@') {
                $out[] = self::t(substr($a, 1), $forceEn);
            } else {
                $out[] = $a;
            }
        }
        return $out;
    }

    /** Idioma de la vista, resuelto una sola vez por petición. */
    private static $_lang = NULL;

    /**
     * Renderiza una fila de health_check en el idioma de la vista (columna
     * Detail). Usa `detail_key` + `detail_args` + `detail_count`.
     */
    public static function render($row, $lang = NULL)
    {
        return self::_format($row, $lang, FALSE);
    }

    /**
     * Renderiza la columna Threshold: `threshold_key` + `threshold_args` si
     * están; si no, se traducen los modos conocidos ("all", "list") y cualquier
     * otro valor se devuelve tal cual.
     */
    public static function thresholdText($row)
    {
        $fallback = isset($row['threshold']) ? (string)$row['threshold'] : '';
        if (!empty($row['threshold_key'])) {
            return self::_text($row['threshold_key'],
                self::_argsOf($row, 'threshold_args'), -1, NULL, FALSE, $fallback);
        }
        if ($fallback !== '' && strpos($fallback, ' ') === FALSE) {
            $k = 'mode.'.$fallback;
            $t = self::t($k);
            if ($t !== $k) return $t;
        }
        return $fallback;
    }

    /** Renderiza el `detail` en inglés (columna de la base y endpoint /health). */
    public static function english($row)
    {
        return self::_format($row, 'en', TRUE);
    }

    /**
     * Texto en inglés para un mensaje key/args/count (lo usa el recolector
     * para el respaldo de `detail` y `threshold`).
     */
    public static function englishMsg($key, $args = array(), $count = -1)
    {
        return self::_text($key, $args, $count, 'en', TRUE, '');
    }

    /**
     * Traduce un `value` que sea una palabra de estado
     * ("active", "not installed", "none"...) y deja el resto igual.
     */
    public static function valueText($row)
    {
        $v = isset($row['value']) ? (string)$row['value'] : '';
        if ($v === '') return $v;
        $k = 'value.'.strtolower(str_replace(array(' ', '-'), '_', $v));
        $t = self::t($k);
        return ($t !== $k) ? $t : $v;
    }

    /** Argumentos (JSON) de una columna de la fila; array vacío si no hay. */
    private static function _argsOf($row, $col)
    {
        if (empty($row[$col])) return array();
        $a = json_decode($row[$col], TRUE);
        return is_array($a) ? $a : array();
    }

    private static function _format($row, $lang, $forceEn)
    {
        $fallback = isset($row['detail']) ? (string)$row['detail'] : '';
        $key = isset($row['detail_key']) ? (string)$row['detail_key'] : '';
        if ($key === '') return $fallback;
        $count = isset($row['detail_count']) ? (int)$row['detail_count'] : -1;
        return self::_text($key, self::_argsOf($row, 'detail_args'), $count, $lang, $forceEn, $fallback);
    }

    private static function _text($key, $args, $count, $lang, $forceEn, $fallback)
    {
        if (is_null($lang)) {
            if (is_null(self::$_lang)) {
                self::$_lang = function_exists('get_language') ? get_language() : 'en';
            }
            $lang = self::$_lang;
        }
        $tpl = self::template($key, $count, $lang, $forceEn);
        if (!is_string($tpl) || $tpl === $key) {
            // Clave desconocida: se retiró (p. ej. tras actualizar el paquete)
            // y la caché aún no se refrescó. Mejor el texto guardado que la
            // clave cruda en pantalla.
            return ($fallback !== '') ? $fallback : $key;
        }
        $vals = self::args($args, $forceEn, $lang);
        if (count($vals) === 0) {
            // Sin argumentos no se puede aplicar vsprintf: si la plantilla los
            // espera, se prefiere el texto de respaldo antes que un "%s" suelto.
            if (strpos((string)$tpl, '%') !== FALSE) return $fallback;
            return is_string($tpl) ? $tpl : $fallback;
        }
        $s = vsprintf($tpl, $vals);
        return is_string($s) ? $s : $fallback;
    }
}
?>
