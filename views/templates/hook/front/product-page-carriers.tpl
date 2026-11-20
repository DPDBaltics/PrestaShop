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

<div id="product-page-carriers">
    {foreach $carriers as $carrier}
        <div class="product-carrier-container clearfix container">
            <div class="row">
                <div class="col-sm-4 image-container">
                    <img class="product-carrier-image" src="{$carrier.carrier_logo|escape:'html':'UTF-8':false}">
                </div>

                <div class="col-sm-8 price-container">
                    <p class="product-carrier-name">
                        {$carrier.name|escape:'html':'UTF-8':false}
                    </p>
                    <p class="product-carrier-text">
                        {l s='from ' mod='dpdbaltics'}
                        {$carrier.shipping_cost|escape:'html':'UTF-8':false}
                    </p>
                </div>
            </div>
        </div>
    {/foreach}
</div>