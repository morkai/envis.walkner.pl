SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases` (
  `id` int(10) UNSIGNED NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `createdBy` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `factory` tinyint(3) UNSIGNED DEFAULT NULL,
  `machine` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `device` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `supporter` tinyint(3) UNSIGNED DEFAULT NULL,
  `editingTime` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `cost` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `subject` varchar(128) COLLATE utf8_polish_ci NOT NULL DEFAULT '',
  `problem` text COLLATE utf8_polish_ci NOT NULL,
  `diagnosis` text CHARACTER SET utf8 NOT NULL,
  `solution` text CHARACTER SET utf8 NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `kind` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `priority` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `case_categories`;
CREATE TABLE `case_categories` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `parent` tinyint(3) UNSIGNED DEFAULT NULL,
  `name` varchar(128) CHARACTER SET utf8 NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_card_pages`;
CREATE TABLE `catalog_card_pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `product` int(10) UNSIGNED NOT NULL,
  `position` tinyint(3) UNSIGNED NOT NULL,
  `layout` varchar(50) COLLATE utf8_polish_ci NOT NULL,
  `contents` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_card_templates`;
CREATE TABLE `catalog_card_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8_polish_ci NOT NULL,
  `contents` text COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_categories`;
CREATE TABLE `catalog_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(60) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `catalog_manufacturers`;
CREATE TABLE `catalog_manufacturers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8_polish_ci NOT NULL,
  `nr` smallint(5) UNSIGNED NOT NULL,
  `label` varchar(100) COLLATE utf8_polish_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_products`;
CREATE TABLE `catalog_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category` int(10) UNSIGNED NOT NULL,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `updatedAt` int(10) UNSIGNED NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  `nr` varchar(30) COLLATE utf8_polish_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_polish_ci NOT NULL,
  `description` text COLLATE utf8_polish_ci NOT NULL,
  `type` varchar(100) COLLATE utf8_polish_ci NOT NULL DEFAULT '',
  `image` int(10) UNSIGNED DEFAULT NULL,
  `productionDate` char(7) COLLATE utf8_polish_ci NOT NULL DEFAULT '0000-00',
  `markings` varchar(100) COLLATE utf8_polish_ci NOT NULL DEFAULT '',
  `kind` int(10) UNSIGNED DEFAULT NULL,
  `manufacturer` int(10) UNSIGNED DEFAULT NULL,
  `revision` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_product_documentations`;
