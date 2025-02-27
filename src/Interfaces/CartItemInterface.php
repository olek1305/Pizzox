<?php

namespace App\Interfaces;

interface CartItemInterface
{
    public function getId(): ?string;
    public function getName(): string;
    public function getPrice(): float;
}
