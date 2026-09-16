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


namespace Invertus\dpdBaltics\Service\API\Parser;

use Invertus\dpdBaltics\Config\Config;

class CourierRequestResponseParser
{
    /**
     * @param mixed $response
     *
     * @return bool
     */
    public function isSuccess($response)
    {
        if (is_string($response)) {
            return strpos($response, Config::API_COURIER_REQUEST_SUCCESS_STATUS) !== false;
        }

        return false;
    }

    /**
     * @param mixed $response
     *
     * @return string
     */
    public function getError($response)
    {
        if (is_string($response)) {
            $errorPosition = strpos($response, Config::API_COURIER_REQUEST_ERROR_STATUS);

            if ($errorPosition === false) {
                return $response;
            }

            return substr($response, $errorPosition);
        }

        if (is_object($response)) {
            if (isset($response->body->message)) {
                return (string) $response->body->message;
            }

            if (isset($response->errlog)) {
                return (string) $response->errlog;
            }
        }

        return json_encode($response);
    }
}
