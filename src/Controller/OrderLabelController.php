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


namespace Invertus\dpdBaltics\Controller;

use Invertus\dpdBaltics\Converter\FormDataConverter;
use Invertus\dpdBaltics\Infrastructure\Utility\VersionUtility;
use Invertus\dpdBaltics\Service\Exception\ExceptionService;
use Invertus\dpdBaltics\Service\Label\LabelPrintingService;
use Invertus\dpdBaltics\Service\ShipmentService;
use Invertus\dpdBaltics\Util\ServerGlobalsUtility;
use Invertus\dpdBalticsApi\Exception\DPDBalticsAPIException;
use Order;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}


class OrderLabelController extends FrameworkBundleAdminController
{
    /**
     * @var \DPDBaltics|\Module
     */
    private $module;

    public function __construct()
    {
        if (VersionUtility::isPsVersionLessThan('9.0.0')) {
            parent::__construct();
        }

        $this->module = \Module::getInstanceByName('dpdbaltics');
    }

    /**
     * Generates print label for given dpd order(when order shipment is already saved)
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute="admin_orders_index")
     *
     * @param int $shipmentId
     * @param string $labelFormat
     * @param string $labelPosition
     *
     * @return Response
     */
    public function printLabelOrderViewAction($shipmentId, $labelFormat, $labelPosition)
    {
        /** @var LabelPrintingService $printingService */
        $printingService = $this->module->getModuleContainer('invertus.dpdbaltics.service.label.label_printing_service');
        $response = $printingService->setLabelOptions($shipmentId, $labelFormat, $labelPosition);

        if (!$response['status'] || !$response['id_dpd_shipment']) {
            return $this->redirectWithError('admin_orders_index', $response['message']);
        }

        return $this->printLabel($response['id_dpd_shipment']);
    }

    /**
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute="admin_orders_index")
     * @param int $orderId
     * @return Response
     */
    public function saveAndPrintLabelOrderViewAction($orderId)
    {
        /** @var ShipmentService $shipmentService */
        $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');
        $response = $shipmentService->formatLabelAndCreateShipmentByOrderId($orderId);

        if (!$response['status'] || !$response['id_dpd_shipment']) {
            return $this->redirectWithError('admin_orders_index', $response['message']);
        }

        return $this->printLabel($response['id_dpd_shipment']);
    }

    /**
     * Generates and prints label in order list
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute="admin_orders_index")
     *
     * @param int $orderId
     *
     * @return Response
     */
    public function printAndSaveLabelFromOrderListAction($orderId)
    {
        /** @var ShipmentService $shipmentService */
        $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');
        $response = $shipmentService->formatLabelShipmentPrintResponse($orderId);

        if (!$response['status'] || !$response['id_dpd_shipment']) {
            return $this->redirectWithError('admin_orders_index', $response['message']);
        }

        return $this->printLabel($response['id_dpd_shipment']);
    }

    /**
     * Generates print multiple lables in order list
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute="admin_orders_index")
     *
     * @return Response
     */
    public function printMultipleLabelsFromOrderListAction()
    {
        $orderIds = Tools::getValue('order_orders_bulk');

        if (!$orderIds) {
            return $this->redirectWithError('admin_orders_index', $this->module->l('Could not print labels order id\'s are missing'));
        }
        /** @var ShipmentService $shipmentService */
        $shipmentService = $this->module->getModuleContainer('invertus.dpdbaltics.service.shipment_service');
        $response = $shipmentService->formatMultipleLabelShipmentPrintResponse($orderIds);

        if (!$response) {
            return $this->redirectWithError('admin_orders_index', $this->module->l('Could not print labels, bad response'));
        }
        $shipmentIds = json_decode($response['shipment_ids']);

        if (!$response['status'] || empty($shipmentIds)) {
            return $this->redirectWithError('admin_orders_index', $response['message']);
        }

        return $this->printMultipleLabels($shipmentIds);
    }

    private function printLabel($shipmentId)
    {
        try {
            $parcelPrintResponse = $this->module->printLabel($shipmentId);
        } catch (DPDBalticsAPIException $e) {
            /** @var ExceptionService $exceptionService */
            $exceptionService = $this->module->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');

            return $this->redirectWithError('admin_orders_index', $exceptionService->getErrorMessageForException(
                $e,
                $exceptionService->getAPIErrorMessages()
            ));
        } catch (\Exception $e) {
            return $this->redirectWithError('admin_orders_index',$this->module->l('Failed to print label: ') . $e->getMessage());
        }

        if (!empty($parcelPrintResponse->getErrLog())) {
            return $this->redirectWithError('admin_orders_index', $parcelPrintResponse->getErrLog());
        }

        return null;
    }

    private function printMultipleLabels($shipmentIds)
    {
        try {
            $parcelPrintResponse = $this->module->printMultipleLabels($shipmentIds);
        } catch (DPDBalticsAPIException $e) {
            /** @var ExceptionService $exceptionService */
            $exceptionService = $this->module->getModuleContainer('invertus.dpdbaltics.service.exception.exception_service');

            return $this->redirectWithError('admin_orders_index', $exceptionService->getErrorMessageForException(
                $e,
                $exceptionService->getAPIErrorMessages()
            ));
        } catch (\Exception $e) {
            return $this->redirectWithError('admin_orders_index',$this->module->l('Failed to print label: ') . $e->getMessage());
        }

        if (!empty($parcelPrintResponse->getErrLog())) {
            return $this->redirectWithError('admin_orders_index',$parcelPrintResponse->getErrLog());
        }

        return null;
    }

    private function redirectWithError($route, $error)
    {
        $this->addFlash('error', $error);

        $redirectLink = ServerGlobalsUtility::getHttpReferer();

        if ($redirectLink) {
            return $this->redirect($_SERVER['HTTP_REFERER'], 302);
        }

        return $this->redirectToRoute($route);
    }
}
