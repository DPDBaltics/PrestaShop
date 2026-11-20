<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */


namespace Invertus\dpdBaltics\Service\Import\API;

use DPDBaltics;
use EntityAddException;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Service\API\ParcelShopSearchApiService;
use Invertus\dpdBaltics\Service\Parcel\ParcelUpdateService;
use Invertus\dpdBalticsApi\Api\DTO\Response\ParcelShopSearchResponse;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ParcelShopImport
{
    const FILE_NAME = 'ParcelShopImport';

    /**
     * @var ParcelShopSearchApiService
     */
    private $apiService;

    /**
     * @var ParcelUpdateService
     */
    private $parcelUpdateService;

    /**
     * @var DPDBaltics
     */
    private $module;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ParcelShopSearchApiService $apiService,
        ParcelUpdateService $parcelUpdateService,
        DPDBaltics $module,
        LoggerInterface $logger
    ) {
        $this->apiService = $apiService;
        $this->parcelUpdateService = $parcelUpdateService;
        $this->module = $module;
        $this->logger = $logger;
    }

    /**
     * Import parcel shops for a country.
     *
     * @param string $selectedCountry Country ISO code
     * @return array
     */
    public function importParcelShops($selectedCountry)
    {
        $startTime = microtime(true);

        $retrieveOpeningHours = $this->shouldRetrieveOpeningHours($selectedCountry);

        /** @var ParcelShopSearchResponse $shops */
        $shops = $this->apiService->getAllCountryParcels(
            $selectedCountry,
            Config::FETCH_PUDO_POINT,
            $retrieveOpeningHours
        );

        $apiTime = round(microtime(true) - $startTime, 2);

        if ($shops->getStatus() === Config::API_RESPONSE_ERROR_STATUS) {
            $this->logger->error(sprintf(
                '[ParcelImport] API ERROR for %s | Error: %s | API took: %ss',
                $selectedCountry,
                $shops->getErrLog(),
                $apiTime
            ));

            return [
                'success' => false,
                'error' => sprintf($this->module->l('Failed to update parcel shops: %s', self::FILE_NAME), $shops->getErrLog())
            ];
        }

        $parcelShops = $shops->getParcelShops();

        if ($parcelShops === null || !is_array($parcelShops)) {
            $this->logger->error(sprintf(
                '[ParcelImport] API returned NO DATA for %s | API took: %ss',
                $selectedCountry,
                $apiTime
            ));

            return [
                'success' => false,
                'error' => sprintf($this->module->l('Failed to update parcel shops: API returned no data for country %s', self::FILE_NAME), $selectedCountry)
            ];
        }

        $parcelCount = count($parcelShops);

        if ($parcelCount === 0) {
            $this->logger->warning(sprintf(
                '[ParcelImport] API returned 0 parcel shops for %s | API took: %ss',
                $selectedCountry,
                $apiTime
            ));

            return [
                'success' => false,
                'error' => sprintf(
                    $this->module->l('DPD returned 0 pickup points for %s. Existing pickup points were kept. The web service account may not be enabled for pickup point retrieval - please contact DPD.', self::FILE_NAME),
                    $selectedCountry
                )
            ];
        }

        $dbStartTime = microtime(true);

        try {
            $this->parcelUpdateService->updateParcels($parcelShops, $selectedCountry);
        } catch (EntityAddException $e) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $dbTime = round(microtime(true) - $dbStartTime, 2);

            $this->logger->error(sprintf(
                '[ParcelImport] DATABASE ERROR for %s | Error: %s | DB time: %ss | Total time: %ss',
                $selectedCountry,
                $e->getMessage(),
                $dbTime,
                $totalTime
            ));

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $dbTime = round(microtime(true) - $dbStartTime, 2);

            $this->logger->error(sprintf(
                '[ParcelImport] EXCEPTION for %s | Type: %s | Error: %s | DB time: %ss | Total time: %ss',
                $selectedCountry,
                get_class($e),
                $e->getMessage(),
                $dbTime,
                $totalTime
            ));

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\Error $e) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $dbTime = round(microtime(true) - $dbStartTime, 2);

            $this->logger->error(sprintf(
                '[ParcelImport] PHP ERROR for %s | Type: %s | Error: %s | File: %s:%d | DB time: %ss | Total time: %ss',
                $selectedCountry,
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $dbTime,
                $totalTime
            ));

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        $totalTime = round(microtime(true) - $startTime, 2);

        return [
            'success' => true,
            'success_message' => sprintf(
                $this->module->l('Successfully imported %d parcel shops in %ss', self::FILE_NAME),
                $parcelCount,
                $totalTime
            )
        ];
    }

    /**
     * Check if opening hours should be retrieved for this country.
     * Large countries may timeout when retrieving opening hours due to API limits.
     *
     * @param string $countryIso
     * @return int
     */
    private function shouldRetrieveOpeningHours($countryIso)
    {
        $countryIso = strtoupper($countryIso);

        if (in_array($countryIso, Config::COUNTRIES_SKIP_OPENING_HOURS, true)) {
            return Config::SKIP_OPENING_HOURS;
        }

        return Config::RETRIEVE_OPENING_HOURS;
    }
}
