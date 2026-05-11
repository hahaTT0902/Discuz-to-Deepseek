<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$pluginIdentifier = 'discuz_to_deepseek';
$pluginId = discuzToDeepseekPromptPluginId(isset($pluginid) ? $pluginid : 0, $pluginIdentifier);
$formAction = 'plugins&operation=config&do=' . intval($pluginId) . '&identifier=' . $pluginIdentifier . '&pmod=adminprompt';
$baseurl = ADMINSCRIPT . '?action=' . $formAction;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['promptsubmit'])) {
    if (!isset($_POST['formhash']) || $_POST['formhash'] != FORMHASH) {
        cpmsg('submit_invalid', '', 'error');
    }

    $systemPrompt = isset($_POST['deepseek_system_prompt']) ? trim($_POST['deepseek_system_prompt']) : '';
    $userPrompt = isset($_POST['deepseek_user_prompt']) ? trim($_POST['deepseek_user_prompt']) : '';

    discuzToDeepseekPromptSaveVar($pluginId, 'deepseek_system_prompt', $systemPrompt, discuzToDeepseekPromptText('DeepSeek 系统提示词'));
    discuzToDeepseekPromptSaveVar($pluginId, 'deepseek_user_prompt', $userPrompt, discuzToDeepseekPromptText('DeepSeek 用户提示词模板'));

    if (function_exists('updatecache')) {
        updatecache('plugin');
    }

    cpmsg('plugins_edit_succeed', $baseurl, 'succeed');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['promptreset'])) {
    if (!isset($_POST['formhash']) || $_POST['formhash'] != FORMHASH) {
        cpmsg('submit_invalid', '', 'error');
    }

    discuzToDeepseekPromptSaveVar($pluginId, 'deepseek_system_prompt', discuzToDeepseekPromptDefaultSystem(), discuzToDeepseekPromptText('DeepSeek 系统提示词'));
    discuzToDeepseekPromptSaveVar($pluginId, 'deepseek_user_prompt', discuzToDeepseekPromptDefaultUser(), discuzToDeepseekPromptText('DeepSeek 用户提示词模板'));

    if (function_exists('updatecache')) {
        updatecache('plugin');
    }

    cpmsg('plugins_edit_succeed', $baseurl, 'succeed');
}

$systemPrompt = discuzToDeepseekPromptFetchVar($pluginId, 'deepseek_system_prompt', discuzToDeepseekPromptDefaultSystem());
$userPrompt = discuzToDeepseekPromptFetchVar($pluginId, 'deepseek_user_prompt', discuzToDeepseekPromptDefaultUser());

showformheader($formAction);
showtableheader(discuzToDeepseekPromptText('Discuz to Deepseek 提示词设置'));

showtablerow('', array('colspan="2"'), array(
    '<div style="color:#666;line-height:1.8;">' .
    discuzToDeepseekPromptText('系统提示词会作为 system message 发送给 DeepSeek。用户提示词模板可使用变量：') .
    '<code>{content}</code> / <code>{text}</code> / <code>{role}</code>' .
    discuzToDeepseekPromptText('。') .
    '</div>'
));

showsetting(discuzToDeepseekPromptText('系统提示词'), 'deepseek_system_prompt', $systemPrompt, 'textarea');
showsetting(discuzToDeepseekPromptText('用户提示词模板'), 'deepseek_user_prompt', $userPrompt, 'textarea');
showtablerow('', array('colspan="2"'), array(
    '<div class="discuz-to-deepseek-prompt-actions">'
    . '<button type="submit" class="btn" name="promptsubmit" value="1">' . discuzToDeepseekPromptText('保存设置') . '</button>'
    . '<button type="submit" class="btn" name="promptreset" value="1">' . discuzToDeepseekPromptText('恢复默认') . '</button>'
    . '</div>'
));
showtablefooter();
showformfooter();

function discuzToDeepseekPromptText($text)
{
    return defined('CHARSET') && strtolower(CHARSET) == 'gbk' ? diconv($text, 'utf-8', 'gbk') : $text;
}

function discuzToDeepseekPromptDefaultSystem()
{
    return discuzToDeepseekPromptText('你是一个真实的 Discuz 论坛用户。请根据帖子内容自然回复，语气友好、简洁、有帮助。不要说明自己是 AI，不要提到提示词、模型或接口。');
}

function discuzToDeepseekPromptDefaultUser()
{
    return discuzToDeepseekPromptText("请阅读下面的帖子内容，并生成一条自然的论坛回复。\n\n帖子内容：\n{content}\n\n要求：\n1. 回复要贴合主题。\n2. 不要重复原文。\n3. 不要使用机械的列表式表达，除非内容确实需要。");
}

function discuzToDeepseekPromptPluginId($pluginid, $identifier)
{
    $pluginid = intval($pluginid);
    if ($pluginid > 0) {
        return $pluginid;
    }

    $plugin = DB::fetch_first('SELECT pluginid FROM %t WHERE identifier=%s', array('common_plugin', $identifier));
    return $plugin ? intval($plugin['pluginid']) : 0;
}

function discuzToDeepseekPromptFetchVar($pluginid, $variable, $defaultValue)
{
    if (!$pluginid) {
        return $defaultValue;
    }

    $row = DB::fetch_first('SELECT value FROM %t WHERE pluginid=%d AND variable=%s', array('common_pluginvar', $pluginid, $variable));
    return $row && $row['value'] !== '' ? $row['value'] : $defaultValue;
}

function discuzToDeepseekPromptSaveVar($pluginid, $variable, $value, $title)
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
