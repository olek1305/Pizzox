<?php

declare(strict_types=1);

namespace App\Document;

use App\Interfaces\CartItemInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'pizza')]
class Pizza implements CartItemInterface
{
    #[ODM\Id]
    public ?string $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\Field(type: 'float', nullable: true)]
    public ?float $priceSmall = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    #[ODM\Field(type: 'float')]
    public float $price;

    #[ODM\Field(type: 'float', nullable: true)]
    public ?float $priceLarge = null;

    #[Assert\NotBlank]
    #[ODM\Field(type: 'collection')]
    public array $toppings = [];

    #[ODM\ReferenceOne(targetDocument: Category::class)]
    private ?Category $category = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @internal Only for test purposes
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getPriceSmall(): float
    {
        return $this->priceSmall;
    }

    public function setPriceSmall(float $price): self
    {
        $this->priceSmall = $price;
        return $this;
    }

    public function getPriceLarge(): float
    {
        return $this->priceLarge;
    }

    public function setPriceLarge(float $price): self
    {
        $this->priceLarge = $price;
        return $this;
    }

    public function getPriceBySize(string $size): float
    {
        return match ($size) {
            'small' => $this->priceSmall ?: 0,
            'large' => $this->priceLarge ?: 0,
            default => $this->price,
        };
    }

    public function getToppings(): array
    {
        return $this->toppings;
    }

    public function setToppings(array $toppings): self
    {
        $this->toppings = $toppings;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCartType(): string
    {
        return 'pizza';
    }
}