<?php

declare(strict_types=1);

/**
 * Minimal Excel interchange helpers for UC01/UC02.
 * Supports XLSX when ZipArchive is available, plus CSV/SpreadsheetML fallback.
 * The requirements do not prescribe a single official Excel file format.
 * [MISSING SPECIFICATION] official template MIME/format.
 */
function excel_col_name(int $index): string
{
    $name = '';
    while ($index > 0) {
        $index--;
        $name = chr(65 + ($index % 26)) . $name;
        $index = intdiv($index, 26);
    }
    return $name;
}

function excel_xml_download(string $filename, array $headers, array $rows): void
{
    $xml = '<?xml version="1.0"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
    $xml .= '<Worksheet ss:Name="Sheet1"><Table>';
    $xml .= '<Row>';
    foreach ($headers as $h) $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars((string)$h, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Data></Cell>';
    $xml .= '</Row>';
    foreach ($rows as $row) {
        $xml .= '<Row>';
        foreach ($row as $value) {
            $type = is_numeric($value) && $value !== '' ? 'Number' : 'String';
            $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Data></Cell>';
        }
        $xml .= '</Row>';
    }
    $xml .= '</Table></Worksheet></Workbook>';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('X-Content-Type-Options: nosniff');
    echo $xml;
    exit;
}

function excel_xlsx_bytes(array $headers, array $rows): string
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Máy chủ chưa bật ZipArchive để tạo XLSX.');
    }

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
    if ($tmp === false) throw new RuntimeException('Không thể tạo file Excel tạm.');

    try {
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không thể tạo file Excel.');
        }

        // Accept both string and numeric cell values. Keep numeric values numeric
        // in the XLSX cell so export does not change their data type. Strings stay
        // shared strings so identifiers with leading zeroes are preserved.
        $esc = static fn($value): string => htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $shared = [];
        $sharedCount = 0;
        $all = array_merge([$headers], $rows);

        foreach ($all as $row) {
            foreach ($row as $value) {
                if (is_int($value) || is_float($value)) continue;
                $value = (string)$value;
                $sharedCount++;
                if (!array_key_exists($value, $shared)) $shared[$value] = count($shared);
            }
        }

        // OOXML requires `count` to be the total number of string-cell references,
        // while `uniqueCount` is the number of unique shared-string entries.
        $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' count="' . $sharedCount . '" uniqueCount="' . count($shared) . '">';
        foreach (array_keys($shared) as $value) {
            $sharedXml .= '<si><t>' . $esc($value) . '</t></si>';
        }
        $sharedXml .= '</sst>';

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $rnum = 1;
        foreach ($all as $row) {
            $sheet .= '<row r="' . $rnum . '">';
            $c = 1;
            foreach ($row as $value) {
                $ref = excel_col_name($c++) . $rnum;
                if (is_int($value) || is_float($value)) {
                    $sheet .= '<c r="' . $ref . '"><v>' . $esc($value) . '</v></c>';
                } else {
                    $key = (string)$value;
                    $sheet .= '<c r="' . $ref . '" t="s"><v>' . $shared[$key] . '</v></c>';
                }
            }
            $sheet .= '</row>';
            $rnum++;
        }
        $sheet .= '</sheetData></worksheet>';

        $zip->addFromString(
            '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
                . '</Types>'
        );
        $zip->addFromString(
            '_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>'
        );
        $zip->addFromString(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>'
        );
        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
                . '</Relationships>'
        );
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->addFromString('xl/sharedStrings.xml', $sharedXml);

        if ($zip->close() !== true) {
            throw new RuntimeException('Không thể hoàn tất file Excel.');
        }

        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Không thể đọc file Excel đã tạo.');
        }
        return $bytes;
    } finally {
        if (is_file($tmp)) unlink($tmp);
    }
}

function excel_xlsx_download(string $filename, array $headers, array $rows): void
{
    if (!class_exists('ZipArchive')) {
        excel_xml_download(preg_replace('/\.xlsx$/i', '.xls', $filename), $headers, $rows);
    }

    $bytes = excel_xlsx_bytes($headers, $rows);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}


