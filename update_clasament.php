<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// Doar admin poate rula acest script (sau poate fi apelat automat)
// Pentru apelare automată, poți elimina require_admin() și adăuga un token secret

$pdo = get_pdo();

// Obține toate meciurile finalizate care nu au fost procesate
$matches = $pdo->query('
    SELECT id_meci, id_competitie, id_grupa, id_echipa_a, id_echipa_b
    FROM meciuri
    WHERE status = "finalizat"
')->fetchAll();

if (empty($matches)) {
    // Nu există meciuri de procesat
    exit;
}

$pdo->beginTransaction();

try {
    foreach ($matches as $match) {
        // Obține seturile pentru meci
        $sets = $pdo->prepare('
            SELECT gameuri_a, gameuri_b 
            FROM seturi 
            WHERE id_meci = :meci_id
        ');
        $sets->execute([':meci_id' => (int)$match['id_meci']]);
        $setsData = $sets->fetchAll();
        
        $pointsA = 0;
        $pointsB = 0;
        $gamesPlusA = 0;
        $gamesMinusA = 0;
        $gamesPlusB = 0;
        $gamesMinusB = 0;
        
        foreach ($setsData as $set) {
            $gamesA = (int)$set['gameuri_a'];
            $gamesB = (int)$set['gameuri_b'];
            
            // 1 punct pentru fiecare set câștigat
            if ($gamesA > $gamesB) {
                $pointsA++;
            } elseif ($gamesB > $gamesA) {
                $pointsB++;
            }
            
            // Calculează game-urile pentru clasament
            $gamesPlusA += $gamesA;
            $gamesMinusA += $gamesB;
            $gamesPlusB += $gamesB;
            $gamesMinusB += $gamesA;
        }
        
        // Actualizează sau inserează în clasament pentru echipa A
        $stmt = $pdo->prepare('
            INSERT INTO clasament (id_competitie, id_echipa, meciuri_jucate, puncte, gameuri_plus, gameuri_minus)
            VALUES (:comp, :team, 1, :points, :plus, :minus)
            ON DUPLICATE KEY UPDATE
                meciuri_jucate = meciuri_jucate + 1,
                puncte = puncte + :points,
                gameuri_plus = gameuri_plus + :plus,
                gameuri_minus = gameuri_minus + :minus
        ');
        
        $stmt->execute([
            ':comp' => (int)$match['id_competitie'],
            ':team' => (int)$match['id_echipa_a'],
            ':points' => $pointsA,
            ':plus' => $gamesPlusA,
            ':minus' => $gamesMinusA
        ]);
        
        // La fel pentru echipa B
        $stmt->execute([
            ':comp' => (int)$match['id_competitie'],
            ':team' => (int)$match['id_echipa_b'],
            ':points' => $pointsB,
            ':plus' => $gamesPlusB,
            ':minus' => $gamesMinusB
        ]);
    }
    
    $pdo->commit();
    
    // Returnează succes (pentru apelare programatică)
    if (php_sapi_name() === 'cli' || isset($_GET['silent'])) {
        return true;
    }
    
    flash('success', 'Clasamentul a fost actualizat cu succes.');
    redirect('clasament.php');
    
} catch (Throwable $e) {
    $pdo->rollBack();
    
    if (php_sapi_name() === 'cli' || isset($_GET['silent'])) {
        error_log('Eroare la actualizarea clasamentului: ' . $e->getMessage());
        return false;
    }
    
    flash('danger', 'Eroare la actualizarea clasamentului: ' . escape_html($e->getMessage()));
    redirect('clasament.php');
}

