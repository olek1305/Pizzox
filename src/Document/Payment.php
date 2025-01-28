<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use DateTime;

#[MongoDB\Document(collection: 'payment')]
class Payment
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $orderId;

    #[MongoDB\Field(type: 'float')]
    private float $amount;

    #[MongoDB\Field(type: 'string')]
    private string $status;

    #[MongoDB\Field(type: 'date')]
    private DateTime $paymentDate;

    #[MongoDB\Field(type: 'string')]
    private string $stripeId;

    #[MongoDB\Field(type: 'string')]
    private string $methodPayment;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
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

    public function getPaymentDate(): DateTime
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(DateTime $paymentDate): self
    {
        $this->paymentDate = $paymentDate;
        return $this;
    }

    public function getStripeId(): string
    {
        return $this->stripeId;
    }

    public function setStripeId(string $stripeId): self
    {
        $this->stripeId = $stripeId;
        return $this;
    }

    public function getmethodPayment(): string
    {
        return $this->methodPayment;
    }

    public function setmethodPayment(string $methodPayment): self
    {
        $this->methodPayment = $methodPayment;
        return $this;
    }
}