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
    public function viewthread_bottom_output($a)
    {
        return $this->renderThreadAutoReply(false);
    }
}

class plugin_discuz_to_deepseek_group extends plugin_discuz_to_deepseek
{
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
