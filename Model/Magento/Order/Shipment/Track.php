<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Magento\Order\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;

class Track
{
    public const CUSTOM_CARRIER_CODE = 'custom';

    private ?\Magento\Sales\Model\Order $magentoOrder = null;

    private \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory;
    private \M2E\TikTokShop\Observer\Shipment\EventRuntimeManager $eventRuntimeManager;
    private \M2E\TikTokShop\Model\Order $order;
    private array $trackingDetails;

    public function __construct(
        \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory,
        \M2E\TikTokShop\Observer\Shipment\EventRuntimeManager $eventRuntimeManager,
        \M2E\TikTokShop\Model\Order $order,
        array $trackingDetails
    ) {
        $this->shipmentTrackFactory = $shipmentTrackFactory;
        $this->eventRuntimeManager = $eventRuntimeManager;
        $this->order = $order;
        $this->trackingDetails = $trackingDetails;
    }

    public function getTracks(): array
    {
        return $this->prepareTracks();
    }

    // ----------------------------------------

    private function prepareTracks(): array
    {
        if (count($this->trackingDetails) == 0) {
            return [];
        }

        // Skip shipment observer
        // ---------------------------------------
        $this->eventRuntimeManager->skipEvents();
        // ---------------------------------------

        $tracks = [];
        foreach ($this->trackingDetails as $trackingDetail) {
            /** @var \M2E\TikTokShop\Model\Order\Item $orderItem */
            foreach ($trackingDetail['order_items'] as $orderItem) {
                $shipment = $this->findShipment($orderItem);
                if ($shipment === null) {
                    continue;
                }

                $trackNumber = (string)$trackingDetail['tracking_number'];
                if ($this->isTrackNumberExistInShipment($trackNumber, $shipment)) {
                    continue;
                }

                $track = $this->createMagentoTrack(
                    $trackNumber,
                    (string)$trackingDetail['shipping_provider_name'],
                    (string)$trackingDetail['shipping_provider_id'],
                );

                $shipment->addTrack($track)->save();

                $tracks[] = $track;
            }
        }

        return $tracks;
    }

    // ---------------------------------------

    private function getCarrierCode(string $shippingProviderId): string
    {
        $shippingProviderMapping = $this->order->getWarehouse()->getShippingProviderMapping();
        $carrierCode = $shippingProviderMapping->getCarrierCodeByProviderId($shippingProviderId);

        return $carrierCode ?? self::CUSTOM_CARRIER_CODE;
    }

    private function getMagentoOrder(): ?\Magento\Sales\Model\Order
    {
        if ($this->magentoOrder !== null) {
            return $this->magentoOrder;
        }

        $this->magentoOrder = $this->order->getMagentoOrder();

        return $this->magentoOrder;
    }

    private function findShipment(\M2E\TikTokShop\Model\Order\Item $orderItem): ?\Magento\Sales\Model\Order\Shipment
    {
        foreach ($this->getMagentoOrder()->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getItems() as $shipmentItem) {
                if ((int)$shipmentItem->getProductId() === $orderItem->getMagentoProductId()) {
                    return $this->fixShipment($shipment);
                }
            }
        }

        return null;
    }

    private function createMagentoTrack(
        string $trackingNumber,
        string $shippingProviderName,
        string $shippingProviderId
    ): \Magento\Sales\Model\Order\Shipment\Track {
        $track = $this->shipmentTrackFactory->create();
        $track->setNumber($trackingNumber);
        $track->setTitle($shippingProviderName);
        $track->setCarrierCode($this->getCarrierCode($shippingProviderId));

        return $track;
    }

    private function fixShipment(\Magento\Sales\Model\Order\Shipment $shipment): \Magento\Sales\Model\Order\Shipment
    {
        // Sometimes Magento returns an array instead of Collection by a call of $shipment->getTracksCollection()
        if (
            $shipment->hasData(ShipmentInterface::TRACKS) &&
            !($shipment->getData(ShipmentInterface::TRACKS) instanceof TrackCollection)
        ) {
            $shipment->unsetData(ShipmentInterface::TRACKS);
        }

        return $shipment;
    }

    private function isTrackNumberExistInShipment(
        string $trackNumber,
        \Magento\Sales\Model\Order\Shipment $shipment
    ): bool {
        $trackNumber = $this->clearTrackNumber($trackNumber);

        foreach ($shipment->getTracks() as $track) {
            $shippingTrackNumber = $this->clearTrackNumber($track->getTrackNumber());
            if ($shippingTrackNumber === $trackNumber) {
                return true;
            }
        }

        return false;
    }

    private function clearTrackNumber(string $trackNumber): string
    {
        return str_replace(['/', ' ', '-'], '', $trackNumber);
    }
}
