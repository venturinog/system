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
  | Formato compartido de los applets del dashboard (Calls, Voicemails). |
  +----------------------------------------------------------------------+
*/

class issabelDashboardFormat
{
    /**
     * Tiempo relativo corto. Las unidades son neutras ("min", "h", "d") y el
     * texto "hace %s" / "%s ago" sale del idioma del módulo.
     */
    public static function ago($ts)
    {
        $ts = (int)$ts;
        if ($ts <= 0) return '';
        $s = time() - $ts;
        if ($s < 0) $s = 0;
        if ($s < 60)   return function_exists('_tr') ? _tr('just now') : 'just now';
        if ($s < 3600)      $v = max(1, (int)floor($s / 60)).' min';
        elseif ($s < 86400) $v = (int)floor($s / 3600).' h';
        else                $v = (int)floor($s / 86400).' d';
        return function_exists('_tr') ? sprintf(_tr('%s ago'), $v) : ($v.' ago');
    }

    /** Segundos -> "mm:ss" (neutro respecto del idioma). */
    public static function duration($sec)
    {
        $sec = (int)$sec;
        if ($sec < 0) $sec = 0;
        return floor($sec / 60).':'.str_pad((string)($sec % 60), 2, '0', STR_PAD_LEFT);
    }
}
?>
