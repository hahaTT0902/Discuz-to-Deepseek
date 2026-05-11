<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once dirname(__FILE__) . '/api/DiscuzToDeepseekUtils.class.php';

class plugin_discuz_to_deepseek
{
    protected function parseEventTid($param)
    {
        if (is_array($param)) {
            if (isset($param['tid'])) {
                return intval($param['tid']);
            }
            if (isset($param['thread']) && is_array($param['thread']) && isset($param['thread']['tid'])) {
                return intval($param['thread']['tid']);
            }
        }

        if (isset($_GET['tid'])) {
            return intval($_GET['tid']);
        }
        if (isset($_GET['ptid'])) {
            return intval($_GET['ptid']);
        }
        return 0;
    }

    protected function triggerPostEventAutoReply($param, $isGroup, $isReply)
    {
        $cache = DiscuzToDeepseekUtils::pluginConfig();
        if (empty($cache['openai'])) {
            return;
        }
        if ($isReply && empty($cache['openautoreply'])) {
            return;
        }

        $tid = $this->parseEventTid($param);
        if ($tid > 0) {
            DiscuzToDeepseekUtils::triggerAutoReply($tid, $isGroup);
        }
    }

    protected function renderThreadAutoReply($isGroup)
    {
        $cache = DiscuzToDeepseekUtils::pluginConfig();
        if (!DiscuzToDeepseekUtils::canRenderThread($cache, $isGroup)) {
            return '';
        }

        $tid = isset($_GET['tid']) ? $_GET['tid'] : 0;
        $url = DiscuzToDeepseekUtils::buildThreadUrl($tid, $isGroup);
        return DiscuzToDeepseekUtils::renderAutoScript($url, !empty($cache['openonload']));
    }

    protected function renderArticleAutoReply()
    {
        $cache = DiscuzToDeepseekUtils::pluginConfig();
        $aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
        if (!$aid || !DiscuzToDeepseekUtils::canRenderArticle($cache)) {
            return '';
        }

        $url = DiscuzToDeepseekUtils::buildArticleUrl($aid);
        return DiscuzToDeepseekUtils::renderAutoScript($url, !empty($cache['openonload']));
    }
}

class plugin_discuz_to_deepseek_forum extends plugin_discuz_to_deepseek
{
    public function post_newthread_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, false, false);
    }

    public function post_reply_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, false, true);
    }

    public function viewthread_bottom_output($a)
    {
        return $this->renderThreadAutoReply(false);
    }
}

class plugin_discuz_to_deepseek_group extends plugin_discuz_to_deepseek
{
    public function post_newthread_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, true, false);
    }

    public function post_reply_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, true, true);
    }

    public function viewthread_bottom_output($a)
    {
        return $this->renderThreadAutoReply(true);
    }
}

class plugin_discuz_to_deepseek_portal extends plugin_discuz_to_deepseek
{
    public function view_article_content_output($a)
    {
        return $this->renderArticleAutoReply();
    }
}

?>
