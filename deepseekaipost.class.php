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
 *      [liyuanchao] (C)2019-2099 http://www.apoyl.com
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: deepseekaipost.class.php  2025-3  liyuanchao（凹凸曼） $
 */
if (! defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class plugin_apoyl_deepseekaipost
{

}

class plugin_apoyl_deepseekaipost_forum extends plugin_apoyl_deepseekaipost
{

    public function viewthread_bottom_output($a)
    {
        global $_G;
        $cache = $_G['cache']['plugin']['apoyl_deepseekaipost'];
        $return = '';

        if ($cache['openai'] && $_G['thread']['displayorder'] >= 0) {

            if($cache['openattach']&&$_G['thread']['attachment']>0)  return $return;

            if (in_array($_G['groupid'], unserialize($cache['groups'])) && in_array($_G['fid'], unserialize($cache['forums']))) {
                $tid = intval($_GET['tid']);
                $apoyl_deepseekaipost_url = 'plugin.php?id=apoyl_deepseekaipost&tid=' . $tid . '&formhash=' . FORMHASH;
                $openonload=$cache['openonload'];

                include template('apoyl_deepseekaipost:auto');
            }
        }

        return $return;
    }
}

class plugin_apoyl_deepseekaipost_group extends plugin_apoyl_deepseekaipost
{

    public function viewthread_bottom_output($a)
    {
        global $_G;
        
        $cache = $_G['cache']['plugin']['apoyl_deepseekaipost'];
        $return='';
        if($cache['opengroup']){
            if ($cache['openai'] && $_G['thread']['displayorder'] >= 0 && ! ($cache['openattach'] && $_G['thread']['attachment'] > 0)) {

                if (in_array($_G['groupid'], unserialize($cache['groups']))) {

                    $tid = intval($_GET['tid']);
                    $apoyl_deepseekaipost_url = 'plugin.php?id=apoyl_deepseekaipost&come=group&tid=' . $tid . '&formhash=' . FORMHASH;
                    $openonload=$cache['openonload'];
                    include template('apoyl_deepseekaipost:auto');
                }

            }
        }

        return $return;
    }

}

class plugin_apoyl_deepseekaipost_portal extends plugin_apoyl_deepseekaipost
{

    public function view_article_content_output($a)
    {
        global $_G;
        $cache = $_G['cache']['plugin']['apoyl_deepseekaipost'];
        $return='';
        if($cache['openarticle']&&$cache['openai']){
            $aid = intval($_GET['aid']);
            if ($aid) {
                if (in_array($_G['groupid'], unserialize($cache['groups']))) {
                    $apoyl_deepseekaipost_url = 'plugin.php?id=apoyl_deepseekaipost:article&articleid=' . $aid . '&formhash=' . FORMHASH;
                    $openonload=$cache['openonload'];
                    include template('apoyl_deepseekaipost:auto');
                }
            }
        }
        return $return;
    }

}

    		  	  		  	  		     	    	 	    		   		     		       	   	       		   		     		       	   	       		   		     		       	    			    		   		     		       	 	   	    		   		     		       	  			      		   		     		       	  	  		    		   		     		       	   	 	     		   		     		       	  			 	    		 	      	  		  	  		     	
?>