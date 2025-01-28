<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTime;

#[MongoDB\Document(collection: 'coupon')]
class Coupon
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $code;

    #[MongoDB\Field(type: 'float')]
    private float $discount;

    #[MongoDB\Field(type: 'date')]
    private DateTime $expiresAt;

    #[MongoDB\Field(type: 'int')]
    private int $usageLimit;

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

    public function getExpiresAt(): DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
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
}