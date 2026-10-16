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
  | Feed de noticias del dashboard (DASHBOARDtodo.md §9). Reemplaza al   |
  | applet News: ahora es un ticker que alimenta el shell del dashboard.  |
  | Usa SimplePie (issabel-php-simplepie) con caché y timeout.            |
  +----------------------------------------------------------------------+
*/

class issabelNewsFeed
{
    var $errMsg = NULL;

    /** URL del canal (settings.db) o el valor por defecto. */
    private function _channel()
    {
        global $arrConf;
        $default = 'https://cloud.issabel.org/news.php';
        if (empty($arrConf['issabel_dsn']['settings'])) return $default;

        $pDB = new paloDB($arrConf['issabel_dsn']['settings']);
        if (!empty($pDB->errMsg)) return $default;

        $channel = function_exists('get_key_settings')
            ? get_key_settings($pDB, 'issabel_news_channel') : '';
        if ($channel === '' || $channel === FALSE) {
            if (function_exists('set_key_settings')) set_key_settings($pDB, 'issabel_news_channel', $default);
            $channel = $default;
        }
        return $channel;
    }

    private function _loadSimplePie()
    {
        if (class_exists('SimplePie')) return TRUE;
        foreach (array('/usr/share/php/php-simplepie/autoloader.php', 'php-simplepie/autoloader.php') as $f) {
            if (file_exists($f)) { require_once $f; break; }
        }
        return class_exists('SimplePie');
    }

    /**
     * Lee el feed y devuelve una lista de titulares.
     * @return array|FALSE  [{title, link, date, description}] o FALSE en error.
     */
    function fetch($limit = 8)
    {
        $this->errMsg = NULL;

        if (!$this->_loadSimplePie()) {
            $this->errMsg = 'SimplePie is not installed';
            return FALSE;
        }

        $channel = $this->_channel();
        $lang = function_exists('get_language') ? get_language() : 'en';
        $langParam = ($lang === 'es') ? 'es' : 'en';

        $feed = new SimplePie();
        $feed->set_feed_url("$channel?lang=$langParam");
        $feed->set_output_encoding('UTF-8');
        $feed->enable_order_by_date(TRUE);
        if (method_exists($feed, 'set_timeout'))  $feed->set_timeout(5);
        if (method_exists($feed, 'set_useragent')) $feed->set_useragent('Issabel6/1.0 (+https://www.issabel.org)');

        $cachedir = '/tmp/rss-cache';
        if (!is_dir($cachedir) && !@mkdir($cachedir)) {
            $feed->enable_cache(FALSE);
        } else {
            $feed->enable_cache(TRUE);
            $feed->set_cache_location($cachedir);
            if (method_exists($feed, 'set_cache_duration')) $feed->set_cache_duration(1800);
        }

        $feed->init();

        if ($feed->error()) {
            // Un fallo de red no debe romper el dashboard.
            $this->errMsg = 'Could not fetch the news feed';
            return FALSE;
        }

        $items = array();
        foreach ($feed->get_items(0, $limit) as $item) {
            $link = (string)$item->get_link();
            if (!preg_match('#^https?://#i', $link)) continue;   // solo http(s)
            $items[] = array(
                'title'       => (string)$item->get_title(),
                'link'        => $link,
                'date'        => (int)$item->get_date('U'),
                'description' => trim(strip_tags((string)$item->get_description())),
            );
        }
        return $items;
    }
}
?>
