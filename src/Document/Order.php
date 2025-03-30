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

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $stripeSessionId = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    private string $fullName;

    #[ODM\Field(type: 'string', nullable: true)]
    #[Assert\Email]
    private string $email;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+?[1-9][0-9]{7,14}$/')]
    private string $phone;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
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

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;
        return $this;
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

    public function addPizza(
        Pizza $pizza,
        int $quantity = 1,
        string $selectedSize = 'medium',
        float $actualPrice = null
    ): self {
        $this->pizzas[] = [
            'id' => $pizza->getId(),
            'name' => $pizza->getName(),
            'price' => $actualPrice ?? $pizza->getPrice(),
            'size' => $selectedSize,
            'toppings' => $pizza->getToppings(),
            'quantity' => $quantity
        ];
        return $this;
    }

    public function setPizzas(array $pizzas): self
    {
        $this->pizzas = $pizzas;
        return $this;
    }

    private function formatSize(string $size): string
    {
        return '(' . strtoupper(substr($size, 0, 1)) . ')';
    }

    // You might also want to add a getter that includes the formatted size
    public function getPizzaNameWithSize(array $pizza): string
    {
        return sprintf('%s %s',
            $pizza['name'],
            $this->formatSize($pizza['size'])
        );
    }

    public function getAdditions(): array
    {
        return $this->additions;
    }

    public function addAddition(Addition $addition, int $quantity = 1): self
    {
        $this->additions[] = [
            'id' => $addition->getId(),
            'name' => $addition->getName(),
            'price' => $addition->getPrice(),
            'quantity' => $quantity
        ];
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

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): self
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