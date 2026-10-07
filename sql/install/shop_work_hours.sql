CREATE TABLE IF NOT EXISTS `PREFIX_dpd_shop_work_hours` (
    `id_dpd_shop_work_hours` INT(11) UNSIGNED AUTO_INCREMENT,
    `parcel_shop_id` varchar(64) NOT NULL,
    `week_day` varchar(64) NOT NULL,
    `open_morning` varchar(64) NOT NULL,
    `close_morning` varchar(64) NOT NULL,
    `open_afternoon` varchar(64) NOT NULL,
    `close_afternoon` varchar(64) NOT NULL,
    PRIMARY KEY (`id_dpd_shop_work_hours`),
    INDEX (`parcel_shop_id`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
