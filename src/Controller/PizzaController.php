<?php

namespace App\Controller;

use App\Document\Category;
use App\Document\Promotion;
use App\Document\Pizza;
use App\Form\PizzaType;
use App\Repository\PizzaRepository;
use App\Repository\AdditionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
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
     */
    public function __construct(
        private readonly PizzaRepository    $pizzaRepository,
        private readonly AdditionRepository $additionRepository,
        private readonly DocumentManager    $documentManager,
        private readonly CacheInterface     $cache
    ) {
        //
    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/pizza', name: 'pizza_index', methods: ['GET'])]
    public function index(): Response
    {
        $pizzaData = $this->cache->get('pizzas_data', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            $pizzas = $this->pizzaRepository->findAllOrderedByName();
            $pizzaData = [];

            // Get pizza promotions within cache callback
            $pizzaPromotions = $this->documentManager->getRepository(Promotion::class)->findBy([
                'itemType' => 'pizza',
                'active' => true
            ]);

            // Create map for pizzas
            $pizzaPromotionMap = [];
            foreach ($pizzaPromotions as $promotion) {
                if ($promotion->isValid()) {
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

            // Get addition promotions within cache callback
            $additionPromotions = $this->documentManager->getRepository(Promotion::class)->findBy([
                'itemType' => 'addition',
                'active' => true
            ]);

            // Create map for additions
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

        return $this->render('pizza/index.html.twig', [
            'pizzaData' => $pizzaData,
            'additionData' => $additionData,
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
    public function create(Request $request, DocumentManager $dm): Response {
        $categories = $this->documentManager->getRepository(Category::class)->findAll();
        $pizza = new Pizza();

        $form = $this->createForm(PizzaType::class, $pizza, [
            'categories' => $categories
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($pizza);
            $dm->flush();

            $this->cache->delete('pizzas_data');

            $this->addFlash('success', 'Pizza created successfully!');
            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('pizza/create.html.twig', [
            'form' => $form->createView()
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
        $categories = $this->documentManager->getRepository(Category::class)->findAll();

        if (!$pizza) {
            throw $this->createNotFoundException('Pizza not found');
        }

        $form = $this->createForm(PizzaType::class, $pizza, [
            'categories' => $categories,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->flush();

            $this->cache->delete('pizzas_data');
            $this->cache->delete('user_cart');

            $this->addFlash('success', 'Pizza updated successfully!');
            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('pizza/edit.html.twig', [
            'form'  => $form->createView(),
            'pizza' => $pizza,
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws InvalidArgumentException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/{id}', name: 'pizza_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, string $id): Response
    {
        $pizza = $this->pizzaRepository->findById($id);

        if (!$pizza) {
            throw $this->createNotFoundException('Pizza not found');
        }

        if (!$this->isCsrfTokenValid('delete' . $pizza->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('pizza_index');
        }

        $this->documentManager->remove($pizza);
        $this->documentManager->flush();

        $this->cache->delete('pizzas_data');
        $this->cache->delete('user_cart');

        $this->addFlash('success', 'Pizza deleted successfully!');
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
}
