<?php

namespace App\Controller;

use App\Document\Category;
use App\Document\Pizza;
use App\Form\PizzaType;
use App\Repository\PizzaRepository;
use App\Repository\AdditionRepository;
use App\Service\CurrencyProvider;
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
     * @param CurrencyProvider $currencyProvider
     */
    public function __construct(
        private readonly PizzaRepository    $pizzaRepository,
        private readonly AdditionRepository $additionRepository,
        private readonly DocumentManager    $documentManager,
        private readonly CacheInterface     $cache,
        private readonly CurrencyProvider   $currencyProvider
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
        $pizzas = $this->cache->get('pizzas_index', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->pizzaRepository->findAllOrderedByName();
        });

        $additions = $this->cache->get('additions_index', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->additionRepository->findAllOrderedByName();
        });

        $currency = $this->currencyProvider->getCurrency();

        return $this->render('pizza/index.html.twig', [
            'pizzas' => $pizzas,
            'additions' => $additions,
            'currency' => $currency
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

            $this->cache->delete('pizzas_index');

            $this->addFlash('success', 'Pizza created successfully!');
            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('pizza/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param string $id
     * @return Response
     */
    #[Route('/pizza/{id}', name: 'pizza_show', requirements: ['id' => '[0-9a-f]{24}'], methods: ['GET'])]
    public function show(string $id): Response
    {
        $pizza = $this->pizzaRepository->findById($id);

        if (!$pizza) {
            throw $this->createNotFoundException('Pizza not found');
        }

        $currency = $this->currencyProvider->getCurrency();

        return $this->render('pizza/show.html.twig', [
            'pizza' => $pizza,
            'currency' => $currency
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

            $this->cache->delete('pizzas_index');

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

        $this->cache->delete('pizzas_index');

        $this->addFlash('success', 'Pizza deleted successfully!');
        return $this->redirectToRoute('pizza_index');
    }
}
