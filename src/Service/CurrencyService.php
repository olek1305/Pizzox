<?php

namespace App\Service;

use JsonException;

class CurrencyService
{
    private array $currencies;

    public function __construct()
    {
        $filePath = dirname(__DIR__, 2) . '/config/currencies.json';

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Currency file not found at $filePath");
        }

        $json = file_get_contents($filePath);

        try {
            $this->currencies = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Error decoding currencies.js: ' . $exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getCurrencyChoices(): array
    {
        $choices = [];
        foreach ($this->currencies as $currency) {
            $choices[$currency['currency'] . ' (' . $currency['symbol'] . ')'] = strtoupper($currency['iso']);
        }
        return $choices;
    }

    /**
     * @param string $currencyIso
     * @return string|null
     */
    public function getCountryByCurrency(string $currencyIso): ?string
    {
        foreach ($this->currencies as $currency) {
            if (strtolower($currency['iso']) === strtolower($currencyIso)) {
                return $currency['country'] ?? null;
            }
        }
        return null;
    }
}