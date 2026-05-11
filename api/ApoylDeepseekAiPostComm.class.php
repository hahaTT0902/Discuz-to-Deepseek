<?php

/**
 *      [liyuanchao] (C)2022-2099 http://www.apoyl.com
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: ApoylDeepseekAiPostComm.class.php  2025-2 liyuanchao（凹凸曼） $
 */
class ApoylDeepseekAiPostComm {

	
	public function factoryAotu($text,$rolename,$cache){
		$newcontent='';
		$isnewcontent=false;
		$reobj='';
		$role='';
		if($cache['selectplatform']==2&&$cache['aliyunapikey']&&$cache['openaliyunds']){

			$file=$this->_fileapoylv2('aliyun');
			if($file){
				include $file;
			}
		}else{
			require dirname(__FILE__) . '/ApoylDeepseekAipost.class.php';
			$apoyldeepseekaipost=new ApoylDeepseekAipost();
			$reobj=$apoyldeepseekaipost->getTextDavinci($text,$rolename,$cache);

			$obj = json_decode($reobj);
			if($obj && isset($obj->choices[0]->message->content)){
				$isnewcontent=true;
				$newcontent=$obj->choices[0]->message->content;
			}
		}

		return array($isnewcontent,$newcontent,$reobj);
	}
	private function _fileapoylv2($filename)
	{
		$fileapoyl = dirname(__FILE__) . '/../components/' . $filename . '.php';
		if (file_exists($fileapoyl))
			return $fileapoyl;
		return '';
	}

}

?>