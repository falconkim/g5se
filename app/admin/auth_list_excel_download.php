<?php
/*
 * /admin/auth_list_excel_download — 관리권한 목록 XLSX 다운로드.
 */
$sub_menu = '100200';
require_once __DIR__.'/_common.php';
require_once __DIR__.'/_layout.php';
admin_require_login();

if ($is_admin !== 'super') {
    header('Location: '.G5_ADMIN_URL, true, 302);
    exit;
}

require_once __DIR__.'/admin.lib.php';
require_once G5_LIB_PATH.'/excel.lib.php';

auth_check_menu($auth, $sub_menu, 'r');

$stx = isset($_GET['stx']) ? trim((string)$_GET['stx']) : '';
$allowedSorts = array('a.mb_id', 'mb_nick', 'au_menu', 'au_auth', 'a.mb_id, au_menu');
$sst = isset($_GET['sst']) && in_array($_GET['sst'], $allowedSorts, true) ? $_GET['sst'] : 'a.mb_id, au_menu';
$sod = isset($_GET['sod']) && in_array(strtolower((string)$_GET['sod']), array('asc', 'desc'), true)
    ? strtolower((string)$_GET['sod'])
    : '';

$where = ' WHERE 1 = 1';
$params = array();
if ($stx !== '') {
    $where .= ' AND a.mb_id LIKE :stx';
    $params[':stx'] = '%'.$stx.'%';
}

$sql = "SELECT a.mb_id, b.mb_nick, a.au_menu, a.au_auth
          FROM {$g5['auth_table']} a
          LEFT JOIN {$g5['member_table']} b ON a.mb_id = b.mb_id
          {$where}
         ORDER BY {$sst} {$sod}";
$result = sql_pdo_query($sql, $params);

$headers = array('회원아이디', '닉네임', '메뉴번호', '메뉴명', '권한');
$rows = array();
while ($row = sql_pdo_fetch_array($result)) {
    // 화면에서 자동 정리되는 유효하지 않은 회원·메뉴 권한은 내보내지 않는다.
    if (($row['mb_id'] === '' && $row['mb_nick'] === '') || !isset($auth_menu[$row['au_menu']])) {
        continue;
    }
    $rows[] = array(
        $row['mb_id'],
        $row['mb_nick'],
        $row['au_menu'],
        $auth_menu[$row['au_menu']],
        $row['au_auth'],
    );
}

$filename = 'admin_permissions_'.date('Ymd_His').'.xlsx';

excel_download($filename, '관리권한', $headers, $rows, array(
    'widths' => array(22, 20, 14, 36, 12),
    'header_bg' => '#D9EAF7',
));
