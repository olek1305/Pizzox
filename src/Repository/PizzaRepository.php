<?php

namespace App\Repository;

use App\Document\Pizza;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Throwable;

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
        return $this->createQueryBuilder('p')
            ->sort('name', 'ASC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * @param string $id
     * @return Pizza|null
     * @throws LockException
     * @throws MappingException
     */
    public function findById(string $id): ?Pizza
    {
        return $this->find($id);
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