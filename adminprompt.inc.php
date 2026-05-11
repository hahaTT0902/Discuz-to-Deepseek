<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$pluginIdentifier = 'apoyl_deepseekaipost';
$pluginId = apoylPromptPluginId(isset($pluginid) ? $pluginid : 0, $pluginIdentifier);
$formAction = 'plugins&operation=config&do=' . intval($pluginId) . '&identifier=' . $pluginIdentifier . '&pmod=adminprompt';
$baseurl = ADMINSCRIPT . '?action=' . $formAction;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['promptsubmit'])) {
    if (!isset($_POST['formhash']) || $_POST['formhash'] != FORMHASH) {
        cpmsg('submit_invalid', '', 'error');
    }

    $systemPrompt = isset($_POST['deepseek_system_prompt']) ? trim($_POST['deepseek_system_prompt']) : '';
    $userPrompt = isset($_POST['deepseek_user_prompt']) ? trim($_POST['deepseek_user_prompt']) : '';

    apoylPromptSaveVar($pluginId, 'deepseek_system_prompt', $systemPrompt, 'DeepSeek system prompt');
    apoylPromptSaveVar($pluginId, 'deepseek_user_prompt', $userPrompt, 'DeepSeek user prompt template');

    if (function_exists('updatecache')) {
        updatecache('plugin');
    }

    cpmsg('plugins_edit_succeed', $baseurl, 'succeed');
}

$systemPrompt = apoylPromptFetchVar($pluginId, 'deepseek_system_prompt');
$userPrompt = apoylPromptFetchVar($pluginId, 'deepseek_user_prompt');

showformheader($formAction);
showtableheader('DeepSeek Prompt');

showtablerow('', array('colspan="2"'), array(
    '<div style="color:#666;line-height:1.8;">' .
    '系统 Prompt 会作为 system message 发送。用户 Prompt 模板留空时使用原始帖子内容；填写后可使用变量：<code>{content}</code> / <code>{text}</code> 帖子内容，<code>{role}</code> 角色设定。' .
    '</div>'
));

showsetting('System Prompt', 'deepseek_system_prompt', $systemPrompt, 'textarea');
showsetting('User Prompt Template', 'deepseek_user_prompt', $userPrompt, 'textarea');
showsubmit('promptsubmit');
showtablefooter();
showformfooter();

function apoylPromptPluginId($pluginid, $identifier)
{
    $pluginid = intval($pluginid);
    if ($pluginid > 0) {
        return $pluginid;
    }

    $plugin = DB::fetch_first('SELECT pluginid FROM %t WHERE identifier=%s', array('common_plugin', $identifier));
    return $plugin ? intval($plugin['pluginid']) : 0;
}

function apoylPromptFetchVar($pluginid, $variable)
{
    if (!$pluginid) {
        return '';
    }

    $row = DB::fetch_first('SELECT value FROM %t WHERE pluginid=%d AND variable=%s', array('common_pluginvar', $pluginid, $variable));
    return $row ? $row['value'] : '';
}

function apoylPromptSaveVar($pluginid, $variable, $value, $title)
{
    if (!$pluginid) {
        return;
    }

    $where = DB::field('pluginid', $pluginid) . ' AND ' . DB::field('variable', $variable);
    $row = DB::fetch_first('SELECT pluginvarid FROM %t WHERE ' . $where, array('common_pluginvar'));
    $data = array(
        'pluginid' => $pluginid,
        'title' => $title,
        'description' => '',
        'variable' => $variable,
        'type' => 'textarea',
        'value' => $value,
        'extra' => '',
        'displayorder' => 0
    );

    if ($row) {
        DB::update('common_pluginvar', $data, $where);
    } else {
        DB::insert('common_pluginvar', $data);
    }
}

?>
