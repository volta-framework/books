<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-server-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Volta\Component\Books;

use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Volta\Component\Books\Exceptions\CacheException;

readonly class CacheItem implements CacheItemInterface
{

    private string $_key;

    public function __construct(string $key)
    {
        $this->_key = $key;
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getKey(): string
    {
        return  $this->_key;
    }

    /**
     * @inheritDoc
     * @return string|null
     */
    public function get(): string|null
    {
        if ($this->isHit()) {
            return file_get_contents($this->_key);
        }
        return null;
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function isHit(): bool
    {
        return is_file($this->_key) && is_readable($this->_key);
    }


    /**
     * @inheritDoc
     * @param mixed $value
     * @return $this
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

    /**
     * NOTE: Not used
     *
     * @inheritDoc
     * @param DateTimeInterface|null $expiration
     * @return $this
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        return $this;
    }

    /**
     * NOTE: Not used
     *
     * @inheritDoc
     * @param \DateInterval|int|null $time
     * @return $this
     */
    public function expiresAfter(\DateInterval|int|null $time): static
    {
        return $this;
    }


}