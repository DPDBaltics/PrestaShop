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


namespace Invertus\dpdBaltics\Logger;

use Configuration;
use DPDLog;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Service\LogsService;
use PrestaShopDatabaseException;
use PrestaShopException;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Logger implements LoggerInterface
{
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const CRITICAL = 'CRITICAL';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    const NOTICE = 'NOTICE';
    const ALERT = 'ALERT';

    /**
     * @var LogsService
     */
    private $logsService;

    public function __construct(LogsService $logsService)
    {
        $this->logsService = $logsService;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $contextd
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function critical($message, array $context = [])
    {
        if (!Configuration::get(Config::TRACK_LOGS)) {
            return;
        }
        $log = new DPDLog();
        $log->response = $message;
        $log->request = !empty($context['request']) ? $this->logsService->hideUsernameAndPasswordFromRequest($context['request']) : null;
        $log->status = self::CRITICAL;
        $log->add();
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function error($message, array $context = [])
    {
        if (!Configuration::get(Config::TRACK_LOGS)) {
            return;
        }
        $log = new DPDLog();
        $log->response = $message;
        $log->request = !empty($context['request']) ? $this->logsService->hideUsernameAndPasswordFromRequest($context['request']) : null;
        $log->status = self::ERROR;
        $log->add();
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!Configuration::get(Config::TRACK_LOGS)) {
            return;
        }

        $log = new DPDLog();
        $log->response = $message;
        $log->request = !empty($context['request']) ? $this->logsService->hideUsernameAndPasswordFromRequest($context['request']) : null;
        $log->status = self::ERROR;

        $log->add();
    }
}
