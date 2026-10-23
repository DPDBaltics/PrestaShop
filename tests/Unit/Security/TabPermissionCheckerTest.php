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


use Invertus\dpdBaltics\Security\TabPermissionChecker;
use PHPUnit\Framework\TestCase;

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.8.9');
}

class TabPermissionCheckerTest extends TestCase
{
    /**
     * @dataProvider getPermissionRoleData
     */
    public function testIsGrantedAsksForTabRoleOfPermission($permission, $expectedRole)
    {
        $askedRoles = [];
        $checker = new TabPermissionChecker(function ($role, $idProfile) use (&$askedRoles) {
            $askedRoles[] = [$role, $idProfile];

            return true;
        });

        $this->assertTrue($checker->isGranted('AdminDPDBalticsSettings', $permission, 5));
        $this->assertSame([[$expectedRole, 5]], $askedRoles);
    }

    public function getPermissionRoleData()
    {
        return [
            ['view', 'ROLE_MOD_TAB_ADMINDPDBALTICSSETTINGS_READ'],
            ['add', 'ROLE_MOD_TAB_ADMINDPDBALTICSSETTINGS_CREATE'],
            ['edit', 'ROLE_MOD_TAB_ADMINDPDBALTICSSETTINGS_UPDATE'],
            ['delete', 'ROLE_MOD_TAB_ADMINDPDBALTICSSETTINGS_DELETE'],
        ];
    }

    public function testIsGrantedReturnsFalseWhenProfileLacksRole()
    {
        $checker = new TabPermissionChecker(function () {
            return false;
        });

        $this->assertFalse($checker->isGranted('AdminOrders', 'edit', 5));
    }

    public function testIsGrantedDeniesUnknownPermissionWithoutAskingCore()
    {
        $wasAsked = false;
        $checker = new TabPermissionChecker(function () use (&$wasAsked) {
            $wasAsked = true;

            return true;
        });

        $this->assertFalse($checker->isGranted('AdminOrders', 'all', 5));
        $this->assertFalse($wasAsked);
    }

    public function testIsGrantedDeniesWhenCoreThrowsForInvalidRole()
    {
        $checker = new TabPermissionChecker(function () {
            throw new \Exception('The slug is invalid');
        });

        $this->assertFalse($checker->isGranted('AdminOrders', 'edit', 5));
    }

    /**
     * @dataProvider getListActionPermissionData
     */
    public function testGetListActionPermission($action, $hasObjectId, $expectedPermission)
    {
        $checker = new TabPermissionChecker(function () {
            return true;
        });

        $this->assertSame($expectedPermission, $checker->getListActionPermission($action, $hasObjectId));
    }

    public function getListActionPermissionData()
    {
        return [
            ['delete', true, 'delete'],
            ['bulkdelete', false, 'delete'],
            ['bulkDelete', false, 'delete'],
            ['save', false, 'add'],
            ['save', true, 'edit'],
            ['new', false, 'add'],
            ['status', true, 'edit'],
            ['position', true, 'edit'],
            ['update_options', false, 'edit'],
            ['update_fields', false, 'edit'],
            ['bulkenableSelection', false, 'edit'],
            ['export', false, null],
            ['reset_filters', false, null],
            ['', false, null],
            [null, false, null],
        ];
    }
}
