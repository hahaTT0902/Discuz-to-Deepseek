<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once dirname(__FILE__) . '/api/ApoylDeepseekAiPostUtils.class.php';

class mobileplugin_apoyl_deepseekaipost
{
    protected function renderThreadAutoReply($isGroup)
    {
        $cache = ApoylDeepseekAiPostUtils::pluginConfig();
        if (!ApoylDeepseekAiPostUtils::canRenderThread($cache, $isGroup)) {
            return '';
        }

        $tid = isset($_GET['tid']) ? $_GET['tid'] : 0;
        $url = ApoylDeepseekAiPostUtils::buildThreadUrl($tid, $isGroup);
        return ApoylDeepseekAiPostUtils::renderAutoScript($url, !empty($cache['openonload']));
    }

    protected function renderArticleAutoReply()
    {
        $cache = ApoylDeepseekAiPostUtils::pluginConfig();
        $aid = isset($_GET['aid']) ? intval($_GET['aid']) : 0;
        if (!$aid || !ApoylDeepseekAiPostUtils::canRenderArticle($cache)) {
            return '';
        }

        $url = ApoylDeepseekAiPostUtils::buildArticleUrl($aid);
        return ApoylDeepseekAiPostUtils::renderAutoScript($url, !empty($cache['openonload']));
    }
}

class mobileplugin_apoyl_deepseekaipost_forum extends mobileplugin_apoyl_deepseekaipost
{
    public function viewthread_bottom_mobile_output($a)
    {
        return $this->renderThreadAutoReply(false);
    }
}

class mobileplugin_apoyl_deepseekaipost_group extends mobileplugin_apoyl_deepseekaipost
{
    public function viewthread_bottom_mobile_output($a)
    {
        return $this->renderThreadAutoReply(true);
    }
}

class mobileplugin_apoyl_deepseekaipost_portal extends mobileplugin_apoyl_deepseekaipost
{
    public function view_article_content_mobile_output($a)
    {
        return $this->renderArticleAutoReply();
    }
}

?>
