<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Category;
use App\Form\AdditionType;
use App\Repository\AdditionRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

final class AdditionController extends AbstractController
{

    /**
     * @param AdditionRepository $additionRepository
     * @param DocumentManager $documentManager
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly AdditionRepository $additionRepository,
        private readonly DocumentManager $documentManager,
        private readonly CacheInterface $cache
    ) {
        //
    }

    /**
     * @param Request $request
     * @param DocumentManager $dm
     * @return Response
     * @throws InvalidArgumentException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/addition/create', name: 'addition_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, DocumentManager $dm): Response
    {
        $categories = $this->documentManager->getRepository(Category::class)->findAll();
        $addition = new Addition();

        $form = $this->createForm(AdditionType::class, $addition, [
            'categories' => $categories
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($addition);
            $dm->flush();

            $this->cache->delete('additions_index');

            $this->addFlash('success', 'Addition created successfully!');
            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('addition/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string $id
     * @return Response
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/addition/{id}', name: 'addition_show', requirements: ['id' => '[0-9a-f]{24}'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(string $id): Response
    {
        $addition = $this->additionRepository->findById($id);

        if (!$addition) {
            throw $this->createNotFoundException('Addition not found');
        }

        return $this->render('addition/show.html.twig', [
            'addition' => $addition
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @param DocumentManager $dm
     * @return Response
     * @throws InvalidArgumentException
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/addition/{id}/edit', name: 'addition_edit', requirements: ['id' => '[0-9a-f]{24}'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, string $id, DocumentManager $dm): Response
    {
        $addition = $this->additionRepository->findById($id);
        $categories = $this->documentManager->getRepository(Category::class)->findAll();

        if (!$addition) {
            throw $this->createNotFoundException('Addition not found');
        }

        $form = $this->createForm(AdditionType::class, $addition, [
            'categories' => $categories,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->flush();

            $this->cache->delete('additions_index');

            $this->addFlash('success', 'Addition updated successfully!');
            return $this->redirectToRoute('pizza_index');
        }

        return $this->render('addition/edit.html.twig', [
            'form' => $form->createView(),
            'addition' => $addition,
        ]);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws InvalidArgumentException
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/addition/{id}', name: 'addition_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, string $id): Response
    {
        $addition = $this->additionRepository->findById($id);

        if (!$addition) {
            throw $this->createNotFoundException('Addition not found');
        }

        if (!$this->isCsrfTokenValid('delete' . $addition->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('pizza_index');
        }

        $this->additionRepository->remove($addition);
        $this->documentManager->flush();

        $this->cache->delete('additions_index');

        $this->addFlash('success', 'Addition deleted successfully!');
        return $this->redirectToRoute('pizza_index');
    }
}
