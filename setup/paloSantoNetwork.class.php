<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
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
  $Id: paloSantoNetwork.class.php, Thu 20 May 2021 09:05:10 AM EDT, nicolas@issabel.com
*/

class paloNetwork
{
    var $errMsg;

    // Constructor
    function __construct()
    {
        $this->errMsg = "";
    }

    /* Ejecuta nmcli y devuelve su salida como arreglo de líneas. Fuerza
     * LC_ALL=C/LANG=C para que la salida sea estable (nmcli la localiza) y
     * devuelve un arreglo vacío si nmcli falla o no está disponible. */
    private static function nmcliLines($args)
    {
        $cmd = '/usr/bin/env LC_ALL=C LANG=C /usr/bin/nmcli';
        foreach ($args as $arg) $cmd .= ' '.escapeshellarg($arg);
        $output = array();
        $ret = NULL;
        exec($cmd.' 2>/dev/null', $output, $ret);
        if ($ret != 0) return array();
        return $output;
    }

    /* Lista las INTERFASES para las que NetworkManager tiene una conexión y si
     * obtienen su IP de forma dinámica. Se reporta dinámica (dhcp) cuando
     * ipv4.method es 'auto'; cualquier otro método ('manual', 'disabled',
     * 'shared', 'link-local') se reporta como estática. Si varias conexiones
     * referencian la misma interfaz, se reporta dinámica si una cualquiera lo
     * es.
     *
     * Antes esto se leía de /etc/sysconfig/network-scripts/ifcfg-*; en EL10 ese
     * directorio ya no existe, así que la fuente de verdad es NetworkManager
     * (nmcli).
     */
    private static function listar_bootproto_interfases()
    {
        $iflist = array();

        foreach (self::nmcliLines(array('-g', 'NAME', 'con', 'show')) as $sName) {
            $sName = trim($sName);
            if ($sName === '') continue;

            $info = self::nmcliLines(array(
                '-g', 'connection.interface-name,ipv4.method', 'con', 'show', $sName));
            if (count($info) < 2) continue;

            $if = trim($info[0]);
            $method = trim($info[1]);
            if ($if === '') continue;

            if (!isset($iflist[$if])) $iflist[$if] = FALSE;
            if (!$iflist[$if]) $iflist[$if] = ($method == 'auto');
        }
        return $iflist;
    }

