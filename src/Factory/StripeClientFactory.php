<?php

namespace App\Factory;

use App\Repository\SettingRepository;
use Stripe\StripeClient;
use Throwable;

readonly class StripeClientFactory
{
    public function __construct(
        private SettingRepository $settingRepository
    ) {
    }

    /**
     * @return StripeClient
     * @throws Throwable
     */
    public function createStripeClient(): StripeClient
    {
        $settings = $this->settingRepository->findLastOrCreate();
        $stripeKey = $settings->getStripeSecretKey();

        if (!$stripeKey) {
            throw new \RuntimeException('Stripe Secret Key is not configured in settings');
        }

        return new StripeClient($stripeKey);
    }
}