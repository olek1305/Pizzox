<?php

namespace App\Controller;

use App\Document\Category;
use App\Document\Promotion;
use App\Document\Pizza;
use App\Document\Setting;
use App\Repository\PizzaRepository;
use App\Repository\AdditionRepository;
use App\Repository\SettingRepository;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


final class PizzaController extends AbstractController
{
    /**
     * @param PizzaRepository $pizzaRepository
     * @param AdditionRepository $additionRepository
     * @param DocumentManager $documentManager
     * @param CacheInterface $cache
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        private readonly PizzaRepository    $pizzaRepository,
        private readonly AdditionRepository $additionRepository,
        private readonly DocumentManager    $documentManager,
        private readonly CacheInterface     $cache,
        private readonly SettingRepository  $settingRepository,
    ) {
        //
    }

    /**
     * @return Response
     * @throws InvalidArgumentException|Throwable
     */
    #[Route('/pizza', name: 'pizza_index', methods: ['GET'])]
    public function index(): Response
    {
        $pizzaData = $this->cache->get('pizzas_data', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            $pizzas = $this->pizzaRepository->findAllOrderedByName();
            $pizzaData = [];

            // Get pizza promotions within a cache callback
            $pizzaPromotions = $this->documentManager->getRepository(Promotion::class)->findBy([
                'itemType' => 'pizza',
                'active' => true
            ]);

            // Create a map for pizzas
            $pizzaPromotionMap = [];
            foreach ($pizzaPromotions as $promotion) {
                // Check if the promotion is valid based on the date
                $now = new DateTime();
                if ($promotion->isValid() && $promotion->getExpiresAt() > $now) {
                    $pizzaPromotionMap[$promotion->getItemId()] = $promotion;
                }
            }

            foreach ($pizzas as $pizza) {
                $pizzaItem = [
                    'id' => $pizza->getId(),
                    'name' => $pizza->getName(),
                    'price' => $pizza->getPrice(),
                    'priceSmall' => $pizza->getPriceSmall(),
                    'priceLarge' => $pizza->getPriceLarge(),
                    'toppings' => $pizza->getToppings(),
                ];

                // Add category if exists
                $pizzaData = $this->getArr($pizza, $pizzaItem, $pizzaPromotionMap, $pizzaData);
            }

            return $pizzaData;
        });

