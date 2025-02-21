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

declare(strict_types=1);

namespace Invertus\dpdBaltics\Service\Label;

use Invertus\dpdBaltics\Adapter\LinkAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LabelUrlFormatter
{
    private $linkAdapter;

    public function __construct(LinkAdapter $linkAdapter)
    {
        $this->linkAdapter = $linkAdapter;
    }

    public function formatJsLabelPrintUrl()
    {
        return $this->linkAdapter->getUrlSmarty([
            'entity' => 'sf',
            'route' => 'dpdbaltics_print_label_order_view',
            'sf-params' => [
                'shipmentId' => 'shipmentId_',
                'labelFormat' => 'labelFormat_',
                'labelPosition' => 'labelPosition_'
            ]
        ]);
    }

    /**
     * Format URL for saving and printing label dynamically
     *
     * @note Additional logic with is used to be compatible with PrestaShop 9
     *
     *
     * @return string
     */
    public function formatJsLabelSaveAndPrintUrl()
    {
        $url = $this->linkAdapter->getUrlSmarty([
            'entity' => 'sf',
            'route' => 'dpdbaltics_save_and_download_printed_label_order_view',
            'sf-params' => [
                'orderId' => 'orderId_',
            ]
        ]);

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            return $url;
        }

        return rtrim(\Context::getContext()->shop->getBaseURL(), '/') . $url;
    }
}
