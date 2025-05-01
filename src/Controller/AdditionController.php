<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Category;
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

        // Format categories for Vue component
        $categoriesForVue = array_map(function ($category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }, $categories);

        if ($request->isXmlHttpRequest() || $request->getContentTypeFormat() === 'json') {
            try {
                $data = json_decode($request->getContent(), true);

                if (!$data) {
                    return $this->json(['error' => 'Invalid JSON data'], 400);
                }

                $validationErrors = [];

                if (!isset($data['name']) || trim($data['name']) === '') {
                    $validationErrors['name'] = 'Addition name is required';
                }

                if (!isset($data['price']) || (float)$data['price'] <= 0) {
                    $validationErrors['price'] = 'Addition price must be greater than 0';
                }

                if (!empty($validationErrors)) {
                    return $this->json([
                        'error' => 'Validation failed',
                        'validationErrors' => $validationErrors
                    ], 400);
                }

                $addition = new Addition();
                $addition->setName($data['name']);
                $addition->setPrice((float)$data['price']);

                if (!empty($data['category'])) {
                    $category = $this->documentManager->getRepository(Category::class)->find($data['category']);
                    if ($category) {
                        $addition->setCategory($category);
                    }
                }

                $dm->persist($addition);
                $dm->flush();

                $this->cache->delete('additions_data');

                $this->addFlash('success', 'flash.addition.created');
                return $this->json([
                    'redirect' => $this->generateUrl('pizza_index'),
                ]);
            } catch (\Exception $e) {
                return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
        }

        return $this->render('addition/create.html.twig', [
            'categories' => $categoriesForVue
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
            if ($request->isXmlHttpRequest() || $request->getContentTypeFormat() === 'json') {
                return $this->json(['error' => 'Addition not found'], 404);
            }
            throw $this->createNotFoundException('Addition not found');
        }

        // Format categories for Vue component
        $categoriesForVue = array_map(function ($category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        }, $categories);

        // Handle JSON request from Vue component
        if ($request->isXmlHttpRequest() || $request->getContentTypeFormat() === 'json') {
            try {
                $data = json_decode($request->getContent(), true);

                if (!$data) {
                    return $this->json(['error' => 'Invalid JSON data'], 400);
                }

                // Input validation
                $validationErrors = [];

                if (!isset($data['name']) || trim($data['name']) === '') {
                    $validationErrors['name'] = 'Addition name is required';
                }

                if (!isset($data['price']) || (float)$data['price'] <= 0) {
                    $validationErrors['price'] = 'Price must be greater than 0';
                }

                if (!empty($validationErrors)) {
                    return $this->json([
                        'error' => 'Validation failed',
                        'validationErrors' => $validationErrors
                    ], 400);
                }

                // Update values
                $addition->setName($data['name']);
                $addition->setPrice((float)$data['price']);

                if (isset($data['category']) && $data['category']) {
                    $category = $this->documentManager->getRepository(Category::class)->find($data['category']);
                    if ($category) {
                        $addition->setCategory($category);
                    }
                } else {
                    $addition->setCategory(null);
                }

                $dm->flush();

                $this->cache->delete('additions_data');

                $this->addFlash('success', 'flash.addition.updated');
                return $this->json([
                    'redirect' => $this->generateUrl('pizza_index'),
                ]);
            } catch (\Exception $e) {
                return $this->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
            }
        }

        // GET request - return form page with Vue props
        return $this->render('addition/edit.html.twig', [
            'addition' => [
                'id' => $addition->getId(),
                'name' => $addition->getName(),
                'price' => $addition->getPrice(),
                'category' => $addition->getCategory()?->getId()
            ],
            'categories' => $categoriesForVue
        ]);
    }

    /**
     * @param string $id
     * @return Response
     * @throws InvalidArgumentException
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/addition/{id}/delete', name: 'addition_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): Response
    {
        $addition = $this->additionRepository->findById($id);

        if (!$addition) {
            throw $this->createNotFoundException('Addition not found');
        }

        // Admin role is already checked by the IsGranted attribute
        $this->documentManager->remove($addition);
        $this->documentManager->flush();
    
        $this->cache->delete('additions_data');
        $this->cache->delete('user_cart');

        $this->addFlash('success', 'flash.addition.deleted');
        return $this->redirectToRoute('pizza_index');
    }
}
