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
  | Applet Voicemails (dashboard): mensajes de voz de la extensión del    |
  | usuario. MVP: metadatos + enlace al módulo. Ver DASHBOARDtodo.md §12. |
  +----------------------------------------------------------------------+
*/
require_once "modules/{$module_name}/libs/Applet_ReportForExtension.class.php";
require_once "modules/{$module_name}/libs/issabelDashboardFormat.class.php";

define('MAX_VM_RECORDS', 8);

class Applet_Voicemails extends Applet_ReportForExtension
{
    protected function _formatReportForExtension($smarty, $module_name, $extension, &$respuesta)
    {
        $smarty->assign(array(
            'LABEL_SENDER'   => _tr('Caller'),
            'LABEL_DURATION' => _tr('Duration'),
            'LABEL_MAILBOX'  => _tr('Mailbox'),
            'LABEL_VIEW_ALL' => _tr('View all'),
            'LABEL_NEW'      => _tr('New'),
        ));

        $voicePath = "/var/spool/asterisk/voicemail/default/$extension/INBOX";
        /* glob() devuelve false si no puede resolver el patrón y el directorio
         * puede no existir (Asterisk lo crea con el contexto de voicemail):
         * ambos casos se tratan como lista vacía. */
        $files = array();
        if (is_dir($voicePath) && is_readable($voicePath)) {
            $encontrados = glob("$voicePath/*.txt");
            if (is_array($encontrados)) $files = $encontrados;
        }

        $smarty->assign('rows', $this->_buildRows($files, $extension));
        $smarty->assign('total', count($files));
        $smarty->assign('LABEL_NO_VM', _tr("No voicemails received."));

        $local_templates_dir = dirname($_SERVER['SCRIPT_FILENAME'])."/modules/$module_name/applets/Voicemails/tpl";
        $respuesta['html'] = $smarty->fetch("$local_templates_dir/voicemails.tpl");
    }

    /**
     * Archivos .txt de la INBOX -> filas para la plantilla (ordenadas de más
     * nueva a más vieja, limitadas a MAX_VM_RECORDS).
     */
    protected function _buildRows($files, $extension)
    {
        $records = array();
        foreach ((array)$files as $archivo) {
            $content = array();
            foreach ((array)@file($archivo) as $linea) {
                $regs = NULL;
                if (preg_match('/^(\w+)\s*=\s*(.*)/', trim($linea), $regs)) {
                    $content[$regs[1]] = trim($regs[2]);
                }
            }
            if (empty($content['origtime'])) continue;
            $records[$content['origtime']] = $content;
        }
        krsort($records, SORT_STRING);

        $rows = array();
        foreach (array_slice($records, 0, MAX_VM_RECORDS) as $c) {
            $caller = $c['callerid'];
            if ($caller === '' || strcasecmp($caller, 'Unknown') === 0) {
                $caller = _tr('Unknown');
            } elseif (preg_match('/^"?([^"<]*)"?\s*<([^>]+)>/', $caller, $mm)) {
                // `"Nombre" <5551234>` -> `Nombre (5551234)`
                $nombre = trim($mm[1]);
                $caller = ($nombre !== '') ? "$nombre (".trim($mm[2]).')' : trim($mm[2]);
            }
            $rows[] = array(
                'sender'   => $caller,
                'when'     => issabelDashboardFormat::ago($c['origtime']),
                'duration' => issabelDashboardFormat::duration($c['duration']),
                'mailbox'  => $extension,
            );
        }
        return $rows;
    }
}
?>
