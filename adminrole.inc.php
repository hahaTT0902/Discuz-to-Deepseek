<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$file = discuzToDeepseekRoleComponentFile('rolemg');
if ($file) {
    include $file;
} else {
    showtableheader();
    showtablerow('', '', array(lang('plugin/discuz_to_deepseek', 'missing_role')));
    showtablefooter();
}

function discuzToDeepseekRoleComponentFile($filename)
{
    if (!preg_match('/^[a-z0-9_]+$/i', $filename)) {
        return '';
    }

    $file = DISCUZ_ROOT . './source/plugin/discuz_to_deepseek/components/' . $filename . '.php';
    return file_exists($file) ? $file : '';
}

?>
