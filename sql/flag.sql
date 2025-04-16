-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mariadb:3306
-- Creato il: Apr 15, 2025 alle 13:50
-- Versione del server: 11.7.2-MariaDB-ubu2404-log
-- Versione PHP: 8.2.27
SET
    FOREIGN_KEY_CHECKS = 0;

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
-- Struttura della tabella `ps_order_flag`
--
DROP TABLE IF EXISTS `ps_order_flag`;

CREATE TABLE IF NOT EXISTS `ps_order_flag` (
    `id_order` int(10) NOT NULL AUTO_INCREMENT,
    `id_order_flag` int(10) NOT NULL,
    `id_employee` int(10) DEFAULT NULL,
    `date_add` datetime DEFAULT NULL,
    `date_upd` datetime DEFAULT NULL,
    PRIMARY KEY (`id_order`),
    KEY `id_order_flag` (`id_order_flag`),
    KEY `id_employee` (`id_employee`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET
    FOREIGN_KEY_CHECKS = 1;

COMMIT;