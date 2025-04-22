<?php

namespace App\Service;

use App\Document\Addition;
use App\Document\Pizza;
use App\Document\Promotion;
use App\Repository\SettingRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Throwable;


readonly class PriceCalculatorService
{
    public function __construct(
        private DocumentManager   $documentManager,
        private SettingRepository $settingRepository
    ) {
        //
    }

    /**
     * Calculate the price of a pizza, including any applicable promotions
     * @param Pizza $pizza
     * @param int $quantity
     * @param string $size
     * @return float
     * @throws Throwable
     */
    public function calculatePizzaPrice(Pizza $pizza, int $quantity, string $size): float
    {
        $priceInfo = $this->calculatePizzaUnitPrice($pizza, $size);
        return $priceInfo['price'] * $quantity;
    }

    /**
     * Calculate the unit price of a pizza, including promotions
     * @param Pizza $pizza
     * @param string $size
     * @return float[]
     * @throws Throwable
     */
    private function calculatePizzaUnitPrice(Pizza $pizza, string $size): array
    {
        // Get the price for the specific size
        $price = $pizza->getPriceBySize($size);

        // If the price for the requested size is not set (0), calculate it from medium price
        if ($price <= 0) {
            $settings = $this->settingRepository->findLastOrCreate();
            $basePrice = $pizza->getPrice(); // Medium price is our reference

            // Get size modifiers and calculation type from settings
            $smallSizeModifier = $settings->getSmallSizeModifier();
            $largeSizeModifier = $settings->getLargeSizeModifier();
            $calculationType = $settings->getPizzaPriceCalculationType();

            $price = match ($size) {
                'small' => $calculationType === 'fixed'
                    ? max(0, $basePrice - $smallSizeModifier)
                    : $basePrice * (1 - ($smallSizeModifier / 100)),
                'large' => $calculationType === 'fixed'
                    ? $basePrice + $largeSizeModifier
                    : $basePrice * (1 + ($largeSizeModifier / 100)),
                default => $basePrice, // medium size (fallback)
            };
        }

        // Check if there's an active promotion for this pizza
        $promotion = $this->documentManager->getRepository(Promotion::class)
            ->findBy(
                [
                    'itemType' => 'pizza',
                    'itemId' => $pizza->getId(),
                    'active' => true
                ],
                [
                    'expiresAt' => 'DESC'
                ]
            );

        if ($promotion) {
            $promotion = $promotion[0];
        }

        $originalPrice = (float)$price;
        $discountedPrice = $originalPrice;

        if ($promotion && $promotion->isValid()) {
            // Apply promotion discount
            if ($promotion->getType() === 'percentage') {
                $discountedPrice = $originalPrice * (1 - ($promotion->getDiscount() / 100));
            } else { // fixed amount
                $discountedPrice = $originalPrice - $promotion->getDiscount();
                if ($discountedPrice < 0) {
                    $discountedPrice = 0;
                }
            }
        }

        return [
            'original_price' => $originalPrice,
            'price' => $discountedPrice
        ];
    }

    /**
     * Get price information for an addition, including any applicable promotions
     * @param Addition $addition
     * @return array
     */
    public function getAdditionPriceInfo(Addition $addition): array
    {
        $price = $addition->getPrice();
        $originalPrice = $price;

        // Check if there's an active promotion for this addition
        $promotion = $this->documentManager->getRepository(Promotion::class)->findOneBy([
            'itemType' => 'addition',
            'itemId' => $addition->getId(),
            'active' => true
        ]);

        if ($promotion && $promotion->isValid()) {
            // Apply promotion discount
            if ($promotion->getType() === 'percentage') {
                $price *= (1 - ($promotion->getDiscount() / 100));
            } else { // fixed amount
                $price -= $promotion->getDiscount();
                if ($price < 0) {
                    $price = 0;
                }
            }
        }

        return [
            'original_price' => (float)number_format($originalPrice, 2, '.', ''),
            'price' => (float)number_format($price, 2, '.', '')
        ];
    }

    /**
     * Calculate the total price for a cart of items
     * @param array $cart
     * @return float
     * @throws LockException
     * @throws MappingException
     * @throws Throwable
     */
    public function calculateCartTotal(array $cart): float
    {
        $total = 0;

        foreach ($cart as $item) {
            if ($item['type'] === 'pizza') {
                $pizzaPrice = $this->calculatePizzaPrice(
                    $item['item'],
                    $item['quantity'],
                    $item['size'] ?? 'medium'
                );
                $total += $pizzaPrice;
            } elseif ($item['type'] === 'addition') {
                $addition = $item['item'];
                $total += $addition->getPrice() * $item['quantity'];
            }
        }

        return $total;
    }

    /**
     * Calculate the price of an addition
     * @param Addition $addition
     * @param int $quantity
     * @return float
     */
    public function calculateAdditionPrice(Addition $addition, int $quantity): float
    {
        $priceInfo = $this->getAdditionPriceInfo($addition);
        return $priceInfo['price'] * $quantity;
    }

    /**
     * Get price information for a pizza, including any applicable promotions
     * @param Pizza $pizza
     * @param string $size
     * @return float[]
     * @throws Throwable
     */
    public function getPizzaPriceInfo(Pizza $pizza, string $size): array
    {
        return $this->calculatePizzaUnitPrice($pizza, $size);
    }
}