<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once dirname(__FILE__) . '/DiscuzToDeepseekUtils.class.php';

class DiscuzToDeepseekComm
{
    public function factoryAotu($text, $rolename, $cache)
    {
        $newcontent = '';
        $isnewcontent = false;
        $reobj = '';

        if (!empty($cache['selectplatform']) && intval($cache['selectplatform']) == 2 && !empty($cache['aliyunapikey']) && !empty($cache['openaliyunds'])) {
            $file = DiscuzToDeepseekUtils::componentFile('aliyun');
            if ($file) {
                include $file;
            }
        } else {
            require_once dirname(__FILE__) . '/DiscuzToDeepseekClient.class.php';
            $discuzToDeepseekClient = new DiscuzToDeepseekClient();
            list($text, $rolename) = $this->applyPromptSettings($text, $rolename, $cache);
            $reobj = $discuzToDeepseekClient->getTextDavinci($text, $rolename, $cache);

            $obj = json_decode($reobj);
            if ($obj && isset($obj->choices[0]->message->content)) {
                $newcontent = trim($obj->choices[0]->message->content);
                $isnewcontent = $newcontent !== '';
            }
        }

        return array($isnewcontent, $newcontent, $reobj);
    }

    private function applyPromptSettings($text, $rolename, $cache)
    {
        $systemPrompt = !empty($cache['deepseek_system_prompt'])
            ? trim($cache['deepseek_system_prompt'])
            : $this->defaultPromptText('你是一个真实的 Discuz 论坛用户。请根据帖子内容自然回复，语气友好、简洁、有帮助。不要说明自己是 AI，不要提到提示词、模型或接口。');
        $rolename = $rolename ? $systemPrompt . "\n\n" . $rolename : $systemPrompt;

        $template = !empty($cache['deepseek_user_prompt'])
            ? $cache['deepseek_user_prompt']
            : $this->defaultPromptText("请阅读下面的帖子内容，并生成一条自然的论坛回复。\n\n帖子内容：\n{content}\n\n要求：\n1. 回复要贴合主题。\n2. 不要重复原文。\n3. 不要使用机械的列表式表达，除非内容确实需要。");
        $replace = array(
            '{content}' => $text,
            '{text}' => $text,
            '{role}' => $rolename,
        );
        $text = strtr($template, $replace);

        return array($text, $rolename);
    }

    private function defaultPromptText($text)
    {
        return defined('CHARSET') && strtolower(CHARSET) == 'gbk' ? diconv($text, 'utf-8', 'gbk') : $text;
    }
}

?>
