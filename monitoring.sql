-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : ven. 20 juin 2025 à 08:43
-- Version du serveur : 9.1.0
-- Version de PHP : 8.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `monitoring`
--

-- --------------------------------------------------------

--
-- Structure de la table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` text NOT NULL,
  `alert_type` text NOT NULL,
  `message` text NOT NULL,
  `valeur_limite` text NOT NULL,
  `is_active` text NOT NULL,
  `created_at` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `appareils`
--

DROP TABLE IF EXISTS `appareils`;
CREATE TABLE IF NOT EXISTS `appareils` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `ip_address` text NOT NULL,
  `type` text NOT NULL,
  `status` enum('actif','inactif') NOT NULL DEFAULT 'actif',
  `last_check` text NOT NULL,
  `created_at` text NOT NULL,
  `created_by` text NOT NULL,
  `location` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `appareils`
--

INSERT INTO `appareils` (`id`, `name`, `ip_address`, `type`, `status`, `last_check`, `created_at`, `created_by`, `location`) VALUES
(7, 'mon pc', '192.168.1.66', 'autre', 'inactif', '2025-06-16 11:53:11', '', '1', 'B1 A IT'),
(10, 'PC ', '192.168.1.114', 'serveur', 'actif', '2025-06-16 11:53:12', '', '4', 'B1 A IT'),
(9, 'mon telephone', '192.168.1.173', 'autre', 'actif', '2025-06-16 11:53:12', '', '1', 'B1 A IT');

-- --------------------------------------------------------

--
-- Structure de la table `tests`
--

DROP TABLE IF EXISTS `tests`;
CREATE TABLE IF NOT EXISTS `tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` int NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'planifié',
  `last_run_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `test_type` varchar(50) DEFAULT 'connectivité',
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tests`
--

INSERT INTO `tests` (`id`, `device_id`, `scheduled_at`, `status`, `last_run_at`, `created_at`, `created_by`, `test_type`) VALUES
(7, 7, '2025-06-11 09:22:56', 'terminé', '2025-06-11 09:22:56', '2025-06-11 09:22:56', 1, 'connectivité'),
(9, 7, '2025-06-11 09:23:55', 'terminé', '2025-06-11 09:23:55', '2025-06-11 09:23:55', 1, 'connectivité'),
(11, 7, '2025-06-11 09:25:26', 'terminé', '2025-06-11 09:25:26', '2025-06-11 09:25:26', 1, 'connectivité'),
(14, 7, '2025-06-11 09:57:20', 'terminé', '2025-06-11 09:57:20', '2025-06-11 09:57:20', 1, 'connectivité'),
(17, 7, '2025-06-11 13:18:34', 'terminé', '2025-06-11 13:18:34', '2025-06-11 13:18:34', 1, 'connectivité'),
(20, 7, '2025-06-11 13:54:57', 'terminé', '2025-06-11 13:54:57', '2025-06-11 13:54:57', 1, 'connectivité'),
(23, 7, '2025-06-11 13:56:33', 'terminé', '2025-06-11 13:56:33', '2025-06-11 13:56:33', 1, 'connectivité'),
(26, 7, '2025-06-11 13:57:41', 'terminé', '2025-06-11 13:57:41', '2025-06-11 13:57:41', 1, 'connectivité'),
(29, 7, '2025-06-11 14:00:30', 'terminé', '2025-06-11 14:00:30', '2025-06-11 14:00:30', 1, 'connectivité'),
(32, 7, '2025-06-11 14:02:08', 'terminé', '2025-06-11 14:02:08', '2025-06-11 14:02:08', 1, 'connectivité'),
(35, 7, '2025-06-11 14:43:55', 'terminé', '2025-06-11 14:43:55', '2025-06-11 14:43:55', 1, 'connectivité'),
(38, 7, '2025-06-11 15:46:58', 'terminé', '2025-06-11 15:46:58', '2025-06-11 15:46:58', 1, 'connectivité'),
(41, 7, '2025-06-11 15:54:12', 'terminé', '2025-06-11 15:54:12', '2025-06-11 15:54:12', 1, 'connectivité'),
(44, 7, '2025-06-12 07:23:09', 'terminé', '2025-06-12 07:23:09', '2025-06-12 07:23:09', 1, 'connectivité'),
(161, 10, '2025-06-16 11:53:12', 'terminé', '2025-06-16 11:53:12', '2025-06-16 11:53:12', 6, 'connectivité'),
(162, 9, '2025-06-16 11:53:12', 'terminé', '2025-06-16 11:53:12', '2025-06-16 11:53:12', 6, 'connectivité'),
(47, 7, '2025-06-12 08:14:18', 'terminé', '2025-06-12 08:14:18', '2025-06-12 08:14:18', 1, 'connectivité'),
(160, 7, '2025-06-16 11:53:11', 'terminé', '2025-06-16 11:53:11', '2025-06-16 11:53:11', 6, 'connectivité'),
(49, 9, '2025-06-12 08:14:20', 'terminé', '2025-06-12 08:14:20', '2025-06-12 08:14:20', 1, 'connectivité'),
(51, 7, '2025-06-12 08:20:33', 'terminé', '2025-06-12 08:20:33', '2025-06-12 08:20:33', 1, 'connectivité'),
(159, 9, '2025-06-16 11:53:00', 'terminé', '2025-06-16 11:53:00', '2025-06-16 11:53:00', 6, 'connectivité'),
(53, 9, '2025-06-12 08:20:34', 'terminé', '2025-06-12 08:20:34', '2025-06-12 08:20:34', 1, 'connectivité'),
(55, 7, '2025-06-12 08:49:50', 'terminé', '2025-06-12 08:49:50', '2025-06-12 08:49:50', 1, 'connectivité'),
(158, 10, '2025-06-16 11:53:00', 'terminé', '2025-06-16 11:53:00', '2025-06-16 11:53:00', 6, 'connectivité'),
(57, 9, '2025-06-12 08:49:52', 'terminé', '2025-06-12 08:49:52', '2025-06-12 08:49:52', 1, 'connectivité'),
(59, 7, '2025-06-12 08:51:38', 'terminé', '2025-06-12 08:51:38', '2025-06-12 08:51:38', 1, 'connectivité'),
(157, 7, '2025-06-16 11:53:00', 'terminé', '2025-06-16 11:53:00', '2025-06-16 11:53:00', 6, 'connectivité'),
(61, 9, '2025-06-12 08:51:39', 'terminé', '2025-06-12 08:51:39', '2025-06-12 08:51:39', 1, 'connectivité'),
(63, 7, '2025-06-12 11:26:41', 'terminé', '2025-06-12 11:26:41', '2025-06-12 11:26:41', 1, 'connectivité'),
(156, 9, '2025-06-16 11:49:33', 'terminé', '2025-06-16 11:49:33', '2025-06-16 11:49:33', 4, 'connectivité'),
(65, 9, '2025-06-12 11:26:43', 'terminé', '2025-06-12 11:26:43', '2025-06-12 11:26:43', 1, 'connectivité'),
(67, 7, '2025-06-12 11:28:19', 'terminé', '2025-06-12 11:28:19', '2025-06-12 11:28:19', 1, 'connectivité'),
(155, 10, '2025-06-16 11:49:32', 'terminé', '2025-06-16 11:49:32', '2025-06-16 11:49:32', 4, 'connectivité'),
(69, 9, '2025-06-12 11:28:20', 'terminé', '2025-06-12 11:28:20', '2025-06-12 11:28:20', 1, 'connectivité'),
(71, 7, '2025-06-12 11:29:53', 'terminé', '2025-06-12 11:29:53', '2025-06-12 11:29:53', 1, 'connectivité'),
(154, 7, '2025-06-16 11:49:31', 'terminé', '2025-06-16 11:49:31', '2025-06-16 11:49:31', 4, 'connectivité'),
(73, 9, '2025-06-12 11:29:55', 'terminé', '2025-06-12 11:29:55', '2025-06-12 11:29:55', 1, 'connectivité'),
(75, 7, '2025-06-12 11:32:25', 'terminé', '2025-06-12 11:32:25', '2025-06-12 11:32:25', 1, 'connectivité'),
(153, 9, '2025-06-16 11:45:36', 'terminé', '2025-06-16 11:45:36', '2025-06-16 11:45:36', 6, 'connectivité'),
(77, 9, '2025-06-12 11:32:26', 'terminé', '2025-06-12 11:32:26', '2025-06-12 11:32:26', 1, 'connectivité'),
(79, 7, '2025-06-12 12:11:43', 'terminé', '2025-06-12 12:11:43', '2025-06-12 12:11:43', 1, 'connectivité'),
(152, 10, '2025-06-16 11:45:36', 'terminé', '2025-06-16 11:45:36', '2025-06-16 11:45:36', 6, 'connectivité'),
(81, 9, '2025-06-12 12:11:45', 'terminé', '2025-06-12 12:11:45', '2025-06-12 12:11:45', 1, 'connectivité'),
(83, 7, '2025-06-12 12:36:44', 'terminé', '2025-06-12 12:36:44', '2025-06-12 12:36:44', 1, 'connectivité'),
(151, 7, '2025-06-16 11:45:35', 'terminé', '2025-06-16 11:45:35', '2025-06-16 11:45:35', 6, 'connectivité'),
(85, 9, '2025-06-12 12:36:46', 'terminé', '2025-06-12 12:36:46', '2025-06-12 12:36:46', 1, 'connectivité'),
(87, 7, '2025-06-12 12:39:22', 'terminé', '2025-06-12 12:39:22', '2025-06-12 12:39:22', 1, 'connectivité'),
(150, 9, '2025-06-16 11:13:10', 'terminé', '2025-06-16 11:13:10', '2025-06-16 11:13:10', 4, 'connectivité'),
(89, 9, '2025-06-12 12:39:24', 'terminé', '2025-06-12 12:39:24', '2025-06-12 12:39:24', 1, 'connectivité'),
(91, 7, '2025-06-12 16:19:45', 'terminé', '2025-06-12 16:19:45', '2025-06-12 16:19:45', 1, 'connectivité'),
(149, 10, '2025-06-16 11:13:09', 'terminé', '2025-06-16 11:13:09', '2025-06-16 11:13:09', 4, 'connectivité'),
(93, 9, '2025-06-12 16:19:46', 'terminé', '2025-06-12 16:19:46', '2025-06-12 16:19:46', 1, 'connectivité'),
(95, 7, '2025-06-13 08:41:16', 'terminé', '2025-06-13 08:41:16', '2025-06-13 08:41:16', 1, 'connectivité'),
(148, 7, '2025-06-16 11:13:09', 'terminé', '2025-06-16 11:13:09', '2025-06-16 11:13:09', 4, 'connectivité'),
(97, 9, '2025-06-13 08:41:18', 'terminé', '2025-06-13 08:41:18', '2025-06-13 08:41:18', 1, 'connectivité'),
(99, 7, '2025-06-13 18:43:37', 'terminé', '2025-06-13 18:43:37', '2025-06-13 18:43:37', 1, 'connectivité'),
(147, 9, '2025-06-16 11:10:44', 'terminé', '2025-06-16 11:10:44', '2025-06-16 11:10:44', 3, 'connectivité'),
(101, 9, '2025-06-13 18:43:39', 'terminé', '2025-06-13 18:43:39', '2025-06-13 18:43:39', 1, 'connectivité'),
(103, 7, '2025-06-13 18:51:31', 'terminé', '2025-06-13 18:51:31', '2025-06-13 18:51:31', 1, 'connectivité'),
(146, 10, '2025-06-16 11:10:43', 'terminé', '2025-06-16 11:10:43', '2025-06-16 11:10:43', 3, 'connectivité'),
(105, 9, '2025-06-13 18:51:32', 'terminé', '2025-06-13 18:51:32', '2025-06-13 18:51:32', 1, 'connectivité'),
(107, 7, '2025-06-13 19:30:19', 'terminé', '2025-06-13 19:30:19', '2025-06-13 19:30:19', 1, 'connectivité'),
(145, 7, '2025-06-16 11:10:42', 'terminé', '2025-06-16 11:10:42', '2025-06-16 11:10:42', 3, 'connectivité'),
(109, 9, '2025-06-13 19:30:21', 'terminé', '2025-06-13 19:30:21', '2025-06-13 19:30:21', 1, 'connectivité'),
(111, 7, '2025-06-13 19:38:56', 'terminé', '2025-06-13 19:38:56', '2025-06-13 19:38:56', 1, 'connectivité'),
(144, 9, '2025-06-16 11:03:41', 'terminé', '2025-06-16 11:03:41', '2025-06-16 11:03:41', 4, 'connectivité'),
(113, 9, '2025-06-13 19:38:57', 'terminé', '2025-06-13 19:38:57', '2025-06-13 19:38:57', 1, 'connectivité'),
(115, 7, '2025-06-13 20:03:40', 'terminé', '2025-06-13 20:03:40', '2025-06-13 20:03:40', 3, 'connectivité'),
(143, 10, '2025-06-16 11:03:41', 'terminé', '2025-06-16 11:03:41', '2025-06-16 11:03:41', 4, 'connectivité'),
(117, 9, '2025-06-13 20:03:42', 'terminé', '2025-06-13 20:03:42', '2025-06-13 20:03:42', 3, 'connectivité'),
(119, 7, '2025-06-13 20:50:49', 'terminé', '2025-06-13 20:50:49', '2025-06-13 20:50:49', 5, 'connectivité'),
(142, 7, '2025-06-16 11:03:40', 'terminé', '2025-06-16 11:03:40', '2025-06-16 11:03:40', 4, 'connectivité'),
(121, 9, '2025-06-13 20:50:51', 'terminé', '2025-06-13 20:50:51', '2025-06-13 20:50:51', 5, 'connectivité'),
(123, 7, '2025-06-13 21:17:38', 'terminé', '2025-06-13 21:17:38', '2025-06-13 21:17:38', 4, 'connectivité'),
(141, 9, '2025-06-15 18:47:48', 'terminé', '2025-06-15 18:47:48', '2025-06-15 18:47:48', 1, 'connectivité'),
(125, 9, '2025-06-13 21:17:39', 'terminé', '2025-06-13 21:17:39', '2025-06-13 21:17:39', 4, 'connectivité'),
(127, 7, '2025-06-13 21:45:58', 'terminé', '2025-06-13 21:45:58', '2025-06-13 21:45:58', 4, 'connectivité'),
(140, 7, '2025-06-15 18:47:48', 'terminé', '2025-06-15 18:47:48', '2025-06-15 18:47:48', 1, 'connectivité'),
(129, 9, '2025-06-13 21:45:59', 'terminé', '2025-06-13 21:45:59', '2025-06-13 21:45:59', 4, 'connectivité'),
(131, 7, '2025-06-13 21:48:20', 'terminé', '2025-06-13 21:48:20', '2025-06-13 21:48:20', 4, 'connectivité'),
(139, 9, '2025-06-13 21:59:17', 'terminé', '2025-06-13 21:59:17', '2025-06-13 21:59:17', 3, 'connectivité'),
(133, 9, '2025-06-13 21:48:22', 'terminé', '2025-06-13 21:48:22', '2025-06-13 21:48:22', 4, 'connectivité'),
(135, 7, '2025-06-13 21:50:56', 'terminé', '2025-06-13 21:50:56', '2025-06-13 21:50:56', 4, 'connectivité'),
(138, 7, '2025-06-13 21:59:16', 'terminé', '2025-06-13 21:59:16', '2025-06-13 21:59:16', 3, 'connectivité'),
(137, 9, '2025-06-13 21:50:58', 'terminé', '2025-06-13 21:50:58', '2025-06-13 21:50:58', 4, 'connectivité');

-- --------------------------------------------------------

--
-- Structure de la table `test_results`
--

DROP TABLE IF EXISTS `test_results`;
CREATE TABLE IF NOT EXISTS `test_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` int NOT NULL,
  `result_value` varchar(255) NOT NULL,
  `threshold_met` tinyint(1) NOT NULL,
  `notes` text,
  `run_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `run_by` int DEFAULT NULL,
  `ping_latency_ms` decimal(10,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `run_by` (`run_by`)
) ENGINE=MyISAM AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `test_results`
--

