<?php

declare(strict_types=1);

namespace M2E\TikTokShop\Model\TikTokShop\Settings;

class IdentifierCode
{
    public const MODE_NOT_SET = 0;
    public const MODE_CUSTOM_ATTRIBUTE = 1;

    public const CONFIG_KEY_MODE = 'identifier_code_mode';
    public const CONFIG_KEY_CUSTOM_ATTRIBUTE = 'identifier_code_custom_attribute';

    private int $mode;
    private string $customAttribute;

    public function __construct(int $mode, string $customAttribute)
    {
        $this->mode = $mode;
        $this->customAttribute = $customAttribute;
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

    public function isEqual(self $identifierCode): bool
    {
        return $this->getMode() === $identifierCode->getMode()
            && $this->getCustomAttribute() === $identifierCode->getCustomAttribute();
    }

    // ----------------------------------------

    public function isModeCustomAttribute(): bool
    {
        return $this->getMode() === self::MODE_CUSTOM_ATTRIBUTE;
    }
}
