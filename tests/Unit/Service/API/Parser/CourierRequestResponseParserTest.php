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

use Invertus\dpdBaltics\Service\API\Parser\CourierRequestResponseParser;
use PHPUnit\Framework\TestCase;

class CourierRequestResponseParserTest extends TestCase
{
    /**
     * @var CourierRequestResponseParser
     */
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new CourierRequestResponseParser();
    }

    public function testLegacyDoneStringIsSuccess()
    {
        $this->assertTrue($this->parser->isSuccess('<p>DONE'));
    }

    public function testLegacyDoneHtmlDocumentIsSuccess()
    {
        $html = "<html>\n<head><title/></head>\n<body>\n<p>DONE</p>\n</body>\n</html>";

        $this->assertTrue($this->parser->isSuccess($html));
    }

    public function testLegacyErrorStringIsNotSuccessAndErrorIsExtracted()
    {
        $response = 'HTTP 402 Error: Pickup order to time wrong for Product 1';

        $this->assertFalse($this->parser->isSuccess($response));
        $this->assertSame('402 Error: Pickup order to time wrong for Product 1', $this->parser->getError($response));
    }

    public function testLegacyStringWithoutErrorMarkerFallsBackToWholeString()
    {
        $response = 'Some unexpected plain-text failure';

        $this->assertFalse($this->parser->isSuccess($response));
        $this->assertSame($response, $this->parser->getError($response));
    }

    public function testJsonErrorObjectIsNotSuccessAndMessageIsExtracted()
    {
        $message = 'Available "pickupTimeFrom" values: [08:00, 09:00, 10:00, 11:00, 12:00, 12:30, 13:00, '
            . '13:30, 14:00, 14:30, 15:00]. Available "pickupTimeTo" values: [15:00, 16:00, 17:00, 18:00].';
        $response = json_decode(json_encode([
            'level' => 'error',
            'body' => ['message' => $message],
        ]));

        $this->assertFalse($this->parser->isSuccess($response));
        $this->assertSame($message, $this->parser->getError($response));
    }

    public function testJsonErrlogObjectIsNotSuccessAndErrlogIsExtracted()
    {
        $response = json_decode(json_encode([
            'status' => 'err',
            'errlog' => 'Failed to Authenticate User. Check your user name and password!',
        ]));

        $this->assertFalse($this->parser->isSuccess($response));
        $this->assertSame(
            'Failed to Authenticate User. Check your user name and password!',
            $this->parser->getError($response)
        );
    }

    public function testUnknownObjectShapeIsNotSuccessAndIsSurfacedAsJson()
    {
        $response = json_decode(json_encode(['foo' => 'bar']));

        $this->assertFalse($this->parser->isSuccess($response));
        $this->assertSame('{"foo":"bar"}', $this->parser->getError($response));
    }

    public function testEmptyArrayResponseIsNotSuccessAndDoesNotCrash()
    {
        $this->assertFalse($this->parser->isSuccess([]));
        $this->assertSame('[]', $this->parser->getError([]));
    }
}
