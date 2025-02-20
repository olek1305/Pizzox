<?php

namespace App\Controller;

use App\Document\Category;
use App\Form\CategoryType;
use App\Repository\AdditionRepository;
use App\Repository\CategoryRepository;
use App\Repository\PizzaRepository;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;
use Symfony\Contracts\Cache\CacheInterface;

class CategoryController extends AbstractController
{
    /**
     * @param CategoryRepository $categoryRepository
     * @param PizzaRepository $pizzaRepository
     * @param AdditionRepository $additionRepository
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly PizzaRepository    $pizzaRepository,
        private readonly AdditionRepository $additionRepository,
        private readonly CacheInterface     $cache
    ) {
        //
    }

    /**
     * @return Response
     */
    #[Route('/category', name: 'category_index')]
    public function index(): Response
    {
        $categories = $this->cache->get('categories_index', function () {
            return $this->categoryRepository->findAll();
        });

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/category/create', name: 'category_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category);
            $this->cache->delete('categories_index');
            $this->addFlash('success', 'Category successfully added!');
            return $this->redirectToRoute('category_create');
        }

        return $this->render('category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string $id
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/category/delete/confirm/{id}', name: 'category_confirm_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function confirmDelete(string $id, Request $request): Response
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            $this->addFlash('error', 'Category not found.');
            return $this->redirectToRoute('category_index');
        }

        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$id, $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('category_index');
        }

        // Remove associated pizzas
        $this->pizzaRepository->removeAllByCategory($id);
        $this->additionRepository->removeAllByCategory($id);

        // Remove the category itself
        $this->categoryRepository->delete($category);

        // Clear cache
        $this->cache->delete('categories_index');
        $this->cache->delete('pizzas_index');
        $this->cache->delete('additions_index');

        $this->addFlash('success', 'Category and all associated pizzas have been deleted successfully.');
        return $this->redirectToRoute('category_index');
    }

    /**
     * @param string $id
     * @param Request $request
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/category/edit/{id}', name: 'category_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(string $id, Request $request): Response
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            $this->addFlash('error', 'Category not found.');
            return $this->redirectToRoute('category_index');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryRepository->save($category);
            $this->cache->delete('categories_index');
            $this->addFlash('success', 'Category name updated.');
            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    /**
     * @param string $id
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/category/toggle/{id}', name: 'category_toggle_active', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleActive(string $id): Response
    {
        $category = $this->categoryRepository->findById($id);

        if (!$category) {
            $this->addFlash('error', 'Category not found.');
            return $this->redirectToRoute('category_index');
        }

        $category->setActive(!$category->isActive());
        $this->categoryRepository->save($category);

        $this->addFlash('success', 'Category status updated.');
        return $this->redirectToRoute('category_index');
    }
}
