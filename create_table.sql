CREATE DATABASE IF NOT EXISTS habit_tracker;
USE habit_tracker;

CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    moltiplicatore DECIMAL(5,2) DEFAULT 1.00,
    data_ultimo_check DATE DEFAULT NULL,
    giorni_streak INT DEFAULT 0,
    scudi_rimanenti INT DEFAULT 1,
    disattivato TINYINT(1) DEFAULT 0
);

CREATE TABLE abitudini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('giornaliera','settimanale') NOT NULL DEFAULT 'giornaliera',
    modalita ENUM('check','contatore') NOT NULL DEFAULT 'check',
    obiettivo INT DEFAULT NULL,
    unita VARCHAR(20) DEFAULT '',
    inverso TINYINT(1) DEFAULT 0,
    xp_ricompensa INT NOT NULL DEFAULT 20,
    icona VARCHAR(10) DEFAULT '✅',
    attiva TINYINT(1) DEFAULT 1,
    ordine INT DEFAULT 0,
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE
);

CREATE TABLE check_abitudini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    abitudine_id INT NOT NULL,
    utente_id INT NOT NULL,
    data_check DATE NOT NULL,
    valore_inserito INT DEFAULT NULL,
    xp_guadagnato INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_check (abitudine_id, utente_id, data_check),
    FOREIGN KEY (abitudine_id) REFERENCES abitudini(id) ON DELETE CASCADE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE
);

CREATE TABLE momenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    data_momento DATE NOT NULL,
    testo TEXT,
    UNIQUE KEY uniq_momento (utente_id, data_momento),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE
);

CREATE TABLE bonus_giornalieri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    data_bonus DATE NOT NULL,
    xp_guadagnato INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_bonus (utente_id, data_bonus),
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE
);