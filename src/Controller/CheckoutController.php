<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Order;
use App\Document\Pizza;
use App\Document\Setting;
use App\Service\CurrencyProvider;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

class CheckoutController extends AbstractController
{
    private string $currency;

    /**
     * @param CacheInterface $cache
     * @param DocumentManager $documentManager
     * @param CurrencyProvider $currencyProvider
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DocumentManager $documentManager,
        CurrencyProvider $currencyProvider
    )
    {
        $this->currency = $currencyProvider->getCurrency();
    }

    /**
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @return RedirectResponse
     * @throws InvalidArgumentException
     * @throws MongoDBException
     * @throws Throwable
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, UrlGeneratorInterface $urlGenerator): RedirectResponse
    {
        // Retrieve cart from cache
        $cart = $this->cache->get('user_cart', function () {
            return [];
        });

        // If the cart is empty, redirect back to cart
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('cart_index');
        }

        $order = new Order();
        $email = $request->request->get('email');
        $order->setFullName($request->request->get('fullName'))
            ->setEmail(empty($email) ? null : $email)
            ->setPhone($request->request->get('phone'))
            ->setAddress($request->request->get('address'));

        $totalPrice = 0;
        foreach ($cart as $item) {
            if ($item['type'] === 'pizza') {
                $pizza = $this->documentManager->getRepository(Pizza::class)->find($item['item_id']);
                if ($pizza) {
                    $order->addPizza($pizza);
                    $totalPrice += $item['price'] * $item['quantity'];
                }
            } elseif ($item['type'] === 'addition') {
                $addition = $this->documentManager->getRepository(Addition::class)->find($item['item_id']);
                if ($addition) {
                    $order->addAddition($addition);
                    $totalPrice += $item['price'] * $item['quantity'];
                }
            }
        }

        $order->setTotalPrice($totalPrice);

        $this->documentManager->persist($order);
        $this->documentManager->flush();

        // Build Stripe line items from the cart
        $lineItems = [];
        foreach ($cart as $item) {
            if (!isset($item['type']) || !in_array($item['type'], ['pizza', 'addition'], true)) {
                throw new \InvalidArgumentException(sprintf('Invalid type specified "%s".', $item['type'] ?? 'null'));
            }

            // Add item to Stripe line items
            $lineItems = array_map(function ($item) {
                return [
                    'price_data' => [
                        'currency' => $this->currency,
                        'product_data' => [
                            'name' => $item['item_name'],
                        ],
                        'unit_amount' => $item['price'] * 100,
                    ],
                    'quantity' => $item['quantity'],
                ];
            }, $cart);
        }

        try {
            $stripeSecretKey = $this->documentManager->getRepository(Setting::class)->findOneBy([])->getStripeSecretKey();

            if (!$stripeSecretKey) {
                throw new Exception('Stripe Secret Key not configured.');
            }

            $stripe = new StripeClient($stripeSecretKey);

            // Create Stripe Session
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $urlGenerator->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $urlGenerator->generate('checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'metadata' => [
                    'customer_name' => $order->getFullName(),
                    'order_id' => $order->getId(),
                    'description' => 'Pizza Restaurant Owner'
                ],
            ]);

            $this->documentManager->persist($order);
            $this->documentManager->flush();

            return $this->redirect($session->url);
        } catch (Exception $e) {
            return $this->redirectToRoute('cart_index');
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function checkoutSuccess(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');

        if (!$sessionId) {
            // Handle missing session ID (redirect or error message)
            $this->addFlash('error', 'Invalid checkout session.');
            return $this->redirectToRoute('cart_index');
        }

        try {
            $stripe = new StripeClient($this->getStripeSecretKey()); // Get your Stripe secret key
            $session = $stripe->checkout->sessions->retrieve($sessionId);


            // Extract order details from Stripe session
            $lineItems = $session->line_items->data;  // No need for calculateTotal

            // Clear the cart now that the order is complete
            $this->cache->delete('user_cart');
            $this->saveCartToCache(); // Ensure empty cart is saved


            return $this->render('checkout/success.html.twig', [
                'lineItems' => $lineItems,
                'total' => $session->amount_total / 100
            ]);

        } catch (ApiErrorException $e) {
            // Handle Stripe API errors
            $this->addFlash('error', 'Error retrieving order details. Please contact support.');
            return $this->redirectToRoute('cart_index');
        } catch (Exception $e) {
            $this->addFlash('error', 'An unexpected error occurred during checkout.');
            return $this->redirectToRoute('cart_index');
        }
    }

    /**
     * @return Response
     */
    #[Route('/checkout/fail', name: 'checkout_fail', methods: ['GET'])]
    public function checkoutFail(): Response
    {
        $this->addFlash('error', 'Payment was canceled or failed. Please try again.');
        //        TODO add OrderStatus to Fail
        return $this->redirectToRoute('cart_index');
    }

    /**
     * @return Response
     */
    #[Route('/checkout/cancel', name: 'checkout_cancel', methods: ['GET'])]
    public function checkoutCancel(): Response
    {
        //        TODO add OrderStatus to Cancel
        return $this->render('checkout/cancel.html.twig');
    }

    /**
     * @return void
     */
    private function saveCartToCache(): void
    {
        $cart = [];
        $cartItem = $this->cache->getItem('user_cart');
        $cartItem->set($cart);
        $cartItem->expiresAfter(3600);
        $this->cache->save($cartItem);
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getStripeSecretKey(): string
    {
        $stripeSecretKey = $this->documentManager->getRepository(Setting::class)->findOneBy([])->getStripeSecretKey();
        if (!$stripeSecretKey) {
            throw new Exception('Stripe Secret Key not configured.'); // Or handle differently
        }
        return $stripeSecretKey;
    }
}