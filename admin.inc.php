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

$currentPluginId = discuzToDeepseekResolvePluginId(isset($pluginid) ? $pluginid : 0);
$hookEnsureResult = '';
$hookColumnsText = '';
if ($currentPluginId > 0) {
    $hookEnsure = discuzToDeepseekEnsureHooks($currentPluginId);
    $hooksChanged = !empty($hookEnsure['changed']);
    $hookEnsureResult = isset($hookEnsure['message']) ? $hookEnsure['message'] : '';
    $hookColumnsText = isset($hookEnsure['columnsText']) ? $hookEnsure['columnsText'] : '';
    if ($hooksChanged && function_exists('updatecache')) {
        updatecache('plugin');
        updatecache('setting');
    }
}

if (isset($_GET['go'], $_GET['formhash']) && $_GET['go'] == 'del' && $_GET['formhash'] == FORMHASH) {
    $delid = intval($_GET['delid']);
    if ($delid > 0) {
        $logTable->delete($delid);
    }
}

if (isset($_GET['go'], $_GET['formhash']) && $_GET['go'] == 'testlog' && $_GET['formhash'] == FORMHASH) {
    $logTable->insert(array(
        'tid' => 0,
        'message' => 'admin_test_log:' . TIMESTAMP,
        'addtime' => TIMESTAMP,
    ));
}

showtableheader();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$prepage = 20;
$start = ($page - 1) * $prepage;
$num = $logTable->count();
$baseurl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=discuz_to_deepseek&pmod=admin';
$prompturl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=discuz_to_deepseek&pmod=adminprompt';
$testlogurl = $baseurl . '&go=testlog&formhash=' . formhash();
$multipage = multi($num, $prepage, $page, $baseurl);
$arr = $logTable->range($start, $prepage, 'addtime desc');

$hookCount = 0;
if ($currentPluginId > 0 && discuzToDeepseekAdminTableExists('common_pluginhook')) {
    $row = DB::fetch_first('SELECT COUNT(*) AS cnt FROM %t WHERE pluginid=%d', array('common_pluginhook', $currentPluginId));
    $hookCount = $row ? intval($row['cnt']) : 0;
}

showtablerow('', array('colspan="5"'), array('<strong>Discuz to Deepseek</strong> &nbsp; <a href="' . $prompturl . '">提示词设置</a> &nbsp; <a href="' . $testlogurl . '">写入测试日志</a>'));
showtablerow('', array('colspan="5"'), array('<div style="color:#666;line-height:1.8;">当前插件ID：' . intval($currentPluginId) . '，已注册Hook数量：' . intval($hookCount) . '。如果发新主题仍无日志，先点击“写入测试日志”确认日志写入链路正常。</div>'));
if ($hookEnsureResult !== '' || $hookColumnsText !== '') {
    showtablerow('', array('colspan="5"'), array('<div style="color:#999;line-height:1.8;">Hook补齐结果：' . dhtmlspecialchars($hookEnsureResult) . '<br/>Hook表字段：' . dhtmlspecialchars($hookColumnsText) . '</div>'));
}

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
        return array('changed' => false, 'message' => 'common_pluginhook_not_exists', 'columnsText' => '');
    }

    $columns = discuzToDeepseekAdminTableColumns('common_pluginhook');
    if (empty($columns) || !isset($columns['pluginid']) || !isset($columns['hook']) || !isset($columns['class']) || !isset($columns['method'])) {
        return array('changed' => false, 'message' => 'hook_columns_invalid', 'columnsText' => implode(',', array_keys($columns)));
    }

    $changed = false;

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

    $baseData = array();
    $baseData['pluginid'] = intval($pluginid);
    if (isset($columns['available'])) {
        $baseData['available'] = 1;
    }
    if (isset($columns['hookscript'])) {
        $baseData['hookscript'] = 'discuz_to_deepseek';
    }
    if (isset($columns['script'])) {
        $baseData['script'] = 'discuz_to_deepseek';
    }
    if (isset($columns['type'])) {
        $baseData['type'] = 0;
    }
    if (isset($columns['hooktype'])) {
        $baseData['hooktype'] = 0;
    }
    if (isset($columns['displayorder'])) {
        $baseData['displayorder'] = 5;
    }
    if (isset($columns['includefile'])) {
        $baseData['includefile'] = 'discuz_to_deepseek';
    }

    foreach ($hooks as $hook) {
        $where = DB::field('pluginid', $pluginid)
            . ' AND ' . DB::field('hook', $hook['hook'])
            . ' AND ' . DB::field('class', $hook['class'])
            . ' AND ' . DB::field('method', $hook['method']);
        $exists = DB::fetch_first('SELECT * FROM %t WHERE ' . $where . ' LIMIT 1', array('common_pluginhook'));

        $data = $baseData;
        $data['hook'] = $hook['hook'];
        $data['class'] = $hook['class'];
        $data['method'] = $hook['method'];
        $data = discuzToDeepseekAdminFillRequiredColumns($columns, $data);

        if ($exists) {
            DB::update('common_pluginhook', $data, $where);
        } else {
            DB::insert('common_pluginhook', $data);
            $changed = true;
        }
    }

    return array('changed' => $changed, 'message' => $changed ? 'hook_inserted_or_updated' : 'hook_already_exists', 'columnsText' => implode(',', array_keys($columns)));
}

function discuzToDeepseekResolvePluginId($pluginid)
{
    $pluginid = intval($pluginid);
    if ($pluginid > 0) {
        return $pluginid;
    }

    $plugin = DB::fetch_first('SELECT pluginid FROM %t WHERE identifier=%s', array('common_plugin', 'discuz_to_deepseek'));
    return $plugin ? intval($plugin['pluginid']) : 0;
}

function discuzToDeepseekAdminTableExists($table)
{
    $tableName = DB::table($table);
    $tableName = str_replace(array('\\', '_', '%'), array('\\\\', '\\_', '\\%'), $tableName);
    $row = DB::fetch_first("SHOW TABLES LIKE '" . addslashes($tableName) . "'");
    return !empty($row);
}

function discuzToDeepseekAdminTableColumns($table)
{
    if (!discuzToDeepseekAdminTableExists($table)) {
        return array();
    }

    $rows = DB::fetch_all('SHOW COLUMNS FROM ' . DB::table($table));
    $columns = array();
    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (!empty($row['Field'])) {
                $columns[$row['Field']] = $row;
            }
        }
    }
    return $columns;
}

function discuzToDeepseekAdminFillRequiredColumns($columns, $data)
{
    foreach ($columns as $field => $meta) {
        if (array_key_exists($field, $data)) {
            continue;
        }

        $isNullable = isset($meta['Null']) && strtoupper($meta['Null']) === 'YES';
        $hasDefault = array_key_exists('Default', $meta) && $meta['Default'] !== null;
        $isAutoIncrement = !empty($meta['Extra']) && stripos($meta['Extra'], 'auto_increment') !== false;
        if ($isNullable || $hasDefault || $isAutoIncrement) {
            continue;
        }

        $type = isset($meta['Type']) ? strtolower($meta['Type']) : '';
        if (strpos($type, 'int') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false || strpos($type, 'decimal') !== false) {
            $data[$field] = 0;
        } else {
            $data[$field] = '';
        }
    }

    return $data;
}

?>
