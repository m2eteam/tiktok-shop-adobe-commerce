<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Settings;

class PackageWeight implements PackageInterface
{
    public const CONFIG_KEY_MODE = 'package_weight_mode';
    public const CONFIG_KEY_CUSTOM_ATTRIBUTE = 'package_weight_custom_attribute';
    public const CONFIG_KEY_CUSTOM_VALUE = 'package_weight_custom_value';

    private int $mode;
    private string $customAttribute;
    private string $customValue;

    public function __construct(int $mode, string $customAttribute, string $customValue)
    {
        $this->mode = $mode;
        $this->customAttribute = $customAttribute;
        $this->customValue = $customValue;
    }

    public function getType(): string
    {
        return 'weight';
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getCustomAttribute(): string
    {
        if (!$this->isModeCustomAttribute()) {
            return '';
        }

        return $this->customAttribute;
    }

    public function getCustomValue(): string
    {
        if (!$this->isModeCustomValue()) {
            return '';
        }

        return $this->customValue;
    }

    public function isEqual(self $packageWidth): bool
    {
        return $this->getMode() === $packageWidth->getMode()
            && $this->getCustomAttribute() === $packageWidth->getCustomAttribute()
            && $this->getCustomValue() === $packageWidth->getCustomValue();
    }

    public function isModeCustomAttribute(): bool
    {
        return $this->getMode() === self::MODE_CUSTOM_ATTRIBUTE;
    }

    public function isModeCustomValue(): bool
    {
        return $this->getMode() === self::MODE_CUSTOM_VALUE;
    }
}