INSERT INTO `test_results` (`id`, `test_id`, `result_value`, `threshold_met`, `notes`, `run_at`, `run_by`, `ping_latency_ms`) VALUES
(6, 7, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut:  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 09:22:56', 1, NULL),
(8, 9, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut:  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 09:23:55', 1, NULL),
(10, 11, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut:  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 09:25:26', 1, NULL),
(13, 14, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut:  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 09:57:20', 1, NULL),
(16, 17, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 13:18:34', 1, NULL),
(19, 20, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.0.161 avec 32 octets de donn?es?: | R?ponse de 192.168.0.161?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.0.161: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 13:54:57', 1, NULL),
(22, 23, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 13:56:33', 1, NULL),
(25, 26, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 13:57:41', 1, NULL),
(28, 29, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 14:00:30', 1, NULL),
(31, 32, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 14:02:08', 1, NULL),
(34, 35, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 14:43:55', 1, NULL),
(161, 162, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.173 avec 32 octets de donn?es?: | R?ponse de 192.168.1.173?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.173: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-16 11:53:12', 6, 0.000),
(37, 38, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 15:46:58', 1, NULL),
(160, 161, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | R?ponse de 192.168.1.114?: octets=32 temps=31 ms TTL=128 |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 31ms, Maximum = 31ms, Moyenne = 31ms', '2025-06-16 11:53:12', 6, 31.000),
(40, 41, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | R?ponse de 192.168.1.107?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-11 15:54:12', 1, NULL),
(158, 159, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.173 avec 32 octets de donn?es?: | R?ponse de 192.168.1.173?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.173: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-16 11:53:00', 6, 0.000),
(159, 160, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:53:11', 6, NULL),
(43, 44, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 07:23:09', 1, NULL),
(156, 157, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:53:00', 6, NULL),
(157, 158, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | R?ponse de 192.168.1.114?: octets=32 temps=308 ms TTL=128 |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 308ms, Maximum = 308ms, Moyenne = 308ms', '2025-06-16 11:53:00', 6, 308.000),
(46, 47, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 08:14:18', 1, NULL),
(48, 49, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=51 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 51ms, Maximum = 51ms, Moyenne = 51ms', '2025-06-12 08:14:20', 1, NULL),
(50, 51, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 08:20:33', 1, NULL),
(155, 156, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.173 avec 32 octets de donn?es?: | R?ponse de 192.168.1.173?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.173: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-16 11:49:33', 4, 0.000),
(52, 53, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=427 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 427ms, Maximum = 427ms, Moyenne = 427ms', '2025-06-12 08:20:34', 1, NULL),
(54, 55, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 08:49:50', 1, NULL),
(154, 155, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:49:32', 4, NULL),
(56, 57, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=139 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 139ms, Maximum = 139ms, Moyenne = 139ms', '2025-06-12 08:49:52', 1, NULL),
(58, 59, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 08:51:38', 1, NULL),
(153, 154, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:49:31', 4, NULL),
(60, 61, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=442 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 442ms, Maximum = 442ms, Moyenne = 442ms', '2025-06-12 08:51:39', 1, NULL),
(62, 63, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 11:26:41', 1, NULL),
(152, 153, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:45:36', 6, NULL),
(64, 65, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 11:26:43', 1, NULL),
(66, 67, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 11:28:19', 1, NULL),
(68, 69, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=42 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 42ms, Maximum = 42ms, Moyenne = 42ms', '2025-06-12 11:28:20', 1, NULL),
(70, 71, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 11:29:53', 1, NULL),
(151, 152, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | R?ponse de 192.168.1.114?: octets=32 temps=9 ms TTL=128 |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 9ms, Maximum = 9ms, Moyenne = 9ms', '2025-06-16 11:45:36', 6, 9.000),
(72, 73, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=539 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 539ms, Maximum = 539ms, Moyenne = 539ms', '2025-06-12 11:29:55', 1, NULL),
(74, 75, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 11:32:25', 1, NULL),
(150, 151, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:45:35', 6, NULL),
(76, 77, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=447 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 447ms, Maximum = 447ms, Moyenne = 447ms', '2025-06-12 11:32:26', 1, NULL),
(78, 79, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 12:11:43', 1, NULL),
(149, 150, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:13:10', 4, NULL),
(80, 81, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=707 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 707ms, Maximum = 707ms, Moyenne = 707ms', '2025-06-12 12:11:45', 1, NULL),
(82, 83, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 12:36:44', 1, NULL),
(148, 149, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | R?ponse de 192.168.1.114?: octets=32 temps=12 ms TTL=128 |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 12ms, Maximum = 12ms, Moyenne = 12ms', '2025-06-16 11:13:09', 4, 12.000),
(84, 85, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=14 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 14ms, Maximum = 14ms, Moyenne = 14ms', '2025-06-12 12:36:46', 1, NULL),
(86, 87, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 12:39:22', 1, NULL),
(147, 148, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:13:09', 4, NULL),
(88, 89, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=507 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 507ms, Maximum = 507ms, Moyenne = 507ms', '2025-06-12 12:39:24', 1, NULL),
(90, 91, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-12 16:19:45', 1, NULL),
(146, 147, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:10:44', 3, NULL),
(92, 93, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=156 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 156ms, Maximum = 156ms, Moyenne = 156ms', '2025-06-12 16:19:46', 1, NULL),
(94, 95, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 08:41:16', 1, NULL),
(145, 146, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:10:43', 3, NULL),
(96, 97, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 08:41:18', 1, NULL),
(98, 99, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 18:43:37', 1, NULL),
(144, 145, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:10:42', 3, NULL),
(100, 101, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 18:43:39', 1, NULL),
(102, 103, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 18:51:31', 1, NULL),
(104, 105, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=306 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 306ms, Maximum = 306ms, Moyenne = 306ms', '2025-06-13 18:51:32', 1, NULL),
(106, 107, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 19:30:19', 1, NULL),
(143, 144, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:03:41', 4, NULL),
(108, 109, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=855 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 855ms, Maximum = 855ms, Moyenne = 855ms', '2025-06-13 19:30:21', 1, NULL),
(110, 111, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 19:38:56', 1, NULL),
(142, 143, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.114 avec 32 octets de donn?es?: | R?ponse de 192.168.1.114?: octets=32 temps=483 ms TTL=128 |  | Statistiques Ping pour 192.168.1.114: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 483ms, Maximum = 483ms, Moyenne = 483ms', '2025-06-16 11:03:41', 4, 483.000),
(112, 113, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=456 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 456ms, Maximum = 456ms, Moyenne = 456ms', '2025-06-13 19:38:57', 1, 456.000),
(114, 115, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 20:03:40', 3, NULL),
(141, 142, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-16 11:03:40', 4, NULL),
(116, 117, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=257 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 257ms, Maximum = 257ms, Moyenne = 257ms', '2025-06-13 20:03:42', 3, 257.000),
(118, 119, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 20:50:49', 5, NULL),
(140, 141, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-15 18:47:48', 1, NULL),
(120, 121, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=38 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 38ms, Maximum = 38ms, Moyenne = 38ms', '2025-06-13 20:50:51', 5, 38.000),
(122, 123, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 21:17:38', 4, NULL),
(124, 125, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=217 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 217ms, Maximum = 217ms, Moyenne = 217ms', '2025-06-13 21:17:39', 4, 217.000),
(126, 127, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 21:45:58', 4, NULL),
(139, 140, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | R?ponse de 192.168.1.66?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-15 18:47:48', 1, 0.000),
(128, 129, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=270 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 270ms, Maximum = 270ms, Moyenne = 270ms', '2025-06-13 21:45:59', 4, 270.000),
(130, 131, 'Déconnecté', 0, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.107 avec 32 octets de donn?es?: | D?lai d\'attente de la demande d?pass?. |  | Statistiques Ping pour 192.168.1.107: |     Paquets?: envoy?s = 1, re?us = 0, perdus = 1 (perte 100%),', '2025-06-13 21:48:20', 4, NULL),
(138, 139, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=396 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 396ms, Maximum = 396ms, Moyenne = 396ms', '2025-06-13 21:59:17', 3, 396.000),
(132, 133, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=426 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 426ms, Maximum = 426ms, Moyenne = 426ms', '2025-06-13 21:48:22', 4, 426.000),
(134, 135, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | R?ponse de 192.168.1.66?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-13 21:50:56', 4, 0.000),
(137, 138, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.66 avec 32 octets de donn?es?: | R?ponse de 192.168.1.66?: octets=32 temps<1ms TTL=128 |  | Statistiques Ping pour 192.168.1.66: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 0ms, Maximum = 0ms, Moyenne = 0ms', '2025-06-13 21:59:16', 3, 0.000),
(136, 137, 'Connecté', 1, 'Vérification de la connectivité IP via commande PING. Résultat brut (sortie exec):  | Envoi d\'une requ?te \'Ping\'  192.168.1.64 avec 32 octets de donn?es?: | R?ponse de 192.168.1.64?: octets=32 temps=405 ms TTL=64 |  | Statistiques Ping pour 192.168.1.64: |     Paquets?: envoy?s = 1, re?us = 1, perdus = 0 (perte 0%), | Dur?e approximative des boucles en millisecondes : |     Minimum = 405ms, Maximum = 405ms, Moyenne = 405ms', '2025-06-13 21:50:58', 4, 405.000);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `role` text NOT NULL,
  `createdate` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
