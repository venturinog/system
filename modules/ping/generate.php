<?php 
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6                                                    |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2017 Issabel Foundation                                |
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
  | The Initial Developer is Issabel Foundation                          |
  +----------------------------------------------------------------------+
*/
$module_name=basename(getcwd());
$documentRoot = $_SERVER["DOCUMENT_ROOT"];
include_once "$documentRoot/libs/paloSantoDB.class.php";
include_once "$documentRoot/libs/paloSantoACL.class.php";
require_once dirname(__FILE__)."/libs/issabelPingHost.class.php";
session_name("issabelSession");
session_start();
$issabel_user = (isset($_SESSION["issabel_user"]))?$_SESSION["issabel_user"]:null;
$pDB = new paloDB("sqlite3:////var/www/db/acl.db");
$pACL = new paloACL($pDB);
$isUserAuth = $pACL->isUserAuthorized($issabel_user,"access",$module_name);
unset($_SESSION);
session_commit();
if(!$isUserAuth) { die('Unauthorized'); }

header('Content-Type: text/html; charset=UTF-8');

// El iframe es un documento aparte: no hereda el tema, así que trae su propia
// paleta (la misma que el primitivo .iss-console del tema Modern). Con
// $withCursor=true pinta un cursor que titila hasta que llega la salida.
function issabelPingConsoleHead($withCursor = false)
{
    $html = "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\">\n"
        ."<style>\n"
        ."html,body{margin:0;padding:0;background:#0d1117;}\n"
        ."body{padding:10px;color:#e6edf3;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.45;}\n"
        ."pre{margin:0;white-space:pre-wrap;word-break:break-word;color:inherit;font-family:inherit;font-size:inherit;}\n";
    if ($withCursor) {
        $html .= "@keyframes iss-blink{0%,49%{opacity:1}50%,100%{opacity:0}}\n"
            .".iss-ping-cursor{display:inline-block;width:.6em;height:1em;vertical-align:text-bottom;background:#e6edf3;animation:iss-blink 1s step-end infinite;}\n"
            ."@media (prefers-reduced-motion: reduce){.iss-ping-cursor{animation:none;opacity:1;}}\n";
    }
    $html .= "</style></head><body><pre>";
    if ($withCursor) {
        $html .= "<span id=\"iss-ping-cursor\" class=\"iss-ping-cursor\"></span>";
    }
    return $html;
}

function issabelPingConsoleFoot()
{
    return "</pre></body></html>\n";
}

function issabelPingConsoleMessage($message)
{
    return issabelPingConsoleHead()
        .htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        .issabelPingConsoleFoot();
}

$send = isset($_REQUEST['send']) ? $_REQUEST['send'] : '';
$host = isset($_REQUEST['hostname']) ? trim($_REQUEST['hostname']) : '';

$cmd = '';
if ($send === 'ping' && $host !== '') {
    $cmd = "/usr/sbin/ping -c 4 ".escapeshellarg($host);
} else if ($send === 'tracepath' && $host !== '') {
    $cmd = "/usr/sbin/tracepath ".escapeshellarg($host);
}

// Defensa en profundidad: el host se valida aquí aunque index.php ya lo haya
// comprobado, porque este script también es accesible de forma directa.
if ($cmd === '' || !issabelPingHost::valid($host)) {
    echo issabelPingConsoleMessage('Invalid host name or IP address.');
    exit;
}

echo issabelPingConsoleHead(true);

$file = popen($cmd." 2>&1", "r");
if ($file !== false) {
    $first = true;
    while (!feof($file)) {
        $line = fgets($file);
        if ($line === false) break;

        // En cuanto llega la primera línea, quitar el cursor y escribirlo.
        if ($first) {
            echo "<script>var c=document.getElementById('iss-ping-cursor');if(c)c.parentNode.removeChild(c);</script>";
            $first = false;
        }
        echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8');

        if (ob_get_level() > 0) ob_flush();
        flush();
    }
    pclose($file);
}

echo issabelPingConsoleFoot();

?>
