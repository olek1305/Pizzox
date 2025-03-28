<?php

namespace App\Tests\Service;

use App\Document\Addition;
use App\Document\Pizza;
use App\Document\Promotion;
use App\Service\PriceCalculatorService;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;
use Stripe\StripeClient;

class PriceCalculationTest extends TestCase
{
    private DocumentManager $documentManagerMock;
    private PriceCalculatorService $priceCalculator;
    private StripeClient $stripeMock;

    protected function setUp(): void
    {
        $this->documentManagerMock = $this->createMock(DocumentManager::class);
        $this->stripeMock = $this->createMock(StripeClient::class);

        // Add a mock for the SettingRepository
        $settingRepositoryMock = $this->createMock(\App\Repository\SettingRepository::class);

        // Now pass both required arguments
        $this->priceCalculator = new PriceCalculatorService(
            $this->documentManagerMock,
            $settingRepositoryMock
        );
    }

    public function testBasicPizzaPriceCalculation(): void
    {
        // Create test pizza
        $pizza = new Pizza();
        $pizza->setName('Margherita');
        $pizza->setPriceSmall(10.00);
        $pizza->setPriceMedium(15.00);
        $pizza->setPriceLarge(20.00);

        // Test quantity and size calculations
        $this->assertEquals(10.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'small'));
        $this->assertEquals(20.00, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'small'));
        $this->assertEquals(15.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'medium'));
        $this->assertEquals(20.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'large'));
        $this->assertEquals(40.00, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'large'));
    }

    public function testPizzaWithPercentagePromotion(): void
    {
        // Create test pizza
        $pizza = new Pizza();
        $pizza->setName('Pepperoni');
        $pizza->setPriceSmall(12.00);
        $pizza->setPriceMedium(18.00);
        $pizza->setPriceLarge(24.00);
        $pizza->setId('pizza123');

        // Create promotion (20% off)
        $promotion = new Promotion();
        $promotion->setItemId('pizza123');
        $promotion->setItemType('pizza');
        $promotion->setType('percentage');
        $promotion->setDiscount(20);
        $promotion->setExpiresAt(new DateTime('+1 day'));
        $promotion->setActive(true);

        // Add the promotion to the pizza
        $pizza->promotion = $promotion;

        // Test discounted prices
        $this->assertEquals(9.60, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'small')); // 12.00 - 20%
        $this->assertEquals(14.40, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'medium')); // 18.00 - 20%
        $this->assertEquals(19.20, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'large')); // 24.00 - 20%
        $this->assertEquals(38.40, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'large')); // 48.00 - 20%
    }

    public function testPizzaWithFixedPromotion(): void
    {
        // Create test pizza
        $pizza = new Pizza();
        $pizza->setName('Hawaiian');
        $pizza->setPriceSmall(11.00);
        $pizza->setPriceMedium(16.00);
        $pizza->setPriceLarge(21.00);
        $pizza->setId('pizza456');

        // Create promotion (3.00 off)
        $promotion = new Promotion();
        $promotion->setItemId('pizza456');
        $promotion->setItemType('pizza');
        $promotion->setType('fixed');
        $promotion->setDiscount(3.00);
        $promotion->setExpiresAt(new DateTime('+1 day'));
        $promotion->setActive(true);

        // Add the promotion to the pizza
        $pizza->promotion = $promotion;

        // Test discounted prices
        $this->assertEquals(8.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'small')); // 11.00 - 3.00
        $this->assertEquals(13.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'medium')); // 16.00 - 3.00
        $this->assertEquals(18.00, $this->priceCalculator->calculatePizzaPrice($pizza, 1, 'large')); // 21.00 - 3.00
        $this->assertEquals(36.00, $this->priceCalculator->calculatePizzaPrice($pizza, 2, 'large')); // 42.00 - 6.00 (3.00 per pizza)
    }

    public function testAdditionPriceCalculation(): void
    {
        // Create test addition
        $addition = new Addition();
        $addition->setName('Extra Cheese');
        $addition->setPrice(2.50);

        // Test quantity calculations
        $this->assertEquals(2.50, $this->priceCalculator->calculateAdditionPrice($addition, 1));
        $this->assertEquals(5.00, $this->priceCalculator->calculateAdditionPrice($addition, 2));
        $this->assertEquals(7.50, $this->priceCalculator->calculateAdditionPrice($addition, 3));
    }

    public function testAdditionWithPromotion(): void
    {
        // Create test addition
        $addition = new Addition();
        $addition->setName('Garlic Bread');
        $addition->setPrice(4.00);
        $addition->setId('addition789');

        // Create promotion (25% off)
        $promotion = new Promotion();
        $promotion->setItemId('addition789');
        $promotion->setItemType('addition');
        $promotion->setType('percentage');
        $promotion->setDiscount(25);
        $promotion->setExpiresAt(new DateTime('+1 day'));
        $promotion->setActive(true);

        // Add the promotion to the addition
        $addition->promotion = $promotion;

        // Test discounted prices
        $this->assertEquals(3.00, $this->priceCalculator->calculateAdditionPrice($addition, 1)); // 4.00 - 25%
        $this->assertEquals(6.00, $this->priceCalculator->calculateAdditionPrice($addition, 2)); // 8.00 - 25%
    }

    public function testMultipleItemsWithMixedPromotions(): void
    {
        // Create pizzas
        $pizza1 = new Pizza();
        $pizza1->setName('Margherita');
        $pizza1->setPriceMedium(15.00);
        $pizza1->setId('pizza1');

        $pizza2 = new Pizza();
        $pizza2->setName('Supreme');
        $pizza2->setPriceLarge(22.00);
        $pizza2->setId('pizza2');

        // Create promotion (20% off)
        $promotion = new Promotion();
        $promotion->setItemId('pizza2');
        $promotion->setItemType('pizza');
        $promotion->setType('percentage');
        $promotion->setDiscount(20);
        $promotion->setExpiresAt(new DateTime('+1 day'));
        $promotion->setActive(true);

        // Add the promotion to the pizza
        $pizza2->promotion = $promotion;

        // Create addition
        $addition = new Addition();
        $addition->setName('Garlic Dip');
        $addition->setPrice(1.50);
        $addition->setId('addition1');

        // Calculate total
        $cart = [
            ['type' => 'pizza', 'item' => $pizza1, 'size' => 'medium', 'quantity' => 1],
            ['type' => 'pizza', 'item' => $pizza2, 'size' => 'large', 'quantity' => 2],
            ['type' => 'addition', 'item' => $addition, 'quantity' => 2]
        ];

        $expectedTotal =
            15.00 + // 1 medium Margherita
            (22.00 * 0.8 * 2) + // 2 large Supreme with 20% off
            (1.50 * 2); // 2 Garlic Dips

        $this->assertEquals($expectedTotal, $this->priceCalculator->calculateCartTotal($cart));
    }

    public function testStripeIntegration(): void
    {
        // This test would verify that Stripe receives the correct price
        // You'd need to mock the Stripe API calls and verify the amount sent

        // Create a sample cart
        $pizza = new Pizza();
        $pizza->setName('Veggie');
        $pizza->setPriceMedium(14.00);

        $cart = [
            ['type' => 'pizza', 'item' => $pizza, 'size' => 'medium', 'quantity' => 2]
        ];

        $expectedTotal = 28.00; // 2 medium pizzas at 14.00 each

        // Mock Stripe checkout session creation
        $checkoutSessionMock = $this->createMock(\Stripe\Checkout\Session::class);
        $checkoutSessionMock->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($params) use ($expectedTotal) {
                // Verify the amount sent to Stripe matches our calculation
                // Note: Stripe uses cents/the smallest currency unit
                $lineItem = $params['line_items'][0];
                return $lineItem['price_data']['unit_amount'] === (int)($expectedTotal * 100);
            }))
            ->willReturn((object)['id' => 'cs_test_123']);

        // Here is where you'd verify the price calculation is correctly sent to Stripe
        $stripePriceCalc = new StripeIntegrationService($this->priceCalculator, $this->stripeMock);
        $session = $stripePriceCalc->createCheckoutSession($cart);

        $this->assertEquals('cs_test_123', $session->id);
    }
}