<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS pre_plugin_discuz_to_deepseek_err (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  tid int(10) unsigned NOT NULL DEFAULT '0',
  message text NOT NULL,
  addtime int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY tid (tid),
  KEY addtime (addtime)
) ENGINE=MyISAM;
EOF;

runquery($sql);

$plugin = DB::fetch_first('SELECT pluginid FROM %t WHERE identifier=%s', array('common_plugin', 'discuz_to_deepseek'));
if ($plugin) {
    $pluginid = intval($plugin['pluginid']);
    foreach (discuzToDeepseekInstallVars() as $var) {
        $exists = DB::fetch_first(
            'SELECT pluginvarid FROM %t WHERE pluginid=%d AND variable=%s',
            array('common_pluginvar', $pluginid, $var['variable'])
        );

        $data = array(
            'pluginid' => $pluginid,
            'displayorder' => $var['displayorder'],
            'title' => $var['title'],
            'description' => $var['description'],
            'variable' => $var['variable'],
            'type' => $var['type'],
            'value' => $var['value'],
            'extra' => $var['extra'],
        );

        if ($exists) {
            DB::update('common_pluginvar', $data, DB::field('pluginvarid', $exists['pluginvarid']));
        } else {
            DB::insert('common_pluginvar', $data);
        }
    }

    if (function_exists('updatecache')) {
        updatecache('plugin');
    }
}

$finish = true;

