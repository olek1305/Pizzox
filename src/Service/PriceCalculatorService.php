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
        $basePrice = $pizza->getPrice();
        $settings = $this->settingRepository->findLastOrCreate();

        // Get size modifiers and calculation type from settings
        $smallSizeModifier = $settings->getSmallSizeModifier();
        $largeSizeModifier = $settings->getLargeSizeModifier();
        $calculationType = $settings->getPizzaPriceCalculationType();

        $price = match ($size) {
            'small' => $calculationType === 'fixed'
                ? $basePrice - $smallSizeModifier
                : $basePrice * (1 - ($smallSizeModifier / 100)),
            'large' => $calculationType === 'fixed'
                ? $basePrice + $largeSizeModifier
                : $basePrice * (1 + ($largeSizeModifier / 100)),
            default => $basePrice, // medium size
        };

        $price = max(0, $price);

        // Check if there's an active promotion for this pizza
        $promotion = $this->documentManager->getRepository(Promotion::class)->findOneBy([
            'itemType' => 'pizza',
            'itemId' => $pizza->getId(),
            'active' => true
        ]);

        $originalPrice = number_format((float)$price, 2, '.', '');
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
            $discountedPrice = number_format((float)$discountedPrice, 2, '.', '');
        }

        return [
            'original_price' => (float)$originalPrice,
            'price' => (float)$discountedPrice
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
        $total = 0.0;

        foreach ($cart as $item) {
            if ($item['type'] === 'pizza') {
                $pizza = $this->documentManager->getRepository(Pizza::class)->find($item['item_id']);
                if ($pizza) {
                    $total += $this->calculatePizzaPrice(
                        $pizza,
                        $item['quantity'],
                        $item['size']
                    );
                }
            } elseif ($item['type'] === 'addition') {
                $addition = $this->documentManager->getRepository(Addition::class)->find($item['item_id']);
                if ($addition) {
                    $priceInfo = $this->getAdditionPriceInfo($addition);
                    $total += $priceInfo['price'] * $item['quantity'];
                }
            }
        }

        return $total;
    }

    /**
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