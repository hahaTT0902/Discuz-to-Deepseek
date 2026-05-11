<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS pre_plugin_discuz_to_deepseek_err (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  tid int(10) unsigned NOT NULL DEFAULT '0',
  message text NOT NULL,
  addtime int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY tid (tid),
  KEY addtime (addtime)
) ENGINE=MyISAM;
EOF;

runquery($sql);
$finish = true;

?>
