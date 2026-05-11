<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$sql = <<<EOF
DROP TABLE IF EXISTS pre_plugin_discuz_to_deepseek_err;
DROP TABLE IF EXISTS pre_plugin_discuz_to_deepseek_articleerr;
DROP TABLE IF EXISTS pre_plugin_discuz_to_deepseek_role;
DROP TABLE IF EXISTS pre_plugin_discuz_to_deepseek_limit;
DROP TABLE IF EXISTS pre_plugin_discuz_to_deepseek_limitarticle;
EOF;

runquery($sql);
$finish = true;

?>
