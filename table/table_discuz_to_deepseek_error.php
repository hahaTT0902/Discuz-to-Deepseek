<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class table_discuz_to_deepseek_error extends discuz_table
{
    public function __construct()
    {
        $this->_table = 'plugin_discuz_to_deepseek_err';
        $this->_pk = 'id';
        parent::__construct();
    }

    public function ensureTable()
    {
        DB::query('CREATE TABLE IF NOT EXISTS ' . DB::table($this->_table) . " (
            id int(10) unsigned NOT NULL AUTO_INCREMENT,
            tid int(10) unsigned NOT NULL DEFAULT '0',
            message text NOT NULL,
            addtime int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (id),
            KEY tid (tid),
            KEY addtime (addtime)
        ) ENGINE=MyISAM", 'SILENT');
    }
}

?>
