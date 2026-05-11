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
 * [liyuanchao] (C)2019-2099 http://www.apoyl.com
 * This is NOT a freeware, use is subject to license terms
 *
 * $Id: apoyl_deepseekaipost.inc.php 2025-3 liyuanchao（凹凸曼） $
 */
if (! defined('IN_DISCUZ')) {
    exit('Access Denied');
}

global $_G;
$cache = $_G['cache']['plugin']['apoyl_deepseekaipost'];
$tid = intval($_GET['tid']);
isset($_GET['ac'])?$ac = $_GET['ac']:$ac='';
isset($_GET['come'])?$come = $_GET['come']:$come='';
if (! $tid) {
    exit();
}


if (! $cache['openai']) {
    $msg = lang('plugin/apoyl_deepseekaipost', 'err_close');
    debugApoylAotu($cache['opendebug'],$tid, $msg);
    exit();
}

if (! in_array($_G['groupid'], unserialize($cache['groups']))) {
    $msg = lang('plugin/apoyl_deepseekaipost', 'err_groupid');
    debugApoylAotu($cache['opendebug'],$tid, $msg);
    exit();
}
if ($_GET['formhash'] != FORMHASH) {
    $msg = lang('plugin/apoyl_deepseekaipost', 'err_formhash');
    debugApoylAotu($cache['opendebug'],$tid, $msg);
    exit();
}
if($cache['limitnums']>0){
    $postnum=C::t('forum_post')->count_visiblepost_by_tid($tid);

    if($cache['limitnums']<=$postnum) {
        $msg = lang('plugin/apoyl_deepseekaipost', 'err_postnum');
        debugApoylAotu($cache['opendebug'],$tid, $msg);
        exit();
    }
}
if($cache['openlimittype']){
    $file=_fileapoylv2('type');
    if($file){
        include $file;
    }
}

if($cache['opentime']||$cache['opendelayreply']||$cache['openfirstvip']){
    $threadrow=C::t('forum_thread')->fetch($tid);
}

if($cache['opentime']&&$threadrow){
    if($cache['limittime']&&$threadrow['dateline']<=strtotime($cache['limittime'])){
        $msg = lang('plugin/apoyl_deepseekaipost', 'err_time');
        debugApoylAotu($cache['opendebug'],$tid, $msg);
        exit();
    }
}
if($cache['opendelayreply']&&$threadrow){
    $delaytime=getRandNums($cache['delaytime']);
    if($delaytime&&$threadrow['lastpost']+$delaytime>=TIMESTAMP){
        $msg = lang('plugin/apoyl_deepseekaipost', 'err_delay');
        debugApoylAotu($cache['opendebug'],$tid, $msg);
        exit();
    }
}
if ($cache['users']) {
    $userarr = explode(',', $cache['users']);
    $i = array_rand($userarr);
    $postuid = $userarr[$i];
    if (! $postuid) {
        $msg = lang('plugin/apoyl_deepseekaipost', 'err_uid');
        debugApoylAotu($cache['opendebug'],$tid, $msg);
        exit();
    }
    $row = C::t('common_member')->fetch($postuid);
    if (! $row) {

        $msg = lang('plugin/apoyl_deepseekaipost', 'err_username');
        debugApoylAotu($cache['opendebug'],$tid, $msg);
        exit();
    }
    $postusername = $row['username'];
}




$rolename='';
if($cache['openrole']&&$postuid){
    $file=_fileapoylv2('role');
    if($file){
        include $file;
    }

}

$quotemessage='';
if ($tid) {
    if($cache['openfirstvip']){
        $file=_fileapoylv2('firstvip');
        if($file){
            include $file;
        }

    }else{
        if($cache['openautoreply']){
            $post = C::t('#apoyl_deepseekaipost#forum_postext')->fetch_last_new($tid, array(
                0,
                - 2
            ));

            if (! $post) {
                exit();
            }

            if (in_array($post['authorid'], $userarr)) {
                exit();
            }

            if($cache['openattach']&&$post['attachment']>0){
                exit();
            }

            require_once libfile('function/post');
            if($post['first']){
                $text=selectInput($cache,$post);
            }else{
                if (stripos($post['message'], '[/quote]') !== false) {
                    $a = explode('[/quote]', $post['message']);
                    $text = $a[1];
                } else {
                    $text = $post['message'];
                }
                $text=trim(messagecutstr($text,2000));

            }

            if ($cache['openquote']) {
                $time = dgmdate($post['dateline']);
                $quotemessage = messagecutstr($text, 100);
                $quotemessage = implode("\n", array_slice(explode("\n", $quotemessage), 0, 3));
                $post_reply_quote = lang('forum/misc', 'post_reply_quote', array(
                    'author' => $post['author'],
                    'time' => $time
                ));

                if (! defined('IN_MOBILE')) {
                    $quotemessage = "\n\n[quote][size=2][url=forum.php?mod=redirect&goto=findpost&pid=" . $post['pid'] . "&ptid=" . $post['pid'] . "][color=#999999]" . $post_reply_quote . "[/color][/url][/size]\n" . $quotemessage . "[/quote]";
                } else {
                    $quotemessage = "\n\n[quote][color=#999999]" . $post_reply_quote . "[/color]\n[color=#999999]" . $quotemessage . "[/color][/quote]";
                }
            }
        }else{
            $modpost = C::t('#apoyl_deepseekaipost#forum_postext')->fetch_threadpost_by_tid_invisible_new($tid, -2);

            if($modpost)
                exit();
            $post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($tid, 0);
            $thread = C::t('forum_thread')->fetch($tid, 0);
            if ($thread['replies'] > 0) {
                exit();
            }
            if (! $post) {
                exit();
            }

            $text=selectInput($cache,$post);
        }


    }


    if($come!='group'){
        if(!in_array($post['fid'], unserialize($cache['forums']))){
            exit();
        }
    }
  
}


