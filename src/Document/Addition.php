<?php

declare(strict_types=1);

namespace App\Document;

use App\Interfaces\CartItemInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

#[ODM\Document(collection: 'addition')]
class Addition implements CartItemInterface
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $name;

    #[ODM\Field(type: 'float')]
    private float $price;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $description = null;

    #[ODM\Field(type: 'bool')]
    private bool $active = true;

    #[ODM\ReferenceOne(targetDocument: Category::class)]
    private ?Category $category;

    #[ODM\Field(type: 'date')]
    private DateTime $createdAt;

    #[ODM\Field(type: 'date')]
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
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

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
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
        return 'addition';
    }

}