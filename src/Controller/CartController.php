<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Promotion;
use App\Document\Pizza;
use App\Repository\SettingRepository;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;


class CartController extends AbstractController
{
    /**
     * @param CacheInterface $cache
     * @param DocumentManager $documentManager
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DocumentManager $documentManager,
        private readonly SettingRepository $settingRepository
    )
    {
        //
    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function index(): Response
    {
        $cart = $this->cache->get('user_cart', function () {
            return [];
        });

        $total = 0;

        foreach ($cart as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $total += $itemTotal;
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total,
        ]);
    }

    /**
     * @param string $pizzaId
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws Throwable
     */
    #[Route('/cart/add/pizza/{pizzaId}', name: 'cart_add_pizza', methods: ['POST'])]
    public function addPizza(string $pizzaId, Request $request): Response
    {
        $cart = $this->getCartFromCache();
        $pizza = $this->documentManager->getRepository(Pizza::class)->find($pizzaId);

        if (!$pizza) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $size = $request->request->get('size', 'medium');

        // Check if this pizza with this size already exists in cart
        $existingItem = null;
        foreach ($cart as $key => $item) {
            if ($item['type'] === 'pizza' &&
                $item['item_id'] === $pizzaId &&
                $item['size'] === $size) {
                $existingItem = $key;
                break;
            }
        }

        $priceInfo = $this->calculatePizzaPrice($pizza, $size);

        if ($existingItem !== null) {
            $cart[$existingItem]['quantity']++;
        } else {
            $cartItem = [
                'type' => 'pizza',
                'item_id' => $pizzaId,
                'item_name' => $pizza->getName(),
                'price' => $priceInfo['price'],
                'quantity' => 1,
                'size' => $size
            ];

            if ($priceInfo['price'] < $priceInfo['original_price']) {
                $cartItem['original_price'] = $priceInfo['original_price'];
            }

            $cart[] = $cartItem;
        }

        $this->saveCartToCache($cart);
        return $this->redirectToRoute('pizza_index');
    }

    /**
     * @param Pizza $pizza
     * @param string $size
     * @return array
     * @throws Throwable
     */
    private function calculatePizzaPrice(Pizza $pizza, string $size): array
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
            'original_price' => $originalPrice,
            'price' => $discountedPrice
        ];
    }

    /**
     * @param string $additionId
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws Throwable
     */
    #[Route('/cart/add/addition/{additionId}', name: 'cart_add_addition', methods: ['POST'])]
    public function addAddition(string $additionId, Request $request): Response
    {
        $cart = $this->getCartFromCache();
        $addition = $this->documentManager->getRepository(Addition::class)->find($additionId);

        if (!$addition) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $quantity = (int)$request->get('quantity', 1);

        // Check if this addition already exists in cart
        $existingItem = null;
        foreach ($cart as $key => $item) {
            if ($item['type'] === 'addition' && $item['item_id'] === $additionId) {
                $existingItem = $key;
                break;
            }
        }

        // Calculate the price taking into account promotions
        $priceInfo = $this->calculateAdditionPrice($addition);

        if ($existingItem !== null) {
            $cart[$existingItem]['quantity'] += $quantity;
        } else {
            $cartItem = [
                'type' => 'addition',
                'item_id' => $additionId,
                'item_name' => $addition->getName(),
                'price' => $priceInfo['price'],
                'quantity' => $quantity
            ];

            if ($priceInfo['price'] < $priceInfo['original_price']) {
                $cartItem['original_price'] = $priceInfo['original_price'];
            }

            $cart[] = $cartItem;
        }

        $this->saveCartToCache($cart);
        return $this->redirectToRoute('pizza_index');
    }

    /**
     * @param Addition $addition
     * @return array
     * @throws Throwable
     */
    private function calculateAdditionPrice(Addition $addition): array
    {
        $basePrice = $addition->getPrice();
        $originalPrice = $basePrice;

        $promotions = $this->documentManager->getRepository(Promotion::class)->findBy([
            'itemType' => 'addition',
            'itemId' => $addition->getId(),
            'active' => true
        ]);

        $finalPrice = $basePrice;
        $now = new DateTime();

        foreach ($promotions as $promotion) {
            if ($promotion->getExpiresAt() && $promotion->getExpiresAt() < $now) {
                continue;
            }
            if ($promotion->getUsageCount() >= $promotion->getUsageLimit()) {
                continue;
            }

            $discountPrice = $basePrice;
            if ($promotion->getType() === 'percentage') {
                $discountPrice = $basePrice * (1 - ($promotion->getDiscount() / 100));
            } elseif ($promotion->getType() === 'fixed') {
                $discountPrice = $basePrice - $promotion->getDiscount();
                $discountPrice = max(0, $discountPrice);
            }

            if ($discountPrice < $finalPrice) {
                $finalPrice = $discountPrice;
            }
        }

        return [
            'price' => $finalPrice,
            'original_price' => $originalPrice
        ];
    }

    /**
     * @param string $itemType
     * @param string $itemId
     * @return Response
     */
    #[Route('/cart/remove/{itemType}/{itemId}', name: 'cart_remove', methods: ['POST'])]
    public function remove(string $itemType, string $itemId): Response
    {
        $cart = $this->getCartFromCache();

        foreach ($cart as $key => $cartItem) {
            if ($cartItem['type'] === $itemType && $cartItem['item_id'] === $itemId) {
                unset($cart[$key]);
                break;
            }
        }

        $this->saveCartToCache($cart);
        return $this->redirectToRoute('cart_index');
    }

    /**
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/cart/clear', name: 'cart_clear', methods: ['POST'])]
    public function clearCart(): JsonResponse
    {
        $this->cache->delete('user_cart');

        return new JsonResponse(['status' => 'success']);
    }

    /**
     * @return array
     */
    private function getCartFromCache(): array
    {
        $cartItem = $this->cache->getItem('user_cart');
        return $cartItem->isHit() ? $cartItem->get() : [];
    }

    /**
     * @param array $cart
     * @return void
     */
    private function saveCartToCache(array $cart): void
    {
        $cartItem = $this->cache->getItem('user_cart');
        $cartItem->set($cart);
        $cartItem->expiresAfter(3600);
        $this->cache->save($cartItem);
    }
}