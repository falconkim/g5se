<?php
$sub_menu = '400400';
require_once __DIR__.'/_common.php';

require_once __DIR__.'/../_layout.php';
admin_require_login();
auth_check_menu($auth, $sub_menu, 'w');
auth_check_menu($auth, $sub_menu, "w");

// 주문정보
$sql = " select *
            from {$g5['g5_shop_order_table']}
            where od_misu = '0'
              and od_status = '준비'
            order by od_id desc ";
$result = sql_pdo_query($sql);

if(!@sql_num_rows($result))
    alert_close('배송처리할 주문 내역이 없습니다.');

require_once G5_LIB_PATH.'/excel.lib.php';

$headers = array('주문번호', '주문자명', '주문자전화1', '주문자전화2', '배송자명', '배송지전화1', '배송지전화2', '배송지주소', '배송회사', '운송장번호');
$widths  = array(18, 15, 15, 15, 15, 15, 15, 50, 20, 20);
$rows = array();

while ($row = sql_pdo_fetch_array($result)) {
    $rows[] = array($row['od_id'],
                    $row['od_name'],
                    $row['od_tel'],
                    $row['od_hp'],
                    $row['od_b_name'],
                    $row['od_b_tel'],
                    $row['od_b_hp'],
                    print_address($row['od_b_addr1'], $row['od_b_addr2'], $row['od_b_addr3'], $row['od_b_addr_jibeon']),
                    $row['od_delivery_company'],
                    $row['od_invoice']);
}

// 각 셀 값을 문자열로 강제 (스프레드시트가 = 로 시작하는 값을 수식으로 평가하지 않도록)
if (function_exists('csv_safe_cell')) {
    foreach ($rows as $ri => $r) {
        foreach ($r as $ci => $c) $rows[$ri][$ci] = csv_safe_cell($c);
    }
}

excel_download('deliverylist-'.date("ymd", time()).'.xlsx', '배송처리', $headers, $rows, array(
    'widths' => $widths,
    'header_bg' => '#ABCDEF',
    'wrap_cols' => array(7), // 배송지주소
));
