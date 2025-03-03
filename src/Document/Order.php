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
    private string $fullName;

    #[ODM\Field(type: 'string')]
    #[Assert\Email]
    private string $email = 'null';

    #[ODM\Field(type: 'string')]
    private string $phone;

    #[ODM\Field(type: 'string')]
    private string $address;

    #[ODM\Field(name: 'pizzas', type: 'collection')]
    private array $pizzas = [];

    #[ODM\Field(name: 'additions', type: 'collection')]
    private array $additions = [];

    #[ODM\Field(type: 'float')]
    #[Assert\PositiveOrZero]
    private float $totalPrice;

    #[ODM\Field(type: 'string', enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::PENDING;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
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

    public function addPizza(Pizza $pizza): self
    {
        $this->pizzas[] = $pizza;
        return $this;
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

    public function addAddition(Addition $addition): self
    {
        $this->additions[] = $addition;
        return $this;
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