<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Pizza;
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
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly DocumentManager $documentManager
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
        // Fetch cart from cache
        $cart = $this->cache->get('user_cart', function () {
            return [];
        });

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
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
        $cart = $this->getCartFromCache();  // Helper function (see below)

        $pizza = $this->documentManager->getRepository(Pizza::class)->find($pizzaId);
        if (!$pizza) {
//            return $this->json(['status' => 'error', 'message' => 'Pizza not found'], 404);
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $quantity = (int)$request->get('quantity', 1);

        $this->addItemToCart($cart, 'pizza', $pizzaId, $pizza->getName(), $quantity, $pizza->getPrice());

        $this->saveCartToCache($cart);

//        return $this->json(['status' => 'success', 'cart' => $cart]);
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
//            return $this->json(['status' => 'error', 'message' => 'Addition not found'], 404);
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        $quantity = (int)$request->get('quantity', 1);

        $this->addItemToCart($cart, 'addition', $additionId, $addition->getName(), $quantity, $addition->getPrice());

        $this->saveCartToCache($cart);

//        return $this->json(['status' => 'success', 'cart' => $cart]);
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
     * @param $cart
     * @param $type
     * @param $itemId
     * @param $itemName
     * @param $quantity
     * @param $price
     * @return void
     */
    private function addItemToCart(&$cart, $type, $itemId, $itemName, $quantity, $price): void
    {
        $found = false;
        foreach ($cart as &$item) {
            if ($item['type'] === $type && $item['item_id'] === $itemId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $cart[] = [
                'type' => $type,
                'item_id' => $itemId,
                'item_name' => $itemName,
                'quantity' => $quantity,
                'price' => $price
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