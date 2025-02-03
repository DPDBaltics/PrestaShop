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


namespace Invertus\dpdBaltics\Repository;

use DbQuery;
use DPDAddressTemplate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AddressRepository extends AbstractEntityRepository
{
    public function getAddressPhonesAndCodes($idAddressTemplate)
    {
        $query = new DbQuery();
        $query->select('mobile_phone, mobile_phone_code');
        $query->from('dpd_address_template');
        $query->where('id_dpd_address_template=' . (int) $idAddressTemplate);

        $result = $this->db->query($query);

        $data = [];

        while ($row = $this->db->nextRow($result)) {
            $data['mobile_phone'] = pSQL($row['mobile_phone']);
            $data['mobile_phone_code'] = pSQL($row['mobile_phone_code']);
        }

        return $data;
    }

    public function findAllByShop()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(DPDAddressTemplate::$definition['table'], 'a');
        $query->where('a.type = "' . DPDAddressTemplate::ADDRESS_TYPE_COLLECTION_REQUEST . '"');

        $addresses = $this->db->executeS($query);
        if (!$addresses) {
            $addresses = [];
        }

        return $addresses;
    }
}
