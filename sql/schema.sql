-- Smash Cup 5x5 - Schema baza de date

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS seturi;
DROP TABLE IF EXISTS meciuri;
DROP TABLE IF EXISTS grupe_echipe;
DROP TABLE IF EXISTS grupe;
DROP TABLE IF EXISTS clasament;
DROP TABLE IF EXISTS jucatori;
DROP TABLE IF EXISTS utilizatori;
DROP TABLE IF EXISTS echipe;
DROP TABLE IF EXISTS divizii;
DROP TABLE IF EXISTS competitii;

CREATE TABLE competitii (
    id_competitie INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(150) NOT NULL,
    sezon VARCHAR(50) NOT NULL,
    faza_curenta VARCHAR(50) DEFAULT 'Grupe',
    data_start DATE NULL,
    data_sfarsit DATE NULL,
    status ENUM('planificat', 'in_desfasurare', 'finalizat') DEFAULT 'planificat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE divizii (
    id_divizie INT AUTO_INCREMENT PRIMARY KEY,
    nume_divizie VARCHAR(100) NOT NULL,
    valoare_banda TINYINT NOT NULL COMMENT 'Utilizeaza valori intre 1 si 4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE utilizatori (
    id_utilizator INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    parola_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'player') NOT NULL DEFAULT 'player',
    nume VARCHAR(100) NOT NULL,
    prenume VARCHAR(100) NOT NULL,
    telefon VARCHAR(30) NULL,
    divizie_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilizatori_divizii FOREIGN KEY (divizie_id) REFERENCES divizii (id_divizie) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE echipe (
    id_echipa INT AUTO_INCREMENT PRIMARY KEY,
    nume_echipa VARCHAR(150) NOT NULL,
    capitan VARCHAR(150) NOT NULL,
    email_capitan VARCHAR(150) NULL,
    telefon_capitan VARCHAR(30) NULL,
    divizie_id INT NOT NULL,
    data_inscriere TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_echipe_divizii FOREIGN KEY (divizie_id) REFERENCES divizii (id_divizie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE jucatori (
    id_jucator INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    prenume VARCHAR(100) NOT NULL,
    email VARCHAR(150) NULL,
    telefon VARCHAR(30) NULL,
    id_echipa INT NULL,
    id_divizie INT NOT NULL,
    user_id INT NULL UNIQUE,
    CONSTRAINT fk_jucatori_echipe FOREIGN KEY (id_echipa) REFERENCES echipe (id_echipa) ON DELETE SET NULL,
    CONSTRAINT fk_jucatori_divizii FOREIGN KEY (id_divizie) REFERENCES divizii (id_divizie),
    CONSTRAINT fk_jucatori_utilizatori FOREIGN KEY (user_id) REFERENCES utilizatori (id_utilizator) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE grupe (
    id_grupa INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    nume VARCHAR(50) NOT NULL,
    CONSTRAINT fk_grupe_competitii FOREIGN KEY (id_competitie) REFERENCES competitii (id_competitie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE grupe_echipe (
    id_grupa INT NOT NULL,
    id_echipa INT NOT NULL,
    PRIMARY KEY (id_grupa, id_echipa),
    CONSTRAINT fk_ge_grupe FOREIGN KEY (id_grupa) REFERENCES grupe (id_grupa) ON DELETE CASCADE,
    CONSTRAINT fk_ge_echipe FOREIGN KEY (id_echipa) REFERENCES echipe (id_echipa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE meciuri (
    id_meci INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    id_grupa INT NULL,
    id_echipa_a INT NOT NULL,
    id_echipa_b INT NOT NULL,
    data_meci DATETIME NULL,
    locatie VARCHAR(150) NULL,
    faza VARCHAR(100) DEFAULT 'Grupa',
    status ENUM('programat', 'in_desfasurare', 'finalizat') DEFAULT 'programat',
    CONSTRAINT fk_meciuri_competitii FOREIGN KEY (id_competitie) REFERENCES competitii (id_competitie) ON DELETE CASCADE,
    CONSTRAINT fk_meciuri_grupe FOREIGN KEY (id_grupa) REFERENCES grupe (id_grupa) ON DELETE SET NULL,
    CONSTRAINT fk_meciuri_echipa_a FOREIGN KEY (id_echipa_a) REFERENCES echipe (id_echipa),
    CONSTRAINT fk_meciuri_echipa_b FOREIGN KEY (id_echipa_b) REFERENCES echipe (id_echipa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE seturi (
    id_set INT AUTO_INCREMENT PRIMARY KEY,
    id_meci INT NOT NULL,
    numar_set TINYINT NOT NULL COMMENT 'Valoare intre 1 si 5',
    jucator_a1 INT NOT NULL,
    jucator_a2 INT NOT NULL,
    jucator_b1 INT NOT NULL,
    jucator_b2 INT NOT NULL,
    gameuri_a TINYINT NOT NULL DEFAULT 0,
    gameuri_b TINYINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_seturi_meciuri FOREIGN KEY (id_meci) REFERENCES meciuri (id_meci) ON DELETE CASCADE,
    CONSTRAINT fk_seturi_jucator_a1 FOREIGN KEY (jucator_a1) REFERENCES jucatori (id_jucator),
    CONSTRAINT fk_seturi_jucator_a2 FOREIGN KEY (jucator_a2) REFERENCES jucatori (id_jucator),
    CONSTRAINT fk_seturi_jucator_b1 FOREIGN KEY (jucator_b1) REFERENCES jucatori (id_jucator),
    CONSTRAINT fk_seturi_jucator_b2 FOREIGN KEY (jucator_b2) REFERENCES jucatori (id_jucator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE clasament (
    id_clasament INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    id_echipa INT NOT NULL,
    meciuri_jucate TINYINT DEFAULT 0,
    puncte INT DEFAULT 0,
    gameuri_plus INT DEFAULT 0,
    gameuri_minus INT DEFAULT 0,
    CONSTRAINT fk_clasament_competitii FOREIGN KEY (id_competitie) REFERENCES competitii (id_competitie) ON DELETE CASCADE,
    CONSTRAINT fk_clasament_echipe FOREIGN KEY (id_echipa) REFERENCES echipe (id_echipa) ON DELETE CASCADE,
    UNIQUE KEY unique_comp_echipa (id_competitie, id_echipa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data pentru divizii
INSERT INTO divizii (nume_divizie, valoare_banda) VALUES
('Nivel 1 (Incepatori)', 1),
('Nivel 2 (Intermediari)', 2),
('Nivel 3 (Avansati)', 3),
('Nivel 4 (Pro)', 4);

-- Competitie exemplu
INSERT INTO competitii (nume, sezon, faza_curenta, data_start, status) VALUES
('Smash Cup 5x5', '2025', 'Grupe', '2025-03-01', 'planificat');

-- Admin implicit (parola: password)
INSERT INTO utilizatori (email, parola_hash, rol, nume, prenume)
VALUES ('admin@padel.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'General');

SET FOREIGN_KEY_CHECKS = 1;

