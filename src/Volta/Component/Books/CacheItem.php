<?php

namespace Volta\Component\Books;

use Psr\Cache\CacheItemInterface;
use Volta\Component\Books\Exceptions\CacheException;

class CacheItem implements CacheItemInterface
{

    private readonly string $_key;

    public function __construct(string $key)
    {
        $this->_key = $key;
    }

    public function getKey(): string
    {
        return  $this->_key;
    }

    public function get(): string|bool
    {
        return file_get_contents($this->_key);
    }

    public function isHit(): bool
    {
        return is_file($this->_key) && is_readable($this->_key);
    }


    /**
     * @throws CacheException
     */
    public function set(mixed $value): static
    {
        $fileHandler = fopen($this->_key, 'w');
        if (false === fwrite($fileHandler, (string) $value) ) {
            throw new CacheException('Could not cache the node');
        }
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        return $this;
    }

    public function expiresAfter(\DateInterval|int|null $time): static
    {
        return $this;
    }


}