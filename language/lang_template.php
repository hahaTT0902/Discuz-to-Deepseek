<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

if (!function_exists('discuz_to_deepseek_lang_text')) {
    function discuz_to_deepseek_lang_text($text)
    {
        return defined('CHARSET') && strtolower(CHARSET) == 'gbk' ? diconv($text, 'utf-8', 'gbk') : $text;
    }
}

$lang = array(
    'tid' => discuz_to_deepseek_lang_text('主题 ID'),
    'err_msg' => discuz_to_deepseek_lang_text('消息'),
    'addtime' => discuz_to_deepseek_lang_text('时间'),
    'ac' => discuz_to_deepseek_lang_text('操作'),
    'del' => discuz_to_deepseek_lang_text('删除'),
    'del_msg' => discuz_to_deepseek_lang_text('确定删除这条日志吗？'),
    'missing_article' => discuz_to_deepseek_lang_text('文章管理组件未安装。'),
    'missing_role' => discuz_to_deepseek_lang_text('角色管理组件未安装。'),
    'err_close' => discuz_to_deepseek_lang_text('Discuz to Deepseek 未启用。'),
    'err_groupid' => discuz_to_deepseek_lang_text('当前用户组不允许触发自动回帖。'),
    'err_formhash' => discuz_to_deepseek_lang_text('表单校验失败。'),
    'err_uid' => discuz_to_deepseek_lang_text('未配置发帖用户 UID。'),
    'err_username' => discuz_to_deepseek_lang_text('配置的发帖用户不存在。'),
    'err_postnum' => discuz_to_deepseek_lang_text('主题回复数已达到限制。'),
    'err_time' => discuz_to_deepseek_lang_text('主题发布时间不符合限制。'),
    'err_delay' => discuz_to_deepseek_lang_text('延迟回复限制阻止了本次请求。'),
    'err_text' => discuz_to_deepseek_lang_text('没有可发送给 DeepSeek 的内容。'),
    'err_curl' => discuz_to_deepseek_lang_text('PHP curl 扩展不可用。'),
    'aiclimit1' => discuz_to_deepseek_lang_text("\n\n请保持回复简短。"),
    'aiclimit2' => discuz_to_deepseek_lang_text("\n\n请自然回复，不要重复原帖内容。"),
    'aiclimit3' => discuz_to_deepseek_lang_text("\n\n请使用友好的论坛交流语气回复。"),
);

?>
