<?php
if (!defined('_GNUBOARD_')) exit;

require_once __DIR__.'/SimpleXLSXGen.php';

// 1-based 열 인덱스를 엑셀 열 문자로 (1→A, 27→AA)
function excel_col_letter($index)
{
    $letter = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod).$letter;
        $index = intdiv($index - $mod, 26);
    }
    return $letter;
}

/**
 * 배열 데이터로 XLSX 객체를 만든다.
 * 헤더 행은 배경색+굵게, 데이터 셀은 전부 문자열로 저장한다(수식 평가 방지).
 *
 * $opts: widths(열 너비 배열), header_bg(#RRGGBB), prepend_rows(헤더 위 행들),
 *        freeze(기본 true), autofilter(기본 true)
 */
function excel_xlsx($sheet_name, $headers, $rows, $opts = array())
{
    $header_bg  = isset($opts['header_bg']) ? $opts['header_bg'] : '#D9EAF7';
    $freeze     = !isset($opts['freeze']) || $opts['freeze'];
    $autofilter = !isset($opts['autofilter']) || $opts['autofilter'];
    $prepend    = isset($opts['prepend_rows']) ? $opts['prepend_rows'] : array();

    // "\0" 접두사는 SimpleXLSXGen 의 raw 문자열 표기 — 타입 자동변환·태그 해석을 막는다
    $as_string = static function ($row) {
        $out = array();
        foreach ($row as $v) $out[] = "\0".(string)$v;
        return $out;
    };

    $data = array();
    foreach ($prepend as $row) $data[] = $as_string($row);

    $header_cells = array();
    foreach ($headers as $h) {
        $header_cells[] = '<style bgcolor="'.$header_bg.'"><b>'.$h.'</b></style>';
    }
    $data[] = $header_cells;
    $header_row = count($data); // 헤더 행 번호 (1-based)

    foreach ($rows as $row) $data[] = $as_string($row);

    $xlsx = new \Shuchkin\SimpleXLSXGen();
    $xlsx->addSheet($data, $sheet_name);

    if (!empty($opts['widths'])) {
        foreach (array_values($opts['widths']) as $i => $w) {
            $xlsx->setColWidth(excel_col_letter($i + 1), $w);
        }
    }
    if ($autofilter && count($headers) > 0) {
        $last = excel_col_letter(count($headers));
        $xlsx->autoFilter('A'.$header_row.':'.$last.$header_row);
    }
    if ($freeze) {
        $xlsx->freezePanes('A'.($header_row + 1));
    }
    return $xlsx;
}

// XLSX 를 만들어 즉시 다운로드하고 종료한다.
function excel_download($filename, $sheet_name, $headers, $rows, $opts = array())
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    session_write_close();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
    excel_xlsx($sheet_name, $headers, $rows, $opts)->downloadAs($filename);
    exit;
}
