<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2017 Issabel Foundation                                |
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
  $Id: index.php, Tue 19 Sep 2023 02:42:39 PM EDT, nicolas@issabel.com
*/

class Applet_ProcessesStatus
{
    private $_initscript_cache = NULL;
    
    function handleJSON_getContent($smarty, $module_name, $appletlist)
    {
        /* Se cierra la sesión para quitar el candado sobre la sesión y permitir
         * que otras operaciones ajax puedan funcionar. */
        $issabeluser = $_SESSION['issabel_user'];
        session_commit();
        
        $respuesta = array(
            'status'    =>  'success',
            'message'   =>  '(no message)',
        );

        $smarty->assign(array(
            'sMsgStart'         => _tr('Start process'),
            'sMsgStop'          => _tr('Stop process'),
            'sMsgRestart'       => _tr('Restart process'),
            'sMsgActivate'      => _tr('Enable process'),
            'sMsgDeactivate'    => _tr('Disable process'),
        ));

        $listaIconos = array(
            'Asterisk'  =>  'icon_pbx.png',
            'MySQL'     =>  'icon_db.png',
            'Apache'    =>  'icon_www.png',
        );
        $sIconoDesconocido = 'system.png';

        $arrServices = $this->getStatusServices();
        foreach (array_keys($arrServices) as $sServicio) {
            switch ($arrServices[$sServicio]['status_service']) {
            case 'OK':
                $arrServices[$sServicio]['status_desc'] = _tr('Running');
                $arrServices[$sServicio]['status_color'] = '#006600';
                break;
            case 'Shutdown':
                $arrServices[$sServicio]['status_desc'] = _tr('Not running');
                $arrServices[$sServicio]['status_color'] = '#880000';
                break;
            default:
                $arrServices[$sServicio]['status_desc'] = _tr('Not installed');
                $arrServices[$sServicio]['status_color'] = '#000088';
                break;
            }
            $arrServices[$sServicio]['status_desc'] = strtoupper($arrServices[$sServicio]['status_desc']);
            $arrServices[$sServicio]['icon'] = isset($listaIconos[$sServicio]) ? $listaIconos[$sServicio] : $sIconoDesconocido;
            $arrServices[$sServicio]['name_service'] = _tr($arrServices[$sServicio]['name_service']);
            $arrServices[$sServicio]['status_service_icon'] = (in_array($arrServices[$sServicio]['status_service'], array('OK', 'Shutdown'))) ? 'icon_arrowdown.png' : 'icon_arrowdown-disabled.png';
            $arrServices[$sServicio]['pointer_style'] = (in_array($arrServices[$sServicio]['status_service'], array('OK', 'Shutdown'))) ? 'pointer' : '';
        }
        $smarty->assign('services', $arrServices);

        $local_templates_dir = dirname($_SERVER['SCRIPT_FILENAME'])."/modules/$module_name/applets/ProcessesStatus/tpl";
        $respuesta['html'] = $smarty->fetch("$local_templates_dir/process_status.tpl");
    
        $json = new Services_JSON();
        Header('Content-Type: application/json');
        return $json->encode($respuesta);
    }

    function handleJSON_processcontrol_stop($smarty, $module_name, $appletlist)
    {
        return $this->_controlServicio('stop');
    }

    function handleJSON_processcontrol_start($smarty, $module_name, $appletlist)
    {
        return $this->_controlServicio('start');
    }

    function handleJSON_processcontrol_restart($smarty, $module_name, $appletlist)
    {
        return $this->_controlServicio('restart');
    }

    function handleJSON_processcontrol_activate($smarty, $module_name, $appletlist)
    {
        return $this->_controlServicio('on');
    }

    function handleJSON_processcontrol_deactivate($smarty, $module_name, $appletlist)
    {
        return $this->_controlServicio('off');
    }

