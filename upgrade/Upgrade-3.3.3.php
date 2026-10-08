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

use Invertus\dpdBaltics\Service\Import\LeftoverImportFilesCleaner;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Removes files that earlier versions left in tmp/import after a ZIP import.
 *
 * @return bool
 */
function upgrade_module_3_3_3(DPDBaltics $module)
{
    $cleaner = new LeftoverImportFilesCleaner();
    $cleaner->clean($module->getLocalPath() . 'tmp/import');

    return true;
}