        $additionData = $this->cache->get('additions_data', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $additions = $this->additionRepository->findAllOrderedByName();
            $additionData = [];

            // Get addition promotions within a cache callback
            $additionPromotions = $this->documentManager->getRepository(Promotion::class)->findBy([
                'itemType' => 'addition',
                'active' => true
            ]);

            // Create a map for additions
            $additionPromotionMap = [];
            foreach ($additionPromotions as $promotion) {
                if ($promotion->isValid()) {
                    $additionPromotionMap[$promotion->getItemId()] = $promotion;
                }
            }

            foreach ($additions as $addition) {
                $additionItem = [
                    'id' => $addition->getId(),
                    'name' => $addition->getName(),
                    'price' => $addition->getPrice(),
                    'description' => $addition->getDescription(),
                    'active' => $addition->isActive(),
                ];

                // Add category if exists
                $additionData = $this->getArr($addition, $additionItem, $additionPromotionMap, $additionData);
            }

            return $additionData;
        });

        // Get footer settings from the database
        $settings = $this->settingRepository->findLastOrCreate();
        $footerSettings = [
            'restaurantName' => $settings->getRestaurantName(),
            'restaurantAddress' => $settings->getRestaurantAddress(),
            'latitude' => $settings->getLatitude(),
            'longitude' => $settings->getLongitude(),
            'mapboxToken' => $settings->getMapboxToken()
        ];

        return $this->render('pizza/index.html.twig', [
            'pizzaData' => $pizzaData,
            'additionData' => $additionData,
            'footerSettings' => $footerSettings
        ]);
    }

    /**
     * @param Request $request
     * @param DocumentManager $dm
     * @return Response
     * @throws InvalidArgumentException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/create', name: 'pizza_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, DocumentManager $dm): Response
    {
        $categories = $this->documentManager->getRepository(Category::class)->findAll();

        // Format categories for Vue component
        $categoriesForVue = array_map(function($category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }, $categories);

        // Get settings for price calculation
        $settings = $this->documentManager->getRepository(Setting::class)->findLastOrCreate();
        $priceSettings = [
            'calculationType' => $settings->getPizzaPriceCalculationType(),
            'smallModifier' => $settings->getSmallSizeModifier(),
            'largeModifier' => $settings->getLargeSizeModifier(),
        ];

        // Handle JSON request from Vue component
        if ($request->isXmlHttpRequest() || $request->getContentTypeFormat() === 'json') {
            try {
                // Get JSON data from request
                $data = json_decode($request->getContent(), true);

                if (!$data) {
                    return $this->json(['error' => 'Invalid JSON data'], 400);
                }

                // Input validation
                $validationErrors = [];

                // Validate name
                if (!isset($data['name']) || trim($data['name']) === '') {
                    $validationErrors['name'] = 'Pizza name is required';
                }

                // Validate price - this is critical for medium pizza
                if (!isset($data['price']) || (float)$data['price'] <= 0) {
                    $validationErrors['price'] = 'Medium pizza price must be greater than 0';
                }

                // If validation fails, return error response
                if (!empty($validationErrors)) {
                    return $this->json([
                        'error' => 'Validation failed',
                        'validationErrors' => $validationErrors
                    ], 400);
                }

                $pizza = new Pizza();
                $pizza->setName($data['name']);
                $pizza->setPrice((float)$data['price']);

                // Handle price fields - set to null if empty or zero
                if (isset($data['priceSmall'])) {
                    if ($data['priceSmall'] === 0) {
                        $pizza->setPriceSmall(null);
                    } else {
                        $pizza->setPriceSmall((float)$data['priceSmall']);
                    }
                }

                $data = $this->getData($data, $pizza);

                // Handle category
                if (!empty($data['category'])) {
                    $category = $this->documentManager->getRepository(Category::class)->find($data['category']);
                    if ($category) {
                        $pizza->setCategory($category);
                    }
                }

                // Save to a database
                $dm->persist($pizza);
                $dm->flush();

                $this->cache->delete('pizzas_data');

                $this->addFlash('success', 'flash.pizza.created');
                return $this->json([
                    'redirect' => $this->generateUrl('pizza_index'),
                ]);
            } catch (Exception $e) {
                return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
        }

        // For regular GET requests, render the template with a Vue component
        return $this->render('pizza/create.html.twig', [
            'categories' => $categoriesForVue,
            'priceSettings' => $priceSettings
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @param DocumentManager $dm
     * @return Response
     * @throws InvalidArgumentException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/{id}/edit', name: 'pizza_edit', requirements: ['id' => '[0-9a-f]{24}'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, string $id, DocumentManager $dm): Response
    {
        $pizza = $this->pizzaRepository->findById($id);
        if (!$pizza) {
            return $request->isXmlHttpRequest() || $request->getContentTypeFormat() === 'json'
                ? $this->json(['error' => 'Pizza not found'], 404)
                : throw $this->createNotFoundException('Pizza not found');
        }

        $categories = $this->documentManager->getRepository(Category::class)->findAll();

        // JSON (Vue) submission
        if ($request->isXmlHttpRequest() || $request->getContentTypeFormat() === 'json') {
            try {
                $data = json_decode($request->getContent(), true);
                if (!$data) {
                    return $this->json(['error' => 'Invalid JSON data'], 400);
                }

                $validationErrors = [];

                if (!isset($data['name']) || trim($data['name']) === '') {
                    $validationErrors['name'] = 'Pizza name is required';
                }

                if (!isset($data['price']) || (float)$data['price'] <= 0) {
                    $validationErrors['price'] = 'Medium pizza price must be greater than 0';
                }

                if (!empty($validationErrors)) {
                    return $this->json([
                        'error' => 'Validation failed',
                        'validationErrors' => $validationErrors
                    ], 400);
                }

                $pizza->setName($data['name']);
                $pizza->setPrice((float)$data['price']);

                if (isset($data['priceSmall'])) {
                    $pizza->setPriceSmall((float)$data['priceSmall'] ?: null);
                }

                if (isset($data['priceLarge'])) {
                    $pizza->setPriceLarge((float)$data['priceLarge'] ?: null);
                }

                if (isset($data['category'])) {
                    $category = !empty($data['category'])
                        ? $this->documentManager->getRepository(Category::class)->find($data['category'])
                        : null;
                    $pizza->setCategory($category);
                }

                $dm->flush();

                $this->cache->delete('pizzas_data');
                $this->cache->delete('user_cart');

                $this->addFlash('success', 'flash.pizza.updated');
                return $this->json([
                    'redirect' => $this->generateUrl('pizza_index'),
                ]);
            } catch (\Exception $e) {
                return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
        }

        // Format categories for Vue
        $categoriesForVue = array_map(fn($category) => [
            'id' => $category->getId(),
            'name' => $category->getName()
        ], $categories);

        $pizzaData = [
            'id' => $pizza->getId(),
            'name' => $pizza->getName(),
            'price' => $pizza->getPrice(),
            'priceSmall' => $pizza->getPriceSmall(),
            'priceLarge' => $pizza->getPriceLarge(),
            'category' => $pizza->getCategory()?->getId()
        ];

        return $this->render('pizza/edit.html.twig', [
            'pizza' => $pizzaData,
            'categories' => $categoriesForVue,
        ]);
    }

    /**
     * @param string $id
     * @return Response
     * @throws InvalidArgumentException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/{id}/delete', name: 'pizza_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): Response
    {
        $pizza = $this->pizzaRepository->findById($id);

        if (!$pizza) {
            throw $this->createNotFoundException('Pizza not found');
        }

        // Admin role is already checked by the IsGranted attribute
        $this->documentManager->remove($pizza);
        $this->documentManager->flush();

        $this->cache->delete('pizzas_data');
        $this->cache->delete('user_cart');

        $this->addFlash('success', 'flash.pizza.deleted');
        return $this->redirectToRoute('pizza_index');
    }

    /**
     * @param mixed $addition
     * @param array $additionItem
     * @param array $additionPromotionMap
     * @param array $additionData
     * @return array
     */
    public function getArr(mixed $addition, array $additionItem, array $additionPromotionMap, array $additionData): array
    {
        $category = $addition->getCategory();
        if ($category) {
            $additionItem['category'] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        } else {
            $additionItem['category'] = null;
        }

        // Add promotion if exists
        if (isset($additionPromotionMap[$addition->getId()])) {
            $promotion = $additionPromotionMap[$addition->getId()];
            $additionItem['coupon'] = [
                'id' => $promotion->getId(),
                'type' => $promotion->getType(),
                'discount' => $promotion->getDiscount(),
                'expiresAt' => $promotion->getExpiresAt() ? $promotion->getExpiresAt()->format('Y-m-d H:i:s') : null,
            ];
        }

        $additionData[] = $additionItem;
        return $additionData;
    }

    /**
     * @param mixed $data
     * @param Pizza $pizza
     * @return mixed
     */
    public function getData(mixed $data, Pizza $pizza): mixed
    {
        if (isset($data['priceLarge'])) {
            if ($data['priceLarge'] === 0) {
                $pizza->setPriceLarge(null);
            } else {
                $pizza->setPriceLarge((float)$data['priceLarge']);
            }
        }

        // Handle toppings array
        if (isset($data['toppings']) && is_array($data['toppings'])) {
            // Filter out empty toppings
            $toppings = array_filter($data['toppings'], function ($item) {
                return !empty($item);
            });
            $pizza->setToppings(array_values($toppings)); // Re-index array
        }
        return $data;
    }
}
