<?php

namespace App\Service;

use InvalidArgumentException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class StripeIntegrationService
{
    public function __construct(private StripeClient $stripeClient)
    {
        //
    }

    /**
     * @param array $cart
     * @param UrlGeneratorInterface $urlGenerator
     * @param string $currency
     * @param string|null $orderId
     * @param string|null $customerEmail
     * @return Session
     * @throws ApiErrorException
     */
    public function createCheckoutSession(array $cart, UrlGeneratorInterface $urlGenerator): Session
    {
        $lineItems = [];
        foreach ($cart as $item) {
            if (!isset($item['type']) || !in_array($item['type'], ['pizza', 'addition'], true)) {
                throw new InvalidArgumentException(sprintf('Invalid item type specified "%s".', $item['type'] ?? 'null'));
            }

            $productName = $item['item_name'];
            if ($item['type'] === 'pizza' && isset($item['size'])) {
                $productName .= ' (' . strtoupper($item['size']) . ')';
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $productName,
                    ],
                    'unit_amount' => (int)($item['price'] * 100), // Price in cents for Stripe
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $sessionConfig = [
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('checkout_success', ['order_id' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $urlGenerator->generate('checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        if ($customerEmail) {
            $sessionConfig['customer_email'] = $customerEmail;
        }

        return $this->stripeClient->checkout->sessions->create($sessionConfig);
    }
}