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
<input name="label_position" class="label-position-input hidden d-none" value="{$selectedOptionId|escape:'html':'UTF-8':false}">
<div class="btn-group">
    {foreach $dpdSelectOptions as $option}
        {if $option.value == $selectedOptionId}
            <a class="btn dropdown-toggle js-printout-position-select printout-position-select fixed-width-xxl"
               data-selected-id="{$option.value|escape:'html':'UTF-8':false}"
               data-toggle="dropdown"
               href="#">
                <img src="{$option.image|escape:'html':'UTF-8':false}">
                {$option.name|escape:'html':'UTF-8':false}
                <span class="caret"></span>
            </a>
        {/if}
    {/foreach}
    <ul class="dropdown-menu">
        {foreach $dpdSelectOptions as $option}
            <li><a data-select-id="{$option.value|escape:'html':'UTF-8':false}" href="javascript:void(0);">
                    <img src="{$option.image|escape:'html':'UTF-8':false}"/> {$option.name|escape:'html':'UTF-8':false}</a>
            </li>
        {/foreach}
    </ul>
</div>