function discuzToDeepseekInstallVars()
{
    return array(
        array('displayorder' => 1, 'title' => 'Enable Auto Reply', 'description' => 'Enable DeepSeek automatic replies.', 'variable' => 'openai', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 2, 'title' => 'DeepSeek API Key', 'description' => 'Bearer token for DeepSeek API.', 'variable' => 'apikey', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 3, 'title' => 'Posting User UIDs', 'description' => 'Comma-separated user IDs used to publish AI replies, for example: 2,3,8.', 'variable' => 'users', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 4, 'title' => 'Allowed User Groups', 'description' => 'User groups that may trigger auto replies.', 'variable' => 'groups', 'type' => 'group', 'value' => '', 'extra' => ''),
        array('displayorder' => 5, 'title' => 'Allowed Forums', 'description' => 'Forums where auto replies are enabled.', 'variable' => 'forums', 'type' => 'forum', 'value' => '', 'extra' => ''),
        array('displayorder' => 6, 'title' => 'Model', 'description' => 'DeepSeek model mode.', 'variable' => 'deepseekllm', 'type' => 'select', 'value' => '1', 'extra' => "1=deepseek-v4-flash\n2=deepseek-v4-pro"),
        array('displayorder' => 7, 'title' => 'System Prompt', 'description' => 'Optional system prompt sent to DeepSeek.', 'variable' => 'deepseek_system_prompt', 'type' => 'textarea', 'value' => '', 'extra' => ''),
        array('displayorder' => 8, 'title' => 'User Prompt Template', 'description' => 'Optional template. Variables: {content}, {text}, {role}.', 'variable' => 'deepseek_user_prompt', 'type' => 'textarea', 'value' => '', 'extra' => ''),
        array('displayorder' => 9, 'title' => 'Auto Reply Latest Post', 'description' => 'Reply to the latest visible post instead of only first post.', 'variable' => 'openautoreply', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 10, 'title' => 'Quote Source Post', 'description' => 'Add quote block before generated reply.', 'variable' => 'openquote', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 11, 'title' => 'Ignore Threads With Attachments', 'description' => 'Do not auto reply when a thread or source post has attachments.', 'variable' => 'openattach', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 12, 'title' => 'Debug Logs', 'description' => 'Write DeepSeek responses and errors to plugin logs.', 'variable' => 'opendebug', 'type' => 'radio', 'value' => '1', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 13, 'title' => 'Render After Page Load', 'description' => 'Delay script injection until the page load event.', 'variable' => 'openonload', 'type' => 'radio', 'value' => '1', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 14, 'title' => 'Enable Group Threads', 'description' => 'Enable automatic replies for Discuz group threads.', 'variable' => 'opengroup', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 15, 'title' => 'Enable Portal Articles', 'description' => 'Enable automatic replies for portal article pages.', 'variable' => 'openarticle', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 16, 'title' => 'Maximum Replies Per Thread', 'description' => '0 means unlimited.', 'variable' => 'limitnums', 'type' => 'text', 'value' => '0', 'extra' => ''),
        array('displayorder' => 17, 'title' => 'Source Content', 'description' => 'Choose the content sent to DeepSeek for the first post.', 'variable' => 'selectfirst', 'type' => 'select', 'value' => '1', 'extra' => "1=Subject only\n2=Subject and content\n3=Content only"),
        array('displayorder' => 18, 'title' => 'Moderate AI Replies', 'description' => 'Put AI replies into moderation unless the trigger group is exempt.', 'variable' => 'openinvisible', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 19, 'title' => 'Moderation Exempt Groups', 'description' => 'Groups that can publish AI replies directly when moderation is enabled.', 'variable' => 'mgroups', 'type' => 'group', 'value' => '', 'extra' => ''),
        array('displayorder' => 20, 'title' => 'Append Footer', 'description' => 'Append a footer text after generated replies.', 'variable' => 'openfrom', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 21, 'title' => 'Footer Text', 'description' => 'Footer text appended when Append Footer is enabled.', 'variable' => 'from', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 22, 'title' => 'Enable Type Limit Component', 'description' => 'Optional extension hook. Requires components/type.php.', 'variable' => 'openlimittype', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 23, 'title' => 'Enable Thread Time Limit', 'description' => 'Only reply to threads newer than the configured time.', 'variable' => 'opentime', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 24, 'title' => 'Thread Time Limit', 'description' => 'Example: 2026-01-01 00:00:00. Used only when Thread Time Limit is enabled.', 'variable' => 'limittime', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 25, 'title' => 'Enable Reply Delay', 'description' => 'Wait before replying after the latest thread activity.', 'variable' => 'opendelayreply', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 26, 'title' => 'Reply Delay Seconds', 'description' => 'Seconds or random range such as 60~300.', 'variable' => 'delaytime', 'type' => 'text', 'value' => '0', 'extra' => ''),
        array('displayorder' => 27, 'title' => 'Enable First VIP Component', 'description' => 'Optional extension hook. Requires components/firstvip.php.', 'variable' => 'openfirstvip', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 28, 'title' => 'Enable Role Component', 'description' => 'Optional extension hook. Requires components/role.php.', 'variable' => 'openrole', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 29, 'title' => 'Reply Style Limit', 'description' => 'Append a built-in style instruction to the prompt.', 'variable' => 'openlimit', 'type' => 'select', 'value' => '0', 'extra' => "0=None\n1=Concise\n2=Natural\n3=Friendly forum style"),
        array('displayorder' => 30, 'title' => 'Enable Limit Trigger Component', 'description' => 'Optional extension hook. Requires components/limittriggering.php.', 'variable' => 'openlimittriggering', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
        array('displayorder' => 31, 'title' => 'AI Platform', 'description' => 'DeepSeek is the built-in platform. Aliyun requires optional component support.', 'variable' => 'selectplatform', 'type' => 'select', 'value' => '1', 'extra' => "1=DeepSeek\n2=Aliyun component"),
        array('displayorder' => 32, 'title' => 'Aliyun API Key', 'description' => 'Used only by optional Aliyun component.', 'variable' => 'aliyunapikey', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 33, 'title' => 'Enable Aliyun Component', 'description' => 'Optional extension hook. Requires components/aliyun.php.', 'variable' => 'openaliyunds', 'type' => 'radio', 'value' => '0', 'extra' => "1=Yes\n0=No"),
    );
}

?>
