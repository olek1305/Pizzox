<?php

namespace App\Repository;

use App\Document\Setting;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Throwable;

class SettingRepository extends DocumentRepository
{
    /**
     * @return Setting
     * @throws Throwable
     */
    public function findLastOrCreate(): Setting
    {
        $setting = $this->createQueryBuilder()
            ->sort('_id', 'DESC')
            ->limit(1)
            ->getQuery()
            ->getSingleResult();

        if (!$setting) {
            $setting = new Setting();
            $this->getDocumentManager()->persist($setting);
            $this->getDocumentManager()->flush();
        }

        return $setting;
    }

    /**
     * @return void
     * @throws MongoDBException
     * @throws Throwable
     */
    public function cleanupDuplicates(): void
    {
        $latestSetting = $this->findLastOrCreate();

        // Remove all settings except the latest one
        $this->createQueryBuilder()
            ->remove()
            ->field('_id')->notEqual($latestSetting->getId())
            ->getQuery()
            ->execute();
    }
}