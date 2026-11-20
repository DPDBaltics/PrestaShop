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
<div class="dpd-document-return-panel panel card">
    <div class="panel-heading card-header">
        <span class="card-header-title">
            {l s='DOCUMENT RETURN' mod='dpdbaltics'}
        </span>
    </div>

    <div class="row form-row">
        <label class="control-label col-xs-2">
            {l s='Enable document return' mod='dpdbaltics'}
        </label>
        <div class="col-xs-4">
            <span class="switch ps-switch prestashop-switch fixed-width-lg">
                <input type="radio"
                       name="DPD_DOCUMENT_RETURN"
                       id="DPD_DOCUMENT_RETURN_on"
                       class="js-document-return ps-switch"
                       value="1">
                <label for="DPD_DOCUMENT_RETURN_on" class="radioCheck">{l s='Yes' mod='dpdbaltics'}</label>
                <input type="radio" name="DPD_DOCUMENT_RETURN"
                       class="ps-switch"
                       id="DPD_DOCUMENT_RETURN_off" value="0"
                       checked="checked">
                <label
                        for="DPD_DOCUMENT_RETURN_off" class="radioCheck">
                    {l s='No' mod='dpdbaltics'}
                </label>
                <a class="slide-button btn"></a>
            </span>
        </div>
    </div>

    <div class="dpd-separator"></div>

    <div class="row form-row">
        <label class="control-label col-xs-2">
            {l s='Document number' mod='dpdbaltics'}
        </label>
        <div class="col-xs-4">
            <input name="dpd_document_return_number" class="form-control fixed-width-lg" type="text"
                   value="{$shipment->document_return_number|escape:'html':'UTF-8':false}">
        </div>
    </div>
</div>