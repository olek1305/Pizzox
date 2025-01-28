<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTime;

#[MongoDB\Document(collection: 'order')]
class Order
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $customerName;

    #[MongoDB\Field(type: 'string')]
    private string $customerEmail;

    #[MongoDB\Field(type: 'string')]
    private ?string $customerPhone = null;

    #[MongoDB\Field(type: 'string')]
    private string $address;

    #[MongoDB\Field(type: 'collection')]
    private array $pizzas;

    #[MongoDB\Field(type: 'collection')]
    private array $additions;

    #[MongoDB\Field(type: 'float')]
    private float $totalPrice;

    #[MongoDB\Field(type: 'string')]
    private string $status;

    #[MongoDB\Field(type: 'date')]
    private DateTime $createdAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): self
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPizzas(): array
    {
        return $this->pizzas;
    }

    public function setPizzas(array $pizzas): self
    {
        $this->pizzas = $pizzas;
        return $this;
    }

    public function getAdditions(): array
    {
        return $this->additions;
    }

    public function setAdditions(array $additions): self
    {
        $this->additions = $additions;
        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}