<?php

namespace App\Service;

use App\Repository\SettingRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Throwable;

class CurrencyProvider
{
    private string $currency;

    /**
     * @param SettingRepository $settingRepository
     * @throws MongoDBException
     * @throws Throwable
     */
    public function __construct(SettingRepository $settingRepository)
    {
        $setting = $settingRepository->findLastOrCreate();
        $this->currency = $setting->getCurrency();
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}