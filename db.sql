-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Lug 06, 2026 alle 11:11
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `habit_tracker`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `abitudini`
--

CREATE TABLE `abitudini` (
  `id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('giornaliera','settimanale') NOT NULL DEFAULT 'giornaliera',
  `modalita` enum('check','contatore') NOT NULL DEFAULT 'check',
  `obiettivo` int(11) DEFAULT NULL,
  `unita` varchar(20) DEFAULT '',
  `inverso` tinyint(1) DEFAULT 0,
  `xp_ricompensa` int(11) NOT NULL DEFAULT 20,
  `icona` varchar(10) DEFAULT '✅',
  `attiva` tinyint(1) DEFAULT 1,
  `data_creazione` datetime DEFAULT current_timestamp(),
  `ordine` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `abitudini`
--

INSERT INTO `abitudini` (`id`, `utente_id`, `nome`, `tipo`, `modalita`, `obiettivo`, `unita`, `inverso`, `xp_ricompensa`, `icona`, `attiva`, `data_creazione`, `ordine`) VALUES
(16, 1, 'Pulire casa', 'settimanale', 'contatore', 1, 'volte', 0, 55, '🧹', 1, '2026-07-06 09:35:51', 10),
(18, 1, 'Solo acqua / bibite zero', 'giornaliera', 'check', NULL, '', 0, 20, '💧', 1, '2026-07-06 09:36:28', 2),
(19, 1, 'Addormentato entro le 23', 'giornaliera', 'check', NULL, '', 0, 20, '😴', 1, '2026-07-06 09:36:28', 3),
(20, 1, 'Telefono spento entro le 22', 'giornaliera', 'check', NULL, '', 0, 20, '📵', 1, '2026-07-06 09:36:28', 4),
(21, 1, 'Allenamento', 'giornaliera', 'check', NULL, '', 0, 60, '🏋️‍♂️', 1, '2026-07-06 09:36:28', 5),
(22, 1, '6k passi', 'giornaliera', 'contatore', 6000, 'passi', 0, 20, '🚶‍♂️', 1, '2026-07-06 09:36:28', 6),
(23, 1, 'Stretching', 'giornaliera', 'check', NULL, '', 0, 20, '🧘‍♂️', 1, '2026-07-06 09:36:28', 7),
(24, 1, 'Letto 30 minuti', 'giornaliera', 'contatore', 30, 'minuti', 0, 20, '📖', 1, '2026-07-06 09:36:28', 8),
(25, 1, 'Studiato', 'giornaliera', 'check', NULL, '', 0, 20, '📚', 1, '2026-07-06 09:36:28', 9),
(26, 1, 'Pianificazione settimanale', 'settimanale', 'check', NULL, '', 0, 20, '🗓️', 1, '2026-07-06 09:36:28', 11),
(27, 1, 'Meal prep', 'settimanale', 'check', NULL, '', 0, 20, '🍱', 1, '2026-07-06 09:36:28', 12),
(28, 1, 'habit check', 'giornaliera', 'check', NULL, '', 0, 15, '💻', 1, '2026-07-06 10:55:16', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `bonus_giornalieri`
--

CREATE TABLE `bonus_giornalieri` (
  `id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `data_bonus` date NOT NULL,
  `xp_guadagnato` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `check_abitudini`
--

CREATE TABLE `check_abitudini` (
  `id` int(11) NOT NULL,
  `abitudine_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `data_check` date NOT NULL,
  `valore_inserito` int(11) DEFAULT NULL,
  `xp_guadagnato` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `momenti`
--

CREATE TABLE `momenti` (
  `id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL,
  `data_momento` date NOT NULL,
  `testo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `momenti`
--

INSERT INTO `momenti` (`id`, `utente_id`, `data_momento`, `testo`) VALUES
(1, 1, '2026-07-06', 'xiX5hf3zKqBG0HFiqOA4V1VMaFBtWFpGUU93K3lRRWJBM1FlbUE9PQ==');

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `moltiplicatore` decimal(5,2) DEFAULT 1.00,
  `data_ultimo_check` date DEFAULT NULL,
  `giorni_streak` int(11) DEFAULT 0,
  `scudi_rimanenti` int(11) DEFAULT 1,
  `disattivato` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `username`, `password`, `moltiplicatore`, `data_ultimo_check`, `giorni_streak`, `scudi_rimanenti`, `disattivato`) VALUES
(1, 'thomas', '$2y$10$yx4XrREWsaOtSiet3Xt7dOcxyngPFahb9VlxkuG7MMhJO.WOOyrI2', 1.10, '2026-07-06', 1, 1, 0);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `abitudini`
--
ALTER TABLE `abitudini`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utente_id` (`utente_id`);

--
-- Indici per le tabelle `bonus_giornalieri`
--
ALTER TABLE `bonus_giornalieri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_bonus` (`utente_id`,`data_bonus`);

--
-- Indici per le tabelle `check_abitudini`
--
ALTER TABLE `check_abitudini`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_check` (`abitudine_id`,`utente_id`,`data_check`),
  ADD KEY `utente_id` (`utente_id`);

--
-- Indici per le tabelle `momenti`
--
ALTER TABLE `momenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_momento` (`utente_id`,`data_momento`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `abitudini`
--
ALTER TABLE `abitudini`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT per la tabella `bonus_giornalieri`
--
ALTER TABLE `bonus_giornalieri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `check_abitudini`
--
ALTER TABLE `check_abitudini`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT per la tabella `momenti`
--
ALTER TABLE `momenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `abitudini`
--
ALTER TABLE `abitudini`
  ADD CONSTRAINT `abitudini_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `bonus_giornalieri`
--
ALTER TABLE `bonus_giornalieri`
  ADD CONSTRAINT `bonus_giornalieri_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `check_abitudini`
--
ALTER TABLE `check_abitudini`
  ADD CONSTRAINT `check_abitudini_ibfk_1` FOREIGN KEY (`abitudine_id`) REFERENCES `abitudini` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `check_abitudini_ibfk_2` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `momenti`
--
ALTER TABLE `momenti`
  ADD CONSTRAINT `momenti_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
