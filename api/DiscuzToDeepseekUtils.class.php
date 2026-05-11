<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class DiscuzToDeepseekUtils
{
    const IDENTIFIER = 'discuz_to_deepseek';

    /**
     * 读取插件配置缓存（Discuz 将插件变量缓存在 $_G['cache']['plugin'][identifier]）。
     */
    public static function pluginConfig()
    {
        global $_G;
        $cache = isset($_G['cache']['plugin'][self::IDENTIFIER]) ? $_G['cache']['plugin'][self::IDENTIFIER] : null;
        if (is_array($cache)) {
            return $cache;
        }
        // fallback: try getglobal path used by some Discuz versions
        $cache = getglobal('plugin/' . self::IDENTIFIER);
        return is_array($cache) ? $cache : array();
    }

    /**
     * 判断用户组是否在允许列表中；列表为空则放行所有用户组。
     */
    public static function isGroupAllowed($cache, $groupid)
    {
        $groups = self::configArray($cache, 'groups');
        if (empty($groups)) {
            return true;
        }
        return in_array(intval($groupid), $groups);
    }

    /**
     * 判断版块是否在允许列表中；列表为空则放行所有版块。
     */
    public static function isForumAllowed($cache, $fid)
    {
        $forums = self::configArray($cache, 'forums');
        if (empty($forums)) {
            return true;
        }
        return in_array(intval($fid), $forums);
    }

    /**
     * 将插件配置中的某个键解析为整数数组。
     */
    public static function configArray($cache, $key)
    {
        $val = isset($cache[$key]) ? $cache[$key] : '';
        if (is_array($val)) {
            return array_map('intval', array_filter($val));
        }
        return self::configCsvInts($val);
    }

    /**
     * 将逗号分隔字符串解析为正整数数组（去重、过滤零值）。
     */
    public static function configCsvInts($str)
    {
        $str = trim((string)$str);
        if ($str === '') {
            return array();
        }
        $result = array();
        foreach (explode(',', $str) as $part) {
            $n = intval(trim($part));
            if ($n > 0) {
                $result[] = $n;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * 判断是否应在帖子页注入自动回帖脚本。
     *
     * @param array $cache   插件配置
     * @param bool  $isGroup 是否为群组帖子
     */
    public static function canRenderThread($cache, $isGroup)
    {
        global $_G;

        if (empty($cache['openai'])) {
            return false;
        }
        if ($isGroup && empty($cache['opengroup'])) {
            return false;
        }
        // 帖子已被屏蔽则跳过
        if (!empty($_G['thread']) && intval($_G['thread']['displayorder']) < 0) {
            return false;
        }
        // 有附件且配置为跳过
        if (!empty($cache['openattach']) && !empty($_G['thread']['attachment'])) {
            return false;
        }
        if (!self::isGroupAllowed($cache, $_G['groupid'])) {
            return false;
        }
        return $isGroup || self::isForumAllowed($cache, $_G['fid']);
    }

    /**
     * 判断是否应在门户文章页注入自动回帖脚本。
     */
    public static function canRenderArticle($cache)
    {
        global $_G;
        return !empty($cache['openai'])
            && !empty($cache['openarticle'])
            && self::isGroupAllowed($cache, $_G['groupid']);
    }

    /**
     * 构建帖子自动回帖请求 URL。
     *
     * @param int  $tid     帖子 ID
     * @param bool $isGroup 是否为群组帖子
     */
    public static function buildThreadUrl($tid, $isGroup)
    {
        $url = 'plugin.php?id=' . self::IDENTIFIER . '&tid=' . intval($tid) . '&formhash=' . FORMHASH;
        if ($isGroup) {
            $url .= '&come=group';
        }
        return $url;
    }

    /**
     * 构建门户文章自动回帖请求 URL。
     *
     * @param int $aid 文章 ID
     */
    public static function buildArticleUrl($aid)
    {
        return 'plugin.php?id=' . self::IDENTIFIER . '&come=article&aid=' . intval($aid) . '&formhash=' . FORMHASH;
    }

    /**
     * 生成注入页面的 JavaScript 脚本片段，用于异步加载插件接口。
     *
     * @param string $url       插件接口 URL
     * @param bool   $openonload 是否等待 window load 事件后再加载
     * @return string HTML 片段
     */
    public static function renderAutoScript($url, $openonload)
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        if ($openonload) {
            return '<script>window.addEventListener("load",function(){'
                . 'var s=document.createElement("script");'
                . 's.src="' . $safeUrl . '";'
                . 'document.body.appendChild(s);'
                . '});</script>';
        }
        return '<script async src="' . $safeUrl . '"></script>';
    }

    /**
     * 在发帖成功后，异步触发插件入口处理自动回帖。
     *
     * @param int  $tid     主题 ID
     * @param bool $isGroup 是否群组帖子
     * @return bool
     */
    public static function triggerAutoReply($tid, $isGroup)
    {
        global $_G;

        $cache = self::pluginConfig();
        $tid = intval($tid);
        if ($tid <= 0 || empty($cache['openai'])) {
            return false;
        }

        if ($isGroup && empty($cache['opengroup'])) {
            return false;
        }

        if (!self::isGroupAllowed($cache, isset($_G['groupid']) ? $_G['groupid'] : 0)) {
            return false;
        }

        $url = self::buildThreadUrl($tid, $isGroup);
        return self::asyncGet($url);
    }

    /**
     * 向站内 URL 发起异步 GET 请求，不阻塞当前发帖流程。
     *
     * @param string $relativeUrl 站内相对 URL
     * @return bool
     */
    public static function asyncGet($relativeUrl)
    {
        global $_G;

        if (!function_exists('curl_init') || empty($_G['siteurl'])) {
            return false;
        }

        $url = rtrim($_G['siteurl'], '/') . '/' . ltrim($relativeUrl, '/');
        $curl = curl_init($url);
        if (!$curl) {
            return false;
        }

        $headers = array('Connection: close');
        if (!empty($_SERVER['HTTP_COOKIE'])) {
            $headers[] = 'Cookie: ' . $_SERVER['HTTP_COOKIE'];
        }

        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $ok = curl_exec($curl) !== false;
        curl_close($curl);

        return $ok;
    }

    /**
     * 查找插件可选组件文件（components/ 目录下）。
     *
     * @param string $name 组件名（仅允许字母、数字、下划线、连字符）
     * @return string|false 文件绝对路径，不存在则返回 false
     */
    public static function componentFile($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$name);
        if ($name === '') {
            return false;
        }
        $path = dirname(dirname(__FILE__)) . '/components/' . $name . '.php';
        return file_exists($path) ? $path : false;
    }

    /**
     * 将调试或错误信息写入插件日志表（仅在 opendebug 开启时写入）。
     *
     * @param bool   $opendebug 是否写日志
     * @param int    $tid       帖子/文章 ID
     * @param mixed  $message   日志内容
     */
    public static function debug($opendebug, $tid, $message)
    {
        if (!$opendebug) {
            return;
        }
        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        if (strlen($message) > 65535) {
            $message = substr($message, 0, 65535);
        }
        C::t('#' . self::IDENTIFIER . '#discuz_to_deepseek_error')->insert(array(
            'tid'     => intval($tid),
            'message' => $message,
            'addtime' => TIMESTAMP,
        ));
    }

    /**
     * 向后兼容别名：等同于 debug()。
     */
    public static function runtimeLog($cache, $tid, $message)
    {
        self::debug(!empty($cache['opendebug']), $tid, $message);
    }

    /**
     * GBK/UTF-8 字符集转换辅助。
     */
    public static function text($text)
    {
        return defined('CHARSET') && strtolower(CHARSET) == 'gbk' ? diconv($text, 'utf-8', 'gbk') : $text;
    }
}
