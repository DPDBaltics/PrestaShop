<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 * International Registered Trademark & Property of INVERTUS, UAB
 */

use Invertus\dpdBaltics\Install\TableIndexInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @return bool
 *
 * @throws PrestaShopDatabaseException
 */
function upgrade_module_3_3_3(DPDBaltics $module)
{
    // A BO zip upgrade can still run on the previous release's classmap-authoritative autoloader
    if (!class_exists(TableIndexInstaller::class)) {
        require_once $module->getLocalPath() . 'src/Install/TableIndexInstaller.php';
    }

    return (new TableIndexInstaller(Db::getInstance(), _DB_PREFIX_))->addMissingIndexes();
}
