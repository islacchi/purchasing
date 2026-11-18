-- Purchasing database dump
-- Generated: 2026-11-18 14:45:19
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `purchasing_db`;
CREATE DATABASE `purchasing_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `purchasing_db`;

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `item_categories`;
CREATE TABLE `item_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_min_shelf_life_months` smallint NOT NULL DEFAULT '0',
  `box_size` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `items_category_id_foreign` (`category_id`),
  CONSTRAINT `items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `item_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(22,'2026_11_09_000018_add_timestamps_to_users_table',1),
(23,'2026_11_10_000001_add_timestamps_to_preplists_table',1),
(24,'2026_11_11_000001_add_brand_and_expiry_to_quotation_items_table',1),
(25,'2026_11_12_000001_convert_quotation_items_expiry_to_date_table',1),
(26,'2026_11_12_000002_drop_offered_shelf_life_months_from_items_tables',1),
(27,'2026_11_12_000003_add_unique_indexes_to_junction_items_tables',1),
(28,'2026_11_12_000004_add_in_stock_to_project_items_table',1),
(29,'2026_11_12_000005_create_role_module_access_table',1),
(30,'2026_11_16_000001_create_po_cart_items_table',1),
(31,'2026_11_17_000002_add_unique_index_to_purchase_order_items',1),
(32,'2026_11_17_080001_rename_priceindex_module_to_item_catalog',1),
(33,'2026_11_17_100001_shift_datetime_columns_by_eight_hours',1),
(34,'2026_11_18_100001_add_created_at_index_to_activity_logs',1),
(35,'2026_11_18_120246_add_box_size_to_items_and_quotation_items',1);

DROP TABLE IF EXISTS `number_sequences`;
CREATE TABLE `number_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint NOT NULL,
  `last_number` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_sequences_key_year_unique` (`key`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `po_cart_items`;
CREATE TABLE `po_cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `quotation_item_id` bigint unsigned NOT NULL,
  `qty` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_cart_items_user_id_quotation_item_id_unique` (`user_id`,`quotation_item_id`),
  KEY `po_cart_items_quotation_item_id_foreign` (`quotation_item_id`),
  CONSTRAINT `po_cart_items_quotation_item_id_foreign` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `po_cart_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `preplist_item_sources`;
CREATE TABLE `preplist_item_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `preplist_item_id` bigint unsigned NOT NULL,
  `project_item_id` bigint unsigned NOT NULL,
  `qty` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `preplist_item_sources_preplist_item_id_foreign` (`preplist_item_id`),
  KEY `preplist_item_sources_project_item_id_foreign` (`project_item_id`),
  CONSTRAINT `preplist_item_sources_preplist_item_id_foreign` FOREIGN KEY (`preplist_item_id`) REFERENCES `preplist_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `preplist_item_sources_project_item_id_foreign` FOREIGN KEY (`project_item_id`) REFERENCES `project_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `preplist_items`;
CREATE TABLE `preplist_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `preplist_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `min_shelf_life_months` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `preplist_items_preplist_item_id_unique` (`preplist_id`,`item_id`),
  KEY `preplist_items_item_id_foreign` (`item_id`),
  CONSTRAINT `preplist_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `preplist_items_preplist_id_foreign` FOREIGN KEY (`preplist_id`) REFERENCES `preplists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `preplists`;
CREATE TABLE `preplists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preplists_reference_no_unique` (`reference_no`),
  KEY `preplists_created_by_foreign` (`created_by`),
  CONSTRAINT `preplists_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `procuring_entities`;
CREATE TABLE `procuring_entities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `project_items`;
CREATE TABLE `project_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `in_stock` int unsigned NOT NULL DEFAULT '0',
  `unit_cost` decimal(15,2) NOT NULL,
  `awarded_unit_price` decimal(15,2) DEFAULT NULL,
  `fulfillment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `project_items_project_id_foreign` (`project_id`),
  KEY `project_items_item_id_foreign` (`item_id`),
  CONSTRAINT `project_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `project_items_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `procuring_entity_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode_of_procurement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_awarded` decimal(15,2) DEFAULT NULL,
  `date_awarded` date DEFAULT NULL,
  `delivery_days` smallint DEFAULT NULL,
  `delivery_due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_procuring_entity_id_foreign` (`procuring_entity_id`),
  KEY `projects_created_by_foreign` (`created_by`),
  KEY `projects_reference_no_index` (`reference_no`),
  CONSTRAINT `projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `projects_procuring_entity_id_foreign` FOREIGN KEY (`procuring_entity_id`) REFERENCES `procuring_entities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_order_item_allocations`;
