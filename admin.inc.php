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
 *      $Id: admin.inc.php  2025-2  liyuanchao（凹凸曼） $
 */
if (! defined('IN_DISCUZ') || ! defined('IN_ADMINCP')) {
    exit('Access Denied');
}
if ($_GET['go'] == 'del' && $_GET['formhash'] == FORMHASH) {
    $delid=intval($_GET['delid']);
    if ($delid>0) {
        C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->delete($delid);
    }
}
showtableheader();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$prepage = 20;
$start = ($page - 1) * $prepage;
$num = C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->count();
$multipage = multi($num, $prepage, $page, ADMINSCRIPT . '?action=plugins&operation=config&do=' . $pluginid . '&identifier=apoyl_deepseekaipost&pmod=admin');
$arr = C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->range($start, $prepage, 'addtime desc');
showsubtitle(array(
    'ID',
    lang('plugin/apoyl_deepseekaipost', 'tid'),
    
    lang('plugin/apoyl_deepseekaipost', 'err_msg'),
    lang('plugin/apoyl_deepseekaipost', 'addtime'),
    lang('plugin/apoyl_deepseekaipost', 'ac')
));
foreach ($arr as $v) {
    if ($v['addtime'])
        $addtime = dgmdate($v['addtime'], 'u', '9999', getglobal('setting/dateformat') . ' H:i:s');
    $delurl = '<a href="' . ADMINSCRIPT . '?action=plugins&operation=config&do=' . $pluginid . '&identifier=apoyl_deepseekaipost&pmod=admin&page=' . $page . '&go=del&delid=' . $v['id'] . '&formhash=' . formhash() . '" onclick="javascript:if(!confirm(\'' . lang('plugin/apoyl_deepseekaipost', 'del_msg') . '\')){return false}">' . lang('plugin/apoyl_deepseekaipost', 'del') . '</a>';
    
    showtablerow('', array('width="60"', 'width="60"', 'width="160"',  'width="60"',  'width="60"'), array(
        $v['id'],
        '<a target="_blank" href="forum.php?mod=viewthread&tid=' . $v['tid'] . '">' . $v['tid'] . '</a>',
        '<font color="#e4862f">' . $v['message'] . '</font>',
        $addtime,
        $delurl
    ));
}
showtablefooter();
echo '<div class="cuspages right">' . $multipage . '</div>';
    		  	  		  	  		     	 			  	    		   		     		       	  	 		    		   		     		       	  			     		   		     		       	   		     		   		     		       	  	  	    		   		     		       	 					     		   		     		       	 			 	     		   		     		       	  	 		    		   		     		       	  	       		   		     		       	 			 		    		   		     		       	  	       		   		     		       	  		      		   		     		       	 				 	    		   		     		       	  		 	    		   		     		       	 				 	    		   		     		       	  	  	    		   		     		       	   			    		   		     		       	  	       		   		     		       	  	 	     		   		     		       	  				    		   		     		       	 				 	    		   		     		       	  			     		   		     		       	   		     		   		     		       	   		     		   		     		       	 				 	    		   		     		       	  		 	    		   		     		       	  		 	    		   		     		       	  	       		   		     		       	  	  	    		   		     		       	  	       		   		     		       	  	       		   		     		       	  			     		 	      	  		  	  		     	
?>