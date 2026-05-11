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
 *      $Id: adminhelp.inc.php  2025-2  liyuanchao（凹凸曼） $
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
showtableheader();

showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_copy')));

showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_aliyunvideo')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_tencentvideo')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_mtime')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_aiarticle')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_weixincrawloverride')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_modquantity')));


showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_mpage')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_paytel')));

showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_threadverify')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_picdivision')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_chatgpt')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_fastpost')));


showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_autopost')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_baiduaipost')));

showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_moderator')));

showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_baidufast')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_auditall')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_listhide')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_aliyunasyncscan')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_threadclose')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_telattach')));


showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_aliyunaiclean')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_aliyuncontent')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','apoyl_tencentcaptcha')));

showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','addr')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','blog')));
showtablerow('','',array(lang('plugin/apoyl_deepseekaipost','qq')));

showtablefooter();

?>