<?php

declare(strict_types=1);

namespace App\Document;

use App\Enum\OrderStatus;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

#[ODM\Document(collection: 'order')]
class Order
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    private string $customerName;

    #[ODM\Field(type: 'string')]
    #[Assert\Email]
    private string $customerEmail;

    #[ODM\Field(type: 'string')]
    private ?string $customerPhone = null;

    #[ODM\Field(type: 'string')]
    private string $address;

    #[ODM\EmbedMany(targetDocument: Pizza::class)]
    private array $pizzas = [];

    #[ODM\EmbedMany(targetDocument: Addition::class)]
    private array $additions = [];

    #[ODM\Field(type: 'float')]
    #[Assert\PositiveOrZero]
    private float $totalPrice;

    #[ODM\Field(type: 'string')]
    private string $status = OrderStatus::PENDING->value;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->status = OrderStatus::PENDING->value;
    }

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

    public function setPizzas(array $pizzas): self
    {
        $this->pizzas = $pizzas;
        return $this;
    }

    public function getPizzas(): array
    {
        return $this->pizzas;
    }

    public function setAdditions(array $additions): self
    {
        $this->additions = $additions;
        return $this;
    }

    public function getAdditions(): array
    {
        return $this->additions;
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