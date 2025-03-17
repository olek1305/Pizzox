<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

#[ODM\Document(collection: 'promotion')]
class Promotion
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $code;

    #[ODM\Field(type: 'float')]
    #[Assert\PositiveOrZero]
    private float $discount;

    #[ODM\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $type;

    #[ODM\Field(type: 'date', nullable: true)]
    private ?DateTime $expiresAt = null;

    #[ODM\Field(type: 'boolean')]
    private bool $active = true;

    #[ODM\Field(type: 'int')]
    private int $usageLimit;

    #[ODM\Field(type: 'int')]
    #[Assert\PositiveOrZero]
    private int $usageCount = 0;

    #[ODM\Field(type: 'string')]
    private string $itemType;

    #[ODM\Field(type: 'string')]
    private string $itemId;



    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
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

    public function getUsageLimit(): int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(int $usageLimit): self
    {
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function incrementUsageCount(): self
    {
        if ($this->usageCount < $this->usageLimit) {
            $this->usageCount++;
        }

        return $this;
    }

    public function isValid(): bool
    {
        $now = new DateTime();

        if (!$this->active) {
            return false;
        }

        if ($this->expiresAt && $this->expiresAt < $now) {
            return false;
        }

        if ($this->usageCount >= $this->usageLimit) {
            return false;
        }

        return true;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): self
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function setItemId(string $itemId): self
    {
        $this->itemId = $itemId;
        return $this;
    }
}