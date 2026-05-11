<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once dirname(__FILE__) . '/api/ApoylDeepseekAiPostUtils.class.php';

global $_G;

$cache = ApoylDeepseekAiPostUtils::pluginConfig();
$tid = intval($_GET['tid']);
$come = isset($_GET['come']) ? trim($_GET['come']) : '';
$text = '';
$post = null;
$threadrow = null;
$quotemessage = '';
$postuid = 0;
$postusername = '';

if (!$tid) {
    exit();
}

if (empty($cache['openai'])) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_close'));
}

if (!ApoylDeepseekAiPostUtils::isGroupAllowed($cache, $_G['groupid'])) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_groupid'));
}

if (!isset($_GET['formhash']) || $_GET['formhash'] != FORMHASH) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_formhash'));
}

$userarr = ApoylDeepseekAiPostUtils::configCsvInts(isset($cache['users']) ? $cache['users'] : '');
if (!$userarr) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_uid'));
}

$postuid = $userarr[array_rand($userarr)];
$member = C::t('common_member')->fetch($postuid);
if (!$member) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_username'));
}
$postusername = $member['username'];

if (!empty($cache['limitnums']) && intval($cache['limitnums']) > 0) {
    $postnum = C::t('forum_post')->count_visiblepost_by_tid($tid);
    if (intval($cache['limitnums']) <= $postnum) {
        exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_postnum'));
    }
}

if (!empty($cache['openlimittype'])) {
    $file = _fileapoylv2('type');
    if ($file) {
        include $file;
    }
}

if (!empty($cache['opentime']) || !empty($cache['opendelayreply']) || !empty($cache['openfirstvip'])) {
    $threadrow = C::t('forum_thread')->fetch($tid);
}

if (!empty($cache['opentime']) && $threadrow && !empty($cache['limittime']) && $threadrow['dateline'] <= strtotime($cache['limittime'])) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_time'));
}

if (!empty($cache['opendelayreply']) && $threadrow) {
    $delaytime = getRandNums(isset($cache['delaytime']) ? $cache['delaytime'] : '');
    if ($delaytime && $threadrow['lastpost'] + $delaytime >= TIMESTAMP) {
        exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_delay'));
    }
}

$rolename = '';
if (!empty($cache['openrole'])) {
    $file = _fileapoylv2('role');
    if ($file) {
        include $file;
    }
}

if (!empty($cache['openfirstvip'])) {
    $file = _fileapoylv2('firstvip');
    if ($file) {
        include $file;
    }
} elseif (!empty($cache['openautoreply'])) {
    $post = C::t('#apoyl_deepseekaipost#forum_postext')->fetch_last_new($tid, array(0, -2));
    if (!$post) {
        exit();
    }

    if (in_array(intval($post['authorid']), $userarr)) {
        exit();
    }

    if (!empty($cache['openattach']) && $post['attachment'] > 0) {
        exit();
    }

    require_once libfile('function/post');
    if ($post['first']) {
        $text = selectInput($cache, $post);
    } else {
        if (stripos($post['message'], '[/quote]') !== false) {
            $parts = explode('[/quote]', $post['message'], 2);
            $text = $parts[1];
        } else {
            $text = $post['message'];
        }
        $text = trim(messagecutstr($text, 2000));
    }

    if (!empty($cache['openquote'])) {
        $quotemessage = buildQuoteMessage($post, $text);
    }
} else {
    $modpost = C::t('#apoyl_deepseekaipost#forum_postext')->fetch_threadpost_by_tid_invisible_new($tid, -2);
    if ($modpost) {
        exit();
    }

    $post = C::t('forum_post')->fetch_threadpost_by_tid_invisible($tid, 0);
    $thread = C::t('forum_thread')->fetch($tid, 0);
    if (!$post || !$thread || $thread['replies'] > 0) {
        exit();
    }

    $text = selectInput($cache, $post);
}

if (!$post || empty($post['fid']) || empty($post['tid'])) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_text'));
}

if ($come != 'group' && !ApoylDeepseekAiPostUtils::isForumAllowed($cache, $post['fid'])) {
    exit();
}

if (!trim($text)) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_text'));
}

if (!function_exists('curl_init')) {
    exitWithDebug($cache, $tid, lang('plugin/apoyl_deepseekaipost', 'err_curl'));
}

$limitword = '';
$openlimit = isset($cache['openlimit']) ? intval($cache['openlimit']) : 0;
if ($openlimit > 0) {
    $limitword = lang('plugin/apoyl_deepseekaipost', 'aiclimit' . $openlimit);
}

