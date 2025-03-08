<?php

namespace App\Service;

use App\Document\Setting;
use App\Repository\SettingRepository;
use Throwable;

class PizzaPriceCalculator
{
    /**
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        private readonly SettingRepository $settingRepository
    ) {
        //
    }

    /**
     * @param float $mediumPrice
     * @param string $size
     * @return float
     * @throws Throwable
     */
    public function calculatePrice(float $mediumPrice, string $size): float
    {
        $settings = $this->settingRepository->findLastOrCreate();

        if ($size === 'medium') {
            return $mediumPrice;
        }

        $modifier = $size === 'large'
            ? $settings->getLargeSizeModifier()
            : $settings->getSmallSizeModifier();

        if ($settings->getPizzaPriceCalculationType() === 'percentage') {
            return $mediumPrice * (1 + ($size === 'large' ? $modifier : -$modifier) / 100);
        }

        return $mediumPrice + ($size === 'large' ? $modifier : -$modifier);
    }
}