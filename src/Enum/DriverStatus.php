<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

enum DriverStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case ON_DUTY = 'on-duty';

    public function getTranslationKey(): string
    {
        return 'driver.status.' . $this->value;
    }

    public function getTranslatedName(TranslatorInterface $translator): string
    {
        return $translator->trans($this->getTranslationKey());
    }
}