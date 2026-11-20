CREATE TABLE IF NOT EXISTS `PREFIX_dpd_zone_range` (
    `id_dpd_zone_range` INT(11) UNSIGNED AUTO_INCREMENT,
    `id_dpd_zone` INT(11) UNSIGNED NOT NULL,
    `id_country` varchar(3) NOT NULL,
    `include_all_zip_codes` TINYINT(1) NOT NULL,
    `zip_code_from` VARCHAR(12) NOT NULL DEFAULT '',
    `zip_code_to` VARCHAR(12) NOT NULL DEFAULT '',
    `zip_code_from_numeric` INT(12) NOT NULL,
    `zip_code_to_numeric` INT(12) NOT NULL,
    PRIMARY KEY (`id_dpd_zone_range`),
    INDEX (`id_country`),
    UNIQUE (`id_dpd_zone`, `id_country`, `zip_code_from`, `zip_code_to`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