CREATE TABLE `purchase_order_item_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_item_id` bigint unsigned NOT NULL,
  `project_item_id` bigint unsigned NOT NULL,
  `qty` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_item_allocations_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_order_item_allocations_project_item_id_foreign` (`project_item_id`),
  CONSTRAINT `purchase_order_item_allocations_project_item_id_foreign` FOREIGN KEY (`project_item_id`) REFERENCES `project_items` (`id`),
  CONSTRAINT `purchase_order_item_allocations_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `quotation_item_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `qty` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `min_remaining_months` smallint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_order_items_purchase_order_quote_item_unique` (`purchase_order_id`,`quotation_item_id`),
  UNIQUE KEY `purchase_order_items_quotation_item_id_unique` (`quotation_item_id`),
  KEY `purchase_order_items_item_id_foreign` (`item_id`),
  CONSTRAINT `purchase_order_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_quotation_item_id_foreign` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_order_receipts`;
CREATE TABLE `purchase_order_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_item_id` bigint unsigned NOT NULL,
  `received_by` bigint unsigned NOT NULL,
  `received_qty` int NOT NULL,
  `lot_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `received_on` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_receipts_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_order_receipts_received_by_foreign` (`received_by`),
  CONSTRAINT `purchase_order_receipts_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_receipts_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `po_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `issued_on` date DEFAULT NULL,
  `expected_delivery_on` date DEFAULT NULL,
  `payment_terms` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deliver_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_quotation_id_foreign` (`quotation_id`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchase_orders_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE `quotation_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint unsigned NOT NULL,
  `preplist_item_id` bigint unsigned NOT NULL,
  `min_remaining_months` smallint DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `supplier_box_size` int unsigned DEFAULT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `available_qty` int DEFAULT NULL,
  `quoted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_items_quotation_preplist_item_unique` (`quotation_id`,`preplist_item_id`),
  KEY `quotation_items_preplist_item_id_foreign` (`preplist_item_id`),
  CONSTRAINT `quotation_items_preplist_item_id_foreign` FOREIGN KEY (`preplist_item_id`) REFERENCES `preplist_items` (`id`),
  CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `preplist_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `rfq_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_size` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_rfq_number_unique` (`rfq_number`),
  KEY `quotations_preplist_id_foreign` (`preplist_id`),
  KEY `quotations_supplier_id_foreign` (`supplier_id`),
  KEY `quotations_created_by_foreign` (`created_by`),
  CONSTRAINT `quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `quotations_preplist_id_foreign` FOREIGN KEY (`preplist_id`) REFERENCES `preplists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_module_access`;
CREATE TABLE `role_module_access` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module_access_role_id_module_unique` (`role_id`,`module`),
  CONSTRAINT `role_module_access_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_module_access` (`id`,`role_id`,`module`,`level`,`created_at`,`updated_at`) VALUES
(1,2,'projects','manage','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(2,2,'preplist','manage','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(3,2,'quotation','manage','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(4,2,'item-catalog','manage','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(5,2,'purchase-order','manage','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(6,2,'entities','manage','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(7,3,'projects','view','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(8,3,'preplist','view','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(9,3,'quotation','view','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(10,3,'item-catalog','view','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(11,3,'purchase-order','view','2026-11-18 14:37:11','2026-11-18 14:37:11'),
(12,3,'entities','view','2026-11-18 14:37:11','2026-11-18 14:37:11');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`name`,`description`) VALUES
(1,'Administrator','Full system access'),
(2,'Procurement Officer','Can create and manage purchase orders'),
(3,'Viewer','Read-only access to all modules');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`role_id`,`name`,`email`,`password`,`is_active`,`created_at`,`updated_at`) VALUES
(1,1,'admin','admin@example.com','$2y$12$qYnAv/vCn8G24is5hAKM0O24msxLvRlyj9um1fpj8WnkZApTW2dgy',1,'2026-11-18 14:37:11','2026-11-18 14:37:11');

SET FOREIGN_KEY_CHECKS=1;
-- Dump completed: 2026-11-18 14:45:19
