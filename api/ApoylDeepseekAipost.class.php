<?php

/**
 *      This is NOT a freeware, use is subject to license terms
 *      应用名称: AIDeepSeek自动回帖 商业版V1.9.0
 *      下载地址: https://addon.dismall.com/plugins/apoyl_deepseekaipost.html
 *      应用开发者: 凹凸曼
 *      开发者QQ: 3489214354
 *      更新日期: 202605111942
 *      授权域名: zwwx.club
 *      授权码: 2026051119cxxquXxUk1
 *      未经应用程序开发者/所有者的书面许可，不得进行反向工程、反向汇编、反向编译等，不得擅自复制、修改、链接、转载、汇编、发表、出版、发展与之有关的衍生产品、作品等
 */


/**
 *      [liyuanchao] (C)2022-2099 http://www.apoyl.com
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: ApoylDeepseekAipost.class.php  2026-4 liyuanchao $
 */
if (! defined('IN_DISCUZ')) {
    exit('Access Denied');
}
class ApoylDeepseekAipost
{

    const COMP = 'https://api.deepseek.com/chat/completions';
    private function fetch($url, $postdata = "", $auth = "", $headers = "")
    {


        $curl = curl_init($url);
        if ($postdata) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }
        if ($auth) {
            curl_setopt($curl, CURLOPT_USERPWD, $auth);
        }
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 300);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        try{
            $response = curl_exec($curl);

            if($response===false){
                if(curl_errno($curl) >0){
                    return json_encode(array('error'=>array('message'=>'Failed to connect to api.deepseek.com port 443: Timed out ,Code:'.curl_errno($curl))));
                }
            }
        }catch(Exception $e){

            return $e->getMessage();
        }
        if (empty($response)) {
            die(curl_error($curl));
            curl_close($curl);
        } else {

            curl_close($curl);
        }
        return $response;
    }
    public function getTextDavinci($prompt,$rolename,$cache)
    {
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . trim($cache['apikey'])
        );

        if($rolename)
            $messages[] = array('role' => 'system', 'content' => $this->apoylUtf($rolename,CHARSET));

        $messages[] = array(
            'role' => 'user',
            'content' => $this->apoylUtf($prompt, CHARSET),
        );

        if($cache['deepseekllm']==2){
            $model='deepseek-v4-pro';
            $postdata = array(
                'model' => $model,
                'messages' => $messages
            );
        }else{
            $model='deepseek-v4-flash';
            $postdata = array(
                'model' => $model,
                'messages' => $messages
            );
        }

        $resp = $this->fetch(self::COMP, json_encode($postdata), '', $headers);


        return $resp;
    }

    private function apoylUtf($var, $charset)
    {
        if ($charset == 'gbk') {
            $var = diconv($var, $charset, 'utf-8');
        }
        return $var;
    }
}

    		  	  		  	  		     	   			    		   		     		       	 	   	    		   		     		       	  		 	    		   		     		       	  			     		   		     		       	   		     		   		     		       	 	   	    		   		     		       	  			     		   		     		       	  			     		   		     		       	 	   	    		   		     		       	  			     		   		     		       	  	       		   		     		       	  	 	     		 	      	  		  	  		     	
?>