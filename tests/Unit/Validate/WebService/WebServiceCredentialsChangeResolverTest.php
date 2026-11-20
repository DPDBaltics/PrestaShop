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
    define('_PS_VERSION_', '1.7.8.9');
}

use Invertus\dpdBaltics\Validate\WebService\WebServiceCredentialsChangeResolver;

class WebServiceCredentialsChangeResolverTest extends PHPUnit_Framework_TestCase
{
    const PLACEHOLDER = '******';

    private $stored = [
        'username' => 'testuser1',
        'password' => 'testpassword1',
        'country' => 'LT',
        'test_mode' => true,
    ];

    /**
     * @dataProvider submissionProvider
     */
    public function testResolve(array $submitted, $expected)
    {
        $resolver = new WebServiceCredentialsChangeResolver();

        $this->assertSame($expected, $resolver->resolve($submitted, $this->stored, self::PLACEHOLDER));
    }

    public function submissionProvider()
    {
        return [
            'unchanged with placeholder password' => [
                ['username' => 'testuser1', 'password' => self::PLACEHOLDER, 'country' => 'LT', 'test_mode' => true],
                null,
            ],
            'unchanged with empty password and lowercase country' => [
                ['username' => 'testuser1', 'password' => '', 'country' => 'lt', 'test_mode' => true],
                null,
            ],
            'empty username clears credentials without a check' => [
                ['username' => '', 'password' => 'x', 'country' => 'LT', 'test_mode' => true],
                null,
            ],
            'new password' => [
                ['username' => 'testuser1', 'password' => 'other', 'country' => 'LT', 'test_mode' => true],
                ['username' => 'testuser1', 'password' => 'other', 'country' => 'LT', 'test_mode' => true],
            ],
            'country change keeps stored password' => [
                ['username' => 'testuser1', 'password' => self::PLACEHOLDER, 'country' => 'lv', 'test_mode' => true],
                ['username' => 'testuser1', 'password' => 'testpassword1', 'country' => 'LV', 'test_mode' => true],
            ],
            'test mode toggle' => [
                ['username' => 'testuser1', 'password' => self::PLACEHOLDER, 'country' => 'LT', 'test_mode' => false],
                ['username' => 'testuser1', 'password' => 'testpassword1', 'country' => 'LT', 'test_mode' => false],
            ],
            'new username' => [
                ['username' => 'test1234', 'password' => 'qwerty12', 'country' => 'LT', 'test_mode' => true],
                ['username' => 'test1234', 'password' => 'qwerty12', 'country' => 'LT', 'test_mode' => true],
            ],
        ];
    }
}
