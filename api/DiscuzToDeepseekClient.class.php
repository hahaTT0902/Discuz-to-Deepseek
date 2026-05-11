<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class DiscuzToDeepseekClient
{
    const COMP = 'https://api.deepseek.com/chat/completions';
    const CONNECT_TIMEOUT = 15;
    const REQUEST_TIMEOUT = 90;

    private function fetch($url, $postdata = '', $auth = '', $headers = array())
    {
        $curl = curl_init($url);
        if (!$curl) {
            return $this->errorResponse('Unable to initialize curl');
        }

        curl_setopt($curl, CURLOPT_POST, $postdata !== '');
        if ($postdata !== '') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        }
        if ($auth) {
            curl_setopt($curl, CURLOPT_USERPWD, $auth);
        }
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $httpCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        curl_close($curl);

        if ($response === false) {
            return $this->errorResponse($error ? $error : 'DeepSeek request failed', $errno, $httpCode);
        }

        if ($response === '') {
            return $this->errorResponse('DeepSeek returned an empty response', 0, $httpCode);
        }

        if ($httpCode >= 400) {
            return $this->errorResponse('DeepSeek HTTP error: ' . $httpCode, 0, $httpCode, $response);
        }

        return $response;
    }

    public function getTextDavinci($prompt, $rolename, $cache)
    {
        if (empty($cache['apikey'])) {
            return $this->errorResponse('DeepSeek API key is empty');
        }

        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . trim($cache['apikey'])
        );

        $messages = array();
        if ($rolename) {
            $messages[] = array('role' => 'system', 'content' => $this->convertToUtf8($rolename, CHARSET));
        }

        $messages[] = array(
            'role' => 'user',
            'content' => $this->convertToUtf8($prompt, CHARSET),
        );

        $model = !empty($cache['deepseekllm']) && intval($cache['deepseekllm']) == 2
            ? 'deepseek-v4-pro'
            : 'deepseek-v4-flash';

        $postdata = array(
            'model' => $model,
            'messages' => $messages
        );

        return $this->fetch(self::COMP, json_encode($postdata), '', $headers);
    }

    private function errorResponse($message, $code = 0, $httpCode = 0, $raw = '')
    {
        return json_encode(array(
            'error' => array(
                'message' => $message,
                'code' => $code,
                'http_code' => $httpCode,
                'raw' => $raw,
            )
        ));
    }

    private function convertToUtf8($var, $charset)
    {
        if ($charset == 'gbk') {
            return diconv($var, $charset, 'utf-8');
        }

        return $var;
    }
}

?>
