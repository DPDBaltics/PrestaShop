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

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Infrastructure\Bootstrap\ModuleTabs;
use Invertus\dpdBaltics\Service\API\ParcelShopSearchApiService;
use Invertus\dpdBaltics\Service\Import\API\ParcelShopImport;
use Invertus\dpdBaltics\Service\Import\ImportMainZone;
use Invertus\dpdBaltics\Service\Parcel\ParcelUpdateService;
use Invertus\dpdBaltics\Service\PudoService;
use Invertus\dpdBaltics\Util\StringUtility;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelShopSearchResponse;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsAjaxController extends AbstractAdminController
{
    public function postProcess()
    {
        if ($this->ajax && !$this->isTabGranted('edit', ModuleTabs::ADMIN_IMPORT_EXPORT_CONTROLLER)) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'error' => $this->getPermissionDeniedMessage('edit'),
            ]));
        }

        return parent::postProcess();
    }

    public function ajaxProcessImportZones()
    {
        /** @var ImportMainZone $importOnLoginService */
        $importOnLoginService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.import.import_main_zone');

        $selectedCountry = Tools::getValue('country');
        switch ($selectedCountry) {
            case 'latvia' :
                $this->ajaxDie(json_encode($importOnLoginService->importLatviaZones()));
                break;
            case 'lithuania':
                $this->ajaxDie(json_encode($importOnLoginService->importLithuaniaZones()));
                break;
            default:
                $this->ajaxDie();
                break;
        }
    }

    public function ajaxProcessImportParcels()
    {
        $countryId = Tools::getValue('countryId');
        $countryIso = Country::getIsoById($countryId);

        // Validate country
        if (empty($countryIso)) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'error' => $this->module->l('Invalid country selected', 'AdminDPDBalticsAjaxController')
            ]));
            return;
        }

        $countryIso = strtoupper($countryIso);

        /** @var ParcelShopImport $parcelShopImport */
        $parcelShopImport = $this->module->getModuleContainer('invertus.dpdbaltics.service.import.api.parcel_shop_import');

        try {
            $result = $parcelShopImport->importParcelShops($countryIso);
            if (isset($result['error'])) {
                $result['error'] = StringUtility::escapeHtml($result['error']);
            }
            $this->ajaxDie(json_encode($result));
        } catch (\Exception $e) {
            $this->handleImportError($e, $countryIso);
        } catch (\Error $e) {
            $this->handleImportError($e, $countryIso);
        }
    }

    /**
     * Handle import errors with helpful messages for timeout scenarios.
     *
     * @param \Exception|\Error $e
     * @param string $countryIso
     */
    private function handleImportError($e, $countryIso)
    {
        $errorMsg = $e->getMessage();
        $isTimeout = stripos($errorMsg, 'timeout') !== false
            || stripos($errorMsg, 'execution time') !== false
            || stripos($errorMsg, 'Maximum execution') !== false;

        if ($isTimeout) {
            $this->ajaxDie(json_encode($this->buildCronRequiredResponse($countryIso)));
        } else {
            $this->ajaxDie(json_encode([
                'success' => false,
                'error' => StringUtility::escapeHtml($e instanceof \Error
                    ? 'PHP Error: ' . $errorMsg
                    : 'Error: ' . $errorMsg)
            ]));
        }
    }

    /**
     * Build response for when cron is required (timeout or large country).
     *
     * @param string $countryIso
     * @return array
     */
    private function buildCronRequiredResponse($countryIso)
    {
        return [
            'success' => false,
            'error' => $this->module->l('This country requires automatic updates.', 'AdminDPDBalticsAjaxController'),
            'requires_cron' => true,
            'cron_command' => 'php bin/console dpdbaltics:update-parcel-shops --country=' . $countryIso
        ];
    }

}
