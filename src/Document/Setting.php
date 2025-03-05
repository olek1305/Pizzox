<?php

declare(strict_types=1);

namespace App\Document;

use App\Repository\SettingRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'setting', repositoryClass: SettingRepository::class)]
class Setting
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private ?string $country = null;

    #[ODM\Field(type: 'string')]
    private ?string $currency = null;

    #[ODM\Field(type: 'string')]
    private ?string $stripeSecretKey = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCurrency(): string
    {
        return $this->currency ?? "Null";
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getStripeSecretKey(): ?string
    {
        return $this->stripeSecretKey;
    }

    public function setStripeSecretKey(?string $stripeSecretKey): self
    {
        $this->stripeSecretKey = $stripeSecretKey;
        return $this;
    }
}