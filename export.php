<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/export.php';
require_once __DIR__ . '/config.php';

$format = $_GET['format'] ?? 'excel';
$type = $_GET['type'] ?? 'clasament';

$pdo = get_db();
$data = [];
$headers = [];
$filename = '';
$title = '';

try {
    switch ($type) {
        case 'clasament':
            $stmt = $pdo->query("
                SELECT c.puncte as 'Puncte',
                       e.nume_echipa as 'Echipă',
                       c.meciuri_castigate as 'Meciuri Câștigate',
                       c.meciuri_pierdute as 'Meciuri Pierdute',
                       c.gameuri_plus as 'Gameuri +',
                       c.gameuri_minus as 'Gameuri -',
                       (c.gameuri_plus - c.gameuri_minus) as 'Diferență'
                FROM clasament c
                JOIN echipe e ON c.id_echipa = e.id_echipa
                ORDER BY c.puncte DESC, (c.gameuri_plus - c.gameuri_minus) DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Puncte', 'Echipă', 'Meciuri Câștigate', 'Meciuri Pierdute', 'Gameuri +', 'Gameuri -', 'Diferență'];
            $filename = 'clasament_' . date('Y-m-d');
            $title = 'Clasament - ' . APP_NAME;
            break;

        case 'echipe':
            $stmt = $pdo->query("
                SELECT e.nume_echipa as 'Echipă',
                       e.capitan as 'Capitan',
                       e.email_capitan as 'Email',
                       e.telefon as 'Telefon',
                       e.status as 'Status',
                       COUNT(j.id_jucator) as 'Număr Jucători'
                FROM echipe e
                LEFT JOIN jucatori j ON e.id_echipa = j.id_echipa
                GROUP BY e.id_echipa
                ORDER BY e.nume_echipa
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Echipă', 'Capitan', 'Email', 'Telefon', 'Status', 'Număr Jucători'];
            $filename = 'echipe_' . date('Y-m-d');
            $title = 'Lista Echipe - ' . APP_NAME;
            break;

        case 'meciuri':
            $stmt = $pdo->query("
                SELECT m.data_meci as 'Data',
                       ea.nume_echipa as 'Echipă A',
                       eb.nume_echipa as 'Echipă B',
                       m.faza as 'Fază',
                       m.status as 'Status'
                FROM meciuri m
                LEFT JOIN echipe ea ON m.id_echipa_a = ea.id_echipa
                LEFT JOIN echipe eb ON m.id_echipa_b = eb.id_echipa
                ORDER BY m.data_meci DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $headers = ['Data', 'Echipă A', 'Echipă B', 'Fază', 'Status'];
            $filename = 'meciuri_' . date('Y-m-d');
            $title = 'Lista Meciuri - ' . APP_NAME;
            break;

        default:
            throw new Exception('Tip de export invalid');
    }

    // Convert to array format for export functions
    $exportData = [];
    foreach ($data as $row) {
        $exportData[] = array_values($row);
    }

    switch ($format) {
        case 'excel':
            export_to_excel($exportData, $filename, $headers);
            break;
        case 'pdf':
            export_to_pdf($exportData, $filename, $title, $headers);
            break;
        case 'doc':
            export_to_doc($exportData, $filename, $title, $headers);
            break;
        default:
            throw new Exception('Format de export invalid');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    header('Location: index.php');
    exit;
}

