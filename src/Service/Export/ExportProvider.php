<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 *  International Registered Trademark & Property of INVERTUS, UAB
 */

namespace Invertus\dpdBaltics\Service\Export;

use Configuration;
use DPDBaltics;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Repository\ZoneRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ExportProvider
{
    const FILE_NAME = 'ExportProvider';

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var DPDBaltics
     */
    private $module;

    public function __construct(DPDBaltics $module)
    {
        $this->module = $module;
    }

    /**
     * Export given data to CSV file
     *
     * @param ExportableInterface $exportable
     * @return array|bool
     */
    public function export(ExportableInterface $exportable)
    {
        $fileName = $exportable->getFileName();
        $exportRows = $exportable->getRows();
        $headers = $exportable->getHeaders();

        if ($exportable->hasErrors()) {
            return $exportable->getErrors();
        }

        if (!$exportRows || !$fileName) {
            return true;
        }

        $delimiter = Configuration::get(Config::EXPORT_FIELD_SEPARATOR);
        $output = fopen('php://output', 'w');

        $this->sendHttpHeaders($fileName);

        if ($headers && is_array($headers)) {
            fputcsv($output, $headers, $delimiter);
        }

        foreach ($exportRows as $row) {
            fputcsv($output, $row, $delimiter);
        }

        fclose($output);
        return true;
    }

    public function returnExportable($exportOption)
    {
        switch ($exportOption) {
            case Config::IMPORT_EXPORT_OPTION_ZONES:
                /** @var ZoneRepository $zonesRepo */
                $zonesRepo = $this->module->getModuleContainer('invertus.dpdbaltics.repository.zone_repository');
                $zones = $zonesRepo->findAllZonesIds();
                if (empty($zones)) {
                    $this->warnings[] = $this->module->l('No zones found for export', self::FILE_NAME);
                    return '';
                }
                /** @var ZoneExport $exportable */
                $exportable = $this->module->getModuleContainer('invertus.dpdbaltics.service.export.zone_export');
                $exportable->setZonesToExport($zones);
                return $exportable;
            case Config::IMPORT_EXPORT_OPTION_SETTINGS:
                /** @var SettingsExport $exportable */
                $exportable = $this->module->getModuleContainer('invertus.dpdbaltics.service.export.settings_export');
                return $exportable;
            case Config::IMPORT_EXPORT_OPTION_PRODUCTS:
                /** @var ProductExport $exportable */
                $exportable = $this->module->getModuleContainer('invertus.dpdbaltics.service.export.product_export');
                return $exportable;
            case Config::IMPORT_EXPORT_OPTION_PRICE_RULES:
                /** @var PriceRulesExport $exportable */
                $exportable = $this->module->getModuleContainer('invertus.dpdbaltics.service.export.price_rules_export');
                return $exportable;
            case Config::IMPORT_EXPORT_OPTION_ADDRESS_TEMPLATES:
                /** @var AddressTemplatesExport $exportable */
                $exportable = $this->module->getModuleContainer('invertus.dpdbaltics.service.export.address_templates_export');
                return $exportable;
            default:
                $this->errors[] = $this->module->l('Invalid export option selected', self::FILE_NAME);
                return '';
        }
    }

    /**
     * Export given data to CSV file in temp directory
     *
     * @param ExportableInterface $exportable
     * @return array|bool
     */
    public function saveToTemp(ExportableInterface $exportable)
    {
        $fileName = $exportable->getFileName();
        $exportRows = $exportable->getRows();
        $headers = $exportable->getHeaders();

        if ($exportable->hasErrors()) {
            return $exportable->getErrors();
        }

        if (!$exportRows || !$fileName) {
            return true;
        }

        $delimiter = Configuration::get(Config::EXPORT_FIELD_SEPARATOR);
        $output = fopen($this->module->getLocalPath() . 'tmp/export/' . $fileName, 'w');
        if ($headers && is_array($headers)) {
            fputcsv($output, $headers, $delimiter);
        }
        foreach ($exportRows as $row) {
            fputcsv($output, $row, $delimiter);
        }
    }

    /**
     * Send HTTP header to force file download
     *
     * @param string $fileName
     */
    private function sendHttpHeaders($fileName)
    {
        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Content-Type: application/force-download; charset=UTF-8');
        header('Cache-Control: no-store, no-cache');
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @param array $warnings
     */
    public function setWarnings($warnings)
    {
        $this->warnings = $warnings;
    }
}
