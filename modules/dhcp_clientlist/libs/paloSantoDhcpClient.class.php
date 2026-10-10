<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.5-9                                                |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
  $Id: paloSantoDhcpClient.class.php, Thu 20 May 2021 09:04:46 AM EDT, nicolas@issabel.com
*/
class paloSantoDhcpClienList {
    var $_DB;
    var $errMsg;

    function __construct(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }


    /////////NEW FUNCTIONS FOR MODULE DHCP_CLIENT

    /*
     * Lee la lista de concesiones del archivo memfile de Kea. El formato es
     * CSV con las columnas:
     * address,hwaddr,client_id,valid_lifetime,expire,subnet_id,fqdn_fwd,
     * fqdn_rev,hostname,state,user_context,pool_id
     */
    function getDhcpClientList()
    {
        $data = array();
        $i = 0;    // Por compat, la lista empieza desde 1
        $sLeaseFile = '/var/lib/kea/kea-leases4.csv';
        if (!file_exists($sLeaseFile)) {
            return $data;
        }
        foreach (file($sLeaseFile) as $line) {
            // Saltarse los comentarios y la linea de encabezado
            if (preg_match('/^\s*#/', $line)) continue;
            if (strpos(ltrim($line), 'address,') === 0) continue;

            $fields = str_getcsv(trim($line));
            if (count($fields) < 10) continue;

            $sIp        = $fields[0];
            $sHwaddr    = $fields[1];
            $iValid     = (int)$fields[3];
            $iExpire    = (int)$fields[4];
            $iState     = (int)$fields[9];

            // state: 0=activa, 1=declinada, 2=expirada/reclamada
            if ($iState == 0) $sBindingState = 'active';
            elseif ($iState == 1) $sBindingState = 'declined';
            else $sBindingState = 'expired';

            $data[++$i] = array(
                'iphost'        => $sIp,
                'macaddress'    => $sHwaddr,
                'binding state' => $sBindingState,
            );

            if ($iExpire > 0) {
                $data[$i]['date_starts'] = date('Y/m/d H:i:s', $iExpire - $iValid);
                $data[$i]['date_ends']   = date('Y/m/d H:i:s', $iExpire);
            } else {
                $data[$i]['date_starts'] = '';
                $data[$i]['date_ends']   = '';
            }
        }

        return $data;
    }
    
    function getDhcpClientListById($id)
    {
        $result = array();
        $arrResult = $this->getDhcpClientList();

        if ($id >= 1 && $id <= count($arrResult)) {
            $result = $arrResult[$id];
            if ($result['date_ends'] == '') $result['date_ends'] = 'never';
        }

        return $result;
    }
}
?>
