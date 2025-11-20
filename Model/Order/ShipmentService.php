<?php

namespace M2E\TikTokShop\Model\Order;

class ShipmentService
{
    public const HANDLE_RESULT_FAILED = -1;
    public const HANDLE_RESULT_SKIPPED = 0;
    public const HANDLE_RESULT_SUCCEEDED = 1;

    private \M2E\TikTokShop\Model\Warehouse\ShippingMapping $shippingProviderMapping;

    private \M2E\TikTokShop\Model\Order\Shipment\TrackingDetailsBuilder $trackingDetailsBuilder;
    private \M2E\TikTokShop\Model\Order\Shipment\ItemLoader $itemLoader;
    private \M2E\TikTokShop\Model\Order\Change\Repository $orderChangeRepository;
    private \M2E\TikTokShop\Model\Order\ChangeCreateService $orderChangeCreateService;
    private \Magento\Framework\UrlInterface $urlInterface;
    private \M2E\TikTokShop\Model\Order\Item\Repository $orderItemRepository;
    private \M2E\TikTokShop\Model\ShippingProvider\Repository $shippingProviderRepository;

    public function __construct(
        \M2E\TikTokShop\Model\Order\Item\Repository $orderItemRepository,
        \M2E\TikTokShop\Model\Order\Shipment\TrackingDetailsBuilder $trackingDetailsBuilder,
        \M2E\TikTokShop\Model\Order\Shipment\ItemLoader $itemLoader,
        \M2E\TikTokShop\Model\Order\Change\Repository $orderChangeRepository,
        \M2E\TikTokShop\Model\Order\ChangeCreateService $orderChangeCreateService,
        \Magento\Framework\UrlInterface $urlInterface,
        \M2E\TikTokShop\Model\ShippingProvider\Repository $shippingProviderRepository
    ) {
        $this->trackingDetailsBuilder = $trackingDetailsBuilder;
        $this->itemLoader = $itemLoader;
        $this->orderChangeRepository = $orderChangeRepository;
        $this->orderChangeCreateService = $orderChangeCreateService;
        $this->urlInterface = $urlInterface;
        $this->orderItemRepository = $orderItemRepository;
        $this->shippingProviderRepository = $shippingProviderRepository;
    }

    public function shipByShipment(
        \M2E\TikTokShop\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment $shipment,
        int $initiator
    ): int {
        $order->getLogService()->setInitiator($initiator);

        if (!$order->canUpdateShippingStatus()) {
            $order->addErrorLog(
                "Shipping details could not be sent to the Channel. " .
                "Reason: Order status on TikTok Shop is already marked as 'Shipped'."
            );

            return self::HANDLE_RESULT_SKIPPED;
        }

        $trackingDetails = $this->trackingDetailsBuilder->build($shipment, $order->getStoreId());
        if ($trackingDetails === null) {
            $order->addErrorLog(
                "Shipping details could not be sent to the Channel. " .
                "Reason: Magento Shipping doesn't have Tracking number."
            );

            return self::HANDLE_RESULT_FAILED;
        }

        $existOrderChange = $this->findExistOrderChange($order, $trackingDetails);
        $orderItemsToShip = $this->itemLoader->loadItemsByShipment($order, $shipment, $existOrderChange);
        if (empty($orderItemsToShip)) {
            $order->addErrorLog(
                "Shipping details could not be sent to the Channel. " .
                "Reason: The order Items have either already been shipped or are not included in this order."
            );

            $this->removeExistOrderChange($order, $existOrderChange);

            return self::HANDLE_RESULT_FAILED;
        }

        if (!$this->getShippingProviderMapping($order)->isConfigured()) {
            $order->addErrorLog(
                'Missing <a href="%url%" target="_blank">Shipping Carrier Mapping</a>. ' .
                'Please ensure the shipping carrier mapping is correctly configured to synchronize ' .
                'order shipping data with %channel_title%',
                [
                    '!url' => $this->urlInterface->getUrl('m2e_tiktokshop/tiktokshop_account/edit', [
                        'id' => $order->getAccountId(),
                        'tab' => 'invoices_and_shipments',
                    ]),
                    '!channel_title' => \M2E\TikTokShop\Helper\Module::getChannelTitle(),
                ]
            );

            $this->removeExistOrderChange($order, $existOrderChange);

            return self::HANDLE_RESULT_FAILED;
        }

        $shippingProviderId = $this->findShippingProviderId(
            $order,
            $trackingDetails
        );
        if ($shippingProviderId === null) {
            $order->addErrorLog(
                sprintf(
                    'Failed to map Magento Shipping Carrier to %s Shipping Carrier.',
                    \M2E\TikTokShop\Helper\Module::getChannelTitle()
                )
            );

            $this->removeExistOrderChange($order, $existOrderChange);

            return self::HANDLE_RESULT_FAILED;
        }

        $orderChange = $this->createOrderChange(
            $order,
            $orderItemsToShip,
            $trackingDetails,
            $shippingProviderId,
            $initiator,
            $existOrderChange
        );

        $this->writeTrackingNumberAddedLog($order, $trackingDetails);

        $this->markItemsAsShippingInProgress($orderItemsToShip, $orderChange);

        return self::HANDLE_RESULT_SUCCEEDED;
    }

