<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Order;
use App\Document\Pizza;
use App\Enum\OrderStatus;
use App\Service\CurrencyProvider;
use App\Service\StripeIntegrationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Cache\InvalidArgumentException;
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
     * @param StripeIntegrationService $stripeService
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DocumentManager $documentManager,
        CurrencyProvider $currencyProvider,
        private readonly StripeIntegrationService $stripeService
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
        $cart = $this->cache->get('user_cart', function () {
            return [];
        });

        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('cart_index');
        }

        $order = new Order();
        $order->setFullName($request->request->get('fullName'))
            ->setEmail($request->request->get('email'))
            ->setPhone($request->request->get('phone'))
            ->setAddress($request->request->get('address'));

        $totalPrice = 0;
        foreach ($cart as $item) {
            if ($item['type'] === 'pizza') {
                $pizza = $this->documentManager->getRepository(Pizza::class)->find($item['item_id']);
                if ($pizza) {
                    $order->addPizza(
                        $pizza,
                        $item['quantity'],
                        $item['size'],
                        $item['price']
                    );
                    $totalPrice += $item['price'] * $item['quantity'];
                }
            } elseif ($item['type'] === 'addition') {
                $addition = $this->documentManager->getRepository(Addition::class)->find($item['item_id']);
                if ($addition) {
                    $order->addAddition($addition, $item['quantity']);
                    $totalPrice += $item['price'] * $item['quantity'];
                }
            }
        }

        $order->setTotalPrice($totalPrice);
        $this->documentManager->persist($order);
        $this->documentManager->flush();

        try {
            $session = $this->stripeService->createCheckoutSession(
                $cart,
                $urlGenerator,
                $this->currency,
                $order->getId(),
                $order->getEmail()
            );

            $order->setStripeSessionId($session->id);
            $order->setStatus(OrderStatus::PENDING);
            $this->documentManager->flush();

            return $this->redirect($session->url);

        } catch (Throwable $e) {
            $this->addFlash('error', 'Payment processing error: ' . $e->getMessage());
            return $this->redirectToRoute('cart_index');
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/checkout/success', name: 'checkout_success', methods: ['GET'])]
    public function checkoutSuccess(Request $request): Response
    {
        $orderId = $request->query->get('order_id');
        $order = $this->documentManager->getRepository(Order::class)->find($orderId);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $order->setStatus(OrderStatus::PAID);
        $this->cache->delete('user_cart');
        $this->documentManager->flush();

        $lineItems = [];

        // Convert pizzas to line items
        foreach ($order->getPizzas() as $pizza) {
            $lineItems[] = [
                'name' => $pizza['name'],
                'quantity' => $pizza['quantity'],
                'price' => [
                    'unit_amount' => $pizza['price'] * 100 // Convert to cents
                ],
                'amount_total' => $pizza['price'] * $pizza['quantity'] * 100,
                'metadata' => [
                    'size' => $pizza['size'][0] ?? null  // Take first size from the array
                ]
            ];
        }

        foreach ($order->getAdditions() as $addition) {
            $lineItems[] = [
                'name' => $addition['name'],
                'quantity' => $addition['quantity'],
                'price' => [
                    'unit_amount' => $addition['price'] * 100
                ],
                'amount_total' => $addition['price'] * $addition['quantity'] * 100
            ];
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
            'lineItems' => $lineItems,
            'currency' => $this->currency,
            'total' => $order->getTotalPrice()
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

                $request->getSession()->remove('current_order_id');

                $this->cache->delete('user_cart');
            }
        }

        $this->addFlash('info', 'Checkout was cancelled.');
        return $this->render('checkout/cancel.html.twig');
    }

    /**
     * @param Order $order
     * @return array
     */
    public function prepareItemsForDisplay(Order $order): array
    {
        $groupedItems = [];

        foreach ($order->getPizzas() as $pizza) {
            $size = is_array($pizza['size']) ? $pizza['size'][0] : ($pizza['size'] ?? 'medium');
            $key = sprintf('%s_%s_%s', $pizza['name'], $size, $pizza['price']);

            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'name' => $pizza['name'] . ' (' . strtoupper($size) . ')',
                    'quantity' => 0,
                    'price' => [
                        'unit_amount' => $pizza['price'] * 100
                    ],
                    'amount_total' => 0
                ];
            }
            $groupedItems[$key]['quantity']++;
            $groupedItems[$key]['amount_total'] += $pizza['price'] * 100;
        }

        foreach ($order->getAdditions() as $addition) {
            $key = 'addition_' . $addition['name'];
            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'name' => $addition['name'],
                    'quantity' => 0,
                    'price' => [
                        'unit_amount' => $addition['price'] * 100
                    ],
                    'amount_total' => 0
                ];
            }
            $groupedItems[$key]['quantity']++;
            $groupedItems[$key]['amount_total'] += $addition['price'] * 100;
        }

        return array_values($groupedItems);
    }
}