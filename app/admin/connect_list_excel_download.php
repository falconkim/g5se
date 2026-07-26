<?php
/*
 * /admin/connect_list_excel_download — 현재 접속자 XLSX 다운로드.
 */
$sub_menu = '200999';
require_once __DIR__.'/_common.php';
require_once __DIR__.'/_layout.php';
admin_require_login();
require_once G5_ADMIN_PATH.'/admin.lib.php';
require_once G5_LIB_PATH.'/excel.lib.php';

if ($is_admin === 'super') {
    auth_check_menu($auth, $sub_menu, 'r');
}

$filter = isset($_GET['filter']) ? (string)$_GET['filter'] : 'all';
if (!in_array($filter, array('all', 'member', 'guest'), true)) {
    $filter = 'all';
}

$where = '';
if ($filter === 'member') {
    $where = " WHERE a.mb_id <> ''";
} elseif ($filter === 'guest') {
    $where = " WHERE a.mb_id = ''";
}

$sql = "SELECT a.mb_id, a.lo_ip, a.lo_datetime, a.lo_location, a.lo_url,
               b.mb_nick, b.mb_name, b.mb_level
          FROM {$g5['login_table']} a
          LEFT JOIN {$g5['member_table']} b ON a.mb_id = b.mb_id
          {$where}
         ORDER BY a.lo_datetime DESC";
$result = sql_pdo_query($sql);

$headers = array('번호', '구분', '회원아이디', '닉네임', '이름', '회원권한', 'IP', '현재 위치', 'URL', '접속 시각');
$rows = array();
$sequence = 1;
while ($row = sql_pdo_fetch_array($result)) {
    $isMember = $row['mb_id'] !== '';
    $rows[] = array(
        $sequence,
        $isMember ? '회원' : '비회원',
        $row['mb_id'],
        $row['mb_nick'],
        $row['mb_name'],
        $isMember ? $row['mb_level'] : '',
        $row['lo_ip'],
        trim((string)$row['lo_location']),
        trim((string)$row['lo_url']),
        $row['lo_datetime'],
    );
    $sequence++;
}

$filterNames = array('all' => 'all', 'member' => 'members', 'guest' => 'guests');
$filename = 'current_connections_'.$filterNames[$filter].'_'.date('Ymd_His').'.xlsx';

excel_download($filename, '현재 접속자', $headers, $rows, array(
    'widths' => array(8, 12, 20, 18, 18, 12, 18, 36, 60, 22),
    'header_bg' => '#D9EAF7',
));