    private function findExistOrderChange(
        \M2E\TikTokShop\Model\Order $order,
        \M2E\TikTokShop\Model\Order\Shipment\Data\TrackingDetails $trackingDetails
    ): ?\M2E\TikTokShop\Model\Order\Change {
        $existChanges = $this->orderChangeRepository->findShippingNotStarted((int)$order->getId());
        foreach ($existChanges as $existChange) {
            $changeParams = $existChange->getParams();

            if (!isset($changeParams['magento_shipment_id'])) {
                continue;
            }

            if ($changeParams['magento_shipment_id'] !== $trackingDetails->getMagentoShipmentId()) {
                continue;
            }

            return $existChange;
        }

        return null;
    }

    /**
     * @param \M2E\TikTokShop\Model\Order\Item[] $itemsToShip
     */
    private function createOrderChange(
        \M2E\TikTokShop\Model\Order $order,
        array $itemsToShip,
        \M2E\TikTokShop\Model\Order\Shipment\Data\TrackingDetails $trackingDetails,
        string $shippingProviderId,
        int $initiator,
        ?\M2E\TikTokShop\Model\Order\Change $existOrderChange
    ): \M2E\TikTokShop\Model\Order\Change {
        $params = [
            'magento_shipment_id' => $trackingDetails->getMagentoShipmentId(),
            'tracking_number' => $trackingDetails->getTrackingNumber(),
            'shipping_provider_id' => $shippingProviderId,
            'items' => array_map(static function ($item) {
                return [
                    'item_id' => $item->getId(),
                ];
            }, $itemsToShip),
        ];

        if ($existOrderChange !== null) {
            $existOrderChange->setParams($params);

            $this->orderChangeRepository->save($existOrderChange);

            return $existOrderChange;
        }

        return $this->orderChangeCreateService->create(
            (int)$order->getId(),
            \M2E\TikTokShop\Model\Order\Change::ACTION_UPDATE_SHIPPING,
            $initiator,
            $params,
        );
    }

    private function findShippingProviderId(
        \M2E\TikTokShop\Model\Order $order,
        \M2E\TikTokShop\Model\Order\Shipment\Data\TrackingDetails $trackingDetails
    ): ?string {
        $shippingProviderId = $this->getShippingProviderMapping($order)
            ->getProviderIdByCarrierCode($trackingDetails->getCarrierCode());

        if (
            $shippingProviderId === null
            && $trackingDetails->isCustomCarrierCode()
            && $order->getAccount()->getInvoiceAndShipmentSettings()->isMapShippingProviderByCustomCarrierTitle()
        ) {
            $shippingProvider = $this->shippingProviderRepository->findByAccountShopWarehouseAndTitle(
                $order->getAccountId(),
                $order->getShopId(),
                $order->getWarehouse()->getId(),
                $trackingDetails->getShippingMethod()
            );

            if ($shippingProvider !== null) {
                $shippingProviderId = $shippingProvider->getShippingProviderId();
            }
        }

        if ($shippingProviderId === null) {
            $shippingProviderId = $this
                ->getShippingProviderMapping($order)
                ->getDefaultProviderId();
        }

        return $shippingProviderId;
    }

    private function writeTrackingNumberAddedLog(
        \M2E\TikTokShop\Model\Order $order,
        Shipment\Data\TrackingDetails $trackingDetails
    ): void {
        $order->addInfoLog(
            'Tracking number "%tracking_number%" for "%carrier_name%" was added to the Shipment.',
            [
                '!tracking_number' => $trackingDetails->getTrackingNumber(),
                '!carrier_name' => $trackingDetails->getShippingMethod(),
            ]
        );
    }

    /**
     * @param \M2E\TikTokShop\Model\Order\Item[] $orderItemsToShip
     * @param \M2E\TikTokShop\Model\Order\Change $orderChange
     *
     * @return void
     */
    private function markItemsAsShippingInProgress(array $orderItemsToShip, Change $orderChange): void
    {
        foreach ($orderItemsToShip as $orderItem) {
            $orderItem->setShippingInProgressYes();

            $this->orderItemRepository->save($orderItem);
        }
    }

    private function removeExistOrderChange(\M2E\TikTokShop\Model\Order $order, ?Change $existOrderChange): void
    {
        if ($existOrderChange === null) {
            return;
        }

        foreach ($existOrderChange->getOrderItemsIdsForShipping() as $id) {
            $item = $order->getItem($id);
            $item->setShippingInProgressNo();

            $this->orderItemRepository->save($item);
        }

        $this->orderChangeRepository->delete($existOrderChange);
    }

    private function getShippingProviderMapping(
        \M2E\TikTokShop\Model\Order $order
    ): \M2E\TikTokShop\Model\Warehouse\ShippingMapping {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->shippingProviderMapping)) {
            $this->shippingProviderMapping = $order->getWarehouse()->getShippingProviderMapping();
        }

        return $this->shippingProviderMapping;
    }
}
