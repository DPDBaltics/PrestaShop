CREATE TABLE IF NOT EXISTS `PREFIX_dpd_pudo_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cart` INT(11) UNSIGNED NOT NULL,
  `country_code` VARCHAR(2) NOT NULL,
  `city` VARCHAR(64) NOT NULL,
  `street` VARCHAR(64) NOT NULL,
  `post_code` VARCHAR(10) NOT NULL,
  `id_carrier` int(11) UNSIGNED NOT NULL,
  `pudo_id` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`id_cart`),
  INDEX (`id_carrier`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
