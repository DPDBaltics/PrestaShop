CREATE TABLE IF NOT EXISTS `PREFIX_dpd_order_phone` (
  `id_dpd_order_phone` INT(11) UNSIGNED AUTO_INCREMENT,
  `id_cart` INT(11) UNSIGNED,
  `phone` VARCHAR(50),
  `phone_area` VARCHAR (10),
  PRIMARY KEY (`id_dpd_order_phone`),
  INDEX (`id_cart`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
