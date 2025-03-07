<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Order;
use App\Document\Pizza;
use App\Document\Setting;
use App\Enum\OrderStatus;
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
                'success_url' => $this->generateUrl('checkout_success', [
                    'order_id' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $urlGenerator->generate('checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'customer_email' => $order->getEmail(),
                'metadata' => [
                    'order_id' => $order->getId(),
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
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function checkoutSuccess(Request $request): Response
    {
        // Get order ID from URL
        $orderId = $request->query->get('order_id');
        if (!$orderId) {
            $this->addFlash('error', 'Order ID not found');
            return $this->redirectToRoute('pizza_index');
        }

        // Find the order in database
        $order = $this->documentManager->getRepository(Order::class)->find($orderId);
        if (!$order) {
            $this->addFlash('error', 'Order not found');
            return $this->redirectToRoute('pizza_index');
        }

        // Remove cache cart
        $this->cache->delete('user_cart');
        $this->saveCartToCache();

        // Update order status
        $order->setStatus(OrderStatus::PAID);
        $this->documentManager->flush();

        // Prepare items for display
        $items = $this->prepareItemsForDisplay($order);

        $this->addFlash('success', 'Your order has been successfully processed!');

        return $this->render('checkout/success.html.twig', [
            'lineItems' => $items,
            'total' => $order->getTotalPrice(),
            'order' => $order,
            'currency' => $this->currency
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/checkout/fail', name: 'checkout_fail', methods: ['GET'])]
    public function checkoutFail(Request $request): Response
    {
        if ($orderId = $request->query->get('order_id')) {
            $order = $this->documentManager->getRepository(Order::class)->find($orderId);
            if ($order) {
                $order->setStatus(OrderStatus::FAILED);
                $this->documentManager->flush();
            }
        }

        $this->addFlash('error', 'Checkout process failed. Please try again.');
        return $this->redirectToRoute('cart_index');
    }

    /**
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/checkout/cancel', name: 'checkout_cancel', methods: ['GET'])]
    public function checkoutCancel(Request $request): Response
    {
        // Check if there's a current order ID in the session
        if ($orderId = $request->getSession()->get('current_order_id')) {
            $order = $this->documentManager->getRepository(Order::class)->find($orderId);
            if ($order) {
                $order->setStatus(OrderStatus::CANCELLED);
                $this->documentManager->flush();

                // Clear the session
                $request->getSession()->remove('current_order_id');

                // Clear the cart
                $this->cache->delete('user_cart');
            }
        }

        $this->addFlash('info', 'Checkout was cancelled.');
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
     * @param Order $order
     * @return array
     */
    public function prepareItemsForDisplay(Order $order): array
    {
        $items = [];

        // Handle pizzas
        foreach ($order->getPizzas() as $pizza) {
            $items[] = [
                'name' => $pizza['name'],
                'quantity' => 1,
                'price' => [
                    'unit_amount' => $pizza['price'] * 100
                ],
                'amount_total' => $pizza['price'] * 100
            ];
        }

        // Handle additions
        foreach ($order->getAdditions() as $addition) {
            $items[] = [
                'name' => $addition['name'],
                'quantity' => 1,
                'price' => [
                    'unit_amount' => $addition['price'] * 100
                ],
                'amount_total' => $addition['price'] * 100
            ];
        }

        return $items;
    }
}