    /**
     * Procedimiento para obtener información sobre las interfases de red del
     * sistema. Actualmente se listan las interfases de tipo Ethernet y las
     * localhost. Para cada interfaz de red se crea una entrada cuya clave es
     * el nombre de la interfaz, y el valor es un arreglo con los siguientes
     * elementos:
     *  Name        : 'Ethernet' o 'Loopback'. Si se identifica la interfaz
     *                como un alias de otra interfaz, se la marca como
     *                Ethernet X Alias Y
     *  Type        : Tipo de configuración de red: static dhcp ...
     *  HW_info     : Información de hardware sobre la interfaz. Sólo para Ethernet.
     *  HWaddr      : MAC de la interfaz Ethernet
     *  Inet Addr   : IPv4 asignada a la interfaz de red
     *  Mask        : Máscara IPv4 asignada a la intefaz de red
     *  Running     : 'Yes' si la interfaz está activa
     *  RX packets  : Número de paquetes recibidos
     *  RX bytes    : Número de bytes recibidos
     *  TX packets  : Número de paquetes enviados
     *  TX bytes    : Número de bytes enviados
     *
     * @return array    Lista de interfases de red
     */
    static function obtener_interfases_red()
    {
        $interfases = array();

        // Se listan todas las interfases físicas, y se toman loopback y ether
        /*
        [root@elx2 net]# ip link show
        1: lo: <LOOPBACK,UP,LOWER_UP> mtu 16436 qdisc noqueue
            link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00
        2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc pfifo_fast qlen 1000
            link/ether 08:00:27:2b:f0:14 brd ff:ff:ff:ff:ff:ff
        3: sit0: <NOARP> mtu 1480 qdisc noop
            link/sit 0.0.0.0 brd 0.0.0.0
         */
        $output = NULL;
        exec('/sbin/ip link show', $output);
        $if_actual = NULL;  // La interfaz que se examina
        $if_flags = NULL;
        foreach ($output as $s) {
            $regs = NULL;
            if (preg_match('/^\d+:\s+([[:alnum:]\._-]+)(@\S+)?:\s*<(.*)>(.*)/', $s, $regs)) {
                $if_actual = $regs[1];
                $if_flags = explode(',', $regs[3]);
                if(preg_match("/state DOWN/",$regs[4])) {  // State down in output, not in flags for a present card that is not plugged
                     $if_flags = array_diff($if_flags, array('UP'));
                     $if_flags[]='DOWN';
                }
            } elseif (!is_null($if_actual) && preg_match('!\s*link/(loopback|ether) ([[:xdigit:]]{2}(:[[:xdigit:]]{2}){5})!', $s, $regs)) {
                $interfases[$if_actual] = array(
                    'Name'          =>  (($regs[1] == 'ether') ? 'Ethernet' : 'Loopback'),
                    'Link'          =>  $regs[1],
                    'Type'          =>  NULL,
                    'HW_info'       =>  NULL,
                    'HWaddr'        =>  $regs[2],
                    'Inet Addr'     =>  NULL,
                    'Mask'          =>  NULL,
                    'Running'       =>  in_array('UP', $if_flags) ? 'Yes' : NULL,
                    'RX packets'    =>  0,
                    'RX bytes'      =>  0,
                    'TX packets'    =>  0,
                    'TX bytes'      =>  0,
                );
                if ($regs[1] == 'ether') {
                    $interfases[$if_actual]['Name'] = $if_actual;
                }
            } else {
                $if_actual = NULL;
            }
        }

        /* Para cada interfaz física, se leen las estadísticas de bytes y
         * paquetes transmitidos y recibidos, así como el controlador del
         * dispositivo de red. */
        foreach (array_keys($interfases) as $if_actual) {
            $fuentes = array(
                'RX packets'    =>  "/sys/class/net/$if_actual/statistics/rx_packets",
                'RX bytes'      =>  "/sys/class/net/$if_actual/statistics/rx_bytes",
                'TX packets'    =>  "/sys/class/net/$if_actual/statistics/tx_packets",
                'TX bytes'      =>  "/sys/class/net/$if_actual/statistics/tx_bytes",
            );
            foreach ($fuentes as $k => $p) {
                if (file_exists($p))
                    $interfases[$if_actual][$k] = trim(file_get_contents($p));
            }

            // Nombre del controlador del dispositivo de red
            if (file_exists("/sys/class/net/$if_actual/device/driver")) {
                $interfases[$if_actual]['HW_info'] = basename(readlink("/sys/class/net/$if_actual/device/driver"));
            }
        }

        /* Para cada interfaz física, se listan sus IPs. La que tiene la
           interfaz sin adornos es la IP de la interfaz. Otras IPs definen
           alias de la interfaz. */
        /*
        [root@elx2 net]# ip addr show dev eth0
        2: eth0: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc pfifo_fast qlen 1000
            link/ether 08:00:27:2b:f0:14 brd ff:ff:ff:ff:ff:ff
            inet 192.168.5.193/16 brd 192.168.255.255 scope global eth0
            inet 192.168.6.1/24 brd 192.168.6.255 scope global eth0:0
            inet6 fe80::a00:27ff:fe2b:f014/64 scope link
               valid_lft forever preferred_lft forever
        [root@elx2 net]# ip addr show dev lo
        1: lo: <LOOPBACK,UP,LOWER_UP> mtu 16436 qdisc noqueue
            link/loopback 00:00:00:00:00:00 brd 00:00:00:00:00:00
            inet 127.0.0.1/8 scope host lo
            inet6 ::1/128 scope host
               valid_lft forever preferred_lft forever
        -bash-4.2# ip addr show dev mv-enp1s0
        3: mv-enp1s0@if2: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500 qdisc noqueue state UP qlen 1000
            link/ether e6:d5:e6:7e:a7:aa brd ff:ff:ff:ff:ff:ff link-netnsid 0
            inet 192.168.5.195/20 brd 192.168.15.255 scope global dynamic mv-enp1s0
               valid_lft 83014sec preferred_lft 83014sec
            inet6 fe80::e4d5:e6ff:fe7e:a7aa/64 scope link
               valid_lft forever preferred_lft forever
         */
        foreach (array_keys($interfases) as $if_actual) {
            $output = NULL;
            exec('/sbin/ip addr show dev '.$if_actual, $output);
            foreach ($output as $s) {
                if (preg_match('|\s*inet (\d+\.\d+\.\d+.\d+)/(\d+).+\s(([[:alnum:]\._-]+)(:(\d+))?)\s*$|', trim($s), $regs)) {
                    // Calcular IP de máscara a partir de número de bits
                    $iMaskBits = $regs[2];
                    $iMask = (0xFFFFFFFF << (32 - $iMaskBits)) & 0xFFFFFFFF;
                    $sMaskIP = implode('.', array(
                        ($iMask >> 24) & 0xFF,
                        ($iMask >> 16) & 0xFF,
                        ($iMask >>  8) & 0xFF,
                        ($iMask      ) & 0xFF,
                    ));

                    // Verificar si es IP de interfaz o de alias
                    if ($regs[3] == $if_actual) {
                        $interfases[$if_actual]['Inet Addr'] = $regs[1];
                        $interfases[$if_actual]['Mask'] = $sMaskIP;
                    } else {
                        $if_alias = $regs[3];
                        $if_orig = $regs[4];
                        $if_aliasnum = $regs[6];
                        $interfases[$if_alias] = array(
                            'Name'          =>  $interfases[$if_orig]['Name'].' Alias '.$if_aliasnum,
                            'Type'          =>  NULL,
                            'HWaddr'        =>  $interfases[$if_orig]['HWaddr'],
                            'Inet Addr'     =>  $regs[1],
                            'Mask'          =>  $sMaskIP,
                            'Running'       =>  $interfases[$if_orig]['Running'],
                        );
                    }
                } else if (preg_match('|^\s*inet6 ([0-9a-fA-F:]+)/(\d+) scope (global|link)|', trim($s), $regs6)) {
                    // Se expone la primera dirección global (o link-local) IPv6
                    if (empty($interfases[$if_actual]['Inet6 Addr'])) {
                        $interfases[$if_actual]['Inet6 Addr'] = $regs6[1];
                        $interfases[$if_actual]['Mask6'] = $regs6[2];
                        $interfases[$if_actual]['Inet6 Scope'] = $regs6[3];
                    }
                }
            }
        }

        // Tipo de interfaz de red según NetworkManager (ipv4.method)
        $if_tipo = self::listar_bootproto_interfases();
        foreach (array_keys($interfases) as $if_actual) {
            $interfases[$if_actual]['Type'] = (isset($if_tipo[$if_actual]) && $if_tipo[$if_actual]) ? 'dhcp' : 'static';
        }

        return $interfases;
    }

