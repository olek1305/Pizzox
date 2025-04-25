<?php

namespace App\Tests\Service;

use App\Document\Addition;
use App\Document\Pizza;
use App\Document\Promotion;
use App\Document\Setting;
use App\Repository\SettingRepository;
use App\Service\PriceCalculatorService;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Throwable;

class PriceCalculationTest extends TestCase
{
    private DocumentManager $documentManagerMock;
    private PriceCalculatorService $priceCalculator;

    protected function setUp(): void
    {
        $this->documentManagerMock = $this->createMock(DocumentManager::class);
        $settingRepositoryMock = $this->createMock(SettingRepository::class);

        $settingMock = $this->createMock(Setting::class);
        $settingMock->method('getSmallSizeModifier')->willReturn(5.0);
        $settingMock->method('getLargeSizeModifier')->willReturn(5.0);
        $settingMock->method('getPizzaPriceCalculationType')->willReturn('fixed');

        $settingRepositoryMock->method('findLastOrCreate')
            ->willReturn($settingMock);

        $this->priceCalculator = new PriceCalculatorService(
            $this->documentManagerMock,
            $settingRepositoryMock
        );
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testBasicPizzaPriceCalculation(): void
    {
        // Create test pizza
        $pizza = new Pizza();
        $pizza->setName('Margherita');
        $pizza->setPriceSmall(10.00);
        $pizza->setPrice(15.00);
        $pizza->setPriceLarge(20.00);

        // Test quantity and size calculations
        $this->assertEqualsWithDelta(10.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'small'), 0.001);
        $this->assertEqualsWithDelta(20.00, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'small'), 0.001);
        $this->assertEqualsWithDelta(15.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'medium'), 0.001);
        $this->assertEqualsWithDelta(20.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'large'), 0.001);
        $this->assertEqualsWithDelta(40.00, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'large'), 0.001);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testPizzaWithPercentagePromotion(): void
    {
        // Create test pizza
        $pizza = new Pizza();
        $pizza->setName('Pepperoni');
        $pizza->setPriceSmall(12.00);
        $pizza->setPrice(18.00);
        $pizza->setPriceLarge(24.00);
        $pizza->setId('pizza123');

        // Create a promotion mock instead of a real object
        $promotion = $this->createMock(Promotion::class);
        $promotion->method('getItemId')->willReturn('pizza123');
        $promotion->method('getItemType')->willReturn('pizza');
        $promotion->method('getType')->willReturn('percentage');
        $promotion->method('getDiscount')->willReturn(20.0);
        $promotion->method('isValid')->willReturn(true);

        // Prepare a mock repository to return our promotion
        $promotionRepoMock = $this->createMock(DocumentRepository::class);
        $promotionRepoMock->method('findBy')
            ->willReturn([$promotion]);

        // Connect promotion repository mock to DocumentManager
        $this->documentManagerMock->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($promotionRepoMock);

        // Test discounted prices
        $this->assertEqualsWithDelta(9.60, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'small'), 0.001); // 12.00 - 20%
        $this->assertEqualsWithDelta(14.40, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'medium'), 0.001); // 18.00 - 20%
        $this->assertEqualsWithDelta(19.20, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'large'), 0.001); // 24.00 - 20%
        $this->assertEqualsWithDelta(38.40, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'large'), 0.001); // 48.00 - 20%
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testPizzaWithFixedPromotion(): void
    {
        // Create test pizza
        $pizza = new Pizza();
        $pizza->setName('Hawaiian');
        $pizza->setPriceSmall(11.00);
        $pizza->setPrice(16.00);
        $pizza->setPriceLarge(21.00);
        $pizza->setId('pizza456');

        // Create a promotion mock
        $promotion = $this->createMock(Promotion::class);
        $promotion->method('getItemId')->willReturn('pizza456');
        $promotion->method('getItemType')->willReturn('pizza');
        $promotion->method('getType')->willReturn('fixed');
        $promotion->method('getDiscount')->willReturn(3.0);
        $promotion->method('isValid')->willReturn(true);

        // Prepare a mock repository to return our promotion
        $promotionRepoMock = $this->createMock(DocumentRepository::class);
        $promotionRepoMock->method('findBy')
            ->willReturn([$promotion]);

        // Connect promotion repository mock to DocumentManager
        $this->documentManagerMock->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($promotionRepoMock);

        // Test discounted prices
        $this->assertEqualsWithDelta(8.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'small'), 0.001); // 11.00 - 3.00
        $this->assertEqualsWithDelta(13.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'medium'), 0.001); // 16.00 - 3.00
        $this->assertEqualsWithDelta(18.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'large'), 0.001); // 21.00 - 3.00
        $this->assertEqualsWithDelta(36.00, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'large'), 0.001); // 42.00 - 6.00
    }

    public function testAdditionPriceCalculation(): void
    {
        // Create a test addition
        $addition = new Addition();
        $addition->setName('Extra Cheese');
        $addition->setPrice(2.50);

        // Test quantity calculations
        $this->assertEqualsWithDelta(2.50, $this->priceCalculator->calculateAdditionPrice($addition, 1), 0.001);
        $this->assertEqualsWithDelta(5.00, $this->priceCalculator->calculateAdditionPrice($addition, 2), 0.001);
        $this->assertEqualsWithDelta(7.50, $this->priceCalculator->calculateAdditionPrice($addition, 3), 0.001);
    }

    /**
     * @return void
     * @throws Throwable
     * @throws LockException
     * @throws MappingException
     */
    public function testMultipleItemsWithMixedPromotions(): void
    {
        // Create pizzas
        $pizza1 = new Pizza();
        $pizza1->setId('pizza1');
        $pizza1->setName('Margherita');
        $pizza1->setprice(15.00);
        $pizza1->setPriceLarge(24.00);

        $pizza2 = new Pizza();
        $pizza2->setId('pizza2');
        $pizza2->setName('Supreme');
        $pizza2->setPriceLarge(22.00);

        // Create addition
        $addition = new Addition();
        $addition->setId('addition1');
        $addition->setName('Garlic Dip');
        $addition->setPrice(1.50);

        // Create a promotion (20% off)
        $promotion = new Promotion();
        $promotion->setItemId('pizza2');
        $promotion->setItemType('pizza');
        $promotion->setType('percentage');
        $promotion->setDiscount(20);
        $promotion->setExpiresAt(new DateTime('+1 day'));
        $promotion->setUsageLimit(100);
        $promotion->setActive(true);

        // Mock the addition repository
        $additionRepoMock = $this->createMock(DocumentRepository::class);
        $additionRepoMock->method('find')
            ->willReturnCallback(function($id) use ($addition) {
                if ($id === 'addition1') return $addition;
                return null;
            });

        // Add the promotion to the pizza
        $promotionRepoMock = $this->createMock(DocumentRepository::class);
        $promotionRepoMock->method('findBy')
            ->willReturnCallback(function($criteria) use ($promotion) {
                if ($criteria['itemId'] === 'pizza2' && $criteria['itemType'] === 'pizza') {
                    return [$promotion];
                }
                return [];
            });

        $pizzaRepoMock = $this->createMock(DocumentRepository::class);
        $this->documentManagerMock->method('getRepository')
            ->willReturnCallback(function($entityClass) use ($pizzaRepoMock, $additionRepoMock, $promotionRepoMock) {
                if ($entityClass === Pizza::class) return $pizzaRepoMock;
                if ($entityClass === Addition::class) return $additionRepoMock;
                if ($entityClass === Promotion::class) return $promotionRepoMock;
                return $this->createMock(DocumentRepository::class);
            });

        $cart = [
            ['item_id' => 'pizza1', 'type' => 'pizza', 'item' => $pizza1, 'size' => 'medium', 'quantity' => 1],
            ['item_id' => 'pizza2', 'type' => 'pizza', 'item' => $pizza2, 'size' => 'large', 'quantity' => 2],
            ['item_id' => 'addition1', 'type' => 'addition', 'item' => $addition, 'quantity' => 2]
        ];

        $expectedTotal =
            15.00 + // 1 medium Margherita
            (22.00 * 0.8 * 2) + // 2 large Supreme with 20% off
            (1.50 * 2); // 2 Garlic Dips

        $actualTotal = $this->priceCalculator->calculateCartTotal($cart);

        $this->assertEqualsWithDelta($expectedTotal, $actualTotal, 0.001);
    }
}