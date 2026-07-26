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
 *        freeze(기본 true), autofilter(기본 true),
 *        wrap_cols(0-based 열 인덱스 배열 — 개행 포함 값을 엑셀에서 줄바꿈 표시)
 */
function excel_xlsx($sheet_name, $headers, $rows, $opts = array())
{
    $header_bg  = isset($opts['header_bg']) ? $opts['header_bg'] : '#D9EAF7';
    $freeze     = !isset($opts['freeze']) || $opts['freeze'];
    $autofilter = !isset($opts['autofilter']) || $opts['autofilter'];
    $prepend    = isset($opts['prepend_rows']) ? $opts['prepend_rows'] : array();
    $wrap_cols  = isset($opts['wrap_cols']) ? array_flip($opts['wrap_cols']) : array();

    // "\0" 접두사는 SimpleXLSXGen 의 raw 문자열 표기 — 타입 자동변환·태그 해석을 막는다.
    // 대신 raw 셀은 스타일이 전혀 붙지 않아 줄바꿈 표시(wraptext)를 할 수 없어서,
    // wrap_cols 열은 아래 두 조건을 모두 만족할 때만 <wraptext> 태그 표기로 내보낸다.
    //   - 셀 중간에 개행이 있을 것: 개행이 있으면 숫자·날짜 자동변환 패턴에 걸리지 않아 값이 그대로 남는다
    //   - strip_tags 로 사라지는 부분이 없을 것: 태그 표기는 남은 텍스트에 strip_tags 가 적용된다
    // 조건을 못 맞추면 raw 로 저장한다 — 줄바꿈 표시를 포기하고 값 보존을 택한다.
    $as_string = static function ($row) use ($wrap_cols) {
        $out = array();
        $i = 0;
        foreach ($row as $v) {
            $s = (string)$v;
            if (isset($wrap_cols[$i]) && strpos(rtrim($s, "\r\n"), "\n") !== false && strip_tags($s) === $s) {
                $out[] = '<wraptext>'.$s;
            } else {
                $out[] = "\0".$s;
            }
            $i++;
        }
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
