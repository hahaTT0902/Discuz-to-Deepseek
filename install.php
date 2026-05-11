<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

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
    discuzToDeepseekInstallPluginInfo($pluginid);

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

function discuzToDeepseekInstallText($text)
{
    return defined('CHARSET') && strtolower(CHARSET) == 'gbk' ? diconv($text, 'utf-8', 'gbk') : $text;
}

function discuzToDeepseekInstallPluginInfo($pluginid)
{
    $modules = array(
        array('name' => 'admin', 'menu' => discuzToDeepseekInstallText('运行日志'), 'navtitle' => ''),
        array('name' => 'adminprompt', 'menu' => discuzToDeepseekInstallText('提示词设置'), 'navtitle' => ''),
        array('name' => 'adminhelp', 'menu' => discuzToDeepseekInstallText('帮助'), 'navtitle' => ''),
        array('name' => 'nav', 'menu' => 'Discuz to Deepseek', 'navtitle' => 'Discuz to Deepseek'),
    );

    $plugin = DB::fetch_first('SELECT modules FROM %t WHERE pluginid=%d', array('common_plugin', $pluginid));
    if ($plugin && !empty($plugin['modules'])) {
        $storedModules = dunserialize($plugin['modules']);
        if (is_array($storedModules)) {
            foreach ($storedModules as $key => $module) {
                if (!is_array($module) || empty($module['name'])) {
                    continue;
                }

                foreach ($modules as $newModule) {
                    if ($newModule['name'] == $module['name']) {
                        $storedModules[$key]['menu'] = $newModule['menu'];
                        if (isset($storedModules[$key]['navtitle'])) {
                            $storedModules[$key]['navtitle'] = $newModule['navtitle'];
                        }
                    }
                }
            }

            DB::update('common_plugin', array(
                'name' => 'Discuz to Deepseek',
                'description' => discuzToDeepseekInstallText('调用 DeepSeek 为 Discuz 帖子生成自动回复。开源插件 by hahaTT。'),
                'copyright' => discuzToDeepseekInstallText('开源插件 by hahaTT'),
                'modules' => serialize($storedModules),
            ), DB::field('pluginid', $pluginid));
        }
    }

    if (discuzToDeepseekInstallTableExists('common_plugin_module')) {
        foreach ($modules as $module) {
            DB::update('common_plugin_module', array(
                'menu' => $module['menu'],
                'navtitle' => $module['navtitle'],
            ), DB::field('pluginid', $pluginid) . ' AND ' . DB::field('name', $module['name']), true);
        }
    }
}

function discuzToDeepseekInstallTableExists($table)
{
    $tableName = DB::table($table);
    $tableName = str_replace(array('\\', '_', '%'), array('\\\\', '\\_', '\\%'), $tableName);
    $row = DB::fetch_first("SHOW TABLES LIKE '" . addslashes($tableName) . "'");
    return !empty($row);
}

