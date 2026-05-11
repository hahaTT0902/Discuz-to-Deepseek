<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class table_forum_postext extends table_forum_post
{
    public function __construct()
    {
        $this->_table = 'forum_post';
        $this->_pk = 'pid';
        parent::__construct();
    }

    public function fetch_threadpost_by_tid_invisible_new($tid, $invisible = null)
    {
        return DB::fetch_first(
            'SELECT tid FROM %t WHERE tid=%d ' . ($invisible !== null ? ' AND ' . DB::field('invisible', $invisible) : '') . ' limit 1',
            array(self::get_tablename('tid:' . $tid), $tid)
        );
    }

    public function fetch_last_new($tid, $invisible = null)
    {
        return DB::fetch_first(
            'SELECT tid,first,attachment,fid,pid,message,subject,authorid,author,dateline FROM %t WHERE tid=%d ' . ($invisible !== null ? ' AND ' . DB::field('invisible', $invisible, 'in') : '') . ' order by pid desc limit 1',
            array(self::get_tablename('tid:' . $tid), $tid)
        );
    }
}

?>