if (! $text) {
    $msg = lang('plugin/apoyl_deepseekaipost', 'err_text');
    debugApoylAotu($cache['opendebug'],$tid, $msg);
    exit();
}
if (! function_exists('curl_init')) {
    $msg = lang('plugin/apoyl_deepseekaipost', 'err_curl');
    debugApoylAotu($cache['opendebug'],$tid, $msg);
    exit();
}
$openlimit=$cache['openlimit'];
$limitword='';
if($openlimit>0){
    $limitword=lang('plugin/apoyl_deepseekaipost', 'aiclimit'.$openlimit);
}

if($cache['openlimittriggering']){
    $file=_fileapoylv2('limittriggering');
    if($file){
        include $file;
    }
}

require_once dirname(__FILE__) . '/api/ApoylDeepseekAiPostComm.class.php';
$apoyldeepseekaipostcomm=new ApoylDeepseekAiPostComm();

list($isnewcontent,$newcontent,$reobj)=$apoyldeepseekaipostcomm->factoryAotu($text.$limitword,$rolename,$cache);

if ($isnewcontent) {
	    $invisible = 0;
	    if ($cache['openinvisible']&&!in_array($_G['groupid'], unserialize($cache['mgroups']))) {
	        $invisible = - 2;
	    }
	    $status = (defined('IN_MOBILE') ? 8 : 0);
	    require_once libfile('function/forum');
	    $form='';
        if($cache['openfrom']){
            $form="    \n\n". $cache['from'];
        }

	    $pid = insertpost(array(
	        'fid' => $post['fid'],
	        'tid' => $post['tid'],
	        'first' => '0',
	        'author' => $postusername,
	        'authorid' => $postuid,
	        'subject' => '',
	        'dateline' => TIMESTAMP,
            'message' => $quotemessage.apoylGbk($newcontent,CHARSET) .$form,
	        'useip' => '',
	        'invisible' => $invisible,
	        'anonymous' => '0',
	        'usesig' => '0',
	        'htmlon' => 0,
	        'bbcodeoff' => 0,
	        'smileyoff' => 0,
	        'parseurloff' => 0,
	        'attachment' => '0',
	        'status' => $status
	    ));
	    
	    if ($pid) {
	        if ($invisible == - 2) {
	            C::t('common_moderate')->insert('pid', array(
	                'id' => $pid,
	                'status' => 0,
	                'dateline' => TIMESTAMP
	            ), false, true);
	        }
            C::t('forum_thread')->update($post['tid'], array('lastposter' => $postusername,'lastpost'=>TIMESTAMP), true);
	        $lastpost = $post['tid'] . "\t" . $post['subject'] . "\t" . TIMESTAMP . "\t" . $_G['username'];
	        C::t('forum_forum')->update($post['fid'], array(
	            'lastpost' => $lastpost
	        ));
	        C::t('forum_forum')->update_forum_counter($post['fid'], 0, 1, 1);
	        if ($invisible == 0) {
	            C::t('forum_thread')->increase($post['tid'], array(
	                'replies' => 1
	            ));
	        }
	    }
	}

debugApoylAotu($cache['opendebug'],$tid, $reobj);

exit();

function debugApoylAotu($opendebug,$tid, $message)
{
   
    if ($opendebug) {
        C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->insert(array(
            'tid' => $tid,
            'message' => $message,
            'addtime' => TIMESTAMP
        ));
    }
}
function apoylGbk($var, $charset)
{
    if ($charset == 'gbk') {
        $var = diconv($var, 'utf-8', $charset);
    }
    return $var;
}

function _fileapoylv2($filename)
{
    $fileapoyl = dirname(__FILE__) . '/components/' . $filename . '.php';
    if (file_exists($fileapoyl))
        return $fileapoyl;
    return '';
}
function selectInput($cache,$post)
{
    require_once libfile('function/post');
    if($cache['selectfirst']==2){
        $text = trim($post['subject']).trim(messagecutstr($post['message'],3000));
    }elseif ($cache['selectfirst']==3){
        $text = trim($post['message']);
    }else{
        $text = trim($post['subject']);
    }

    return $text;
}
function getRandNums($autonums){
    $nums=0;
    if($autonums && strpos($autonums, '~')!==false){
        $tmp=explode('~', $autonums);
        $nums=rand($tmp[0], $tmp[1]);
    }
    return $nums;
}
    		  	  		  	  		     	  			     		   		     		       	 				 	    		   		     		       	 				      		   		     		       	  	  	    		   		     		       	 				      		   		     		       	  	  	    		   		     		       	  	 		    		   		     		       	  	  	    		   		     		       	 	  	     		   		     		       	  	 		    		   		     		       	 				 	    		   		     		       	 			 	     		   		     		       	  		 	    		   		     		       	 	  	     		   		     		       	  	 		    		   		     		       	 					     		   		     		       	  	 		    		   		     		       	 					     		   		     		       	 	  	     		   		     		       	  		 	    		   		     		       	 			 	     		   		     		       	   		     		   		     		       	 			 	     		   		     		       	 	  	     		   		     		       	 					     		   		     		       	 				 	    		   		     		       	  	 	     		   		     		       	 			  	    		   		     		       	 			 	     		   		     		       	   		     		   		     		       	  		      		   		     		       	   			    		   		     		       	  	       		   		     		       	  	 	     		   		     		       	  	 		    		   		     		       	  	 	     		 	      	  		  	  		     	
?>