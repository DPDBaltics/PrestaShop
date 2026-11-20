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
<form id="order-return-form" action="{$href|escape:'html':'UTF-8':false}" method="post">
    <div class="box">
        <h3>
            {l s='DPD Baltics' d='dpdbaltics'}
        </h3>
        <p>
            {l s='If you wish to return one or more products, please click the button below to download label.' d='dpdbaltics'}
        </p>
        <footer class="form-footer">
            <div class="row">
                <div class="pl-1">
                    <select
                            name="return_template_id"
                            {if !$show_template}style="display: none"{/if}
                            class="selectpicker col-lg-3"
                            data-style="btn-primary"
                    >
                        {foreach $return_template_ids as $key => $return_template_id}
                            <option value="{$key|escape:'html':'UTF-8':false}">
                                {$return_template_id|escape:'html':'UTF-8':false}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="clearfix">&nbsp;</div>
                <div class="pl-1">
                    <button
                            type="submit"
                            class="btn btn-primary col-lg-3"
                    >
                        {l s='Request DPD return' d='dpdbaltics'}
                    </button>
                </div>
            </div>
        </footer>
    </div>
</form>
