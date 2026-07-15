<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\Order\Shipment\Data;

class TrackingDetails
{
    private int $magentoShipmentId;
    private string $carrierCode;
    private string $carrierTitle;
    private string $shippingMethod;
    private string $trackingNumber;
    private bool $isCustomCarrier;

    public function __construct(
        int $magentoShipmentId,
        string $carrierCode,
        string $carrierTitle,
        string $shippingMethod,
        string $trackingNumber,
        bool $isCustomCarrier
    ) {
        $this->magentoShipmentId = $magentoShipmentId;
        $this->carrierCode = $carrierCode;
        $this->carrierTitle = $carrierTitle;
        $this->shippingMethod = $shippingMethod;
        $this->trackingNumber = $trackingNumber;
        $this->isCustomCarrier = $isCustomCarrier;
    }

    public function getMagentoShipmentId(): int
    {
        return $this->magentoShipmentId;
    }

    public function getCarrierCode(): string
    {
        return $this->carrierCode;
    }

    public function getCarrierTitle(): string
    {
        return $this->carrierTitle;
    }

    public function getShippingMethod(): string
    {
        return $this->shippingMethod;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function isCustomCarrier(): bool
    {
        return $this->isCustomCarrier;
    }
}
