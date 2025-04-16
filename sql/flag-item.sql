-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mariadb:3306
-- Creato il: Apr 15, 2025 alle 13:48
-- Versione del server: 11.7.2-MariaDB-ubu2404-log
-- Versione PHP: 8.2.27
SET
    SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
    time_zone = "+00:00";

--
-- Database: `ps_dalavoro_workwearitalia`
--
-- --------------------------------------------------------
--
-- Struttura della tabella `ps_order_flag_item`
--
DROP TABLE IF EXISTS `ps_order_flag_item`;

CREATE TABLE IF NOT EXISTS `ps_order_flag_item` (
    `id_order_flag_item` int(10) NOT NULL AUTO_INCREMENT,
    `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    `icon` varchar(64) NOT NULL,
    `color` char(7) NOT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime DEFAULT NULL,
    PRIMARY KEY (`id_order_flag_item`)
) ENGINE = InnoDB AUTO_INCREMENT = 5 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `ps_order_flag_item`
--
INSERT INTO
    `ps_order_flag_item` (
        `id_order_flag_item`,
        `name`,
        `icon`,
        `color`,
        `date_add`,
        `date_upd`
    )
VALUES
    (
        1,
        'OK',
        'verified',
        '#70b580',
        '2025-04-14 16:49:24',
        NULL
    ),
    (
        2,
        'ATTENZIONE',
        'warning',
        '#e9bd0c',
        '2025-04-14 16:49:47',
        NULL
    ),
    (
        3,
        'ERRORE',
        'error',
        '#f54c3e',
        '2025-04-14 16:50:13',
        NULL
    ),
    (
        4,
        'VERIFICA PAGAMENTO',
        'credit_score',
        '#25b9d7',
        '2025-04-14 16:50:36',
        NULL
    );

COMMIT;