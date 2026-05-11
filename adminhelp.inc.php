<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

if (isset($_GET['go']) && $_GET['go'] == 'testapi') {
    require_once libfile('function/cache');
    loadcache('plugin');
    $pluginConfig = $_G['cache']['plugin']['discuz_to_deepseek'];
    
    require_once dirname(__FILE__) . '/api/DiscuzToDeepseekClient.class.php';
    $client = new DiscuzToDeepseekClient();
    $rawResponse = $client->getTextDavinci('测试消息，请回复"收到，API通信正常"', '你是一个API测试机器人', $pluginConfig);
    
    cpmsg('<strong>测试结果:</strong><br><br><pre style="white-space: pre-wrap; font-size: 14px; padding: 10px; background: #f9f9f9; border: 1px solid #ccc; max-width: 800px;">' . dhtmlspecialchars(print_r($rawResponse, true)) . '</pre>', 'action=plugins&operation=config&do=' . $pluginid . '&identifier=discuz_to_deepseek&pmod=adminhelp', 'succeed');
}

showtableheader('Discuz to Deepseek 帮助 & 测试');
showtablerow('', '', array('插件标识：discuz_to_deepseek'));
showtablerow('', '', array('前台入口：plugin.php?id=discuz_to_deepseek'));
showtablerow('', '', array('提示词设置：请在插件后台的提示词设置页面中配置。'));
showtablerow('', '', array('API 连通性测试：<a href="admin.php?action=plugins&operation=config&do=' . $pluginid . '&identifier=discuz_to_deepseek&pmod=adminhelp&go=testapi" class="addtr">点击测试 API 是否可达</a>'));
showtablerow('', '', array('安装目录：source/plugin/discuz_to_deepseek'));
showtablerow('', '', array('开源插件 by hahaTT'));
showtablefooter();

?>
