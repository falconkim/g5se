<?php
/*
 * /admin/poll_list_excel_download — 투표 목록 XLSX 다운로드.
 */
$sub_menu = '200900';
require_once __DIR__.'/_common.php';
require_once __DIR__.'/_layout.php';
admin_require_login();
require_once __DIR__.'/admin.lib.php';
require_once G5_LIB_PATH.'/excel.lib.php';

auth_check_menu($auth, $sub_menu, 'r');

$stx = isset($_GET['stx']) ? trim((string)$_GET['stx']) : '';
$allowedSorts = array('po_id', 'po_subject', 'po_level', 'po_use', 'po_etc');
$sst = isset($_GET['sst']) && in_array($_GET['sst'], $allowedSorts, true) ? $_GET['sst'] : 'po_id';
$sod = isset($_GET['sod']) && in_array(strtolower((string)$_GET['sod']), array('asc', 'desc'), true)
    ? strtolower((string)$_GET['sod'])
    : '';

$where = ' WHERE 1 = 1';
$params = array();
if ($stx !== '') {
    $where .= ' AND po_subject LIKE :stx';
    $params[':stx'] = '%'.$stx.'%';
}

$sql = "SELECT po_id, po_subject, po_level, po_etc, po_use, po_date,
               (po_cnt1+po_cnt2+po_cnt3+po_cnt4+po_cnt5+po_cnt6+po_cnt7+po_cnt8+po_cnt9) AS total_votes
          FROM {$g5['poll_table']}
          {$where}
         ORDER BY {$sst} {$sod}";
$result = sql_pdo_query($sql, $params);

$headers = array('번호', '제목', '투표권한', '투표수', '기타의견', '사용', '등록일');
$rows = array();
while ($row = sql_pdo_fetch_array($result)) {
    $rows[] = array(
        $row['po_id'],
        $row['po_subject'],
        $row['po_level'],
        $row['total_votes'],
        $row['po_etc'] ? '사용' : '미사용',
        $row['po_use'] ? '사용' : '미사용',
        $row['po_date'],
    );
}

$filename = 'polls_'.date('Ymd_His').'.xlsx';

excel_download($filename, '투표 목록', $headers, $rows, array(
    'widths' => array(10, 60, 14, 14, 14, 12, 16),
    'header_bg' => '#D9EAF7',
));
