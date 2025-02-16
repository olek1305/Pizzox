<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELED = 'canceled';


    public function getTranslationKey(): string
    {
        return 'payment.status.' . $this->value;
    }

    public function getTranslatedName(TranslatorInterface $translator): string
    {
        return $translator->trans($this->getTranslationKey());
    }
}