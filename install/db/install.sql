CREATE TABLE IF NOT EXISTS `wbs24_wbapi_orders_stack` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `external_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `account_index` int(11) NOT NULL DEFAULT '1',
 `order` text COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `external_id_account_index` (`external_id`, `account_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wbs24_wbapi_prices_stack` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `nm_id` int(11) COLLATE utf8_unicode_ci NOT NULL,
 `account_index` int(11) NOT NULL DEFAULT '1',
 `price` int(11) COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `nm_id_account_index` (`nm_id`, `account_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wbs24_wbapi_products` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `barcode` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `article` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `chrt_id` int(11) COLLATE utf8_unicode_ci NOT NULL,
 `nm_id` int(11) COLLATE utf8_unicode_ci NOT NULL,
 `account_index` int(11) NOT NULL DEFAULT '1',
 PRIMARY KEY (`id`),
 UNIQUE KEY `nm_id_account_index_barcode` (`nm_id`, `account_index`, `barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wbs24_wbapi_orders_directory` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `external_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `parent_external_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `order_group_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `offer_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `account_index` int(11) NOT NULL DEFAULT '1',
 PRIMARY KEY (`id`),
 UNIQUE KEY `external_id_account_index` (`external_id`, `account_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `wbs24_wbapi_orders_stack_converted` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `external_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `account_index` int(11) NOT NULL DEFAULT '1',
 `order` text COLLATE utf8_unicode_ci NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `external_id_account_index` (`external_id`, `account_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