CREATE TABLE `catalog_product_documentations` (
  `product` int(10) UNSIGNED NOT NULL,
  `documentation` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_product_files`;
CREATE TABLE `catalog_product_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `product` int(10) UNSIGNED NOT NULL,
  `uploader` tinyint(3) UNSIGNED NOT NULL,
  `uploadedAt` int(10) UNSIGNED NOT NULL,
  `file` varchar(250) COLLATE utf8_polish_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `catalog_product_images`;
CREATE TABLE `catalog_product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product` int(10) UNSIGNED NOT NULL,
  `file` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `catalog_product_kinds`;
CREATE TABLE `catalog_product_kinds` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8_polish_ci NOT NULL,
  `nr` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `declaration_templates`;
CREATE TABLE `declaration_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8_polish_ci NOT NULL,
  `pattern` varchar(200) COLLATE utf8_polish_ci NOT NULL,
  `code` text COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `documentations`;
CREATE TABLE `documentations` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `machine` varchar(64) DEFAULT NULL,
  `device` varchar(64) DEFAULT NULL,
  `title` varchar(128) NOT NULL DEFAULT '',
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `documentation_files`;
CREATE TABLE `documentation_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `documentation` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `file` varchar(128) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `emails`;
CREATE TABLE `emails` (
  `id` int(10) UNSIGNED NOT NULL,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) COLLATE utf8_polish_ci NOT NULL,
  `from` varchar(100) COLLATE utf8_polish_ci NOT NULL,
  `to` varchar(50) COLLATE utf8_polish_ci NOT NULL,
  `body` text COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `email_attachments`;
CREATE TABLE `email_attachments` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8_polish_ci NOT NULL,
  `type` varchar(60) COLLATE utf8_polish_ci NOT NULL,
  `size` int(10) UNSIGNED NOT NULL,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `engines`;
CREATE TABLE `engines` (
  `id` varchar(64) NOT NULL DEFAULT '',
  `machine` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `owner` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `variable` varchar(64) NOT NULL DEFAULT '',
  `factory` tinyint(3) UNSIGNED DEFAULT NULL,
  `machine` varchar(64) DEFAULT NULL,
  `device` varchar(64) DEFAULT NULL,
  `operator` enum('==','!=','<','<=','>','>=') NOT NULL DEFAULT '==',
  `valueType` enum('concrete','min','max') NOT NULL DEFAULT 'concrete',
  `value` float(9,5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `event_actions`;
CREATE TABLE `event_actions` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `event` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(32) NOT NULL DEFAULT '',
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `factories`;
CREATE TABLE `factories` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `latitude` decimal(18,15) NOT NULL DEFAULT 0.000000000000000,
  `longitude` decimal(18,15) NOT NULL DEFAULT 0.000000000000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `grid_views`;
CREATE TABLE `grid_views` (
  `grid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `view` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `creator` tinyint(3) UNSIGNED NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `options` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `grid_view_defaults`;
CREATE TABLE `grid_view_defaults` (
  `grid` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `view` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `user` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `help`;
CREATE TABLE `help` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent` int(10) UNSIGNED DEFAULT NULL,
  `position` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `title` varchar(128) NOT NULL DEFAULT '',
  `contents` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `help_tags`;
CREATE TABLE `help_tags` (
  `tag` varchar(64) NOT NULL DEFAULT '',
  `page` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `issues`;
CREATE TABLE `issues` (
  `id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `creator` tinyint(3) UNSIGNED NOT NULL,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `updatedAt` int(10) UNSIGNED NOT NULL,
  `owner` tinyint(3) UNSIGNED DEFAULT NULL,
  `ownerStakes` decimal(10,2) UNSIGNED NOT NULL,
  `ownerStakesType` tinyint(1) UNSIGNED NOT NULL,
  `subject` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `relatedFactory` tinyint(3) UNSIGNED DEFAULT NULL,
  `relatedMachine` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `relatedDevice` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `relatedProduct` int(10) UNSIGNED DEFAULT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT 2,
  `kind` tinyint(1) NOT NULL DEFAULT 3,
  `type` tinyint(1) NOT NULL DEFAULT 4,
  `orderNumber` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orderDate` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orderInvoice` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orderInvoiceDate` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expectedFinishAt` date DEFAULT NULL,
  `percent` float DEFAULT NULL,
  `quantity` decimal(10,4) UNSIGNED NOT NULL DEFAULT 1.0000,
  `unit` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'szt.',
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PLN',
  `price` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `per` int(11) UNSIGNED NOT NULL DEFAULT 1,
  `vat` tinyint(3) UNSIGNED NOT NULL DEFAULT 23
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_assignees`;
CREATE TABLE `issue_assignees` (
  `issue` int(10) UNSIGNED NOT NULL,
  `assignee` tinyint(3) UNSIGNED NOT NULL,
  `stakes` decimal(10,2) UNSIGNED NOT NULL,
  `stakesType` tinyint(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_emails`;
CREATE TABLE `issue_emails` (
  `id` int(11) NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_files`;
CREATE TABLE `issue_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `issue` int(10) UNSIGNED NOT NULL,
  `uploader` tinyint(3) UNSIGNED NOT NULL,
  `uploadedAt` int(10) UNSIGNED NOT NULL,
  `file` varchar(60) COLLATE utf8_polish_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `issue_history`;
CREATE TABLE `issue_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `issue` int(10) UNSIGNED NOT NULL,
  `parent` int(10) UNSIGNED DEFAULT NULL,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `createdBy` tinyint(3) UNSIGNED NOT NULL,
  `changes` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `tasks` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_relations`;
CREATE TABLE `issue_relations` (
  `issue1` int(10) UNSIGNED NOT NULL,
  `issue2` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_subscribers`;
CREATE TABLE `issue_subscribers` (
  `issue` int(10) UNSIGNED NOT NULL,
  `user` tinyint(3) UNSIGNED NOT NULL,
  `recentlyNotifiedAt` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_tasks`;
CREATE TABLE `issue_tasks` (
  `id` int(1) UNSIGNED NOT NULL,
  `issue` int(1) UNSIGNED NOT NULL,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `createdBy` tinyint(3) UNSIGNED NOT NULL,
  `assignedTo` tinyint(3) UNSIGNED DEFAULT NULL,
  `completedAt` int(10) UNSIGNED DEFAULT NULL,
  `completedBy` tinyint(3) UNSIGNED DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `summary` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `position` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `issue_templates`;
CREATE TABLE `issue_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `createdBy` tinyint(3) UNSIGNED NOT NULL,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `issue_template_tasks`;
CREATE TABLE `issue_template_tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `template` int(10) UNSIGNED NOT NULL,
  `summary` varchar(255) COLLATE utf8_polish_ci NOT NULL,
  `description` text COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

DROP TABLE IF EXISTS `issue_times`;
CREATE TABLE `issue_times` (
  `id` int(10) UNSIGNED NOT NULL,
  `issue` int(10) UNSIGNED NOT NULL,
  `user` tinyint(3) UNSIGNED NOT NULL,
  `createdAt` int(10) UNSIGNED NOT NULL,
  `timeSpent` int(10) UNSIGNED NOT NULL,
  `comment` varchar(250) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `limits`;
CREATE TABLE `limits` (
  `machine` varchar(64) NOT NULL DEFAULT '',
  `device` varchar(64) NOT NULL DEFAULT '',
  `variable` varchar(64) NOT NULL DEFAULT '',
  `min` float(9,5) NOT NULL DEFAULT 0.00000,
  `max` float(9,5) NOT NULL DEFAULT 0.00000
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` tinyint(3) UNSIGNED DEFAULT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(40) NOT NULL DEFAULT '',
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `machines`;
CREATE TABLE `machines` (
  `id` varchar(64) NOT NULL DEFAULT '',
  `factory` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int(10) UNSIGNED NOT NULL,
  `createdBy` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` varchar(128) NOT NULL DEFAULT '',
  `introduction` text NOT NULL,
  `body` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `id` int(10) UNSIGNED NOT NULL,
  `issue` int(10) UNSIGNED DEFAULT NULL,
  `number` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `createdAt` date NOT NULL,
  `updatedAt` int(10) UNSIGNED NOT NULL,
  `closedAt` date DEFAULT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `client` text COLLATE utf8_unicode_ci NOT NULL,
  `clientContact` text COLLATE utf8_unicode_ci NOT NULL,
  `supplier` text COLLATE utf8_unicode_ci NOT NULL,
  `supplierContact` text COLLATE utf8_unicode_ci NOT NULL,
  `intro` text COLLATE utf8_unicode_ci NOT NULL,
  `outro` text COLLATE utf8_unicode_ci NOT NULL,
  `sentTo` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cancelled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `search` text CHARACTER SET utf8 COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `offer_items`;
CREATE TABLE `offer_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `offer` int(10) UNSIGNED NOT NULL,
  `issue` int(10) UNSIGNED DEFAULT NULL,
  `position` tinyint(3) UNSIGNED NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `quantity` decimal(10,4) UNSIGNED NOT NULL,
  `unit` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'szt.',
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PLN',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `per` int(11) NOT NULL DEFAULT 1,
  `vat` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `offer_templates`;
CREATE TABLE `offer_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `template` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `plc_devices`;
CREATE TABLE `plc_devices` (
  `id` int(10) UNSIGNED NOT NULL,
  `machine` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `initialValue` float(16,8) NOT NULL DEFAULT 0.00000000,
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `kind` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `comment1` text COLLATE utf8_unicode_ci NOT NULL,
  `comment2` text COLLATE utf8_unicode_ci NOT NULL,
  `comment3` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `plc_machines`;
CREATE TABLE `plc_machines` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comments` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `plc_modules`;
CREATE TABLE `plc_modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `machine` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `type` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `plc_module_elements`;
CREATE TABLE `plc_module_elements` (
  `id` int(10) UNSIGNED NOT NULL,
  `module` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `element` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `position` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `plc_module_types`;
CREATE TABLE `plc_module_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `inputs` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `outputs` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `role_privilages`;
CREATE TABLE `role_privilages` (
  `role` varchar(64) NOT NULL DEFAULT '',
  `privilage` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `storages`;
CREATE TABLE `storages` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `storage_products`;
CREATE TABLE `storage_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `storage` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `index` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `supplier` text COLLATE utf8_unicode_ci NOT NULL,
  `contact` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `role` varchar(64) DEFAULT NULL,
  `super` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(128) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `createdAt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastVisitAt` datetime DEFAULT NULL,
  `allowedFactories` text DEFAULT NULL,
  `allowedMachines` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `values`;
CREATE TABLE `values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `machine` varchar(64) NOT NULL DEFAULT '',
  `engine` varchar(64) NOT NULL DEFAULT '',
  `variable` varchar(64) NOT NULL DEFAULT '',
  `value` float(10,2) NOT NULL DEFAULT 0.00,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `variables`;
CREATE TABLE `variables` (
  `id` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `vis_factories`;
CREATE TABLE `vis_factories` (
  `factory` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `width` smallint(5) UNSIGNED NOT NULL DEFAULT 640,
  `height` smallint(5) UNSIGNED NOT NULL DEFAULT 480,
  `fg_color` varchar(32) NOT NULL DEFAULT 'black',
  `bg_color` varchar(32) NOT NULL DEFAULT 'transparent',
  `bg_image` varchar(128) NOT NULL DEFAULT 'none',
  `bg_position` varchar(32) NOT NULL DEFAULT 'center center',
  `bg_repeat` varchar(9) NOT NULL DEFAULT 'no-repeat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `vis_factory_machines`;
CREATE TABLE `vis_factory_machines` (
  `machine` varchar(64) NOT NULL DEFAULT '',
  `top` smallint(6) NOT NULL DEFAULT 0,
  `left` smallint(6) NOT NULL DEFAULT 0,
  `zindex` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(128) NOT NULL DEFAULT 'default.gif',
  `image_width` int(11) NOT NULL DEFAULT 200,
  `image_height` int(11) NOT NULL DEFAULT 200,
  `image_max_width` int(11) NOT NULL DEFAULT 400,
  `image_max_height` int(11) NOT NULL DEFAULT 450,
  `fg_color` varchar(32) NOT NULL DEFAULT 'inherit',
  `bg_color` varchar(32) NOT NULL DEFAULT 'transparent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `vis_machines`;
CREATE TABLE `vis_machines` (
  `machine` varchar(64) NOT NULL DEFAULT '',
  `width` smallint(5) UNSIGNED NOT NULL DEFAULT 640,
  `height` smallint(5) UNSIGNED NOT NULL DEFAULT 480,
  `fg_color` varchar(32) NOT NULL DEFAULT 'black',
  `bg_color` varchar(32) NOT NULL DEFAULT 'transparent',
  `bg_image` varchar(128) NOT NULL DEFAULT 'none',
  `bg_position` varchar(32) NOT NULL DEFAULT 'center center',
  `bg_repeat` varchar(9) NOT NULL DEFAULT 'no-repeat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `vis_machine_devices`;
CREATE TABLE `vis_machine_devices` (
  `machine` varchar(64) NOT NULL DEFAULT '',
  `device` varchar(64) NOT NULL DEFAULT '',
  `top` smallint(6) NOT NULL DEFAULT 0,
  `left` smallint(6) NOT NULL DEFAULT 0,
  `zindex` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(128) NOT NULL DEFAULT 'default.gif',
  `image_width` int(11) NOT NULL DEFAULT 200,
  `image_height` int(11) NOT NULL DEFAULT 200,
  `image_max_width` int(11) NOT NULL DEFAULT 400,
  `image_max_height` int(11) NOT NULL DEFAULT 450,
  `fg_color` varchar(32) NOT NULL DEFAULT 'inherit',
  `bg_color` varchar(32) NOT NULL DEFAULT 'transparent',
  `variables_fg_color` varchar(32) NOT NULL DEFAULT 'inherit',
  `variables_bg_color` varchar(32) NOT NULL DEFAULT 'transparent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `vis_machine_device_variables`;
CREATE TABLE `vis_machine_device_variables` (
  `machine` varchar(64) NOT NULL DEFAULT '',
  `device` varchar(64) NOT NULL DEFAULT '',
  `variable` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supporter` (`supporter`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `factory` (`factory`),
  ADD KEY `machine` (`machine`),
  ADD KEY `device` (`device`);

ALTER TABLE `case_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent`);

ALTER TABLE `catalog_card_pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product` (`product`);

ALTER TABLE `catalog_card_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `catalog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_name` (`parent`,`name`),
  ADD KEY `parent` (`parent`);

ALTER TABLE `catalog_manufacturers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nr` (`nr`);

ALTER TABLE `catalog_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `image` (`image`),
  ADD KEY `category` (`category`),
  ADD KEY `manufacturer` (`manufacturer`),
  ADD KEY `kind` (`kind`);

ALTER TABLE `catalog_product_documentations`
  ADD PRIMARY KEY (`product`,`documentation`),
  ADD KEY `documentation` (`documentation`);

ALTER TABLE `catalog_product_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product` (`product`),
  ADD KEY `uploader` (`uploader`);

ALTER TABLE `catalog_product_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file` (`file`),
  ADD KEY `product` (`product`);

ALTER TABLE `catalog_product_kinds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nr` (`nr`);

ALTER TABLE `declaration_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `documentations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine` (`machine`),
  ADD KEY `device` (`device`);

ALTER TABLE `documentation_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documentation` (`documentation`);

ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `to` (`to`);

ALTER TABLE `email_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

ALTER TABLE `engines`
  ADD PRIMARY KEY (`id`,`machine`),
  ADD KEY `machine` (`machine`),
  ADD KEY `owner` (`owner`);

ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variable` (`variable`),
  ADD KEY `factory` (`factory`),
  ADD KEY `machine` (`machine`),
  ADD KEY `device` (`device`);

ALTER TABLE `event_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event` (`event`);

ALTER TABLE `factories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `grid_views`
  ADD PRIMARY KEY (`view`),
  ADD KEY `creator` (`creator`),
  ADD KEY `grid` (`grid`);

ALTER TABLE `grid_view_defaults`
  ADD PRIMARY KEY (`grid`,`user`),
  ADD KEY `view` (`view`),
  ADD KEY `user` (`user`);

ALTER TABLE `help`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent`);

ALTER TABLE `help_tags`
  ADD PRIMARY KEY (`tag`,`page`),
  ADD KEY `tag` (`tag`),
  ADD KEY `page` (`page`);

ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner` (`owner`),
  ADD KEY `creator` (`creator`),
  ADD KEY `relatedFactory` (`relatedFactory`),
  ADD KEY `relatedMachine` (`relatedMachine`),
  ADD KEY `relatedDevice` (`relatedDevice`),
  ADD KEY `relatedProduct` (`relatedProduct`);

ALTER TABLE `issue_assignees`
  ADD PRIMARY KEY (`issue`,`assignee`),
  ADD KEY `assignee` (`assignee`);

ALTER TABLE `issue_emails`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `issue_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue` (`issue`),
  ADD KEY `uploader` (`uploader`);

ALTER TABLE `issue_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue` (`issue`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `parent` (`parent`);

ALTER TABLE `issue_relations`
  ADD PRIMARY KEY (`issue1`,`issue2`),
  ADD KEY `issue2` (`issue2`);

ALTER TABLE `issue_subscribers`
  ADD PRIMARY KEY (`issue`,`user`),
  ADD KEY `user` (`user`);

ALTER TABLE `issue_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue` (`issue`),
  ADD KEY `completedBy` (`completedBy`),
  ADD KEY `createdBy` (`createdBy`),
  ADD KEY `assignedTo` (`assignedTo`);

ALTER TABLE `issue_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`);

ALTER TABLE `issue_template_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template` (`template`);

ALTER TABLE `issue_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue` (`issue`),
  ADD KEY `user` (`user`);

ALTER TABLE `limits`
  ADD PRIMARY KEY (`device`,`variable`,`machine`),
  ADD KEY `variable` (`variable`),
  ADD KEY `machine` (`machine`);

ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);

ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factory` (`factory`);

ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `createdBy` (`createdBy`);

ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`number`),
  ADD KEY `issue` (`issue`);
ALTER TABLE `offers` ADD FULLTEXT KEY `fulltext` (`search`);

ALTER TABLE `offer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offer` (`offer`),
  ADD KEY `issue` (`issue`);

ALTER TABLE `offer_templates`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `plc_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine` (`machine`);

ALTER TABLE `plc_machines`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `plc_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `machine` (`machine`);

ALTER TABLE `plc_module_elements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_2` (`module`,`element`),
  ADD KEY `module` (`module`),
  ADD KEY `element` (`element`);

ALTER TABLE `plc_module_types`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `role_privilages`
  ADD PRIMARY KEY (`role`,`privilage`);

ALTER TABLE `storages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner` (`owner`);

ALTER TABLE `storage_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_uniq_in_storage` (`storage`,`index`),
  ADD KEY `storage` (`storage`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `credentials` (`email`,`password`),
  ADD KEY `role` (`role`);

ALTER TABLE `values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variable` (`variable`),
  ADD KEY `machine` (`machine`),
  ADD KEY `engine` (`engine`),
  ADD KEY `dev_var` (`variable`,`machine`,`engine`);

ALTER TABLE `variables`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `vis_factories`
  ADD PRIMARY KEY (`factory`);

ALTER TABLE `vis_factory_machines`
  ADD PRIMARY KEY (`machine`);

ALTER TABLE `vis_machines`
  ADD PRIMARY KEY (`machine`);

ALTER TABLE `vis_machine_devices`
  ADD PRIMARY KEY (`device`,`machine`),
  ADD KEY `machine` (`machine`);

ALTER TABLE `vis_machine_device_variables`
  ADD PRIMARY KEY (`device`,`variable`,`machine`),
  ADD KEY `variable` (`variable`);


ALTER TABLE `cases`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `case_categories`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_card_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_card_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_manufacturers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_product_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `catalog_product_kinds`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `declaration_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `documentations`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `documentation_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `emails`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `events`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `event_actions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `factories`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `help`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_tasks`
  MODIFY `id` int(1) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_template_tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `issue_times`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `offers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `offer_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `offer_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `plc_devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `plc_machines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `plc_modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `plc_module_elements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `plc_module_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `storages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `storage_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;


ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_2` FOREIGN KEY (`factory`) REFERENCES `factories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_4` FOREIGN KEY (`device`) REFERENCES `engines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_5` FOREIGN KEY (`supporter`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_6` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_7` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_8` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `case_categories`
  ADD CONSTRAINT `case_categories_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `case_categories` (`id`) ON DELETE CASCADE;

ALTER TABLE `catalog_card_pages`
  ADD CONSTRAINT `catalog_card_pages_ibfk_1` FOREIGN KEY (`product`) REFERENCES `catalog_products` (`id`) ON DELETE CASCADE;

ALTER TABLE `catalog_categories`
  ADD CONSTRAINT `catalog_categories_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `catalog_categories` (`id`) ON DELETE CASCADE;

ALTER TABLE `catalog_products`
  ADD CONSTRAINT `catalog_products_ibfk_3` FOREIGN KEY (`category`) REFERENCES `catalog_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `catalog_products_ibfk_4` FOREIGN KEY (`image`) REFERENCES `catalog_product_images` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `catalog_products_ibfk_5` FOREIGN KEY (`manufacturer`) REFERENCES `catalog_manufacturers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `catalog_products_ibfk_6` FOREIGN KEY (`kind`) REFERENCES `catalog_product_kinds` (`id`) ON DELETE SET NULL;

ALTER TABLE `catalog_product_documentations`
  ADD CONSTRAINT `catalog_product_documentations_ibfk_1` FOREIGN KEY (`product`) REFERENCES `catalog_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `catalog_product_documentations_ibfk_2` FOREIGN KEY (`documentation`) REFERENCES `documentations` (`id`) ON DELETE CASCADE;

ALTER TABLE `catalog_product_files`
  ADD CONSTRAINT `catalog_product_files_ibfk_1` FOREIGN KEY (`product`) REFERENCES `catalog_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `catalog_product_files_ibfk_2` FOREIGN KEY (`uploader`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `catalog_product_images`
  ADD CONSTRAINT `catalog_product_images_ibfk_1` FOREIGN KEY (`product`) REFERENCES `catalog_products` (`id`) ON DELETE CASCADE;

ALTER TABLE `documentations`
  ADD CONSTRAINT `documentations_ibfk_2` FOREIGN KEY (`device`) REFERENCES `engines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `documentations_ibfk_3` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentations_ibfk_4` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `documentation_files`
  ADD CONSTRAINT `documentation_files_ibfk_1` FOREIGN KEY (`documentation`) REFERENCES `documentations` (`id`) ON DELETE CASCADE;

ALTER TABLE `email_attachments`
  ADD CONSTRAINT `email_attachments_ibfk_1` FOREIGN KEY (`email`) REFERENCES `emails` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `engines`
  ADD CONSTRAINT `engines_ibfk_1` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`variable`) REFERENCES `variables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`factory`) REFERENCES `factories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_4` FOREIGN KEY (`device`) REFERENCES `engines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_5` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `event_actions`
  ADD CONSTRAINT `event_actions_ibfk_1` FOREIGN KEY (`event`) REFERENCES `events` (`id`) ON DELETE CASCADE;

ALTER TABLE `grid_view_defaults`
  ADD CONSTRAINT `grid_view_defaults_ibfk_2` FOREIGN KEY (`view`) REFERENCES `grid_views` (`view`) ON DELETE CASCADE,
  ADD CONSTRAINT `grid_view_defaults_ibfk_3` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `help`
  ADD CONSTRAINT `help_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `help` (`id`) ON DELETE CASCADE;

ALTER TABLE `help_tags`
  ADD CONSTRAINT `help_tags_ibfk_1` FOREIGN KEY (`page`) REFERENCES `help` (`id`) ON DELETE CASCADE;

ALTER TABLE `issues`
  ADD CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`creator`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issues_ibfk_2` FOREIGN KEY (`owner`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issues_ibfk_3` FOREIGN KEY (`relatedFactory`) REFERENCES `factories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issues_ibfk_4` FOREIGN KEY (`relatedMachine`) REFERENCES `machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `issues_ibfk_5` FOREIGN KEY (`relatedDevice`) REFERENCES `engines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `issues_ibfk_6` FOREIGN KEY (`relatedProduct`) REFERENCES `catalog_products` (`id`) ON DELETE SET NULL;

ALTER TABLE `issue_assignees`
  ADD CONSTRAINT `issue_assignees_ibfk_1` FOREIGN KEY (`assignee`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_assignees_ibfk_2` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE;

ALTER TABLE `issue_files`
  ADD CONSTRAINT `issue_files_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_files_ibfk_2` FOREIGN KEY (`uploader`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `issue_history`
  ADD CONSTRAINT `issue_history_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_history_ibfk_2` FOREIGN KEY (`parent`) REFERENCES `issue_history` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_history_ibfk_3` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `issue_relations`
  ADD CONSTRAINT `issue_relations_ibfk_1` FOREIGN KEY (`issue1`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_relations_ibfk_2` FOREIGN KEY (`issue2`) REFERENCES `issues` (`id`) ON DELETE CASCADE;

ALTER TABLE `issue_subscribers`
  ADD CONSTRAINT `issue_subscribers_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_subscribers_ibfk_2` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `issue_tasks`
  ADD CONSTRAINT `issue_tasks_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_tasks_ibfk_3` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_tasks_ibfk_4` FOREIGN KEY (`completedBy`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `issue_tasks_ibfk_5` FOREIGN KEY (`assignedTo`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `issue_template_tasks`
  ADD CONSTRAINT `template` FOREIGN KEY (`template`) REFERENCES `issue_templates` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `issue_times`
  ADD CONSTRAINT `issue_times_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_times_ibfk_2` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_times_ibfk_3` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issue_times_ibfk_4` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `limits`
  ADD CONSTRAINT `limits_ibfk_1` FOREIGN KEY (`device`) REFERENCES `engines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `limits_ibfk_2` FOREIGN KEY (`variable`) REFERENCES `variables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `limits_ibfk_3` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `machines`
  ADD CONSTRAINT `machines_ibfk_1` FOREIGN KEY (`factory`) REFERENCES `factories` (`id`) ON DELETE CASCADE;

ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `offers`
  ADD CONSTRAINT `offers_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE SET NULL;

ALTER TABLE `offer_items`
  ADD CONSTRAINT `offer_items_ibfk_1` FOREIGN KEY (`issue`) REFERENCES `issues` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `offer_items_ibfk_2` FOREIGN KEY (`offer`) REFERENCES `offers` (`id`) ON DELETE CASCADE;

ALTER TABLE `plc_devices`
  ADD CONSTRAINT `plc_devices_ibfk_1` FOREIGN KEY (`machine`) REFERENCES `plc_machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `plc_modules`
  ADD CONSTRAINT `plc_modules_ibfk_1` FOREIGN KEY (`type`) REFERENCES `plc_module_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plc_modules_ibfk_2` FOREIGN KEY (`machine`) REFERENCES `plc_machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `plc_module_elements`
  ADD CONSTRAINT `plc_module_elements_ibfk_1` FOREIGN KEY (`module`) REFERENCES `plc_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plc_module_elements_ibfk_2` FOREIGN KEY (`element`) REFERENCES `plc_devices` (`id`) ON DELETE CASCADE;

ALTER TABLE `role_privilages`
  ADD CONSTRAINT `role_privilages_ibfk_1` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `storages`
  ADD CONSTRAINT `storages_ibfk_1` FOREIGN KEY (`owner`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `storage_products`
  ADD CONSTRAINT `storage_products_ibfk_1` FOREIGN KEY (`storage`) REFERENCES `storages` (`id`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `values`
  ADD CONSTRAINT `values_ibfk_1` FOREIGN KEY (`engine`) REFERENCES `engines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `values_ibfk_2` FOREIGN KEY (`variable`) REFERENCES `variables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `values_ibfk_3` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `vis_factories`
  ADD CONSTRAINT `vis_factories_ibfk_1` FOREIGN KEY (`factory`) REFERENCES `factories` (`id`) ON DELETE CASCADE;

ALTER TABLE `vis_factory_machines`
  ADD CONSTRAINT `vis_factory_machines_ibfk_1` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vis_factory_machines_ibfk_2` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `vis_machines`
  ADD CONSTRAINT `vis_machines_ibfk_1` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vis_machines_ibfk_2` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `vis_machine_devices`
  ADD CONSTRAINT `vis_machine_devices_ibfk_1` FOREIGN KEY (`device`) REFERENCES `engines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vis_machine_devices_ibfk_2` FOREIGN KEY (`machine`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

ALTER TABLE `vis_machine_device_variables`
  ADD CONSTRAINT `vis_machine_device_variables_ibfk_3` FOREIGN KEY (`device`) REFERENCES `engines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vis_machine_device_variables_ibfk_4` FOREIGN KEY (`variable`) REFERENCES `variables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;
