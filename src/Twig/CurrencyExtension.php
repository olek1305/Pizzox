<?php

namespace App\Twig;

use App\Service\CurrencyProvider;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CurrencyExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CurrencyProvider $currencyProvider
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'currency' => $this->currencyProvider->getCurrency()
        ];
    }
}