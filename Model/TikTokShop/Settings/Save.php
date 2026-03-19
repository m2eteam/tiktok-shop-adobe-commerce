<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Settings;

class Save
{
    private \M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingsRepository;

    public function __construct(\M2E\TikTokShop\Model\TikTokShop\Settings\Repository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function execute(array $data)
    {
        $this->saveIdentifierCode($data);
        $this->savePackageWidth($data);
        $this->savePackageHeight($data);
        $this->savePackageLength($data);
        $this->savePackageWeight($data);
    }

    private function saveIdentifierCode(array $data): bool
    {
        $mode = $data[IdentifierCode::CONFIG_KEY_MODE] ?? IdentifierCode::MODE_NOT_SET;
        $customAttribute = $data[IdentifierCode::CONFIG_KEY_CUSTOM_ATTRIBUTE] ?? '';

        $newData = new IdentifierCode((int)$mode, (string)$customAttribute);

        if ($newData->isEqual($this->settingsRepository->getIdentifierCode())) {
            return false;
        }

        $this->settingsRepository->setIdentifierCode($newData);

        return true;
    }

    private function savePackageWidth(array $data): bool
    {
        $mode = $data[PackageWidth::CONFIG_KEY_MODE] ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $data[PackageWidth::CONFIG_KEY_CUSTOM_ATTRIBUTE] ?? '';
        $customValue = $data[PackageWidth::CONFIG_KEY_CUSTOM_VALUE] ?? '';

        $newData = new PackageWidth(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );

        if ($newData->isEqual($this->settingsRepository->getPackageWidth())) {
            return false;
        }

        $this->settingsRepository->setPackageWidth($newData);

        return true;
    }

    private function savePackageHeight(array $data): bool
    {
        $mode = $data[PackageHeight::CONFIG_KEY_MODE] ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $data[PackageHeight::CONFIG_KEY_CUSTOM_ATTRIBUTE] ?? '';
        $customValue = $data[PackageHeight::CONFIG_KEY_CUSTOM_VALUE] ?? '';

        $newData = new PackageHeight(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );

        if ($newData->isEqual($this->settingsRepository->getPackageHeight())) {
            return false;
        }

        $this->settingsRepository->setPackageHeight($newData);

        return true;
    }

    private function savePackageLength(array $data): bool
    {
        $mode = $data[PackageLength::CONFIG_KEY_MODE] ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $data[PackageLength::CONFIG_KEY_CUSTOM_ATTRIBUTE] ?? '';
        $customValue = $data[PackageLength::CONFIG_KEY_CUSTOM_VALUE] ?? '';

        $newData = new PackageLength(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );

        if ($newData->isEqual($this->settingsRepository->getPackageLength())) {
            return false;
        }

        $this->settingsRepository->setPackageLength($newData);

        return true;
    }

    private function savePackageWeight(array $data): bool
    {
        $mode = $data[PackageWeight::CONFIG_KEY_MODE] ?? PackageInterface::MODE_NOT_SET;
        $customAttribute = $data[PackageWeight::CONFIG_KEY_CUSTOM_ATTRIBUTE] ?? '';
        $customValue = $data[PackageWeight::CONFIG_KEY_CUSTOM_VALUE] ?? '';

        $newData = new PackageWeight(
            (int)$mode,
            (string)$customAttribute,
            (string)$customValue
        );

        if ($newData->isEqual($this->settingsRepository->getPackageWeight())) {
            return false;
        }

        $this->settingsRepository->setPackageWeight($newData);

        return true;
    }
}
