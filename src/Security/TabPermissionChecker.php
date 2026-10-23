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


namespace Invertus\dpdBaltics\Security;

use Access;
use Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TabPermissionChecker
{
    const ROLE_SUFFIXES = [
        'view' => 'READ',
        'add' => 'CREATE',
        'edit' => 'UPDATE',
        'delete' => 'DELETE',
    ];

    /**
     * @var callable
     */
    private $roleGrantChecker;

    /**
     * @param callable|null $roleGrantChecker receives a role slug and a profile id, defaults to Access::isGranted
     */
    public function __construct(?callable $roleGrantChecker = null)
    {
        $this->roleGrantChecker = $roleGrantChecker ?: [Access::class, 'isGranted'];
    }

    /**
     * @param string $tabClassName
     * @param string $permission one of view, add, edit, delete
     * @param int $idProfile
     *
     * @return bool
     */
    public function isGranted($tabClassName, $permission, $idProfile)
    {
        if (!isset(self::ROLE_SUFFIXES[$permission])) {
            return false;
        }

        $role = 'ROLE_MOD_TAB_' . strtoupper($tabClassName) . '_' . self::ROLE_SUFFIXES[$permission];

        try {
            return (bool) call_user_func($this->roleGrantChecker, $role, (int) $idProfile);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Permission a core list or form action needs. Core lets "?action=<name>" reach process<Name>() without any check.
     *
     * @param string|null $action
     * @param bool $hasObjectId
     *
     * @return string|null null when the action does not change data
     */
    public function getListActionPermission($action, $hasObjectId)
    {
        $action = strtolower((string) $action);

        if ($action === 'delete' || $action === 'bulkdelete') {
            return 'delete';
        }

        if ($action === 'new' || ($action === 'save' && !$hasObjectId)) {
            return 'add';
        }

        if (in_array($action, ['save', 'status', 'position', 'update_options', 'update_fields'], true)) {
            return 'edit';
        }

        if (strpos($action, 'bulk') === 0) {
            return 'edit';
        }

        return null;
    }
}
