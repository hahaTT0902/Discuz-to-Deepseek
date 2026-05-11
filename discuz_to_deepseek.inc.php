<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once dirname(__FILE__) . '/api/DiscuzToDeepseekUtils.class.php';

global $_G;

$cache = DiscuzToDeepseekUtils::pluginConfig();
$tid   = intval(isset($_GET['tid']) ? $_GET['tid'] : 0);
$come  = isset($_GET['come']) ? trim($_GET['come']) : '';

// ── Portal article auto-reply entry point ───────────────────────────────────
if ($come === 'article') {
    $aid = intval(isset($_GET['aid']) ? $_GET['aid'] : 0);
    if (!$aid) {
        exit();
    }
    if (!discuzToDeepseekTrustedRequest($cache, $aid, 'article')) {
        exit();
    }
    if (!DiscuzToDeepseekUtils::canRenderArticle($cache)) {
        exit();
    }
    if (!DiscuzToDeepseekUtils::isGroupAllowed($cache, $_G['groupid'])) {
        exit();
    }
    if (!function_exists('curl_init')) {
        exit();
    }

    $userarr = DiscuzToDeepseekUtils::configCsvInts(isset($cache['users']) ? $cache['users'] : '');
    if (!$userarr) {
        exit();
    }
    $postuid  = $userarr[array_rand($userarr)];
    $amember  = C::t('common_member')->fetch($postuid);
    if (!$amember) {
        exit();
    }

    $articleTitle   = C::t('portal_article_title')->fetch($aid);
    $articleContent = C::t('portal_article_content')->fetch($aid);
    if (!$articleTitle || empty($articleTitle['title'])) {
        exit();
    }
    $bodyText = isset($articleContent['content']) ? strip_tags($articleContent['content']) : '';
    $bodyText = trim(mb_substr($bodyText, 0, 3000, 'UTF-8'));

    $selectfirst = isset($cache['selectfirst']) ? intval($cache['selectfirst']) : 2;
    if ($selectfirst == 1) {
        $atext = trim($articleTitle['title']);
    } elseif ($selectfirst == 3) {
        $atext = $bodyText;
    } else {
        $atext = trim($articleTitle['title']) . "\n" . $bodyText;
    }

    if (!trim($atext)) {
        exit();
    }

    require_once dirname(__FILE__) . '/api/DiscuzToDeepseekComm.class.php';
    $acomm = new DiscuzToDeepseekComm();
    list($aisnew, $anewcontent) = $acomm->factoryAotu($atext, '', $cache);
    if ($aisnew) {
        C::t('portal_comment')->insert(array(
            'aid'       => $aid,
            'authorid'  => $postuid,
            'author'    => $amember['username'],
            'dateline'  => TIMESTAMP,
            'message'   => discuzToDeepseekCharset($anewcontent, CHARSET),
            'useip'     => '',
            'invisible' => 0,
            'anonymous' => 0,
        ));
    }
    exit();
}
// ── End portal article entry point ──────────────────────────────────────────

$text         = '';
$post         = null;
$threadrow    = null;
$quotemessage = '';
$postuid      = 0;
$postusername = '';

if (!$tid) {
    exit();
}

if (empty($cache['openai'])) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_close'));
}

if (!DiscuzToDeepseekUtils::isGroupAllowed($cache, $_G['groupid'])) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_groupid'));
}

if (!discuzToDeepseekTrustedRequest($cache, $tid, $come)) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_formhash'));
}

$userarr = DiscuzToDeepseekUtils::configCsvInts(isset($cache['users']) ? $cache['users'] : '');
if (!$userarr) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_uid'));
}

$postuid = $userarr[array_rand($userarr)];
$member  = C::t('common_member')->fetch($postuid);
if (!$member) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_username'));
}
$postusername = $member['username'];

if (!empty($cache['limitnums']) && intval($cache['limitnums']) > 0) {
    $postnum = C::t('forum_post')->count_visiblepost_by_tid($tid);
    if (intval($cache['limitnums']) <= $postnum) {
        exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_postnum'));
    }
}

if (!empty($cache['openlimittype'])) {
    $file = discuzToDeepseekComponentFile('type');
    if ($file) {
        include $file;
    }
}

if (!empty($cache['opentime']) || !empty($cache['opendelayreply']) || !empty($cache['openfirstvip'])) {
    $threadrow = C::t('forum_thread')->fetch($tid);
}

if (!empty($cache['opentime']) && $threadrow && !empty($cache['limittime']) && $threadrow['dateline'] <= strtotime($cache['limittime'])) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_time'));
}

if (!empty($cache['opendelayreply']) && $threadrow) {
    $delaytime = getRandNums(isset($cache['delaytime']) ? $cache['delaytime'] : '');
    if ($delaytime && $threadrow['lastpost'] + $delaytime >= TIMESTAMP) {
        exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_delay'));
    }
}

$rolename = '';
if (!empty($cache['openrole'])) {
    $file = discuzToDeepseekComponentFile('role');
    if ($file) {
        include $file;
    }
}

if (!empty($cache['openfirstvip'])) {
    $file = discuzToDeepseekComponentFile('firstvip');
    if ($file) {
        include $file;
    }
} elseif (!empty($cache['openautoreply'])) {
    $post = C::t('#discuz_to_deepseek#forum_postext')->fetch_last_new($tid, array(0, -2));
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
            $text  = $parts[1];
        } else {
            $text = $post['message'];
        }
        $text = trim(messagecutstr($text, 2000));
    }

    if (!empty($cache['openquote'])) {
        $quotemessage = buildQuoteMessage($post, $text);
    }
} else {
    $modpost = C::t('#discuz_to_deepseek#forum_postext')->fetch_threadpost_by_tid_invisible_new($tid, -2);
    if ($modpost) {
        exit();
    }

    $post = C::t('#discuz_to_deepseek#forum_postext')->fetch_last_new($tid, array(0));
    if (!$post || !$post['first']) {
        exit();
    }

    $text = selectInput($cache, $post);
}

