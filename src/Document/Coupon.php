<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

#[MongoDB\Document(collection: 'coupon')]
class Coupon
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $code;

    #[MongoDB\Field(type: 'float')]
    #[Assert\PositiveOrZero]
    private float $discount;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $type;

    #[MongoDB\Field(type: 'date', nullable: true)]
    private ?DateTime $expiresAt = null;

    #[MongoDB\Field(type: 'boolean')]
    private bool $active = true;

    #[MongoDB\Field(type: 'int')]
    private int $usageLimit;

    #[MongoDB\Field(type: 'int')]
    private int $usageCount = 0;


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
}