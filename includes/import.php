<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db_connect.php';

/**
 * Import data from Excel file.
 */
function import_from_excel(string $file_path): array
{
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        throw new Exception('PhpSpreadsheet nu este instalat. Instaleaza cu: composer require phpoffice/phpspreadsheet');
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = [];

    foreach ($worksheet->getRowIterator() as $row) {
        $rowData = [];
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getValue();
        }

        if (!empty(array_filter($rowData))) {
            $data[] = $rowData;
        }
    }

    return $data;
}

/**
 * Import data from CSV file.
 */
function import_from_csv(string $file_path): array
{
    $data = [];
    $handle = fopen($file_path, 'r');

    if ($handle === false) {
        throw new Exception('Nu s-a putut deschide fisierul CSV');
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (!empty(array_filter($row))) {
            $data[] = $row;
        }
    }

    fclose($handle);
    return $data;
}

/**
 * Import teams from Excel/CSV.
 */
function import_teams_from_file(string $file_path, string $format = 'excel'): array
{
    $data = $format === 'excel' ? import_from_excel($file_path) : import_from_csv($file_path);
    $pdo = get_db();
    $imported = [];
    $errors = [];

    // Skip header row if exists
    if (!empty($data) && (strtolower($data[0][0]) === 'nume echipa' || strtolower($data[0][0]) === 'nume_echipa')) {
        array_shift($data);
    }

    foreach ($data as $index => $row) {
        if (count($row) < 2) {
            $errors[] = "Randul " . ($index + 1) . ": Date incomplete";
            continue;
        }

        try {
            $nume_echipa = trim($row[0] ?? '');
            $capitan = trim($row[1] ?? '');
            $email = trim($row[2] ?? '');
            $telefon = trim($row[3] ?? '');

            if (empty($nume_echipa) || empty($capitan)) {
                $errors[] = "Randul " . ($index + 1) . ": Nume echipa sau capitan lipsa";
                continue;
            }

            $stmt = $pdo->prepare("
                INSERT INTO echipe (nume_echipa, capitan, email_capitan, telefon, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([$nume_echipa, $capitan, $email, $telefon]);
            $imported[] = $nume_echipa;
        } catch (PDOException $e) {
            $errors[] = "Randul " . ($index + 1) . ": " . $e->getMessage();
        }
    }

    return [
        'imported' => $imported,
        'errors' => $errors,
        'total' => count($data)
    ];
}

