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
     * @throws MongoDBException
     * @throws Throwable
     */
    public function findOrCreateSetting(): Setting
    {
        $setting = $this->findOneBy([]);
        if (!$setting) {
            $setting = new Setting();
            $setting->setCurrency('PLN');
            $this->getDocumentManager()->persist($setting);
            $this->getDocumentManager()->flush();
        }

        return $setting;
    }
}