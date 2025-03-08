<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Pizza;
use App\Interfaces\CartItemInterface;
use App\Service\CurrencyProvider;
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


class CartController extends AbstractController
{
    /**
     * @param CacheInterface $cache
     * @param DocumentManager $documentManager
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DocumentManager $documentManager,
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
            $total += $item['price'] * $item['quantity'];
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $total
        ]);
    }

    /**
     * @param string $pizzaId
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/cart/add/pizza/{pizzaId}', name: 'cart_add_pizza', methods: ['POST'])]
    public function addPizza(string $pizzaId, Request $request): Response
    {
        $cart = $this->getCartFromCache();
        $pizza = $this->documentManager->getRepository(Pizza::class)->find($pizzaId);

        if (!$pizza) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $quantity = (int)$request->get('quantity', 1);
        $this->addItemToCart($cart, $pizza, $quantity);

        $this->saveCartToCache($cart);
        return $this->redirectToRoute('pizza_index');
    }


    /**
     * @param string $additionId
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
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
        $this->addItemToCart($cart, $addition, $quantity);

        $this->saveCartToCache($cart);
        return $this->redirectToRoute('pizza_index');
    }


    /**
     * @param Request $request
     * @param string $itemType
     * @param string $itemId
     * @return Response
     */
    #[Route('/cart/remove/{itemType}/{itemId}', name: 'cart_remove', methods: ['POST'])]
    public function remove(Request $request, string $itemType, string $itemId): Response
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
     * @param array $cart
     * @param CartItemInterface $item
     * @param int $quantity
     * @return void
     */
    private function addItemToCart(array &$cart, CartItemInterface $item, int $quantity): void
    {
        $found = false;
        foreach ($cart as &$cartItem) {
            if ($cartItem['item_id'] === $item->getId() && $cartItem['type'] === $item->getCartType()) {
                $cartItem['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $cart[] = [
                'item_id' => $item->getId(),
                'item_name' => $item->getName(),
                'quantity' => $quantity,
                'price' => $item->getPrice(),
                'type' => $item->getCartType()
            ];
        }
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