if (!empty($cache['openlimittriggering'])) {
    $file = _fileapoylv2('limittriggering');
    if ($file) {
        include $file;
    }
}

require_once dirname(__FILE__) . '/api/ApoylDeepseekAiPostComm.class.php';
$apoyldeepseekaipostcomm = new ApoylDeepseekAiPostComm();
list($isnewcontent, $newcontent, $reobj) = $apoyldeepseekaipostcomm->factoryAotu($text . $limitword, $rolename, $cache);

if ($isnewcontent) {
    $invisible = 0;
    if (!empty($cache['openinvisible']) && !in_array($_G['groupid'], ApoylDeepseekAiPostUtils::configArray($cache, 'mgroups'))) {
        $invisible = -2;
    }

    $status = defined('IN_MOBILE') ? 8 : 0;
    $from = !empty($cache['openfrom']) && isset($cache['from']) ? "    \n\n" . $cache['from'] : '';

    require_once libfile('function/forum');
    $pid = insertpost(array(
        'fid' => $post['fid'],
        'tid' => $post['tid'],
        'first' => '0',
        'author' => $postusername,
        'authorid' => $postuid,
        'subject' => '',
        'dateline' => TIMESTAMP,
        'message' => $quotemessage . apoylGbk($newcontent, CHARSET) . $from,
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
        if ($invisible == -2) {
            C::t('common_moderate')->insert('pid', array(
                'id' => $pid,
                'status' => 0,
                'dateline' => TIMESTAMP
            ), false, true);
        }

        C::t('forum_thread')->update($post['tid'], array('lastposter' => $postusername, 'lastpost' => TIMESTAMP), true);
        $lastpost = $post['tid'] . "\t" . $post['subject'] . "\t" . TIMESTAMP . "\t" . $postusername;
        C::t('forum_forum')->update($post['fid'], array('lastpost' => $lastpost));
        C::t('forum_forum')->update_forum_counter($post['fid'], 0, 1, 1);
        if ($invisible == 0) {
            C::t('forum_thread')->increase($post['tid'], array('replies' => 1));
        }
    }
}

debugApoylAotu(!empty($cache['opendebug']), $tid, $reobj);
exit();

function exitWithDebug($cache, $tid, $message)
{
    debugApoylAotu(!empty($cache['opendebug']), $tid, $message);
    exit();
}

function debugApoylAotu($opendebug, $tid, $message)
{
    ApoylDeepseekAiPostUtils::debug($opendebug, $tid, $message);
}

function apoylGbk($var, $charset)
{
    if ($charset == 'gbk') {
        return diconv($var, 'utf-8', $charset);
    }

    return $var;
}

function _fileapoylv2($filename)
{
    return ApoylDeepseekAiPostUtils::componentFile($filename);
}

function selectInput($cache, $post)
{
    require_once libfile('function/post');

    if (isset($cache['selectfirst']) && intval($cache['selectfirst']) == 2) {
        return trim($post['subject']) . trim(messagecutstr($post['message'], 3000));
    }

    if (isset($cache['selectfirst']) && intval($cache['selectfirst']) == 3) {
        return trim($post['message']);
    }

    return trim($post['subject']);
}

function getRandNums($autonums)
{
    $autonums = trim((string)$autonums);
    if ($autonums === '') {
        return 0;
    }

    if (strpos($autonums, '~') === false) {
        return max(0, intval($autonums));
    }

    $tmp = explode('~', $autonums, 2);
    $min = max(0, intval($tmp[0]));
    $max = max($min, intval($tmp[1]));
    return rand($min, $max);
}

function buildQuoteMessage($post, $text)
{
    require_once libfile('function/post');

    $time = dgmdate($post['dateline']);
    $quotemessage = messagecutstr($text, 100);
    $quotemessage = implode("\n", array_slice(explode("\n", $quotemessage), 0, 3));
    $post_reply_quote = lang('forum/misc', 'post_reply_quote', array(
        'author' => $post['author'],
        'time' => $time
    ));

    if (!defined('IN_MOBILE')) {
        return "\n\n[quote][size=2][url=forum.php?mod=redirect&goto=findpost&pid=" . intval($post['pid']) . "&ptid=" . intval($post['tid']) . "][color=#999999]" . $post_reply_quote . "[/color][/url][/size]\n" . $quotemessage . "[/quote]";
    }

    return "\n\n[quote][color=#999999]" . $post_reply_quote . "[/color]\n[color=#999999]" . $quotemessage . "[/color][/quote]";
}

?>
