{**
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
 *}
<div class="col-lg-4">
    <div class="form-row row">
        <label class="control-label col-lg-12" for="">
            {l s='Shipment date' mod='dpdbaltics'}
        </label>
        <div class="input-group col-lg-12">
            <input type="text"
                   name="date_shipment"
                   class="js-dpd-datepicker"
                   title="{l s='Shipment date' mod='dpdbaltics'}"
                   value="{$shipment->date_shipment}"
            >
            <div class="input-group-addon">
                <i class="icon-calendar-o"></i>
            </div>
        </div>
    </div>
</div>

