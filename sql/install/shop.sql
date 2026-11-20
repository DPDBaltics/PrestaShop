CREATE TABLE IF NOT EXISTS `PREFIX_dpd_shop` (
  `id_dpd_shop` INT(11) UNSIGNED AUTO_INCREMENT,
  `parcel_shop_id` varchar(64) NOT NULL,
  `company` varchar(64) NOT NULL,
  `country` varchar(2) NOT NULL,
  `city` varchar(64) NOT NULL,
  `p_code` VARCHAR(12) NOT NULL,
  `street` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `phone` varchar(64) NOT NULL,
  `longitude` decimal(20,6) NOT NULL,
  `latitude` decimal(20,6) NOT NULL,
  PRIMARY KEY (`id_dpd_shop`),
  INDEX (`parcel_shop_id`),
  INDEX (`country`, `city`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

