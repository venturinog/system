<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
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
  $Id: paloSantoDHCP.class.php,v 1.1 2008/01/04 10:39:57 bmacias Exp $ */

include_once("libs/paloSantoDB.class.php");

/* Clase que implementa DHCP Server (Kea en Issabel 6) */
class PaloSantoDHCP
{
    var $errMsg;

    function getConfigurationDHCP()
    {
        // Leer el archivo de configuracion de Kea (JSON)
        $arrConfigurationDHCP = array();
        $sPath = '/etc/kea/kea-dhcp4.conf';
        if (!file_exists($sPath)) {
            $this->errMsg = _tr("DHCP configuration reading error: Verify that the DHCP service is installed and try again.");
            return $arrConfigurationDHCP;
        }
        $conf = json_decode(file_get_contents($sPath), TRUE);
        if (!is_array($conf) || !isset($conf['Dhcp4']['subnet4'][0])) {
            $this->errMsg = _tr("DHCP configuration reading error: Verify that the DHCP service is installed and try again.");
            return $arrConfigurationDHCP;
        }

        $dhcp4 = $conf['Dhcp4'];
        $subnet = $dhcp4['subnet4'][0];

        // RANGO DE IPS
        if (isset($subnet['pools'][0]['pool'])) {
            $regs = NULL;
            $patron = '/^\s*(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s*-\s*(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s*$/';
            if (preg_match($patron, $subnet['pools'][0]['pool'], $regs)) {
                $arrConfigurationDHCP["IPS_RANGE"]["in_ip_ini"] = $regs[1];
                $arrConfigurationDHCP["IPS_RANGE"]["in_ip_fin"] = $regs[2];
            }
        }

        // LEASE TIME
        if (isset($dhcp4['valid-lifetime'])) {
            $arrConfigurationDHCP["LEASE_TIME"]["in_lease_time"] = $dhcp4['valid-lifetime'];
        }

        // Las opciones pueden estar en la subred o en el ambito global
        foreach (array($subnet, $dhcp4) as $scope) {
            if (!isset($scope['option-data'])) continue;
            foreach ($scope['option-data'] as $od) {
                $name = isset($od['name']) ? $od['name'] : (isset($od['code']) ? $od['code'] : NULL);
                if ($name === 'routers' && !isset($arrConfigurationDHCP["GATEWAY"])) {
                    $arrConfigurationDHCP["GATEWAY"]["in_gw"] = $od['data'];
                } elseif ($name === 'netbios-name-servers' && !isset($arrConfigurationDHCP["WINS"])) {
                    $arrConfigurationDHCP["WINS"]["in_wins"] = $od['data'];
                } elseif ($name === 'domain-name-servers') {
                    $dnsList = preg_split('/,\s*/', $od['data']);
                    foreach ($dnsList as $dnsString) {
                        if (!isset($arrConfigurationDHCP["DNS1"])) {
                            $arrConfigurationDHCP["DNS1"]["in_dns1"] = $dnsString;
                        } else if (!isset($arrConfigurationDHCP["DNS2"])) {
                            $arrConfigurationDHCP["DNS2"]["in_dns2"] = $dnsString;
                        }
                    }
                }
            }
        }

        // NEXT SERVER (PXE Boot Server)
        if (isset($subnet['next-server'])) {
            $arrConfigurationDHCP["NEXT"]["in_next"] = $subnet['next-server'];
        }
        //TODO: filename parameter

        if (!isset($arrConfigurationDHCP["IPS_RANGE"])) {
            // Error, no se encontro el rango de IPs, la directiva mas importante...
            $this->errMsg = _tr("Could not find IP range");
        }

        //Lleno de vacio los q no se encontraron... Para tener q mostrar .. esto solo por presentacion.
        if (!isset($arrConfigurationDHCP["DNS2"])) {
            $arrConfigurationDHCP["DNS2"]["in_dns2"] = "";
        }
        if (!isset($arrConfigurationDHCP["WINS"])) {
            $arrConfigurationDHCP["WINS"]["in_wins"] = "";
        }
        if (!isset($arrConfigurationDHCP["LEASE_TIME"])) {
            $arrConfigurationDHCP["LEASE_TIME"]["in_lease_time"] = "7200";
        }

        return $arrConfigurationDHCP;
    }

    function getStatusServiceDHCP()
    {
        $output = $ret = NULL;
        exec('/usr/bin/systemctl is-active --quiet kea-dhcp4 2>&1', $output, $ret);
        return ($ret == 0) ? 'active' : 'desactive';
    }

    function startServiceDHCP()
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper dhcpconfig --start 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    function stopServiceDHCP()
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper dhcpconfig --stop 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    function calcularIpSubred($ipCualquiera, $mascaraRed)
    {
        if(empty($ipCualquiera) or empty($mascaraRed)) {
            return false;
        }

        $arrLanIp   = explode(".", $ipCualquiera);
        $arrLanMask = explode(".", $mascaraRed);

        $IPSubnetOct1 = (int)$arrLanIp[0]&(int)$arrLanMask[0];
        $IPSubnetOct2 = (int)$arrLanIp[1]&(int)$arrLanMask[1];
        $IPSubnetOct3 = (int)$arrLanIp[2]&(int)$arrLanMask[2];
        $IPSubnetOct4 = (int)$arrLanIp[3]&(int)$arrLanMask[3];
        $strResultado = $IPSubnetOct1 . "." . $IPSubnetOct2 . "." . $IPSubnetOct3 . "." . $IPSubnetOct4;
        return $strResultado;
    }

    function updateFileConfDHCP(
                $ip_gw,
                $ip_gw_nm,
                $ip_wins,
                $ip_dns1,
                $ip_dns2,
                $IPSubnet,
                $conf_red_actual,
                $ip_ini,
                $ip_fin,
                $in_lease_time,
                $ip_next,
                $in_filename)
    {
        // $ip_gw_nm $IPSubnet $conf_red_actual no se usan
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper dhcpconfig --config'.
            ' --ip-start '.escapeshellcmd($ip_ini).
            ' --ip-end '.escapeshellcmd($ip_fin).
            ' --lease-time '.escapeshellcmd($in_lease_time).
            (($ip_gw != '') ? ' --gateway '.escapeshellcmd($ip_gw) : '').
            (($ip_wins != '') ? ' --wins '.escapeshellcmd($ip_wins) : '').
            (($ip_next != '') ? ' --next-server '.escapeshellcmd($ip_next) : '').
            (($in_filename != '') ? ' --filename '.escapeshellcmd($in_filename) : '').
            (($ip_dns1 != '') ? ' --dns1 '.escapeshellcmd($ip_dns1) : '').
            (($ip_dns2 != '') ? ' --dns2 '.escapeshellcmd($ip_dns2) : '').
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }
}
?>
