<?php

namespace App\Repository;

use App\Document\Addition;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use Throwable;

class AdditionRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        parent::__construct($dm, $dm->getUnitOfWork(), $dm->getClassMetadata(Addition::class));
    }

    /**
     * @return array
     * @throws MongoDBException
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder()
            ->sort('name', 'ASC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * @param string $id
     * @return Addition|null
     * @throws LockException
     * @throws MappingException
     */
    public function findById(string $id): ?Addition
    {
        if (!preg_match('/^[0-9a-f]{24}$/', $id)) {
            throw new InvalidArgumentException('Invalid MongoDB ID format');
        }
        $objectId = new ObjectId($id);
        return $this->find($objectId);
    }

    /**
     * @param Addition $addition
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function save(Addition $addition): void
    {
        $this->dm->persist($addition);
        $this->dm->flush();
    }

    /**
     * @param Addition $addition
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function remove(Addition $addition): void
    {
        $this->dm->remove($addition);
        $this->dm->flush();
    }

    /**
     * @param string $category
     * @return int
     * @throws MongoDBException
     */
    public function countByCategory(string $category): int
    {
        return $this->createQueryBuilder()
            ->field('type')->equals($category)
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * @param string $categoryId
     * @return void
     * @throws MongoDBException
     */
    public function removeCategory(string $categoryId): void
    {
        $this->createQueryBuilder()
            ->findAndRemove()
            ->field('category')->equals($categoryId)
            ->getQuery()
            ->execute();
    }
}