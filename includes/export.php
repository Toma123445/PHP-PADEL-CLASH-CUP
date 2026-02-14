<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db_connect.php';

/**
 * Export data to Excel format using PhpSpreadsheet.
 */
function export_to_excel(array $data, string $filename, array $headers = []): void
{
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        // Fallback: Simple CSV export
        export_to_csv($data, $filename, $headers);
        return;
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $row = 1;
    if (!empty($headers)) {
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, $row, $header);
            $col++;
        }
        $row++;
    }

    foreach ($data as $rowData) {
        $col = 1;
        foreach ($rowData as $value) {
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        $row++;
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
}

/**
 * Export data to CSV format (fallback).
 */
function export_to_csv(array $data, string $filename, array $headers = []): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if (!empty($headers)) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Export data to PDF using TCPDF or FPDF.
 */
function export_to_pdf(array $data, string $filename, string $title, array $headers = []): void
{
    // Check if TCPDF is available
    if (!class_exists('TCPDF')) {
        // Simple HTML to PDF fallback
        export_to_pdf_html($data, $filename, $title, $headers);
        return;
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(APP_NAME);
    $pdf->SetAuthor(APP_NAME);
    $pdf->SetTitle($title);
    $pdf->SetSubject($title);
    $pdf->AddPage();

    $html = '<h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<table border="1" cellpadding="5">';

    if (!empty($headers)) {
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th><b>' . htmlspecialchars($header) . '</b></th>';
        }
        $html .= '</tr>';
    }

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filename . '.pdf', 'D');
    exit;
}

/**
 * Simple HTML to PDF fallback.
 */
function export_to_pdf_html(array $data, string $filename, string $title, array $headers = []): void
{
    header('Content-Type: text/html; charset=UTF-8');
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #007bff; color: white; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($title) . '</h1>
    <table>';

    if (!empty($headers)) {
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
    }

    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>
    <script>window.print();</script>
</body>
</html>';
    exit;
}

/**
 * Export data to DOC format (RTF).
 */
function export_to_doc(array $data, string $filename, string $title, array $headers = []): void
{
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment;filename="' . $filename . '.doc"');
    header('Cache-Control: max-age=0');

    echo "{\\rtf1\\ansi\\deff0\n";
    echo "{\\fonttbl{\\f0 Times New Roman;}}\n";
    echo "\\f0\\fs24\n";
    echo "\\b " . $title . "\\b0\\par\\par\n";

    if (!empty($headers)) {
        echo "\\trowd\\trgaph108\\trleft-108\n";
        foreach ($headers as $header) {
            echo "\\cellx" . (count($headers) * 2000) . "\n";
        }
        echo "\\pard\\intbl\n";
        foreach ($headers as $header) {
            echo "\\b " . $header . "\\b0\\cell\n";
        }
        echo "\\row\n";
    }

    foreach ($data as $row) {
        echo "\\trowd\\trgaph108\\trleft-108\n";
        foreach ($row as $cell) {
            echo "\\cellx" . (count($row) * 2000) . "\n";
        }
        echo "\\pard\\intbl\n";
        foreach ($row as $cell) {
            echo $cell . "\\cell\n";
        }
        echo "\\row\n";
    }

    echo "}\n";
    exit;
}

