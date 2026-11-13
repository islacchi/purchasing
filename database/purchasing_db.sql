-- Purchasing database dump
-- Generated: 2026-11-13 07:22:29
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `purchasing_db`;
CREATE DATABASE `purchasing_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `purchasing_db`;

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `record_label` varchar(255) DEFAULT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` (`id`,`user_id`,`user_name`,`role_name`,`action`,`module`,`record_label`,`subject_type`,`subject_id`,`created_at`) VALUES
(1,3,'user1','Procurement Officer','Created','Entities','LGU - BISAYA','App\\Models\\ProcuringEntity',2,'2026-11-12 02:27:08'),
(2,2,'admin','Administrator','Edited','Projects','wewew','App\\Models\\Project',5,'2026-11-12 05:35:11'),
(3,2,'admin','Administrator','Edited','Projects','wewew','App\\Models\\Project',5,'2026-11-12 05:35:32'),
(4,2,'admin','Administrator','Edited','Projects','wewew','App\\Models\\Project',5,'2026-11-12 05:37:44'),
(5,2,'admin','Administrator','Edited','Projects','wewew','App\\Models\\Project',5,'2026-11-12 05:48:06'),
(6,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 06:20:38'),
(7,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 06:21:18'),
(8,2,'admin','Administrator','Edited','Users','kyle','App\\Models\\User',5,'2026-11-12 06:31:14'),
(9,2,'admin','Administrator','Created','Preplist','zxzxc','App\\Models\\Preplist',16,'2026-11-12 06:35:45'),
(10,2,'admin','Administrator','Created','Quotation','RFQ-2026-0018','App\\Models\\Quotation',18,'2026-11-12 06:35:53'),
(11,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 07:07:36'),
(12,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 07:08:16'),
(13,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 07:23:18'),
(14,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 07:23:55'),
(15,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 07:44:41'),
(16,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 07:46:31'),
(17,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 08:03:26'),
(18,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 08:04:27'),
(19,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 08:19:13'),
(20,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 08:19:34'),
(21,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 08:29:36'),
(22,2,'admin','Administrator','Edited','Users','Role module access matrix',NULL,NULL,'2026-11-12 08:30:07'),
(23,2,'admin','Administrator','Edited','Projects','wewew','App\\Models\\Project',5,'2026-11-12 08:34:08'),
(24,2,'admin','Administrator','Created','Preplist','adadqwddqwed','App\\Models\\Preplist',17,'2026-11-12 08:34:54'),
(25,2,'admin','Administrator','Deleted','Preplist','adadqwddqwed','App\\Models\\Preplist',17,'2026-11-12 08:35:02'),
(26,2,'admin','Administrator','Created','Settings','Database back-up: purchasing-2026-11-13-060100.sql',NULL,NULL,'2026-11-13 06:01:06'),
(27,2,'admin','Administrator','Deleted','Settings','Database back-up: purchasing-2026-11-13-060100.sql',NULL,NULL,'2026-11-13 06:01:06'),
(28,2,'admin','Administrator','Created','Settings','Database back-up: purchasing-2026-11-13-060756.sql',NULL,NULL,'2026-11-13 06:07:56'),
(29,2,'admin','Administrator','Deleted','Settings','Database back-up: purchasing-2026-11-13-060756.sql',NULL,NULL,'2026-11-13 06:08:11'),
(30,2,'admin','Administrator','Created','Settings','Database back-up: purchasing-2026-11-13-060914.sql',NULL,NULL,'2026-11-13 06:09:14'),
(31,2,'admin','Administrator','Deleted','Settings','Database back-up: purchasing-2026-11-13-060914.sql',NULL,NULL,'2026-11-13 06:09:14'),
(32,2,'admin','Administrator','Created','Settings','Database back-up: purchasing-2026-11-13-061057.sql',NULL,NULL,'2026-11-13 06:10:57'),
(33,2,'admin','Administrator','Downloaded','Settings','Database back-up: purchasing-2026-11-13-061057.sql',NULL,NULL,'2026-11-13 06:31:32'),
(34,2,'admin','Administrator','Deleted','Settings','Database back-up: purchasing-2026-11-13-061057.sql',NULL,NULL,'2026-11-13 06:42:19'),
(35,2,'admin','Administrator','Deleted','Settings','Database back-up: purchasing-2026-11-13-063811.sql',NULL,NULL,'2026-11-13 06:42:22');

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `item_categories`;
CREATE TABLE `item_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `item_categories` (`id`,`name`) VALUES
(1,'drugs and medicines'),
(2,'supplies and equipments');

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `default_min_shelf_life_months` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `items_category_id_foreign` (`category_id`),
  CONSTRAINT `items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `items` (`id`,`category_id`,`name`,`unit`,`default_min_shelf_life_months`) VALUES
(1,1,'Mefenamic Capsule - 500mg','Box',0),
(2,2,'test','test',0),
(3,1,'test2','Box',0),
(4,2,'wat','wat',0);

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES
(1,'0001_01_01_000000_create_roles_table',1),
(2,'0001_01_01_000000_create_users_table',1),
(3,'0001_01_01_000001_create_cache_table',1),
(4,'0001_01_01_000002_create_jobs_table',1),
(5,'2026_11_09_000001_create_number_sequences_table',1),
(6,'2026_11_09_000002_create_procuring_entities_table',1),
(7,'2026_11_09_000003_create_item_categories_table',1),
(8,'2026_11_09_000004_create_items_table',1),
(9,'2026_11_09_000005_create_suppliers_table',1),
(10,'2026_11_09_000006_create_projects_table',1),
(11,'2026_11_09_000007_create_preplists_table',1),
(12,'2026_11_09_000008_create_preplist_items_table',1),
(13,'2026_11_09_000009_create_activity_logs_table',1),
(14,'2026_11_09_000010_create_project_items_table',1),
(15,'2026_11_09_000011_create_preplist_item_sources_table',1),
(16,'2026_11_09_000012_create_quotations_table',1),
(17,'2026_11_09_000013_create_quotation_items_table',1),
(18,'2026_11_09_000014_create_purchase_orders_table',1),
(19,'2026_11_09_000015_create_purchase_order_items_table',1),
(20,'2026_11_09_000016_create_purchase_order_receipts_table',1),
(21,'2026_11_09_000017_create_purchase_order_item_allocations_table',1),
(22,'2026_11_09_000018_add_timestamps_to_users_table',2),
(23,'2026_11_10_000001_add_timestamps_to_preplists_table',3),
(24,'2026_11_11_000001_add_brand_and_expiry_to_quotation_items_table',4),
(25,'2026_11_12_000001_convert_quotation_items_expiry_to_date_table',5),
(26,'2026_11_12_000002_drop_offered_shelf_life_months_from_items_tables',6),
(28,'2026_11_12_000003_add_unique_indexes_to_junction_items_tables',7),
(30,'2026_11_12_000004_add_in_stock_to_project_items_table',8),
(31,'2026_11_12_000005_create_role_module_access_table',9);

DROP TABLE IF EXISTS `number_sequences`;
CREATE TABLE `number_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `year` smallint(6) NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_sequences_key_year_unique` (`key`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `number_sequences` (`id`,`key`,`year`,`last_number`) VALUES
(1,'preplist',2026,17),
(2,'rfq',2026,18),
(3,'po',2026,6);

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `preplist_item_sources`;
CREATE TABLE `preplist_item_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `preplist_item_id` bigint(20) unsigned NOT NULL,
  `project_item_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `preplist_item_sources_preplist_item_id_foreign` (`preplist_item_id`),
  KEY `preplist_item_sources_project_item_id_foreign` (`project_item_id`),
  CONSTRAINT `preplist_item_sources_preplist_item_id_foreign` FOREIGN KEY (`preplist_item_id`) REFERENCES `preplist_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `preplist_item_sources_project_item_id_foreign` FOREIGN KEY (`project_item_id`) REFERENCES `project_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preplist_item_sources` (`id`,`preplist_item_id`,`project_item_id`,`qty`) VALUES
(3,3,3,21),
(4,4,4,21),
(7,7,3,21),
(8,8,4,21),
(11,11,3,21),
(12,12,4,21),
(13,13,3,21),
(14,14,4,21),
(15,15,3,21),
(16,16,4,21),
(17,17,3,21),
(18,18,4,21),
(19,19,3,21),
(20,20,4,21),
(21,21,3,21),
(22,22,3,21),
(25,24,5,12),
(26,24,6,12),
(27,25,3,21),
(28,26,4,21),
(29,27,3,21),
(30,28,4,21),
(33,31,3,21),
(34,32,4,21),
(35,33,3,21),
(36,34,4,21),
(37,35,5,12),
(38,36,3,21),
(39,37,4,21),
(40,38,5,12),
(41,39,5,12),
(42,40,13,500);

DROP TABLE IF EXISTS `preplist_items`;
CREATE TABLE `preplist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `preplist_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `min_shelf_life_months` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preplist_items_preplist_item_id_unique` (`preplist_id`,`item_id`),
  KEY `preplist_items_item_id_foreign` (`item_id`),
  KEY `preplist_items_preplist_id_foreign` (`preplist_id`),
  CONSTRAINT `preplist_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `preplist_items_preplist_id_foreign` FOREIGN KEY (`preplist_id`) REFERENCES `preplists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preplist_items` (`id`,`preplist_id`,`item_id`,`min_shelf_life_months`) VALUES
(3,1,2,0),
(4,1,3,0),
(7,2,2,0),
(8,2,3,0),
(11,3,2,0),
(12,3,3,1),
(13,4,2,0),
(14,4,3,0),
(15,5,2,0),
(16,5,3,0),
(17,6,2,5),
(18,6,3,2),
(19,7,2,5),
(20,7,3,2),
(21,8,2,3),
(22,9,2,3),
(24,10,1,1),
(25,11,2,0),
(26,11,3,0),
(27,12,2,0),
(28,12,3,0),
(31,13,2,1),
(32,13,3,1),
(33,14,2,0),
(34,14,3,0),
(35,14,1,0),
(36,15,2,0),
(37,15,3,0),
(38,15,1,0),
(39,16,1,0),
(40,17,4,0);

DROP TABLE IF EXISTS `preplists`;
CREATE TABLE `preplists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `reference_no` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preplists_reference_no_unique` (`reference_no`),
  KEY `preplists_created_by_foreign` (`created_by`),
  CONSTRAINT `preplists_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preplists` (`id`,`created_by`,`name`,`reference_no`,`notes`,`deleted_at`,`created_at`,`updated_at`) VALUES
(1,2,'TEST PREP LIST','PL-2026-0001','waragud','2026-11-10 02:45:52',NULL,'2026-11-10 02:45:52'),
(2,2,'test prep list','PL-2026-0002','test notes','2026-11-11 00:21:09','2026-11-10 02:46:10','2026-11-11 00:21:09'),
(3,2,'asdadsd','PL-2026-0003','asdasd','2026-11-11 00:21:13','2026-11-10 03:17:13','2026-11-11 00:21:13'),
(4,2,'merged','PL-2026-0004',NULL,'2026-11-11 00:21:21','2026-11-11 00:17:13','2026-11-11 00:21:21'),
(5,2,'test','PL-2026-0005','this is for testing only','2026-11-11 01:48:33','2026-11-11 00:21:49','2026-11-11 01:48:33'),
(6,2,'TEST PREP LIST','PL-2026-0006','TEST PREP LIST','2026-11-11 01:49:44','2026-11-11 01:49:13','2026-11-11 01:49:44'),
(7,2,'TEST PREP LIST','PL-2026-0007','TEST PREP LIST','2026-11-11 05:29:22','2026-11-11 01:49:37','2026-11-11 05:29:22'),
(8,2,'wew','PL-2026-0008','wew','2026-11-11 01:50:53','2026-11-11 01:50:26','2026-11-11 01:50:53'),
(9,2,'wew','PL-2026-0009','wew','2026-11-11 01:50:56','2026-11-11 01:50:42','2026-11-11 01:50:56'),
(10,2,'gfsdgsd','PL-2026-0010',NULL,'2026-11-11 05:29:25','2026-11-11 02:01:21','2026-11-11 05:29:25'),
(11,2,'bayri mi','PL-2026-0011',NULL,'2026-11-11 05:29:28','2026-11-11 05:28:02','2026-11-11 05:29:28'),
(12,2,'bayad pls','PL-2026-0012',NULL,'2026-11-11 05:30:48','2026-11-11 05:29:44','2026-11-11 05:30:48'),
(13,2,'Prep list test','PL-2026-0013',NULL,NULL,'2026-11-11 06:25:13','2026-11-11 06:25:13'),
(14,2,'test again 2','PL-2026-0014',NULL,'2026-11-11 06:34:09','2026-11-11 06:33:36','2026-11-11 06:34:09'),
(15,2,'TEST PREP LIST2','PL-2026-0015',NULL,NULL,'2026-11-11 06:34:23','2026-11-11 06:34:23'),
(16,2,'zxzxc','PL-2026-0016',NULL,NULL,'2026-11-12 06:35:45','2026-11-12 06:35:45'),
(17,2,'adadqwddqwed','PL-2026-0017',NULL,'2026-11-12 08:35:02','2026-11-12 08:34:54','2026-11-12 08:35:02');

DROP TABLE IF EXISTS `procuring_entities`;
CREATE TABLE `procuring_entities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_no` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `procuring_entities` (`id`,`name`,`type`,`address`,`contact_person`,`contact_no`,`email`,`deleted_at`) VALUES
(1,'LGU - ANDA','Government Hospital','asdsdad','Joyce Espejo','Call my name and i\'ll be there','asdasdasd@gmail.com',NULL),
(2,'LGU - BISAYA','Government Hospital',NULL,'Peter gihapon','62626523625','kyle@gmail.com',NULL);

DROP TABLE IF EXISTS `project_items`;
CREATE TABLE `project_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `in_stock` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_cost` decimal(15,2) NOT NULL,
  `awarded_unit_price` decimal(15,2) DEFAULT NULL,
  `fulfillment_status` varchar(255) NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `project_items_project_id_foreign` (`project_id`),
  KEY `project_items_item_id_foreign` (`item_id`),
  CONSTRAINT `project_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `project_items_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `project_items` (`id`,`project_id`,`item_id`,`quantity`,`in_stock`,`unit_cost`,`awarded_unit_price`,`fulfillment_status`) VALUES
(3,2,2,21,0,'21.00','21.00','pending'),
(4,2,3,21,0,'21.00','21.00','pending'),
(5,3,1,12,0,'12.00','12.00','pending'),
(6,4,1,12,0,'12.00','12.00','pending'),
(13,5,4,500,250,'5000.00','500.00','pending');

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `procuring_entity_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `reference_no` varchar(255) NOT NULL,
  `mode_of_procurement` varchar(255) NOT NULL,
  `amount_awarded` decimal(15,2) DEFAULT NULL,
  `date_awarded` date DEFAULT NULL,
  `delivery_days` smallint(6) DEFAULT NULL,
  `delivery_due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_procuring_entity_id_foreign` (`procuring_entity_id`),
  KEY `projects_created_by_foreign` (`created_by`),
  KEY `projects_reference_no_index` (`reference_no`),
  CONSTRAINT `projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `projects_procuring_entity_id_foreign` FOREIGN KEY (`procuring_entity_id`) REFERENCES `procuring_entities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `projects` (`id`,`procuring_entity_id`,`created_by`,`title`,`reference_no`,`mode_of_procurement`,`amount_awarded`,`date_awarded`,`delivery_days`,`delivery_due_date`,`notes`,`deleted_at`) VALUES
(1,1,2,'dum','asdawdasdadwaswd','competitive_bidding','5000.00','2026-11-19',7,'2026-11-26','hehe','2026-11-10 02:11:42'),
(2,1,2,'Drugs for me','test','direct_contracting','50000.00','2026-12-12',45,'2027-01-26','this is a test data',NULL),
(3,1,2,'testubg','213','competitive_bidding','21321.00','2026-11-13',45,'2026-12-28','wrqwf',NULL),
(4,1,2,'test2','test','small_value','124.00','2026-11-13',30,'2026-12-13','22wf',NULL),
(5,1,2,'wewew','wewewe','competitive_bidding','50000.00','2026-11-10',7,'2026-11-17',NULL,NULL);

DROP TABLE IF EXISTS `purchase_order_item_allocations`;
CREATE TABLE `purchase_order_item_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_item_id` bigint(20) unsigned NOT NULL,
  `project_item_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_item_allocations_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_order_item_allocations_project_item_id_foreign` (`project_item_id`),
  CONSTRAINT `purchase_order_item_allocations_project_item_id_foreign` FOREIGN KEY (`project_item_id`) REFERENCES `project_items` (`id`),
  CONSTRAINT `purchase_order_item_allocations_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_order_item_allocations` (`id`,`purchase_order_item_id`,`project_item_id`,`qty`) VALUES
(11,9,3,21),
(12,10,4,21);

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `quotation_item_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `min_remaining_months` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_items_purchase_order_quote_item_unique` (`purchase_order_id`,`quotation_item_id`),
  KEY `purchase_order_items_quotation_item_id_foreign` (`quotation_item_id`),
  KEY `purchase_order_items_item_id_foreign` (`item_id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  CONSTRAINT `purchase_order_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_quotation_item_id_foreign` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_order_items` (`id`,`purchase_order_id`,`quotation_item_id`,`item_id`,`qty`,`unit_price`,`min_remaining_months`) VALUES
(9,6,17,2,21,'2530.00',1),
(10,6,18,3,21,'300.00',1);

DROP TABLE IF EXISTS `purchase_order_receipts`;
CREATE TABLE `purchase_order_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_item_id` bigint(20) unsigned NOT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `received_qty` int(11) NOT NULL,
  `lot_no` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `received_on` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_receipts_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_order_receipts_received_by_foreign` (`received_by`),
  CONSTRAINT `purchase_order_receipts_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_receipts_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `po_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `issued_on` date DEFAULT NULL,
  `expected_delivery_on` date DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `deliver_to` varchar(255) DEFAULT NULL,
  `delivery_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_quotation_id_foreign` (`quotation_id`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchase_orders_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `purchase_orders` (`id`,`quotation_id`,`supplier_id`,`created_by`,`po_number`,`status`,`issued_on`,`expected_delivery_on`,`payment_terms`,`deliver_to`,`delivery_fee`,`notes`,`cancelled_at`,`cancel_reason`) VALUES
(6,17,1,2,'PO-2026-0006','cancelled',NULL,'2026-12-11','30 days from delivery',NULL,'0.00','Deliver in a single shipment. Include the PO number on all packing slips and invoices.','2026-11-11 07:37:07',NULL);

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `preplist_item_id` bigint(20) unsigned NOT NULL,
  `min_remaining_months` smallint(6) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `available_qty` int(11) DEFAULT NULL,
  `quoted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_items_quotation_preplist_item_unique` (`quotation_id`,`preplist_item_id`),
  KEY `quotation_items_preplist_item_id_foreign` (`preplist_item_id`),
  KEY `quotation_items_quotation_id_foreign` (`quotation_id`),
  CONSTRAINT `quotation_items_preplist_item_id_foreign` FOREIGN KEY (`preplist_item_id`) REFERENCES `preplist_items` (`id`),
  CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quotation_items` (`id`,`quotation_id`,`preplist_item_id`,`min_remaining_months`,`unit_price`,`brand`,`expiry`,`available_qty`,`quoted_at`) VALUES
(17,17,31,1,'2530.00','AYASIB','2027-02-02',NULL,'2026-11-11 06:29:20'),
(18,17,32,1,'300.00','RENE','2027-02-02',NULL,'2026-11-11 06:29:20');

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `preplist_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `rfq_number` varchar(255) NOT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_rfq_number_unique` (`rfq_number`),
  KEY `quotations_preplist_id_foreign` (`preplist_id`),
  KEY `quotations_supplier_id_foreign` (`supplier_id`),
  KEY `quotations_created_by_foreign` (`created_by`),
  CONSTRAINT `quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `quotations_preplist_id_foreign` FOREIGN KEY (`preplist_id`) REFERENCES `preplists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quotations` (`id`,`preplist_id`,`supplier_id`,`created_by`,`rfq_number`,`generated_at`,`sent_at`,`document_path`,`document_name`,`document_size`) VALUES
(17,13,1,2,'RFQ-2026-0017','2026-11-11 06:28:13',NULL,'quotations/RFQ-2026-0017.xlsx','RFQ-PL-2026-0013-kyle.xlsx',154621),
(18,16,1,2,'RFQ-2026-0018','2026-11-12 06:35:53',NULL,NULL,NULL,NULL);

DROP TABLE IF EXISTS `role_module_access`;
CREATE TABLE `role_module_access` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `module` varchar(50) NOT NULL,
  `level` varchar(20) NOT NULL DEFAULT 'none',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module_access_role_id_module_unique` (`role_id`,`module`),
  CONSTRAINT `role_module_access_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_module_access` (`id`,`role_id`,`module`,`level`,`created_at`,`updated_at`) VALUES
(1,3,'projects','manage','2026-11-12 06:17:57','2026-11-12 08:30:07'),
(2,3,'preplist','manage','2026-11-12 06:17:57','2026-11-12 07:46:31'),
(3,3,'quotation','manage','2026-11-12 06:17:57','2026-11-12 08:19:34'),
(4,3,'priceindex','manage','2026-11-12 06:17:57','2026-11-12 06:17:57'),
(5,3,'purchase-order','manage','2026-11-12 06:17:57','2026-11-12 07:46:31'),
(6,3,'entities','manage','2026-11-12 06:17:57','2026-11-12 08:04:27'),
(7,4,'projects','view','2026-11-12 06:17:57','2026-11-12 06:17:57'),
(8,4,'preplist','view','2026-11-12 06:17:57','2026-11-12 06:17:57'),
(9,4,'quotation','view','2026-11-12 06:17:57','2026-11-12 06:17:57'),
(10,4,'priceindex','view','2026-11-12 06:17:57','2026-11-12 06:17:57'),
(11,4,'purchase-order','view','2026-11-12 06:17:57','2026-11-12 06:17:57'),
(12,4,'entities','view','2026-11-12 06:17:57','2026-11-12 06:17:57');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`name`,`description`) VALUES
(2,'Administrator','Full system access'),
(3,'Procurement Officer','Can create and manage purchase orders'),
(4,'Viewer','Read-only access to all modules');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_no` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`,`name`,`address`,`contact_person`,`contact_no`,`email`,`deleted_at`) VALUES
(1,'NINE LIVES PHARMA INCORPORATED LT. CO','taga tiptip ko bai 2','Peter Kyle Bisaya','62626523625','kyle@gmail.com',NULL),
(2,'carlo cutie',NULL,'sdawdad','asdawdasd','asdasdasd@gmail.com',NULL);

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`role_id`,`name`,`email`,`password`,`is_active`,`created_at`,`updated_at`) VALUES
(2,2,'admin','admin@example.com','$2y$12$ubzJUQAv7HFYCBLMALyUQOyXxT5qiPhEYGVd9EYnoblotuNHSEqYu',1,'2026-11-09 03:17:50','2026-11-09 03:17:50'),
(3,3,'user1','user1@example.com','$2y$12$riPutKXkT/TdHmETTIhoS.Lc7ryP60tTFW2xIRVCux7gR4.ZPP8om',1,'2026-11-09 03:17:50','2026-11-09 03:17:50'),
(5,4,'kyle','kyle@gmail.com','$2y$12$LA2Sh0JIv5jegbi3CZYHReHuu1QdwYDKdC3e7ZlM9eoC7xjeEW1ZO',1,'2026-11-09 03:22:38','2026-11-12 06:31:14');

SET FOREIGN_KEY_CHECKS=1;
-- Dump completed: 2026-11-13 07:22:30