    private function _controlServicio($sAccion)
    {
        global $arrConf;
        
        $respuesta = array(
            'status'    =>  'success',
            'message'   =>  '(no message)',
        );

        $acciones = array('start', 'restart', 'stop', 'on', 'off');
        $servicios = array(
            'Asterisk'  =>  'asterisk',
            'MySQL'     =>  'mysqld',
            'Apache'    =>  'httpd',
        );
        if (!isset($_REQUEST['process'])) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Invalid request');
        } elseif (!in_array($_REQUEST['process'], array_keys($servicios))) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Invalid service');
        } elseif (!in_array($sAccion, $acciones)) {
            $respuesta['status'] = 'error';
            $respuesta['message'] = _tr('Invalid process action');
        } else {
            $pDBACL = new paloDB($arrConf['issabel_dsn']['acl']);
            if (!empty($pDBACL->errMsg)) {
                $respuesta['status'] = 'error';
                $respuesta['message'] = "ERROR DE DB: $pDBACL->errMsg";
            } else {
                $pACL = new paloACL($pDBACL);
                if (!empty($pACL->errMsg)) {
                    $respuesta['status'] = 'error';
                    $respuesta['message'] = "ERROR DE ACL: $pACL->errMsg";
                } elseif (!$pACL->isUserAdministratorGroup($_SESSION['issabel_user'])) {
                    $respuesta['status'] = 'error';
                    $respuesta['message'] = _tr('Process control restricted to administrators');
                } else {
                    $flag = 0;
                    $sServicio = $_REQUEST['process'];
                    $output = $retval = NULL;
                    if (($sAccion == 'off') || ($sAccion == 'on')) {
                        exec('/usr/bin/issabel-helper rchkconfig --level 3 '.
                            escapeshellarg($servicios[$sServicio]).' '.
                            escapeshellarg($sAccion), $output, $retval);    
                
                        $arrServices = $this->getStatusServices();  
                        if ((($arrServices[$sServicio]["status_service"] == "Shutdown") && ($sAccion == 'on')) ||
                            (($arrServices[$sServicio]["status_service"] == "OK") && ($sAccion == 'off')))
                            $sAccion = ($sAccion == 'off') ? 'stop' : 'start';
                        else
                            $flag = 1;
                    }
                    if ($flag != 1)
                        exec('sudo -u root service generic-cloexec '.$servicios[$sServicio].' '.$sAccion.' 1>/dev/null 2>/dev/null');
                }
            }
        }
 
        $json = new Services_JSON();
        Header('Content-Type: application/json');
        return $json->encode($respuesta);
    }

    private function getStatusServices()
    {
        /* Issabel 6: solo se monitorean los servicios críticos de la plataforma.
         * (Telefonía/hardware, IM, fax y correo se retiraron; ver GUItodo/AGENTS.)
         * A futuro: Issabel Network e Issabel Webphone (ver TODO.md). */
        $arrSERVICES["Asterisk"]["status_service"] = $this->_existPID_ByFile("/var/run/asterisk/asterisk.pid","asterisk");
        $arrSERVICES["Asterisk"]["activate"] = $this->_isActivate("asterisk");
        $arrSERVICES["Asterisk"]["name_service"]   = "Telephony Service";

        $arrSERVICES["MySQL"]["status_service"]    = $this->_existPID_ByCMD(array("mysqld", "mariadbd"),array("mysqld", "mariadb"));
        $arrSERVICES["MySQL"]["activate"]      = $this->_isActivate(array("mysqld", "mariadb"));
        $arrSERVICES["MySQL"]["name_service"]      = "Database Service";

        $arrSERVICES["Apache"]["status_service"]   = $this->_existPID_ByCMD('httpd',"httpd");
        $arrSERVICES["Apache"]["activate"]     = $this->_isActivate("httpd");
        $arrSERVICES["Apache"]["name_service"]     = "Web Server";

        return $arrSERVICES;
    }

    private function _existPID_ByFile($filePID, $nameService)
    {
        if (!$this->_existService($nameService)) return "Not_exists";
        if (file_exists($filePID)) {
            $pid = trim(file_get_contents($filePID));
            return (is_dir("/proc/$pid")) ? 'OK' : 'Shutdown';
        }
        return "Shutdown";
    }

    private function _existPID_ByCMD($serviceName, $nameService)
    {
        if (!is_array($nameService)) $nameService = array($nameService);
        if (!$this->_existService($nameService)) return "Not_exists";
        if (!is_array($serviceName)) $serviceName = array($serviceName);
        $cmd = '/usr/sbin/pidof ' . implode(' ', array_map('escapeshellarg', $serviceName));
        foreach (explode(' ', trim(`$cmd`)) as $pid) {
            if (ctype_digit($pid) && (is_dir("/proc/$pid"))) return 'OK';
        }
        return 'Shutdown';
    }

    private function _existService($nameService)
    {
        if (!is_array($nameService)) $nameService = array($nameService);
        foreach ($nameService as $ns) {
            if (file_exists("/usr/lib/systemd/system/{$ns}.service"))
                return TRUE;
            if (file_exists("/etc/rc.d/init.d/{$ns}"))
                return TRUE;
        }
        return FALSE;
    }

    private function _isActivate($process)
    {
        if (!is_array($this->_initscript_cache)) {
            $this->_initscript_cache = array();
            
            // Esta lista asume systemd
            foreach (glob('/etc/systemd/system/multi-user.target.wants/*.service') as $path) {
                $regs = NULL;
                if (preg_match('|([^/]+)\.service$|', $path, $regs))
                    $this->_initscript_cache[] = $regs[1];
            }
            foreach (glob('/usr/lib/systemd/system/*.service') as $path) {
                $regs = NULL;
                if (preg_match('|([^/]+)\.service$|', $path, $regs))
                    $this->_initscript_cache[] = $regs[1];
            }
            
            // Esta lista asume scripts SysV
            foreach (glob('/etc/rc3.d/S*') as $path) {
                $regs = NULL;
                if (preg_match('|/S\d+(\S+)$|', $path, $regs))
                    $this->_initscript_cache[] = $regs[1];
            }
        }

        if (!is_array($process)) $process = array($process);
        foreach ($process as $p) {
            if (in_array($p, $this->_initscript_cache)) return 1;
        }
        return 0;
    }

}
?>
