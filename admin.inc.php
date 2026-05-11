<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$logTable = C::t('#discuz_to_deepseek#discuz_to_deepseek_error');
$logTable->ensureTable();

$currentPluginId = isset($pluginid) ? intval($pluginid) : 0;
if ($currentPluginId > 0) {
    discuzToDeepseekEnsureHooks($currentPluginId);
}

if (isset($_GET['go'], $_GET['formhash']) && $_GET['go'] == 'del' && $_GET['formhash'] == FORMHASH) {
    $delid = intval($_GET['delid']);
    if ($delid > 0) {
        $logTable->delete($delid);
    }
}

showtableheader();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$prepage = 20;
$start = ($page - 1) * $prepage;
$num = $logTable->count();
$baseurl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=discuz_to_deepseek&pmod=admin';
$prompturl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=discuz_to_deepseek&pmod=adminprompt';
$multipage = multi($num, $prepage, $page, $baseurl);
$arr = $logTable->range($start, $prepage, 'addtime desc');

showtablerow('', array('colspan="5"'), array('<strong>Discuz to Deepseek</strong> &nbsp; <a href="' . $prompturl . '">提示词设置</a>'));

showsubtitle(array(
    'ID',
    lang('plugin/discuz_to_deepseek', 'tid'),
    lang('plugin/discuz_to_deepseek', 'err_msg'),
    lang('plugin/discuz_to_deepseek', 'addtime'),
    lang('plugin/discuz_to_deepseek', 'ac')
));

foreach ($arr as $v) {
    $id = intval($v['id']);
    $tid = intval($v['tid']);
    $addtime = !empty($v['addtime']) ? dgmdate($v['addtime'], 'u', '9999', getglobal('setting/dateformat') . ' H:i:s') : '';
    $message = nl2br(dhtmlspecialchars($v['message']));
    $delurl = $baseurl . '&page=' . $page . '&go=del&delid=' . $id . '&formhash=' . formhash();
    $delhtml = '<a href="' . $delurl . '" onclick="javascript:if(!confirm(\'' . lang('plugin/discuz_to_deepseek', 'del_msg') . '\')){return false}">' . lang('plugin/discuz_to_deepseek', 'del') . '</a>';

    showtablerow('', array('width="60"', 'width="80"', 'style="white-space:normal;word-break:break-all;line-height:1.7;"', 'width="150"', 'width="60"'), array(
        $id,
        '<a target="_blank" href="forum.php?mod=viewthread&tid=' . $tid . '">' . $tid . '</a>',
        '<div style="max-width:780px;color:#e4862f;">' . $message . '</div>',
        $addtime,
        $delhtml
    ));
}

showtablefooter();
echo '<div class="cuspages right">' . $multipage . '</div>';

function discuzToDeepseekEnsureHooks($pluginid)
{
    if (!discuzToDeepseekAdminTableExists('common_pluginhook')) {
        return;
    }

    $hooks = array(
        array('hook' => 'viewthread_bottom',     'class' => 'plugin_discuz_to_deepseek_forum',        'method' => 'viewthread_bottom_output'),
        array('hook' => 'viewthread_bottom',     'class' => 'plugin_discuz_to_deepseek_group',        'method' => 'viewthread_bottom_output'),
        array('hook' => 'view_article_content',  'class' => 'plugin_discuz_to_deepseek_portal',       'method' => 'view_article_content_output'),
        array('hook' => 'post_newthread_succeed','class' => 'plugin_discuz_to_deepseek_forum',        'method' => 'post_newthread_succeed'),
        array('hook' => 'post_reply_succeed',    'class' => 'plugin_discuz_to_deepseek_forum',        'method' => 'post_reply_succeed'),
        array('hook' => 'post_newthread_end',    'class' => 'plugin_discuz_to_deepseek_forum',        'method' => 'post_newthread_end'),
        array('hook' => 'post_reply_end',        'class' => 'plugin_discuz_to_deepseek_forum',        'method' => 'post_reply_end'),
        array('hook' => 'post_newthread_succeed','class' => 'plugin_discuz_to_deepseek_group',        'method' => 'post_newthread_succeed'),
        array('hook' => 'post_reply_succeed',    'class' => 'plugin_discuz_to_deepseek_group',        'method' => 'post_reply_succeed'),
        array('hook' => 'post_newthread_end',    'class' => 'plugin_discuz_to_deepseek_group',        'method' => 'post_newthread_end'),
        array('hook' => 'post_reply_end',        'class' => 'plugin_discuz_to_deepseek_group',        'method' => 'post_reply_end'),
        array('hook' => 'viewthread_bottom',     'class' => 'mobileplugin_discuz_to_deepseek_forum',  'method' => 'viewthread_bottom_mobile_output'),
        array('hook' => 'viewthread_bottom',     'class' => 'mobileplugin_discuz_to_deepseek_group',  'method' => 'viewthread_bottom_mobile_output'),
        array('hook' => 'view_article_content',  'class' => 'mobileplugin_discuz_to_deepseek_portal', 'method' => 'view_article_content_mobile_output'),
    );

    foreach ($hooks as $hook) {
        $exists = DB::fetch_first(
            'SELECT hookid FROM %t WHERE pluginid=%d AND class=%s AND method=%s',
            array('common_pluginhook', $pluginid, $hook['class'], $hook['method'])
        );

        $data = array(
            'pluginid' => $pluginid,
            'available' => 1,
            'hook' => $hook['hook'],
            'hookscript' => 'discuz_to_deepseek',
            'class' => $hook['class'],
            'method' => $hook['method'],
            'type' => 0,
            'displayorder' => 5,
        );

        if ($exists) {
            DB::update('common_pluginhook', $data, DB::field('hookid', $exists['hookid']));
        } else {
            DB::insert('common_pluginhook', $data);
        }
    }
}

function discuzToDeepseekAdminTableExists($table)
{
    $tableName = DB::table($table);
    $tableName = str_replace(array('\\', '_', '%'), array('\\\\', '\\_', '\\%'), $tableName);
    $row = DB::fetch_first("SHOW TABLES LIKE '" . addslashes($tableName) . "'");
    return !empty($row);
}

?>
