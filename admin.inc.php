<?php

if (!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

if (isset($_GET['go'], $_GET['formhash']) && $_GET['go'] == 'del' && $_GET['formhash'] == FORMHASH) {
    $delid = intval($_GET['delid']);
    if ($delid > 0) {
        C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->delete($delid);
    }
}

showtableheader();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$prepage = 20;
$start = ($page - 1) * $prepage;
$num = C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->count();
$baseurl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=apoyl_deepseekaipost&pmod=admin';
$prompturl = ADMINSCRIPT . '?action=plugins&operation=config&do=' . intval($pluginid) . '&identifier=apoyl_deepseekaipost&pmod=adminprompt';
$multipage = multi($num, $prepage, $page, $baseurl);
$arr = C::t('#apoyl_deepseekaipost#apoyl_deepseekaipost_error')->range($start, $prepage, 'addtime desc');

showtablerow('', array('colspan="5"'), array('<a href="' . $prompturl . '">DeepSeek Prompt 设置</a>'));

showsubtitle(array(
    'ID',
    lang('plugin/apoyl_deepseekaipost', 'tid'),
    lang('plugin/apoyl_deepseekaipost', 'err_msg'),
    lang('plugin/apoyl_deepseekaipost', 'addtime'),
    lang('plugin/apoyl_deepseekaipost', 'ac')
));

foreach ($arr as $v) {
    $id = intval($v['id']);
    $tid = intval($v['tid']);
    $addtime = !empty($v['addtime']) ? dgmdate($v['addtime'], 'u', '9999', getglobal('setting/dateformat') . ' H:i:s') : '';
    $message = dhtmlspecialchars($v['message']);
    $delurl = $baseurl . '&page=' . $page . '&go=del&delid=' . $id . '&formhash=' . formhash();
    $delhtml = '<a href="' . $delurl . '" onclick="javascript:if(!confirm(\'' . lang('plugin/apoyl_deepseekaipost', 'del_msg') . '\')){return false}">' . lang('plugin/apoyl_deepseekaipost', 'del') . '</a>';

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