function excel_csv_download(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'wb');
    if ($out === false) throw new RuntimeException('Không thể tạo file CSV.');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

function excel_normalize_header(string $v): string
{
    $v = trim($v);
    $v = preg_replace('/\xEF\xBB\xBF/', '', $v) ?? $v;
    return strtolower($v);
}

function excel_parse_csv(string $path): array
{
    $fh = fopen($path, 'rb');
    if (!$fh) throw new RuntimeException('Không thể đọc file.');
    $rows = [];
    while (($row = fgetcsv($fh)) !== false) {
        $row = array_map(static fn($v) => trim((string)$v), $row);
        if ($rows === [] && isset($row[0])) {
            // UTF-8 BOM is a transport/encoding marker, not part of the header value.
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]) ?? $row[0];
        }
        $rows[] = $row;
    }
    fclose($fh);
    return $rows;
}

function excel_parse_spreadsheetml(string $content): array
{
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content);
    if (!$xml) throw new InvalidArgumentException('File Excel XML không hợp lệ.');
    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
    $rows = [];
    foreach ($xml->xpath('//ss:Worksheet/ss:Table/ss:Row') as $row) {
        $cells = [];
        foreach ($row->xpath('./ss:Cell/ss:Data') as $cell) $cells[] = trim((string)$cell);
        $rows[] = $cells;
    }
    return $rows;
}

function excel_parse_xlsx(string $path): array
{
    if (!class_exists('ZipArchive')) throw new InvalidArgumentException('Máy chủ chưa bật ZipArchive để đọc XLSX.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new InvalidArgumentException('File XLSX không hợp lệ.');
    $shared = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sx = simplexml_load_string($sharedXml);
        if (!$sx) throw new InvalidArgumentException('sharedStrings.xml không hợp lệ.');
        $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($sx->xpath('//m:si') as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $texts = $si->xpath('.//m:t');
            if ($texts === false) throw new InvalidArgumentException('sharedStrings.xml không thể truy vấn namespace.');
            $shared[] = implode('', array_map('strval', $texts));
        }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) throw new InvalidArgumentException('Không tìm thấy worksheet đầu tiên.');
    $sx = simplexml_load_string($sheetXml);
    if (!$sx) throw new InvalidArgumentException('Worksheet không hợp lệ.');
    $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $rows = [];
    $sheetRows = $sx->xpath('//m:sheetData/m:row');
    if ($sheetRows === false) throw new InvalidArgumentException('Worksheet không thể truy vấn namespace.');
    foreach ($sheetRows as $row) {
        $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $cells = [];
        $rowCells = $row->xpath('./m:c');
        if ($rowCells === false) throw new InvalidArgumentException('Worksheet row không thể truy vấn namespace.');
        foreach ($rowCells as $cell) {
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)\d+/', $ref, $m);
            $col = 1;
            if (!empty($m[1])) {
                $col = 0;
                foreach (str_split($m[1]) as $ch) $col = $col * 26 + ord($ch) - 64;
            }
            while (count($cells) < $col - 1) $cells[] = '';
            $v = (string)($cell->v ?? '');
            if ((string)$cell['t'] === 's') $v = $shared[(int)$v] ?? '';
            if ((string)$cell['t'] === 'inlineStr') {
                $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $v = implode('', array_map('strval', $cell->xpath('./m:is//m:t') ?: []));
            }
            $cells[] = trim($v);
        }
        $rows[] = $cells;
    }
    return $rows;
}

function excel_parse_upload(string $path, string $name): array
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $content = file_get_contents($path);
    if ($content === false) throw new InvalidArgumentException('Không thể đọc file.');
    if ($ext === 'xlsx') return excel_parse_xlsx($path);
    if ($ext === 'csv' || $ext === 'txt') return excel_parse_csv($path);
    if ($ext === 'xls' || str_contains(substr($content, 0, 300), 'urn:schemas-microsoft-com:office:spreadsheet')) return excel_parse_spreadsheetml($content);
    throw new InvalidArgumentException('Định dạng file không được hỗ trợ. Vui lòng dùng file mẫu Excel/CSV.');
}