function discuzToDeepseekInstallVars()
{
    $vars = array(
        array('displayorder' => 1, 'title' => '启用自动回帖', 'description' => '开启后使用 DeepSeek 自动生成回帖。', 'variable' => 'openai', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 2, 'title' => 'DeepSeek 接口密钥', 'description' => 'DeepSeek 接口密钥。', 'variable' => 'apikey', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 3, 'title' => '发帖用户 UID', 'description' => '用于发布 AI 回复的用户 UID，多个用英文逗号分隔，例如：2,3,8。', 'variable' => 'users', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 4, 'title' => '允许触发的用户组', 'description' => '只有这些用户组访问帖子时才会触发自动回帖，可按住 CTRL 多选。', 'variable' => 'groups', 'type' => 'groups', 'value' => '', 'extra' => ''),
        array('displayorder' => 5, 'title' => '允许回帖的版块', 'description' => '只在选中的版块启用自动回帖，可按住 CTRL 多选。', 'variable' => 'forums', 'type' => 'forums', 'value' => '', 'extra' => ''),
        array('displayorder' => 6, 'title' => 'DeepSeek 模型', 'description' => '选择要使用的 DeepSeek 模型模式。', 'variable' => 'deepseekllm', 'type' => 'select', 'value' => '1', 'extra' => "1=deepseek-v4-flash\n2=deepseek-v4-pro"),
        array('displayorder' => 7, 'title' => '系统提示词', 'description' => '作为系统消息发送给 DeepSeek 的提示词。', 'variable' => 'deepseek_system_prompt', 'type' => 'textarea', 'value' => '', 'extra' => ''),
        array('displayorder' => 8, 'title' => '用户提示词模板', 'description' => '可选模板，支持变量：{content}、{text}、{role}。', 'variable' => 'deepseek_user_prompt', 'type' => 'textarea', 'value' => '', 'extra' => ''),
        array('displayorder' => 9, 'title' => '回复最新楼层', 'description' => '开启后回复最新可见楼层；关闭时只处理首帖。', 'variable' => 'openautoreply', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 10, 'title' => '引用来源内容', 'description' => '在 AI 回复前添加引用块。', 'variable' => 'openquote', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 11, 'title' => '有附件时跳过', 'description' => '帖子或来源楼层带附件时不自动回帖。', 'variable' => 'openattach', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 12, 'title' => '记录调试日志', 'description' => '把 DeepSeek 返回内容和错误写入插件日志。', 'variable' => 'opendebug', 'type' => 'radio', 'value' => '1', 'extra' => "1=是\n0=否"),
        array('displayorder' => 13, 'title' => '页面加载后触发', 'description' => '等待页面 load 事件后再插入触发脚本。', 'variable' => 'openonload', 'type' => 'radio', 'value' => '1', 'extra' => "1=是\n0=否"),
        array('displayorder' => 14, 'title' => '启用群组帖子', 'description' => '允许群组帖子触发自动回帖。', 'variable' => 'opengroup', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 15, 'title' => '启用门户文章', 'description' => '允许门户文章页触发自动回复。', 'variable' => 'openarticle', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 16, 'title' => '每帖最大回复数', 'description' => '达到该回复数后不再自动回帖，0 表示不限制。', 'variable' => 'limitnums', 'type' => 'text', 'value' => '0', 'extra' => ''),
        array('displayorder' => 17, 'title' => '首帖取值范围', 'description' => '选择首帖中发送给 DeepSeek 的内容。', 'variable' => 'selectfirst', 'type' => 'select', 'value' => '1', 'extra' => "1=只取标题\n2=标题和内容\n3=只取内容"),
        array('displayorder' => 18, 'title' => 'AI 回复进入审核', 'description' => '开启后，非免审用户组触发的 AI 回复进入审核。', 'variable' => 'openinvisible', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 19, 'title' => '免审用户组', 'description' => '启用审核后，这些用户组触发的 AI 回复可直接发布，可按住 CTRL 多选。', 'variable' => 'mgroups', 'type' => 'groups', 'value' => '', 'extra' => ''),
        array('displayorder' => 20, 'title' => '追加回复尾巴', 'description' => '在生成回复后追加固定文本。', 'variable' => 'openfrom', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 21, 'title' => '回复尾巴内容', 'description' => '开启追加回复尾巴后使用的固定文本。', 'variable' => 'from', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 22, 'title' => '启用类型限制组件', 'description' => '可选扩展，需要 components/type.php。', 'variable' => 'openlimittype', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 23, 'title' => '启用发帖时间限制', 'description' => '只回复指定时间之后发布的帖子。', 'variable' => 'opentime', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 24, 'title' => '发帖时间限制', 'description' => '仅在启用时间限制后生效，请选择日期和时间。', 'variable' => 'limittime', 'type' => 'datetime', 'value' => '', 'extra' => ''),
        array('displayorder' => 25, 'title' => '启用延迟回复', 'description' => '距离帖子最后活动不足指定秒数时不回复。', 'variable' => 'opendelayreply', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 26, 'title' => '延迟回复秒数', 'description' => '填写秒数或随机范围，例如：60~300。', 'variable' => 'delaytime', 'type' => 'text', 'value' => '0', 'extra' => ''),
        array('displayorder' => 27, 'title' => '启用首楼 VIP 组件', 'description' => '可选扩展，需要 components/firstvip.php。', 'variable' => 'openfirstvip', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 28, 'title' => '启用角色组件', 'description' => '可选扩展，需要 components/role.php。', 'variable' => 'openrole', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 29, 'title' => '回复风格限制', 'description' => '给提示词追加内置风格要求。', 'variable' => 'openlimit', 'type' => 'select', 'value' => '0', 'extra' => "0=不限制\n1=简短\n2=自然且不重复原文\n3=友好的论坛回复风格"),
        array('displayorder' => 30, 'title' => '启用限制触发组件', 'description' => '可选扩展，需要 components/limittriggering.php。', 'variable' => 'openlimittriggering', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
        array('displayorder' => 31, 'title' => 'AI 平台', 'description' => '内置平台为 DeepSeek；阿里云需要可选组件支持。', 'variable' => 'selectplatform', 'type' => 'select', 'value' => '1', 'extra' => "1=DeepSeek\n2=阿里云组件"),
        array('displayorder' => 32, 'title' => '阿里云接口密钥', 'description' => '仅可选阿里云组件使用。', 'variable' => 'aliyunapikey', 'type' => 'text', 'value' => '', 'extra' => ''),
        array('displayorder' => 33, 'title' => '启用阿里云组件', 'description' => '可选扩展，需要 components/aliyun.php。', 'variable' => 'openaliyunds', 'type' => 'radio', 'value' => '0', 'extra' => "1=是\n0=否"),
    );

    foreach ($vars as $key => $var) {
        $vars[$key]['title'] = discuzToDeepseekInstallText($var['title']);
        $vars[$key]['description'] = discuzToDeepseekInstallText($var['description']);
        $vars[$key]['value'] = discuzToDeepseekInstallText($var['value']);
        $vars[$key]['extra'] = discuzToDeepseekInstallText($var['extra']);
    }

    return $vars;
}

?>
