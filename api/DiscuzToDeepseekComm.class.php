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
        if (!empty($cache['deepseek_system_prompt'])) {
            $systemPrompt = trim($cache['deepseek_system_prompt']);
            $rolename = $rolename ? $systemPrompt . "\n\n" . $rolename : $systemPrompt;
        }

        if (!empty($cache['deepseek_user_prompt'])) {
            $template = $cache['deepseek_user_prompt'];
            $replace = array(
                '{content}' => $text,
                '{text}' => $text,
                '{role}' => $rolename,
            );
            $text = strtr($template, $replace);
        }

        return array($text, $rolename);
    }
}

?>
