<?php

declare(strict_types=1);

namespace App\Document;

use App\Repository\SettingRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'setting', repositoryClass: SettingRepository::class)]
class Setting
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private ?string $country = null;

    #[ODM\Field(type: 'string')]
    private ?string $mapboxToken = null;

    #[ODM\Field(type: 'string')]
    private ?string $restaurantName = null;

    #[ODM\Field(type: 'string')]
    private ?string $restaurantAddress = null;

    #[ODM\Field(type: 'float')]
    private ?float $latitude = null;

    #[ODM\Field(type: 'float')]
    private ?float $longitude = null;

    #[ODM\Field(type: 'string')]
    private ?string $currency = null;

    #[ODM\Field(type: 'string')]
    private ?string $stripeSecretKey = null;

    #[ODM\Field(type: 'string')]
    private string $pizzaPriceCalculationType = 'fixed';

    #[ODM\Field(type: 'float')]
    private float $largeSizeModifier = 12.0;

    #[ODM\Field(type: 'float')]
    private float $smallSizeModifier = 8.0;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMapboxToken(): ?string
    {
        return $this->mapboxToken;
    }

    public function setMapboxToken(?string $mapboxToken): self
    {
        $this->mapboxToken = $mapboxToken;
        return $this;
    }

    public function getRestaurantName(): ?string
    {
        return $this->restaurantName;
    }

    public function setRestaurantName(?string $restaurantName): self
    {
        $this->restaurantName = $restaurantName;
        return $this;
    }

    public function getRestaurantAddress(): ?string
    {
        return $this->restaurantAddress;
    }

    public function setRestaurantAddress(?string $restaurantAddress): self
    {
        $this->restaurantAddress = $restaurantAddress;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
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

    public function getPizzaPriceCalculationType(): string
    {
        return $this->pizzaPriceCalculationType;
    }

    public function setPizzaPriceCalculationType(string $type): self
    {
        $this->pizzaPriceCalculationType = $type;
        return $this;
    }

    public function getLargeSizeModifier(): float
    {
        return $this->largeSizeModifier;
    }

    public function setLargeSizeModifier(float $modifier): self
    {
        $this->largeSizeModifier = $modifier;
        return $this;
    }

    public function getSmallSizeModifier(): float
    {
        return $this->smallSizeModifier;
    }

    public function setSmallSizeModifier(float $modifier): self
    {
        $this->smallSizeModifier = $modifier;
        return $this;
    }
}