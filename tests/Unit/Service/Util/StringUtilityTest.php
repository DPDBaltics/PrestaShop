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

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', 'test');
}

use Invertus\dpdBaltics\Util\StringUtility;

class StringUtilityTest extends PHPUnit_Framework_TestCase
{
    public function testTrimString(){
        $stringDataMock ="Nam quis nulla. Integer malesuada. In in enim a arcu imperdiet malesuada. Sed vel lectus.";

        $expectation = "Nam quis nulla. Integer malesuada. In in eni...";

        $stringUtility = new StringUtility();
        $trimmedTestString = $stringUtility->trimString($stringDataMock);

        $this->assertSame($trimmedTestString, $expectation);
    }

    public function testRemoveSpecialCharacters() {
        $stringDataMock ="vėlyvųjų viduramžių standartas";
        $stringUtility = new StringUtility();
        $formattedTestString = $stringUtility->removeSpecialCharacters($stringDataMock);
        $expectation = "velyvuju viduramziu standartas";
        $this->assertEquals( $expectation, $formattedTestString);
    }

    /**
     * @dataProvider getEscapeHtmlData
     */
    public function testEscapeHtml($message, $expectedResult)
    {
        $this->assertSame($expectedResult, StringUtility::escapeHtml($message));
    }

    public function getEscapeHtmlData()
    {
        return [
            'markup from DPD is shown as text' => [
                'Zip code value "<img src=x onerror=alert(1)>" is not valid',
                'Zip code value &quot;&lt;img src=x onerror=alert(1)&gt;&quot; is not valid',
            ],
            'message already escaped by Module::l() is not escaped twice' => [
                'Failed to create shipment &quot;&lt;b&gt;1&lt;/b&gt;&quot;',
                'Failed to create shipment &quot;&lt;b&gt;1&lt;/b&gt;&quot;',
            ],
            'encoded markup cannot be decoded back into a tag' => [
                '&lt;script&gt;alert(1)&lt;/script&gt;',
                '&lt;script&gt;alert(1)&lt;/script&gt;',
            ],
            'single quotes are escaped' => [
                "Shipment data field 'Product' is invalid",
                'Shipment data field &#039;Product&#039; is invalid',
            ],
            'plain text is unchanged' => [
                'receiverPhoneNo can not be empty!',
                'receiverPhoneNo can not be empty!',
            ],
            'invalid UTF-8 keeps the message instead of blanking it' => [
                "Adresas \xe0 <b>",
                "Adresas \u{FFFD} &lt;b&gt;",
            ],
            'multibyte text is kept' => [
                'Ąžuolų g. "<b>"',
                'Ąžuolų g. &quot;&lt;b&gt;&quot;',
            ],
            'null becomes an empty string' => [
                null,
                '',
            ],
        ];
    }

}
