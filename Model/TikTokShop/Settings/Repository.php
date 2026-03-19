<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Settings;

class Repository
{
    private const CONFIG_GROUP = '/tts/configuration/';

    private \M2E\TikTokShop\Model\Config\Manager $configManager;

    public function __construct(\M2E\TikTokShop\Model\Config\Manager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getIdentifierCode(): IdentifierCode
    {
        $mode = $this->getConfig(IdentifierCode::CONFIG_KEY_MODE) ?? IdentifierCode::MODE_NOT_SET;
        $customAttribute = $this->getConfig(IdentifierCode::CONFIG_KEY_CUSTOM_ATTRIBUTE);

        return new IdentifierCode((int)$mode, (string)$customAttribute);
    }

    public function setIdentifierCode(IdentifierCode $identifierCode): void
    {
        $this->setConfig(
            IdentifierCode::CONFIG_KEY_MODE,
            $identifierCode->getMode()
        );

        $this->setConfig(
            IdentifierCode::CONFIG_KEY_CUSTOM_ATTRIBUTE,
            $identifierCode->getCustomAttribute()
        );
    }

    // ----------------------------------------

    public function getPackageWidth(): PackageWidth
    {
        $mode = $this->getConfig(PackageWidth::CONFIG_KEY_MODE) ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $this->getConfig(PackageWidth::CONFIG_KEY_CUSTOM_ATTRIBUTE);
        $customValue = $this->getConfig(PackageWidth::CONFIG_KEY_CUSTOM_VALUE);

        return new PackageWidth(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );
    }

    public function setPackageWidth(PackageWidth $packageWidth): void
    {
        $this->setConfig(
            PackageWidth::CONFIG_KEY_MODE,
            $packageWidth->getMode()
        );

        $this->setConfig(
            PackageWidth::CONFIG_KEY_CUSTOM_ATTRIBUTE,
            $packageWidth->getCustomAttribute()
        );

        $this->setConfig(
            PackageWidth::CONFIG_KEY_CUSTOM_VALUE,
            $packageWidth->getCustomValue()
        );
    }

    // ----------------------------------------

    public function getPackageHeight(): PackageHeight
    {
        $mode = $this->getConfig(PackageHeight::CONFIG_KEY_MODE) ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $this->getConfig(PackageHeight::CONFIG_KEY_CUSTOM_ATTRIBUTE);
        $customValue = $this->getConfig(PackageHeight::CONFIG_KEY_CUSTOM_VALUE);

        return new PackageHeight(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );
    }

    public function setPackageHeight(PackageHeight $packageHeight): void
    {
        $this->setConfig(
            PackageHeight::CONFIG_KEY_MODE,
            $packageHeight->getMode()
        );

        $this->setConfig(
            PackageHeight::CONFIG_KEY_CUSTOM_ATTRIBUTE,
            $packageHeight->getCustomAttribute()
        );

        $this->setConfig(
            PackageHeight::CONFIG_KEY_CUSTOM_VALUE,
            $packageHeight->getCustomValue()
        );
    }

    // ----------------------------------------

    public function getPackageLength(): PackageLength
    {
        $mode = $this->getConfig(PackageLength::CONFIG_KEY_MODE) ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $this->getConfig(PackageLength::CONFIG_KEY_CUSTOM_ATTRIBUTE);
        $customValue = $this->getConfig(PackageLength::CONFIG_KEY_CUSTOM_VALUE);

        return new PackageLength(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );
    }

    public function setPackageLength(PackageLength $packageLength): void
    {
        $this->setConfig(
            PackageLength::CONFIG_KEY_MODE,
            $packageLength->getMode()
        );

        $this->setConfig(
            PackageLength::CONFIG_KEY_CUSTOM_ATTRIBUTE,
            $packageLength->getCustomAttribute()
        );

        $this->setConfig(
            PackageLength::CONFIG_KEY_CUSTOM_VALUE,
            $packageLength->getCustomValue()
        );
    }

    // ----------------------------------------

    public function getPackageWeight(): PackageWeight
    {
        $mode = $this->getConfig(PackageWeight::CONFIG_KEY_MODE) ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $this->getConfig(PackageWeight::CONFIG_KEY_CUSTOM_ATTRIBUTE);
        $customValue = $this->getConfig(PackageWeight::CONFIG_KEY_CUSTOM_VALUE);

        return new PackageWeight(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );
    }

    public function setPackageWeight(PackageWeight $packageWeight): void
    {
        $this->setConfig(
            PackageWeight::CONFIG_KEY_MODE,
            $packageWeight->getMode()
        );

        $this->setConfig(
            PackageWeight::CONFIG_KEY_CUSTOM_ATTRIBUTE,
            $packageWeight->getCustomAttribute()
        );

        $this->setConfig(
            PackageWeight::CONFIG_KEY_CUSTOM_VALUE,
            $packageWeight->getCustomValue()
        );
    }

    // ----------------------------------------

    private function setConfig(string $key, $value)
    {
        $this->configManager->setGroupValue(self::CONFIG_GROUP, $key, $value);
    }

    private function getConfig(string $key)
    {
        return $this->configManager->getGroupValue(self::CONFIG_GROUP, $key);
    }
}
