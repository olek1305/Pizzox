<?php

declare(strict_types=1);

namespace App\Document;

use App\Enum\PaymentStatus;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

#[MongoDB\Document(collection: 'payment')]
class Payment
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\ReferenceOne(targetDocument: Order::class)]
    private Order $order;

    #[MongoDB\Field(type: 'float')]
    #[Assert\Positive]
    private float $amount;

    #[MongoDB\Field(type: 'string')]
    private string $status;

    #[MongoDB\Field(type: 'string')]
    private string $paymentMethod;

    #[MongoDB\Field(type: 'date')]
    private DateTime $createdAt;

    public function __construct(Order $order, float $amount, string $paymentMethod)
    {
        $this->order = $order;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
        $this->status = PaymentStatus::PENDING->value;
        $this->createdAt = new DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;
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

    public function setStatus(PaymentStatus $status): self
    {
        $this->status = $status->value;
        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

}