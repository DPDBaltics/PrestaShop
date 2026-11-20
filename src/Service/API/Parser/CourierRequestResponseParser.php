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

        // The live API answers in JSON, which Unirest hands over as an object.
        // Only its failures carry a marker, and a success has no field we can
        // match on, so an object without an error marker means DPD accepted it.
        if (is_object($response)) {
            return !$this->isErrorObject($response);
        }

        return false;
    }

    /**
     * @param object $response
     *
     * @return bool
     */
    private function isErrorObject($response)
    {
        if ($this->fieldEquals($response, 'level', Config::API_COURIER_REQUEST_JSON_ERROR_LEVEL)) {
            return true;
        }

        if ($this->fieldEquals($response, 'status', Config::API_RESPONSE_ERROR_STATUS)) {
            return true;
        }

        return isset($response->errlog) && is_scalar($response->errlog) && '' !== (string) $response->errlog;
    }

    /**
     * Compares a response field without casting. DPD's answer is untrusted, so an
     * array or object in a field we expect to be text must not reach a string cast.
     *
     * @param object $response
     * @param string $field
     * @param string $expected
     *
     * @return bool
     */
    private function fieldEquals($response, $field, $expected)
    {
        if (!isset($response->$field) || !is_scalar($response->$field)) {
            return false;
        }

        return strtolower((string) $response->$field) === $expected;
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
