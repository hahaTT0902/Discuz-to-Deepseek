<?php

/** *      应用名称: Discuz to Deepseek *      应用开发者: 凹凸曼
 *      开发者QQ: 3489214354
 *      更新日期: 202605111942
 *      授权域名: zwwx.club
 *      授权码: 2026051119cxxquXxUk1
 *      未经应用程序开发者/所有者的书面许可，不得进行反向工程、反向汇编、反向编译等，不得擅自复制、修改、链接、转载、汇编、发表、出版、发展与之有关的衍生产品、作品等
 */

/** * */
if(!defined('IN_DISCUZ')){
exit('Acccess Denied');
} 
class table_discuz_to_deepseek_error extends discuz_table{
	public function __construct(){
		$this->_table = 'plugin_discuz_to_deepseek_err';
		$this->_pk    = 'id';
		parent::__construct();
	}

}
?>