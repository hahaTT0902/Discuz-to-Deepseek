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
    protected function canRenderThreadWithReason($cache, $isGroup, &$reason)
    {
        global $_G;

        if (empty($cache['openai'])) {
            $reason = 'render_skip_openai_off';
            return false;
        }
        if ($isGroup && empty($cache['opengroup'])) {
            $reason = 'render_skip_group_off';
            return false;
        }
        if (!empty($_G['thread']) && intval($_G['thread']['displayorder']) < 0) {
            $reason = 'render_skip_thread_hidden';
            return false;
        }
        if (!empty($cache['openattach']) && !empty($_G['thread']['attachment'])) {
            $reason = 'render_skip_attachment';
            return false;
        }
        if (!DiscuzToDeepseekUtils::isGroupAllowed($cache, isset($_G['groupid']) ? $_G['groupid'] : 0)) {
            $reason = 'render_skip_group_denied';
            return false;
        }
        if (!$isGroup && !DiscuzToDeepseekUtils::isForumAllowed($cache, isset($_G['fid']) ? $_G['fid'] : 0)) {
            $reason = 'render_skip_forum_denied';
            return false;
        }

        $reason = 'render_ready';
        return true;
    }

    protected function extractTidFromValue($value)
    {
        if (is_numeric($value)) {
            $tid = intval($value);
            return $tid > 0 ? $tid : 0;
        }

        if (!is_array($value)) {
            return 0;
        }

        if (isset($value['tid']) && intval($value['tid']) > 0) {
            return intval($value['tid']);
        }

        foreach ($value as $item) {
            $tid = $this->extractTidFromValue($item);
            if ($tid > 0) {
                return $tid;
            }
        }

        return 0;
    }

    protected function resolveCurrentTid()
    {
        global $_G;

        if (isset($_GET['tid']) && intval($_GET['tid']) > 0) {
            return intval($_GET['tid']);
        }
        if (isset($_G['tid']) && intval($_G['tid']) > 0) {
            return intval($_G['tid']);
        }
        if (isset($_G['thread']) && is_array($_G['thread']) && isset($_G['thread']['tid']) && intval($_G['thread']['tid']) > 0) {
            return intval($_G['thread']['tid']);
        }
        return 0;
    }

    protected function parseEventTid($param)
    {
        $tid = $this->extractTidFromValue($param);
        if ($tid > 0) {
            return $tid;
        }

        if (isset($_GET['tid']) && intval($_GET['tid']) > 0) {
            return intval($_GET['tid']);
        }
        if (isset($_GET['ptid']) && intval($_GET['ptid']) > 0) {
            return intval($_GET['ptid']);
        }
        return $this->resolveCurrentTid();
    }

    protected function triggerPostEventAutoReply($param, $isGroup, $isReply)
    {
        $cache = DiscuzToDeepseekUtils::pluginConfig();
        $debugEnabled = DiscuzToDeepseekUtils::isDebugEnabled($cache);
        if (empty($cache['openai'])) {
            return;
        }
        if ($isReply && empty($cache['openautoreply'])) {
            return;
        }

        $tid = $this->parseEventTid($param);
        if ($tid <= 0) {
            DiscuzToDeepseekUtils::debug($debugEnabled, 0, 'event_tid_empty');
            return;
        }

        DiscuzToDeepseekUtils::debug($debugEnabled, $tid, 'event_received:' . ($isReply ? 'reply' : 'newthread'));

        if (!DiscuzToDeepseekUtils::triggerAutoReply($tid, $isGroup)) {
            DiscuzToDeepseekUtils::debug($debugEnabled, $tid, 'event_trigger_failed');
        }
    }

    protected function renderThreadAutoReply($isGroup)
    {
        $cache = DiscuzToDeepseekUtils::pluginConfig();
        $debugEnabled = DiscuzToDeepseekUtils::isDebugEnabled($cache);
        $reason = '';
        if (!$this->canRenderThreadWithReason($cache, $isGroup, $reason)) {
            DiscuzToDeepseekUtils::debug($debugEnabled, 0, $reason);
            return '';
        }

        $tid = $this->resolveCurrentTid();
        if ($tid <= 0) {
            DiscuzToDeepseekUtils::debug($debugEnabled, 0, 'render_skip_tid_empty');
            return '';
        }
        $url = DiscuzToDeepseekUtils::buildThreadUrl($tid, $isGroup);
        DiscuzToDeepseekUtils::debug($debugEnabled, $tid, 'render_injected:' . $url);
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
    public function global_footer()
    {
        if (!isset($_GET['mod']) || $_GET['mod'] !== 'viewthread') {
            return '';
        }
        return $this->renderThreadAutoReply(false);
    }

    public function post_newthread_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, false, false);
    }

    public function post_reply_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, false, true);
    }

    public function post_newthread_end($param)
    {
        $this->triggerPostEventAutoReply($param, false, false);
    }

    public function post_reply_end($param)
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
    public function global_footer()
    {
        if (!isset($_GET['mod']) || $_GET['mod'] !== 'viewthread') {
            return '';
        }
        return $this->renderThreadAutoReply(true);
    }

    public function post_newthread_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, true, false);
    }

    public function post_reply_succeed($param)
    {
        $this->triggerPostEventAutoReply($param, true, true);
    }

    public function post_newthread_end($param)
    {
        $this->triggerPostEventAutoReply($param, true, false);
    }

    public function post_reply_end($param)
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
