<?php

namespace App\Repository;

use App\Document\Pizza;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use InvalidArgumentException;
use Throwable;
use MongoDB\BSON\ObjectId;


class PizzaRepository extends DocumentRepository
{
    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        parent::__construct($dm, $dm->getUnitOfWork(), $dm->getClassMetadata(Pizza::class));
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
     * @return Pizza|null
     */
    public function findById(string $id): ?Pizza
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

    /**
     * @param Pizza $pizza
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function save(Pizza $pizza): void
    {
        try {
            $this->dm->persist($pizza);
            $this->dm->flush();
        } catch (MongoDBException $e) {
            throw new MongoDBException('Failed to save pizza: ' . $e->getMessage());
        }
    }

    /**
     * @param Pizza $pizza
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function remove(Pizza $pizza): void
    {
        {
            try {
                $this->dm->remove($pizza);
                $this->dm->flush();
            } catch (MongoDBException $e) {
                throw new MongoDBException('Failed to remove pizza: ' . $e->getMessage());
            }
        }
    }
}