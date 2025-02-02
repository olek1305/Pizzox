<?php

namespace App\Controller;

use App\Document\Pizza;
use App\Form\PizzaType;
use App\Repository\PizzaRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class PizzaController extends AbstractController
{
    /**
     * @var PizzaRepository
     */
    private PizzaRepository $pizzaRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var DocumentManager
     */
    private DocumentManager $documentManager;

    /**
     * @param PizzaRepository $pizzaRepository
     * @param LoggerInterface $logger
     * @param DocumentManager $documentManager
     */
    public function __construct(PizzaRepository $pizzaRepository, LoggerInterface $logger, DocumentManager $documentManager)
    {
        $this->pizzaRepository = $pizzaRepository;
        $this->logger = $logger;
        $this->documentManager = $documentManager;
    }

    /**
     * @return Response
     * @throws MongoDBException
     */
    #[Route('/pizza', name: 'pizza_index', methods: ['GET'])]
    public function index(): Response
    {
        $pizzas = $this->pizzaRepository->findAllOrderedByName();

        return $this->render('pizza/index.html.twig', [
            'pizzas' => $pizzas,
        ]);
    }

    /**
     * @param Request $request
     * @param DocumentManager $dm
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/create', name: 'pizza_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, DocumentManager $dm): Response {
        $pizza = new Pizza();

        $form = $this->createForm(PizzaType::class, $pizza);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $toppingsArray = $form->get('toppings')->getData();
            $pizza->setToppings($toppingsArray);

            $dm->persist($pizza);
            $dm->flush();

            $this->addFlash('success', 'Pizza created successfully!');
            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('pizza/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string $id
     * @return Response
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/pizza/{id}', name: 'pizza_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $this->logger->debug('Attempting to find pizza by ID', ['id' => $id]);


        $pizza = $this->pizzaRepository->findById($id);

        if (!$pizza) {
            $this->logger->error('Pizza not found', ['id' => $id]);
            throw $this->createNotFoundException('Pizza not found');
        }

        return $this->render('pizza/show.html.twig', [
            'pizza' => $pizza,
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/{id}/edit', name: 'pizza_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, string $id): Response
    {
        $pizza = $this->pizzaRepository->findById($id);

        if (!$pizza) {
            $this->logger->error('Pizza not found', ['id' => $id]);
            throw $this->createNotFoundException('Pizza not found');
        }

        $form = $this->createForm(PizzaType::class, $pizza);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->pizzaRepository->save($pizza);

            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('pizza/edit.html.twig', [
            'pizza' => $pizza,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/pizza/{id}', name: 'pizza_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, string $id): Response
    {
        if ($request->isMethod('DELETE')) {
            $pizza = $this->pizzaRepository->findById($id);

            if (!$pizza) {
                $this->logger->error('Pizza not found', ['id' => $id]);
                throw $this->createNotFoundException('Pizza not found');
            }

            $this->pizzaRepository->remove($pizza);
        }

        return $this->redirectToRoute('pizza_index');
    }
}
