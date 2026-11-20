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

<div class="well clearfix recipient-address-container dpd-static-form">
    <div class="alert alert-info js-receiver-address-disabled-info {if !isset($addressChangeable) || $addressChangeable} hidden d-none{/if}" role="alert">
        {l s='Receiver address cannot be changed because at least one label is already printed.' mod='dpdbaltics'}
    </div>

    <div class="card-text">
        <div class="alert alert-info js-edit-receiver-address-info hidden" role="alert">{l s='Here You can edit receiver address ONLY for this order, customer addresses will not be changed.' mod='dpdbaltics'}</div>
        <div class="form-group row type-text dpd-form-group">
            <label class="control-label form-control-label form-control-label col-4 col-sm-4 col-lg-3">{l s='Recipient address:' mod='dpdbaltics'}</label>
            <div class="col-lg-4">
                <select
                        class="js-recipient-address-select js-dpd-rec-input form-control"
                        name="addressId"
                        title="{l s='Recipient address' mod='dpdbaltics'}"
                        {if isset($addressChangeable) && !$addressChangeable}disabled="disabled"{/if}
                >

                    {foreach $combinedAddresses as $address}
                        <option value="{$address.id_address|intval}"
                                {if $orderDetails.order_address.id == $address.id_address}selected{/if}>{$address.alias|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <hr>

        {if isset($orderDetails.order_address.company)}
            <div class="form-group dpd-form-group row type-text">
                <label class="control-label form-control-label form-control-label col-4 col-sm-4 col-lg-3">{l s='Company name:' mod='dpdbaltics'}</label>
                <div class="col-lg-4 col-4">
                    <div class="js-dpd-recipient-detail-input hidden d-none">
                        <input name="company" class="js-dpd-rec-input form-control" type="text"
                               value="{$orderDetails.order_address.company|escape:'htmlall':'UTF-8'}">
                    </div>

                    <div class="dpd-recipient-detail js-dpd-recipient-detail">
                        {$orderDetails.order_address.company|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
            </div>
        {/if}

        <div class="form-group dpd-form-group row">
            <label class="control-label form-control-label form-control-label col-4 col-sm-4 col-lg-3">{l s='Name and surname:' mod='dpdbaltics'}</label>
            <div class="col-lg-4 col-4">
                <div class="js-dpd-recipient-detail-input hidden d-none">
                    <label class="control-label form-control-label required" for="receiver-name">
                        {l s='Name' mod='dpdbaltics'}
                    </label>

                    <input name="name" id="receiver-name" class="form-control js-dpd-rec-input dpd-required-input" type="text"
                            {if !empty($orderDetails.order_address.firstname)}
                                value="{$orderDetails.order_address.firstname|escape:'htmlall':'UTF-8'}"
                            {/if}
                    >

                    <label class="control-label form-control-label required" for="receiver-surname">{l s='Surname' mod='dpdbaltics'}</label>

                    <input name="surname" id="receiver-surname" class="js-dpd-rec-input form-control dpd-required-input" type="text"
                            {if !empty($orderDetails.order_address.lastname)}
                                value="{$orderDetails.order_address.lastname|escape:'htmlall':'UTF-8'}"
                            {/if}
                    >
                </div>

                <div class="dpd-recipient-detail js-dpd-recipient-detail">
                    {if $orderDetails.order_address.firstname && $orderDetails.order_address.lastname}
                        {$orderDetails.order_address.firstname|escape:'htmlall':'UTF-8'}
                        {$orderDetails.order_address.lastname|escape:'htmlall':'UTF-8'}
                    {else}
                        {$orderDetails.customer.firstname|escape:'htmlall':'UTF-8'}
                        {$orderDetails.customer.lastname|escape:'htmlall':'UTF-8'}
                    {/if}
                </div>
            </div>
        </div>

        <div class="form-group dpd-form-group row">
            <label class="control-label form-control-label col-4 col-sm-4 col-lg-3">
                {l s='Street and house no.' mod='dpdbaltics'}:
            </label>
            <div class="col-lg-4 col-4">
                <div class="js-dpd-recipient-detail-input hidden d-none">
                    <label class="control-label form-control-label required" for="receiver-address1">
                        {l s='Address 1' mod='dpdbaltics'}
                    </label>

                    <input name="address1" id="receiver-address1" class=" form-control js-dpd-rec-input dpd-required-input"
                           type="text"
                            {if !empty($orderDetails.order_address.address1)}
                                value="{$orderDetails.order_address.address1|escape:'htmlall':'UTF-8'}"
                            {/if}
                    >

                    <label class="control-label form-control-label" for="receiver-address2">{l s='Address 2' mod='dpdbaltics'}</label>

                    <input name="address2" class="form-control js-dpd-rec-input" id="receiver-address2" type="text"
                            {if !empty($orderDetails.order_address.address2)}
                                value="{$orderDetails.order_address.address2|escape:'htmlall':'UTF-8'}"
                            {/if}
                    >
                </div>

                <div class="dpd-recipient-detail js-dpd-recipient-detail">
                    {$orderDetails.order_address.address1|escape:'htmlall':'UTF-8'}
                    {if $orderDetails.order_address.address2}
                        {$orderDetails.order_address.address2|escape:'htmlall':'UTF-8'}
                    {/if}
                </div>
            </div>
        </div>

        {if isset($orderDetails.order_address.postcode)}
            <div class="form-group dpd-form-group row">
                <label class="control-label form-control-label col-4 col-sm-4 col-lg-3 required">{l s='Post code:' mod='dpdbaltics'}</label>
                <div class="col-lg-4 col-4">
                    <div class="js-dpd-recipient-detail-input hidden d-none">
                        <input name="postcode" class="form-control js-dpd-rec-input dpd-required-input" type="text"
                               value="{$orderDetails.order_address.postcode|escape:'htmlall':'UTF-8'}">
                    </div>

                    <div class="dpd-recipient-detail js-dpd-recipient-detail">
                        {$orderDetails.order_address.postcode|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
            </div>
        {/if}

        {if isset($orderDetails.order_address.city)}
            <div class="form-group dpd-form-group row">
                <label class="control-label form-control-label col-4 col-sm-4 col-lg-3 required">{l s='City:' mod='dpdbaltics'}</label>
                <div class="col-lg-4 col-4">
                    <div class="js-dpd-recipient-detail-input hidden d-none">
                        <input name="city" class="form-control js-dpd-rec-input dpd-required-input" type="text"
                               value="{$orderDetails.order_address.city|escape:'htmlall':'UTF-8'}">
                    </div>

                    <div class="dpd-recipient-detail js-dpd-recipient-detail">
                        {$orderDetails.order_address.city|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
            </div>
        {/if}

        {if isset($orderDetails.order_address.country)}
            <div class="form-group choice-type dpd-form-group row">
                <label class="control-label form-control-label col-4 col-sm-4 col-lg-3 required">{l s='Country:' mod='dpdbaltics'}</label>
                <div class="col-lg-4 col-4">
                    <div class="js-dpd-recipient-detail-input hidden d-none">
                        <select id="dpd-id-country" name="country" class="form-control js-dpd-rec-input dpd-required-input">
                            {foreach $receiverAddressCountries as $country}
                                <option value="{$country.id_country|escape:'htmlall':'UTF-8'}"
                                        {if $orderDetails.order_address.id_country == $country.id_country}selected{/if}
                                >
                                    {$country.name|escape:'htmlall':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="dpd-recipient-detail js-dpd-recipient-detail">
                        {$orderDetails.order_address.country|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
            </div>
            <div class="form-group dpd-form-group row" id="dpd-contains_states"
                 {if !isset($stateName) || !$stateName}style="display:none;{/if}">
                <label class="control-label form-control-label col-4 col-sm-4 col-lg-3">
                    {l s='State:' mod='dpdbaltics'}
                </label>

                <div class="col-lg-4 col-4">
                    <div class="js-dpd-recipient-detail-input hidden d-none">
                        <select name="state" id="dpd-id-state" class="js-dpd-rec-input">
                        </select>
                    </div>
                    <input class="dpd-selected-state hidden d-none" value="{$orderDetails.order_address.id_state|escape:'html':'UTF-8':false}">
                </div>

                <div class="dpd-recipient-detail js-dpd-recipient-detail">
                    {if isset($stateName) && $stateName} {$stateName|escape:'htmlall':'UTF-8'} {/if}
                </div>
            </div>
        {/if}

        {if isset($orderDetails.customer.email)}
            <div class="form-group dpd-form-group row">
                <label class="control-label form-control-label col-4 col-sm-4 col-lg-3 required">{l s='Email:' mod='dpdbaltics'}</label>

                <div class="col-lg-4 col-4 dpd-recipient-address-email">
                    <div class="js-dpd-recipient-detail-input hidden d-none">
                        <input name="email" class=" form-control js-dpd-rec-input dpd-required-input" type="text"
                               value="{$orderDetails.customer.email|escape:'htmlall':'UTF-8'}">
                    </div>

                    <div class="dpd-recipient-detail js-dpd-recipient-detail">
                        {$orderDetails.customer.email|escape:'htmlall':'UTF-8'}
                    </div>
                </div>
            </div>
        {/if}

        <div class="form-group dpd-form-group row">
            <label class="control-label form-control-label col-4 col-sm-4 col-lg-3 required">
                {l s='Phone area:' mod='dpdbaltics'}
            </label>
            <div class="col-lg-4 col-4 dpd-recipient-address-phone-area">
                <div class="js-dpd-recipient-detail-input hidden d-none">
                    <select
                            class="form-control js-dpd-rec-input dpd-required-input"
                            name="phoneArea">
                            {if isset($orderDetails.dpd_order_phone.phone_area)}
                                {html_options options=$mobilePhoneCodeList selected=$orderDetails.dpd_order_phone.phone_area}
                            {else}
                                {html_options options=$mobilePhoneCodeList}
                            {/if}
                    </select>
                </div>
                <div class="dpd-recipient-detail js-dpd-recipient-detail">
                    {if isset($orderDetails.dpd_order_phone.phone_area)}
                        {$orderDetails.dpd_order_phone.phone_area|escape:'htmlall':'UTF-8'}
                    {/if}
                </div>
            </div>
        </div>

        <div class="form-group dpd-form-group row">
            <label class="control-label form-control-label col-4 col-sm-4 col-lg-3 required">
                {l s='Phone:' mod='dpdbaltics'}
            </label>

            <div class="col-lg-4 col-4 dpd-recipient-address-phone">
                <div class="js-dpd-recipient-detail-input hidden d-none">
                    <input
                            name="phone"
                            class="js-dpd-rec-input form-control dpd-required-input"
                            type="text"
                            {if isset($orderDetails.dpd_order_phone.phone)}
                                value="{$orderDetails.dpd_order_phone.phone|escape:'htmlall':'UTF-8'}"
                            {/if}
                    >
                </div>

                <div class="dpd-recipient-detail js-dpd-recipient-detail">
                    {if isset($orderDetails.dpd_order_phone.phone)}
                        {$orderDetails.dpd_order_phone.phone|escape:'htmlall':'UTF-8'}
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <button
            type="button"
            class="btn btn-default pull-right btn-secondary float-right js-dpd-recipient-detail-edit"
            {if isset($addressChangeable) && !$addressChangeable}disabled="disabled"{/if}
    >
        {l s='Edit' mod='dpdbaltics'}
    </button>

    <button
            type="button"
            class="hidden d-none btn btn-default button-secondary float-right pull-right js-dpd-recipient-detail-save"
            {if isset($addressChangeable) && !$addressChangeable}disabled="disabled"{/if}
    >
        {l s='Save' mod='dpdbaltics'}
    </button>
</div>