if (!$post || empty($post['fid']) || empty($post['tid'])) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_text'));
}

if ($come != 'group' && !DiscuzToDeepseekUtils::isForumAllowed($cache, $post['fid'])) {
    exit();
}

if (!trim($text)) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_text'));
}

if (!function_exists('curl_init')) {
    exitWithDebug($cache, $tid, lang('plugin/discuz_to_deepseek', 'err_curl'));
}

$limitword = '';
$openlimit = isset($cache['openlimit']) ? intval($cache['openlimit']) : 0;
if ($openlimit > 0) {
    $limitword = lang('plugin/discuz_to_deepseek', 'aiclimit' . $openlimit);
}

if (!empty($cache['openlimittriggering'])) {
    $file = discuzToDeepseekComponentFile('limittriggering');
    if ($file) {
        include $file;
    }
}

require_once dirname(__FILE__) . '/api/DiscuzToDeepseekComm.class.php';
$discuzToDeepseekComm = new DiscuzToDeepseekComm();
list($isnewcontent, $newcontent, $reobj) = $discuzToDeepseekComm->factoryAotu($text . $limitword, $rolename, $cache);

if ($isnewcontent) {
    $invisible = 0;
    if (!empty($cache['openinvisible']) && !in_array($_G['groupid'], DiscuzToDeepseekUtils::configArray($cache, 'mgroups'))) {
        $invisible = -2;
    }

    $status = defined('IN_MOBILE') ? 8 : 0;
    $from   = !empty($cache['openfrom']) && isset($cache['from']) ? "    \n\n" . $cache['from'] : '';

    require_once libfile('function/forum');
    $pid = insertpost(array(
        'fid'         => $post['fid'],
        'tid'         => $post['tid'],
        'first'       => '0',
        'author'      => $postusername,
        'authorid'    => $postuid,
        'subject'     => '',
        'dateline'    => TIMESTAMP,
        'message'     => $quotemessage . discuzToDeepseekCharset($newcontent, CHARSET) . $from,
        'useip'       => '',
        'invisible'   => $invisible,
        'anonymous'   => '0',
        'usesig'      => '0',
        'htmlon'      => 0,
        'bbcodeoff'   => 0,
        'smileyoff'   => 0,
        'parseurloff' => 0,
        'attachment'  => '0',
        'status'      => $status,
    ));

    if ($pid) {
        if ($invisible == -2) {
            C::t('common_moderate')->insert('pid', array(
                'id'       => $pid,
                'status'   => 0,
                'dateline' => TIMESTAMP,
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

debugDiscuzToDeepseek(!empty($cache['opendebug']), $tid, $reobj);
exit();

function exitWithDebug($cache, $tid, $message)
{
    debugDiscuzToDeepseek(!empty($cache['opendebug']), $tid, $message);
    exit();
}

function debugDiscuzToDeepseek($opendebug, $tid, $message)
{
    DiscuzToDeepseekUtils::debug($opendebug, $tid, $message);
}

function discuzToDeepseekCharset($var, $charset)
{
    if ($charset == 'gbk') {
        return diconv($var, 'utf-8', $charset);
    }
    return $var;
}

function discuzToDeepseekComponentFile($filename)
{
    return DiscuzToDeepseekUtils::componentFile($filename);
}

function discuzToDeepseekTrustedRequest($cache, $id, $come)
{
    $isInternal = isset($_GET['internal']) && $_GET['internal'] === '1';
    if ($isInternal) {
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        if ($token === '') {
            return false;
        }
        $expected = DiscuzToDeepseekUtils::internalToken($id, $come);
        return discuzToDeepseekHashEquals($expected, $token);
    }

    return isset($_GET['formhash']) && $_GET['formhash'] === FORMHASH;
}

function discuzToDeepseekHashEquals($knownString, $userString)
{
    if (function_exists('hash_equals')) {
        return hash_equals($knownString, $userString);
    }

    $knownString = (string)$knownString;
    $userString = (string)$userString;
    $knownLength = strlen($knownString);
    $userLength = strlen($userString);
    if ($knownLength !== $userLength) {
        return false;
    }

    $result = 0;
    for ($i = 0; $i < $knownLength; $i++) {
        $result |= ord($knownString[$i]) ^ ord($userString[$i]);
    }
    return $result === 0;
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

    $time      = dgmdate($post['dateline']);
    $quotemessage = messagecutstr($text, 100);
    $quotemessage = implode("\n", array_slice(explode("\n", $quotemessage), 0, 3));
    $post_reply_quote = lang('forum/misc', 'post_reply_quote', array(
        'author' => $post['author'],
        'time'   => $time,
    ));

    if (!defined('IN_MOBILE')) {
        return "\n\n[quote][size=2][url=forum.php?mod=redirect&goto=findpost&pid=" . intval($post['pid']) . "&ptid=" . intval($post['tid']) . "][color=#999999]" . $post_reply_quote . "[/color][/url][/size]\n" . $quotemessage . "[/quote]";
    }

    return "\n\n[quote][color=#999999]" . $post_reply_quote . "[/color]\n[color=#999999]" . $quotemessage . "[/color][/quote]";
}
