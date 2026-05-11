<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class DiscuzToDeepseekUtils
{
    public static function pluginConfig()
    {
        global $_G;
        return isset($_G['cache']['plugin']['discuz_to_deepseek']) && is_array($_G['cache']['plugin']['discuz_to_deepseek'])
            ? $_G['cache']['plugin']['discuz_to_deepseek']
            : array();
    }

    public static function configArray($cache, $key)
    {
        if (empty($cache[$key])) {
            return array();
        }

        if (is_array($cache[$key])) {
            return $cache[$key];
        }

        $value = @unserialize($cache[$key]);
        return is_array($value) ? $value : array();
    }

    public static function configCsvInts($value)
    {
        $items = array();
        foreach (explode(',', (string)$value) as $item) {
            $item = intval(trim($item));
            if ($item > 0) {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    public static function isGroupAllowed($cache, $groupid)
    {
        return in_array($groupid, self::configArray($cache, 'groups'));
    }

    public static function isForumAllowed($cache, $fid)
    {
        return in_array($fid, self::configArray($cache, 'forums'));
    }

    public static function canRenderThread($cache, $isGroup)
    {
        global $_G;

        if (empty($cache['openai']) || empty($_G['thread']) || $_G['thread']['displayorder'] < 0) {
            return false;
        }

        if ($isGroup && empty($cache['opengroup'])) {
            return false;
        }

        if (!empty($cache['openattach']) && !empty($_G['thread']['attachment'])) {
            return false;
        }

        if (!self::isGroupAllowed($cache, $_G['groupid'])) {
            return false;
        }

        return $isGroup || self::isForumAllowed($cache, $_G['fid']);
    }

    public static function canRenderArticle($cache)
    {
        global $_G;
        return !empty($cache['openarticle'])
            && !empty($cache['openai'])
            && self::isGroupAllowed($cache, $_G['groupid']);
    }

    public static function renderAutoScript($url, $openonload)
    {
        $return = '';
        $discuz_to_deepseek_url = $url;
        include template('discuz_to_deepseek:auto');
        return $return;
    }

    public static function buildThreadUrl($tid, $isGroup)
    {
        $url = 'plugin.php?id=discuz_to_deepseek';
        if ($isGroup) {
            $url .= '&come=group';
        }
        return $url . '&tid=' . intval($tid) . '&formhash=' . FORMHASH;
    }

    public static function buildArticleUrl($aid)
    {
        return 'plugin.php?id=discuz_to_deepseek:article&articleid=' . intval($aid) . '&formhash=' . FORMHASH;
    }

    public static function componentFile($filename)
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $filename)) {
            return '';
        }

        $file = dirname(__FILE__) . '/../components/' . $filename . '.php';
        return file_exists($file) ? $file : '';
    }

    public static function debug($opendebug, $tid, $message)
    {
        if (!$opendebug) {
            return;
        }

        $logTable = C::t('#discuz_to_deepseek#discuz_to_deepseek_error');
        $logTable->ensureTable();
        $logTable->insert(array(
            'tid' => intval($tid),
            'message' => (string)$message,
            'addtime' => TIMESTAMP
        ));
    }
}

?>
