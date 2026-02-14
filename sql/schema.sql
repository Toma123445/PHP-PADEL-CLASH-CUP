-- Schema baza de date pentru Smash Cup 5x5

CREATE DATABASE IF NOT EXISTS padel_tournament CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE padel_tournament;

-- Tabel divizii
CREATE TABLE IF NOT EXISTS divizii (
    id_divizie INT AUTO_INCREMENT PRIMARY KEY,
    nume_divizie VARCHAR(50) NOT NULL,
    valoare_banda DECIMAL(3,1) NOT NULL DEFAULT 0.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel competitii
CREATE TABLE IF NOT EXISTS competitii (
    id_competitie INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    sezon VARCHAR(20) NOT NULL,
    faza_curenta VARCHAR(50) DEFAULT 'Inscrieri',
    data_start DATE,
    data_final DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel echipe
CREATE TABLE IF NOT EXISTS echipe (
    id_echipa INT AUTO_INCREMENT PRIMARY KEY,
    nume_echipa VARCHAR(100) NOT NULL UNIQUE,
    capitan VARCHAR(100) NOT NULL,
    email_capitan VARCHAR(255) NOT NULL,
    telefon VARCHAR(20),
    divizie_principala INT,
    status ENUM('pending', 'validat', 'respins') DEFAULT 'pending',
    id_competitie INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (divizie_principala) REFERENCES divizii(id_divizie) ON DELETE SET NULL,
    FOREIGN KEY (id_competitie) REFERENCES competitii(id_competitie) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel jucatori
CREATE TABLE IF NOT EXISTS jucatori (
    id_jucator INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(50) NOT NULL,
    prenume VARCHAR(50) NOT NULL,
    id_echipa INT NOT NULL,
    id_divizie INT NOT NULL,
    email VARCHAR(255),
    telefon VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_echipa) REFERENCES echipe(id_echipa) ON DELETE CASCADE,
    FOREIGN KEY (id_divizie) REFERENCES divizii(id_divizie) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel grupe
CREATE TABLE IF NOT EXISTS grupe (
    id_grupa INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    nume VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_competitie) REFERENCES competitii(id_competitie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel relatie echipa-grupa
CREATE TABLE IF NOT EXISTS echipe_grupe (
    id_echipa INT NOT NULL,
    id_grupa INT NOT NULL,
    PRIMARY KEY (id_echipa, id_grupa),
    FOREIGN KEY (id_echipa) REFERENCES echipe(id_echipa) ON DELETE CASCADE,
    FOREIGN KEY (id_grupa) REFERENCES grupe(id_grupa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel meciuri
CREATE TABLE IF NOT EXISTS meciuri (
    id_meci INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    id_grupa INT,
    id_echipa_a INT NOT NULL,
    id_echipa_b INT NOT NULL,
    data_meci DATETIME,
    faza VARCHAR(50) DEFAULT 'Grupe',
    status ENUM('programat', 'in_desfasurare', 'finalizat') DEFAULT 'programat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_competitie) REFERENCES competitii(id_competitie) ON DELETE CASCADE,
    FOREIGN KEY (id_grupa) REFERENCES grupe(id_grupa) ON DELETE SET NULL,
    FOREIGN KEY (id_echipa_a) REFERENCES echipe(id_echipa) ON DELETE CASCADE,
    FOREIGN KEY (id_echipa_b) REFERENCES echipe(id_echipa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel seturi
CREATE TABLE IF NOT EXISTS seturi (
    id_set INT AUTO_INCREMENT PRIMARY KEY,
    id_meci INT NOT NULL,
    numar_set TINYINT NOT NULL CHECK (numar_set BETWEEN 1 AND 5),
    jucator_a1 INT,
    jucator_a2 INT,
    jucator_b1 INT,
    jucator_b2 INT,
    gameuri_a INT DEFAULT 0,
    gameuri_b INT DEFAULT 0,
    handicap_a DECIMAL(3,1) DEFAULT 0.0,
    handicap_b DECIMAL(3,1) DEFAULT 0.0,
    FOREIGN KEY (id_meci) REFERENCES meciuri(id_meci) ON DELETE CASCADE,
    FOREIGN KEY (jucator_a1) REFERENCES jucatori(id_jucator) ON DELETE SET NULL,
    FOREIGN KEY (jucator_a2) REFERENCES jucatori(id_jucator) ON DELETE SET NULL,
    FOREIGN KEY (jucator_b1) REFERENCES jucatori(id_jucator) ON DELETE SET NULL,
    FOREIGN KEY (jucator_b2) REFERENCES jucatori(id_jucator) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel clasament
CREATE TABLE IF NOT EXISTS clasament (
    id_clasament INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    id_echipa INT NOT NULL,
    id_grupa INT,
    puncte INT DEFAULT 0,
    gameuri_plus INT DEFAULT 0,
    gameuri_minus INT DEFAULT 0,
    meciuri_castigate INT DEFAULT 0,
    meciuri_pierdute INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_competitie) REFERENCES competitii(id_competitie) ON DELETE CASCADE,
    FOREIGN KEY (id_echipa) REFERENCES echipe(id_echipa) ON DELETE CASCADE,
    FOREIGN KEY (id_grupa) REFERENCES grupe(id_grupa) ON DELETE SET NULL,
    UNIQUE KEY unique_team_competition (id_competitie, id_echipa, id_grupa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel utilizatori (pentru autentificare)
CREATE TABLE IF NOT EXISTS utilizatori (
    id_utilizator INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    parola VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'capitan') DEFAULT 'capitan',
    id_echipa INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_echipa) REFERENCES echipe(id_echipa) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel mesaje contact
CREATE TABLE IF NOT EXISTS mesaje_contact (
    id_mesaj INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subiect VARCHAR(200) NOT NULL,
    mesaj TEXT NOT NULL,
    status ENUM('nou', 'citit', 'raspuns') DEFAULT 'nou',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Date initiale
INSERT INTO divizii (nume_divizie, valoare_banda) VALUES
('Incepator', 0.0),
('Mediu', 1.0),
('Avansat', 2.0),
('Expert', 3.0);

INSERT INTO competitii (nume, sezon, faza_curenta) VALUES
('Smash Cup 5x5', '2025-2026', 'Inscrieri');

-- Utilizator admin default (parola: admin123)
INSERT INTO utilizatori (username, email, parola, rol) VALUES
('admin', 'admin@smashcup.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

