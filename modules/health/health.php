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
  | Endpoint JSON de solo lectura del Health Center (DASHBOARDtodo.md §12).|
  |                                                                     |
  | Se consume en:  https://<host>/modules/health/health.php            |
  | Autenticación:  cabecera `X-Health-Token` (recomendado) o `?token=`. |
  |                 El token y el ON/OFF viven en settings.db.          |
  | Apagado por defecto.                                                |
  +----------------------------------------------------------------------+
*/

$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== ''
    ? $_SERVER['DOCUMENT_ROOT']
    : dirname(dirname(dirname(__FILE__)));

require_once "$documentRoot/libs/misc.lib.php";
require_once "$documentRoot/configs/default.conf.php";
require_once "$documentRoot/libs/paloSantoDB.class.php";
require_once "$documentRoot/libs/paloSantoJSON.class.php";
require_once "$documentRoot/modules/health/libs/issabelHealth.class.php";

function health_api_out($code, $payload)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $json = new Services_JSON();
    echo $json->encode($payload);
    exit;
}

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
if (!in_array($method, array('GET', 'HEAD'), TRUE)) {
    health_api_out(405, array('status' => 'error', 'message' => 'Method not allowed'));
}

$h = new issabelHealth();

// Apagado: no revelamos que existe.
if (!$h->apiEnabled()) {
    health_api_out(404, array('status' => 'error', 'message' => 'Not found'));
}

// Token por cabecera (recomendado) o por query string.
$token = '';
if (isset($_SERVER['HTTP_X_HEALTH_TOKEN'])) {
    $token = $_SERVER['HTTP_X_HEALTH_TOKEN'];
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
}

if (!$h->apiTokenValid($token)) {
    header('WWW-Authenticate: X-Health-Token');
    health_api_out(401, array('status' => 'error', 'message' => 'Invalid or missing token'));
}

health_api_out(200, $h->apiPayload());
