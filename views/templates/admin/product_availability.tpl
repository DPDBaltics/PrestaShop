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

<div id="productAvailabilityBlock">
    <input type="hidden" name="product_id" value="{$productId|escape:'html':'UTF-8':false}">
    <div class="row">
        <div class="col-md-8">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="text-center">{l s='Day' mod='dpdbaltics'}</th>
                        <th class="text-center zone-range-table-column">{l s='From (inclusive)' mod='dpdbaltics'}</th>
                        <th class="text-center zone-range-table-column">{l s='To (inclusive)' mod='dpdbaltics'}</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="zoneRangeForm">
                    <tr class="js-product-availability-range-block active">
                        <td class="text-center">
                            <select title="{l s='Day' mod='dpdbaltics'}" class="js-range-input js-day-select">
                                {foreach $daysList as $day}
                                    <option value="{$day|escape:'html':'UTF-8':false}">
                                        {$day|escape:'html':'UTF-8':false}
                                    </option>
                                {/foreach}
                            </select>
                        </td>
                        <td class="text-center">
                            <input class="js-range-input js-product-availability-from-input form-control"
                                   title="{l s='From' mod='dpdbaltics'}"
                                   type="time"
                            >
                        </td>
                        <td class="text-center">
                            <input class="js-range-input js-product-availability-to-input form-control"
                                   title="{l s='To' mod='dpdbaltics'}"
                                   type="time"
                            >
                        </td>
                        <td class="text-center">
                            <button class="btn btn-primary btn-sm js-add-product-availability-btn" type="button">
                                {l s='Add' mod='dpdbaltics'}
                            </button>
                        </td>
                    </tr>
                </tbody>

                <tbody id="productAvailabilityValues">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- HTML templates used in Javascript -->
<div class="hidden" id="dpdAvailabilityRangeTemplates">
    <table>
        <tr class="js-availability-range-block js-availability-range-template">
            <td class="text-center">
                <select title="{l s='Day' mod='dpdbaltics'}" class="js-range-input js-day-select">
                    {foreach $daysList as $day}
                        <option value="{$day|escape:'html':'UTF-8':false}">
                            {$day|escape:'html':'UTF-8':false}
                        </option>
                    {/foreach}
                </select>
            </td>
            <td class="text-center">
                <input class="js-range-input js-product-availability-from-input form-control"
                       title="{l s='From' mod='dpdbaltics'}"
                       type="time"
                >
            </td>

            <td class="text-center">
                <input class="js-range-input js-product-availability-to-input form-control"
                       title="{l s='To' mod='dpdbaltics'}"
                       type="time"
                >
            </td>

            <td class="text-center">
                <button class="btn btn-danger btn-sm js-remove-availability-range-btn" type="button">
                    {l s='Remove' mod='dpdbaltics'}
                </button>

                <input type="hidden" class="js-availability-range-id">
            </td>
        </tr>
    </table>

    <div class="alert alert-danger js-alert-error-template" role="alert">
        <span class="js-message"></span>
    </div>
</div>
