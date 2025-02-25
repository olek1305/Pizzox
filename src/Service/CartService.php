<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class CartService
{
    public function __construct(private readonly CacheInterface $cache)
    {
        //
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function getCart(): array
    {
        return $this->cache->get('user_cart', function () {
            return [];
        });
    }
}