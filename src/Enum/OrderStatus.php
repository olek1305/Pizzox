<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PREPARING = 'preparing';
    case OUT_FOR_DELIVERY = 'out-for-delivery';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getTranslationKey(): string
    {
        return 'order.status.' . $this->value;
    }

    public function getTranslatedName(TranslatorInterface $translator): string
    {
        return $translator->trans($this->getTranslationKey());
    }
}