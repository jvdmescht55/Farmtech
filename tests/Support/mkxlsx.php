<?php
// Tiny .xlsx writer for import tests (inline strings and numbers only).
// Tiny xlsx writer for tests: mkxlsx(path, [sheetName => rows]) with inline strings / numbers.
function mkxlsx(string $path, array $sheets): void {
    $z = new ZipArchive; $z->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $n = count($sheets);
    $ct = '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
    for ($i=1;$i<=$n;$i++) $ct .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    $z->addFromString('[Content_Types].xml', $ct.'</Types>');
    $z->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $wb = '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
    $rels = '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $i = 0;
    foreach ($sheets as $name => $rows) { $i++;
        $wb .= '<sheet name="'.htmlspecialchars($name).'" sheetId="'.$i.'" r:id="rId'.$i.'"/>';
        $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        $x = '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $r => $row) { $x .= '<row r="'.($r+1).'">';
            foreach ($row as $c => $v) { if ($v === '' || $v === null) continue; $ref = chr(65+$c).($r+1);
                $x .= is_int($v)||is_float($v) ? '<c r="'.$ref.'"><v>'.$v.'</v></c>' : '<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars($v).'</t></is></c>'; }
            $x .= '</row>'; }
        $z->addFromString('xl/worksheets/sheet'.$i.'.xml', $x.'</sheetData></worksheet>');
    }
    $z->addFromString('xl/workbook.xml', $wb.'</sheets></workbook>');
    $z->addFromString('xl/_rels/workbook.xml.rels', $rels.'</Relationships>');
    $z->close();
}
