<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'delivery_driver')]
class DeliveryDriver
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    private string $name;

    #[MongoDB\Field(type: 'string')]
    private string $phone;

    #[MongoDB\Field(type: 'collection')]
    private array $assignedOrders;

    #[MongoDB\Field(type: 'string')]
    private string $status;

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

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAssignedOrders(): array
    {
        return $this->assignedOrders;
    }

    public function setAssignedOrders(array $assignedOrders): self
    {
        $this->assignedOrders = $assignedOrders;
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
}