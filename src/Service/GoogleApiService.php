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

namespace Invertus\dpdBaltics\Service;

use Address;
use Configuration;
use Country;
use Invertus\dpdBaltics\Config\Config;
use Language;
use Shop;
use State;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GoogleApiService
{
    public $geolocationApi;

    public $isSslEnabled;

    /** indicates result type retrieved from reverse giolocation
     * @var array
     */
    public $resultTypes = [
        'locality',
        'country',
    ];
    /**
     * @var Language
     */
    private $language;
    /**
     * @var Shop
     */
    private $shop;

    public function __construct(Language $language, Shop $shop)
    {
        $apiKey = Configuration::get(Config::GOOGLE_API_KEY);
        $this->isSslEnabled =
            (Configuration::get('PS_SSL_ENABLED')) && Configuration::get('PS_SSL_ENABLED_EVERYWHERE');
        $this->geolocationApi = $this->getGeolocationUrl($apiKey);
        $this->language = $language;
        $this->shop = $shop;
    }

    public function getFormattedGoogleMapsUrl()
    {
        $default_country = new Country((int)Tools::getCountry());
        $url = 'http';
        if ((Configuration::get('PS_SSL_ENABLED')) && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) {
            $url .='s';
        }
        $url .= '://maps.google.com/maps/api/js?';
        $url .= 'region='.Tools::substr($default_country->iso_code, 0, 2);
        if (Configuration::get(Config::GOOGLE_API_KEY)) {
            $url .= '&key='.Configuration::get(Config::GOOGLE_API_KEY);
        }
        return $url;
    }

    public function getDefaultCoordinates($idAddress)
    {
        $address = new Address($idAddress, $this->language->id);
        return $this->getCoordinatesByAddress(
            $address->address1,
            $address->address2,
            $address->city,
            $address->postcode,
            $address->country,
            $address->id_state
        );
    }

    public function getCoordinatesByAddress($address1, $address2, $city, $postCode, $countryName, $id_state = 0)
    {
        if (!Configuration::get(Config::GOOGLE_API_KEY)) {
            return false;
        }

        $requestData = [];
        if ($address1) {
            $requestData[] = $address1;
        }

        if ($address2) {
            $requestData[] = $address2;
        }

        if ($city) {
            $requestData[] = $city;
        }

        if ($id_state) {
            $state = new State($id_state, $this->language->id, $this->shop->id);
            if (Validate::isLoadedObject($state)) {
                $requestData[] = $state->name;
            }
        }

        if ($postCode) {
            $requestData[] = $postCode;
        }

        if ($countryName) {
            $requestData[] = $countryName;
        }
        $requestStringified = str_replace(' ', '+', implode(',', $requestData));
        return $this->getResultFromGoogleApiService($requestStringified);
    }

    public function getCoordinatesByPostCode($postCode, $countryName)
    {
        if (!Configuration::get(Config::GOOGLE_API_KEY)) {
            return false;
        }

        $requestData = [];

        if ($countryName) {
            $requestData[] = $countryName;
        }

        if ($postCode) {
            $requestData[] = $postCode;
        }

        $requestStringified = str_replace(' ', '+', implode(',', $requestData));

        return $this->getResultFromGoogleApiService($requestStringified);
    }

    public function getAddressByCoordinates($lat, $lng)
    {
        $apiKey = Configuration::get(Config::GOOGLE_API_KEY);
        if (!$apiKey) {
            return false;
        }
        
        if (!$this->isSslEnabled) {
            return false;
        }

        $reverseGeolocationUrl = $this->getReverseGeolocationUrl($apiKey);
        $content = Tools::file_get_contents($reverseGeolocationUrl.$lat.','.$lng);
        $dataArray = Tools::jsonDecode($content);

        if (!isset($dataArray->results) || empty($dataArray->results)) {
            return false;
        }

        // country code
        if (!isset($dataArray->results[1]->address_components[0]->short_name)) {
            return false;
        }
        // city name
        if (!isset($dataArray->results[0]->address_components[0]->long_name)) {
            return false;
        }

        $shortCountryCode = $dataArray->results[1]->address_components[0]->short_name;
        $cityName = $dataArray->results[0]->address_components[0]->long_name;
        return [
            'iso_code' => $shortCountryCode,
            'city' => $cityName
        ];
    }

    public function getResultFromGoogleApiService($requestStringified)
    {
        $response = Tools::jsonDecode(Tools::file_get_contents($this->getFormattedAddress($requestStringified)), true);
        if (!isset($response['results'][0]['geometry']['location'])) {
            return false;
        }
        return $response['results'][0]['geometry']['location'];
    }

    private function getGeolocationUrl($apiKey)
    {
        return 'https://maps.googleapis.com/maps/api/geocode/json?key='.
            $apiKey.'&sensor=false&address=';
    }


    private function getReverseGeolocationUrl($apiKey)
    {
        return 'https://maps.googleapis.com/maps/api/geocode/json?key='.
            $apiKey.'&result_type='.implode('|', $this->resultTypes).'&language=en&latlng=';
    }

    private function getFormattedAddress($address)
    {
        $apiKey = Configuration::get(Config::GOOGLE_API_KEY);
        return $this->getGeolocationUrl($apiKey).$address;
    }
}
