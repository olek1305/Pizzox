<?php

namespace App\Repository;

use App\Document\Setting;
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
}