    // Es decir que no se incluye "lo" ni interfases virtuales
    static function obtener_interfases_red_fisicas()
    {
        $arrInterfasesRedPreliminar=array();
        $arrInterfasesRedPreliminar=self::obtener_interfases_red();

        // Selecciono solo las interfases de red fisicas
        $arrInterfasesRed=array();
        $hideNic=array();
        foreach($arrInterfasesRedPreliminar as $nombreReal => $arrData) {
            if(preg_match("/^dummy/",$nombreReal)) continue;
            if(preg_match("/\./",$nombreReal)) {
                // if vlan, hide parent/real nic
                $parts = preg_split("/\./",$nombreReal);
                $hideNic[] = $parts[0];
            }
            if (isset($arrData['Link']) && $arrData['Link'] == 'ether')
                $arrInterfasesRed[$nombreReal]=$arrData;
        }
        foreach($hideNic as $nic) {
            unset($arrInterfasesRed[$nic]);
        }

        return $arrInterfasesRed;
    }

    /**
     * Procedimiento para interrogar la configuración general de red del
     * sistema.
     *
     * @return array arreglo con los siguientes valores:
     *      dns:        arreglo con 0 o más DNS asignados
     *      host:       nombre de host para el sistema
     *      gateway:    El gateway predeterminado asignado para el sistema
     */
    static function obtener_configuracion_red()
    {
        $archivoResolv = "/etc/resolv.conf";
        $arrResult = array(
            'dns'         =>  array(),
            'dns6'        =>  array(),
            'host'        =>  NULL,
            'gateway'     =>  NULL,
            'gateway_dev' =>  NULL,
        );

        //- Obtengo los dnss (NetworkManager; /etc/resolv.conf como respaldo)
        foreach (self::nmcliLines(array('-t', '-e', 'yes', '-f', 'IP4.DNS,IP6.DNS', 'device', 'show')) as $linea) {
            $regs = NULL;
            if (preg_match('/^IP4\.DNS(\[\d+\])?:(.+)$/', $linea, $regs)) {
                $dns = trim($regs[2]);
                if ($dns !== '' && !in_array($dns, $arrResult['dns'])) $arrResult['dns'][] = $dns;
            } else if (preg_match('/^IP6\.DNS(\[\d+\])?:(.+)$/', $linea, $regs)) {
                $dns = trim($regs[2]);
                if ($dns !== '' && !in_array($dns, $arrResult['dns6'])) $arrResult['dns6'][] = $dns;
            }
        }
        if (count($arrResult['dns']) == 0 && ($fh = fopen($archivoResolv, "r"))) {
            while(!feof($fh)) {
                $linea = fgets($fh, 4048);
                if(preg_match("/^nameserver[[:space:]]+(.*)$/", $linea, $arrReg)) {
                    $arrResult['dns'][] = trim($arrReg[1]);
                }
            }
            fclose($fh);
        }

        //- Obtengo el hostname
        $arrResult['host'] = trim(file_get_contents("/proc/sys/kernel/hostname"));

        //- Obtengo el Default Gateway (NetworkManager)
        $gwdev = NULL;
        foreach (self::nmcliLines(array('-t', '-e', 'yes', '-f', 'GENERAL.DEVICE,IP4.GATEWAY', 'device', 'show')) as $linea) {
            $regs = NULL;
            if (preg_match('/^GENERAL\.DEVICE:(.*)$/', $linea, $regs)) {
                $gwdev = trim($regs[1]);
            } else if (preg_match('/^IP4\.GATEWAY:(.+)$/', $linea, $regs)) {
                $gw = trim($regs[1]);
                if ($gw !== '' && is_null($arrResult['gateway'])) {
                    $arrResult['gateway'] = $gw;
                    $arrResult['gateway_dev'] = $gwdev;
                }
            }
        }
        if (is_null($arrResult['gateway'])) {
            // Respaldo si NetworkManager no reporta el gateway
            $arrOutput = NULL;
            exec("/sbin/ip -4 route show default", $arrOutput);
            if(is_array($arrOutput)) {
                foreach($arrOutput as $linea) {
                    $regs = NULL;
                    if(preg_match("/^default via (\S+) dev (\S+)/", $linea, $regs)) {
                        $arrResult['gateway'] = $regs[1];
                        $arrResult['gateway_dev'] = $regs[2];
                        break;
                    }
                }
            }
        }
        return $arrResult;
    }

