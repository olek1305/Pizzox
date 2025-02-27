<?php

namespace App\Repository;

use App\Document\Category;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use MongoDB\BSON\ObjectId;
use Throwable;
use Exception;
use InvalidArgumentException;

class CategoryRepository
{
    private ObjectRepository $repository;

    /**
     * @param DocumentManager $documentManager
     */
    public function __construct(private readonly DocumentManager $documentManager)
    {
        $this->repository = $this->documentManager->getRepository(Category::class);
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @param string $id
     * @return Category|null
     */
    public function find(string $id): ?Category
    {
        try {
            $objectId = new ObjectId($id);
            return $this->repository->find($objectId);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param Category $category
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function save(Category $category): void
    {
        $this->documentManager->persist($category);
        $this->documentManager->flush();
    }

    /**
     * @param Category $category
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function delete(Category $category): void
    {
        $this->documentManager->remove($category);
        $this->documentManager->flush();
    }

    /**
     * @param string $id
     * @return Category|null
     */
    public function findById(string $id): ?Category
    {
        if (!preg_match('/^[0-9a-f]{24}$/', $id)) {
            throw new InvalidArgumentException('Invalid MongoDB ID format');
        }

        try {
            $objectId = new ObjectId($id);
            return $this->find($objectId);
        } catch (Exception) {
            return null;
        }
    }
}