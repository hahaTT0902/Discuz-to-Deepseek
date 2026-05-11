<?php

/**
 * Discuz to Deepseek
 * 开源插件 by hahaTT
 */

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$logTable = C::t('#discuz_to_deepseek#discuz_to_deepseek_error');
$logTable->ensureTable();

if (isset($_GET['go'], $_GET['formhash']) && $_GET['go'] == 'del' && $_GET['formhash'] == FORMHASH) {
    $delid = intval($_GET['delid']);
    if ($delid > 0) {
        $logTable->delete($delid);
    }
}

showtableheader();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$prepage = 20;
$start = ($page - 1) * $prepage;
$num = $logTable->count();
$baseurl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=discuz_to_deepseek&pmod=admin';
$prompturl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=discuz_to_deepseek&pmod=adminprompt';
$multipage = multi($num, $prepage, $page, $baseurl);
$arr = $logTable->range($start, $prepage, 'addtime desc');

showtablerow('', array('colspan="5"'), array('<strong>Discuz to Deepseek</strong> &nbsp; <a href="' . $prompturl . '">提示词设置</a>'));

showsubtitle(array(
    'ID',
    lang('plugin/discuz_to_deepseek', 'tid'),
    lang('plugin/discuz_to_deepseek', 'err_msg'),
    lang('plugin/discuz_to_deepseek', 'addtime'),
    lang('plugin/discuz_to_deepseek', 'ac')
));

foreach ($arr as $v) {
    $id = intval($v['id']);
    $tid = intval($v['tid']);
    $addtime = !empty($v['addtime']) ? dgmdate($v['addtime'], 'u', '9999', getglobal('setting/dateformat') . ' H:i:s') : '';
    $message = dhtmlspecialchars($v['message']);
    $delurl = $baseurl . '&page=' . $page . '&go=del&delid=' . $id . '&formhash=' . formhash();
    $delhtml = '<a href="' . $delurl . '" onclick="javascript:if(!confirm(\'' . lang('plugin/discuz_to_deepseek', 'del_msg') . '\')){return false}">' . lang('plugin/discuz_to_deepseek', 'del') . '</a>';

    showtablerow('', array('width="60"', 'width="60"', 'width="160"', 'width="60"', 'width="60"'), array(
        $id,
        '<a target="_blank" href="forum.php?mod=viewthread&tid=' . $tid . '">' . $tid . '</a>',
        '<font color="#e4862f">' . $message . '</font>',
        $addtime,
        $delhtml
    ));
}

showtablefooter();
echo '<div class="cuspages right">' . $multipage . '</div>';

?>