    /**
     * Procedimiento para escribir la configuracin de red del sistema en los
     * archivos de configuración, a partir del arreglo indicado en el parámetro.
     * El arreglo indicado en el parámetro debe de tener los siguientes
     * elementos:
     *      $arreglo["host"]        Nombre simbolico del sistema
     *      $arreglo["dns_ip_1"]    DNS primario de la maquina
     *      $arreglo["dns_ip_2"]    DNS secundario de la maquina
     *      $arreglo["gateway_ip"]  IP del gateway asociado a la interfaz externa
     *  La función devuelve VERDADERO en caso de éxito, FALSO en caso de error.
     *
     * @param   mixed   $config_red Nueva configuración deseada de la red
     *
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function escribir_configuracion_red_sistema($config_red)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper netconfig --genconf';

        if(!isset($config_red['dns_ip_2'])) {
            $config_red['dns_ip_2']='';
        }
        $sComando .= ' --host '.escapeshellarg($config_red['host']).
            ' --gateway '.escapeshellarg($config_red['gateway_ip']).
            ' --dns1 '.escapeshellarg($config_red['dns_ip_1']).
            ($config_red['dns_ip_2'] == '' ? '' : ' --dns2 '.escapeshellarg($config_red['dns_ip_2'])).
            (empty($config_red['dns_ip_6']) ? '' : ' --dns6 '.escapeshellarg($config_red['dns_ip_6'])).
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para escribir la configuración de red de una interfaz
     * Ethernet específica.
     *
     * @param   string  $dev    Dispositivo de red a modificar: eth0
     * @param   string  $tipo   Una de las cadenas: static dhcp
     * @param   string  $ip     (opcional)  IP a asignar en caso static
     * @param   string  $mask   (opcional)  Máscara a asignar en caso static
     * @param   string  $ip6method (opcional) auto|manual|dhcp|disabled
     * @param   string  $ip6addr   (opcional) IPv6 estática (addr/prefijo)
     * @param   string  $ip6gw     (opcional) Gateway IPv6
     *
     * @return  bool    VERDADERO en éxito, FALSO en error
     */
    function escribirConfiguracionInterfaseRed($dev, $tipo, $ip="", $mask="", $ip6method="", $ip6addr="", $ip6gw="")
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper netconfig --ifconf'.
            ' --device '.escapeshellarg($dev).
            ' --bootproto '.escapeshellarg($tipo).
            (($ip == '') ? '' : ' --ipaddr '.escapeshellarg($ip)).
            (($mask == '') ? '' : ' --netmask '.escapeshellarg($mask)).
            (($ip6method == '') ? '' : ' --ip6method '.escapeshellarg($ip6method)).
            (($ip6addr == '') ? '' : ' --ip6addr '.escapeshellarg($ip6addr)).
            (($ip6gw == '') ? '' : ' --ip6gw '.escapeshellarg($ip6gw)).
            ' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Devuelve el cambio de red pendiente de confirmar, o NULL si no hay.
     *
     * @return array|NULL   Arreglo con 'token', 'summary', 'expires', 'remaining'
     */
    function obtenerCambioRedPendiente()
    {
        $this->errMsg = '';
        $output = $ret = NULL;
        exec('/usr/bin/issabel-helper netconfig --pending 2>&1', $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return NULL;
        }
        $data = json_decode(implode('', $output), TRUE);
        if (!is_array($data) || empty($data['pending'])) return NULL;
        return $data;
    }

