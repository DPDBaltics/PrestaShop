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

namespace Invertus\dpdBaltics\Validate\WebService;

use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Service\API\WebServiceAuthApiService;
use Invertus\dpdBalticsApi\ApiConfig\ApiConfig;

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebServiceCredentialsValidator
{
    const VALID = 'valid';
    const WRONG_PASSWORD = 'wrong_password';
    const UNKNOWN_USER = 'unknown_user';
    const REJECTED = 'rejected';
    const UNVERIFIED = 'unverified';

    const WRONG_PASSWORD_ERROR = 'Failed to Authenticate User';
    const UNKNOWN_USER_ERROR = 'Unable to find user to authenticate';

    /**
     * @var WebServiceAuthApiService
     */
    private $authApiService;

    public function __construct(WebServiceAuthApiService $authApiService)
    {
        $this->authApiService = $authApiService;
    }

    /**
     * Only a clear authentication failure is reported as invalid. Network errors and
     * unexpected responses are UNVERIFIED, so a DPD outage never blocks the settings save.
     *
     * @param string $username
     * @param string $password
     * @param string $countryIso
     * @param bool $testMode
     *
     * @return string
     */
    public function validate($username, $password, $countryIso, $testMode)
    {
        try {
            $response = $this->authApiService->checkCredentials(
                $this->getUrl($countryIso, $testMode),
                $username,
                $password,
                $countryIso
            );
        } catch (\Throwable $e) {
            return self::UNVERIFIED;
        }

        if ((int) $response->code === 401) {
            return self::REJECTED;
        }

        $body = $response->body;

        if ((int) $response->code !== 200 || !is_object($body) || !isset($body->status)) {
            return self::UNVERIFIED;
        }

        if ($body->status === Config::API_SUCCESS_STATUS) {
            return self::VALID;
        }

        $error = isset($body->errlog) ? (string) $body->errlog : '';

        if (stripos($error, self::WRONG_PASSWORD_ERROR) !== false) {
            return self::WRONG_PASSWORD;
        }

        if (stripos($error, self::UNKNOWN_USER_ERROR) !== false) {
            return self::UNKNOWN_USER;
        }

        return self::UNVERIFIED;
    }

    /**
     * Mirrors ApiConfiguration::getUrl() for values that are not saved yet.
     */
    private function getUrl($countryIso, $testMode)
    {
        $apiUrls = $testMode ? ApiConfig::TEST_URLS : ApiConfig::LIVE_URLS;
        $countryIso = strtoupper((string) $countryIso);

        if (!isset($apiUrls[$countryIso])) {
            $countryIso = Config::LATVIA_ISO_CODE;
        }

        return $apiUrls[$countryIso];
    }
}
