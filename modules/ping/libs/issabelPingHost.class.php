<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2026 Issabel Foundation                                |
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
*/

/**
 * Validación del destino del módulo Ping & Traceroute.
 *
 * Acepta una IPv4, una IPv6 o un nombre de host resoluble (mismo criterio que
 * el validador "domain" de paloForm). Es la única fuente de verdad: la usa
 * index.php para el feedback de la interfaz y generate.php antes de ejecutar
 * el comando, de modo que ningún valor arbitrario llega al shell.
 */
class issabelPingHost
{
    /**
     * @param string $host destino escrito por el usuario
     * @return bool
     */
    public static function valid($host)
    {
        $host = trim((string)$host);
        if ($host === '') return false;

        // IPv4 o IPv6.
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) return true;

        // Nombre de host: etiquetas alfanuméricas separadas por puntos.
        return preg_match('/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/i', $host) === 1;
    }
}