    /**
     * Confirma (cancela la reversión de) un cambio de red pendiente.
     */
    function confirmarCambioRed($token)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper netconfig --confirm '.escapeshellarg($token).' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Revierte (aplica el snapshot de) un cambio de red pendiente.
     */
    function revertirCambioRed($token)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper netconfig --rollback '.escapeshellarg($token).' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Obtiene las rutas estáticas IPv4 por interfaz (NetworkManager).
     *
     * @return array   Arreglo interfaz => lista de especificaciones de ruta
     */
    function obtenerRutasRed()
    {
        $this->errMsg = '';
        $output = $ret = NULL;
        exec('/usr/bin/issabel-helper netconfig --getroutes 2>&1', $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return array();
        }
        $data = json_decode(implode('', $output), TRUE);
        return is_array($data) ? $data : array();
    }

    /**
     * Fija la lista completa de rutas estáticas IPv4 de una interfaz.
     *
     * @param string $dev         Dispositivo de red
     * @param string $routespec   Lista de rutas en sintaxis nmcli
     *
     * @return bool   VERDADERO en éxito, FALSO en error
     */
    function escribirRutasRed($dev, $routespec)
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper netconfig --setroutes'
            .' --device '.escapeshellarg($dev)
            .' --routespec '.escapeshellarg($routespec)
            .' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Aplica un change set (JSON) de forma atómica (interfaz + gateway + rutas).
     * Si el cambio es bloqueante, queda pendiente de confirmación.
     *
     * @param string $changeJson   Conjunto de cambios en JSON
     * @param string $clientIp     IP del cliente (para clasificar la de gestión)
     *
     * @return bool   VERDADERO en éxito, FALSO en error
     */
    function aplicarCambioRed($changeJson, $clientIp = '')
    {
        $this->errMsg = '';
        $sComando = '/usr/bin/issabel-helper netconfig --apply'
            .' --change '.escapeshellarg($changeJson)
            .($clientIp === '' ? '' : ' --client-ip '.escapeshellarg($clientIp))
            .' 2>&1';
        $output = $ret = NULL;
        exec($sComando, $output, $ret);
        if ($ret != 0) {
            $this->errMsg = implode('', $output);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Compute IPv4 network address given IPv4 host address and bits in netmask.
     * Function that returns the network address of the given ip for the given mask
     *
     * @param string     $ip         IPv4 host address in dotted-quad format
     * @param string     $mask       Number of bits in network mask
     *
     * @return string    Computed IPv4 network address
     */
    static function getNetAdress($ip, $mask)
    {
        $octetos_ip = explode('.', $ip);
        $octetos_net = array(0, 0, 0, 0);
        if ($mask <= 0 || $mask > 32) return NULL;
        for ($k = 0; $k < 4 && $mask; $k++) {
            $octetmask = ($mask >= 8) ? 8 : $mask;
            $mask -= $octetmask;
            $octetos_net[$k] = (int)$octetos_ip[$k] & ((0xFF << (8 - $octetmask)) & 0xFF);
        }
        return implode('.', $octetos_net);
    }

    /**
     * Count the number of bits set in a network mask and returns the count:
     * 255.255.128.0 => 17
     * This assumes the network mask is well formed.
     *
     * @param string    $mask   IP mask in dotted-quad format
     *
     * @return int      Number of bits set in the mask
     */
    static function maskToDecimalFormat($mask)
    {
        $mask = explode(".", $mask);
        $decimal = 0;
        foreach($mask as $octeto) {
            $octeto = (int)$octeto & 0xFF;
            while (($octeto & 0x80) != 0) {
                $octeto = ($octeto << 1) & 0xFF;
                $decimal++;
            }
        }
        return $decimal;
    }
}
?>
