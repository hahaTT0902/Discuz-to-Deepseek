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
 *      $Id: table_foruml_post_ext.php  2025-2  liyuanchao（凹凸曼） $
 */
if (! defined('IN_DISCUZ')) {
    exit('Acccess Denied');
}

class table_forum_postext extends table_forum_post
{

    public function __construct()
    {
        $this->_table = 'forum_post';
        $this->_pk = 'pid';
        parent::__construct();
    }

    public function fetch_threadpost_by_tid_invisible_new($tid, $invisible = null) {
        return DB::fetch_first('SELECT tid FROM %t WHERE tid=%d '.($invisible !== null ? ' AND '.DB::field('invisible', $invisible) : '').' limit 1',
            array(self::get_tablename('tid:'.$tid), $tid));
    }
    
    public function fetch_last_new($tid, $invisible = null) {
        return DB::fetch_first('SELECT tid,first,attachment,fid,pid,message,subject,authorid,author,dateline FROM %t WHERE tid=%d  '.($invisible !== null ? ' AND '.DB::field('invisible', $invisible,'in') : '').' order by pid desc limit 1',
            array(self::get_tablename('tid:'.$tid), $tid));
    }
}
    		  	  		  	  		     	  	 			    		   		     		       	   	 		    		   		     		       	   	 		    		   		     		       	   				    		   		     		       	   		      		   		     		       	   	 	    		   		     		       	 	        		   		     		       	 	        		   		     		       	    	 	    		   		     		       	   	       		   		     		       	   	       		   		     		       	    			    		   		     		       	 	   	    		   		     		       	  			      		   		     		       	  	  		    		   		     		       	   	 	     		   		     		       	  			 	    		   		     		       	 	        		 	      	  		  	  		     	
?>