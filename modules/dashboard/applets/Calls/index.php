<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 6.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2026 Issabel Foundation                                |
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  +----------------------------------------------------------------------+
  | Applet Calls (dashboard): llamadas recientes de la extensión del      |
  | usuario, entrantes y salientes. Ver DASHBOARDtodo.md §12.            |
  +----------------------------------------------------------------------+
*/
global $arrConf;
require_once "modules/{$module_name}/libs/Applet_ReportForExtension.class.php";
require_once "modules/{$module_name}/libs/issabelDashboardFormat.class.php";

define('MAX_CALL_RECORDS', 8);

class Applet_Calls extends Applet_ReportForExtension
{
    protected function _formatReportForExtension($smarty, $module_name, $extension, &$respuesta)
    {
        $smarty->assign(array(
            'LABEL_WHEN'       => _tr('Time'),
            'LABEL_PEER'       => _tr('Number'),
            'LABEL_STATUS'     => _tr('Status'),
            'LABEL_DURATION'   => _tr('Duration'),
            'LABEL_VIEW_ALL'   => _tr('View all'),
            'LABEL_IN'         => _tr('Incoming'),
            'LABEL_OUT'        => _tr('Outgoing'),
        ));

        $pDB = new paloDB(generarDSNSistema('asteriskuser', 'asteriskcdrdb'));
        if (!empty($pDB->errMsg)) {
            $respuesta['status']  = 'error';
            $respuesta['message'] = _tr('Failed to access call list.');
            return;
        }

        $sql = <<<SQL_LLAMADAS
SELECT calldate, src, dst, disposition, duration, (src = ?) AS is_out
FROM cdr
WHERE src = ? OR dst = ? OR SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1) = ?
ORDER BY calldate DESC
LIMIT ?
SQL_LLAMADAS;
        $recordset = $pDB->fetchTable($sql, TRUE,
            array($extension, $extension, $extension, $extension, MAX_CALL_RECORDS));
        if (!is_array($recordset)) {
            $respuesta['status']  = 'error';
            $respuesta['message'] = _tr('Failed to access call list.');
            return;
        }

        $smarty->assign('rows', $this->_buildRows($recordset, $extension));
        $smarty->assign('LABEL_NO_CALLS', _tr("No calls received."));

        $local_templates_dir = dirname($_SERVER['SCRIPT_FILENAME'])."/modules/$module_name/applets/Calls/tpl";
        $respuesta['html'] = $smarty->fetch("$local_templates_dir/calls.tpl");
    }

    /**
     * CDR -> filas para la plantilla. Función aparte para poder probarla sin
     * base de datos.
     */
    protected function _buildRows($recordset, $extension)
    {
        $rows = array();
        foreach ((array)$recordset as $r) {
            $isOut = (!empty($r['is_out']) || $r['src'] === $extension);
            $peer  = $isOut ? $r['dst'] : $r['src'];
            $st    = self::_statusKey($r['disposition']);
            $rows[] = array(
                'when'         => issabelDashboardFormat::ago(strtotime($r['calldate'])),
                'dir'          => $isOut ? 'out' : 'in',
                'peer'         => ($peer === '' ? _tr('Unknown') : $peer),
                'status'       => $st,
                'badge'        => self::_statusBadge($st),
                'status_label' => self::_statusLabel($st),
                'duration'     => ($st === 'answered' ? issabelDashboardFormat::duration($r['duration']) : ''),
            );
        }
        return $rows;
    }

    private static function _statusKey($disposition)
    {
        switch (strtoupper(trim((string)$disposition))) {
        case 'ANSWERED':   return 'answered';
        case 'NO ANSWER':  return 'noanswer';
        case 'BUSY':       return 'busy';
        case 'FAILED':     return 'failed';
        case 'CONGESTION': return 'congestion';
        default:           return 'other';
        }
    }

    private static function _statusBadge($st)
    {
        switch ($st) {
        case 'answered':   return 'bg-green-lt';
        case 'noanswer':   return 'bg-red-lt';
        case 'busy':       return 'bg-yellow-lt';
        case 'failed':     return 'bg-red-lt';
        case 'congestion': return 'bg-orange-lt';
        default:           return 'bg-secondary-lt';
        }
    }

    private static function _statusLabel($st)
    {
        switch ($st) {
        case 'answered':   return _tr('Answered');
        case 'noanswer':   return _tr('No answer');
        case 'busy':       return _tr('Busy');
        case 'failed':     return _tr('Failed');
        case 'congestion': return _tr('Congestion');
        default:           return _tr('Unknown');
        }
    }
}
?>
