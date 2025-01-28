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

    #[ODM\Field(type: 'collection')]
    public array $ingredients;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['small', 'medium', 'large'])]
    #[ODM\Field(type: 'string')]
    public string $size;

    #[ODM\Field(type: 'date')]
    public \DateTime $createdAt;

    #[ODM\Field(type: 'date')]
    public \DateTime $updatedAt;

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

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function setIngredients(array $ingredients): self
    {
        $this->ingredients = $ingredients;
        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}