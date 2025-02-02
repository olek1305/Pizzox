<?php

declare(strict_types=1);

namespace App\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document(collection: 'pizza')]
class Pizza
{
    #[ODM\Id]
    public ?string $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[ODM\Field(type: 'string')]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Positive]
    #[ODM\Field(type: 'float')]
    public float $price;

    #[Assert\NotBlank]
    #[ODM\Field(type: 'collection')]
    public array $toppings = [];

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['small', 'medium', 'large'], multiple: true)]
    #[ODM\Field(type: 'collection')]
    public array $size = [];

    public function getId(): ?string
    {
        return $this->id;
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

    public function getToppings(): array
    {
        return $this->toppings;
    }

    public function setToppings(array $toppings): self
    {
        $this->toppings = $toppings;

        return $this;
    }

    public function getSize(): array
    {
        return $this->size;
    }

    public function setSize(array $size): self
    {
        $this->size = $size;
        return $this;
    }
}