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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Volta\Component\Books\Exceptions\CacheException;

class Cache implements CacheItemPoolInterface
{

    #region - Construction


    protected array $_cacheOptions = [];

    /**
     * @var string Directory to cache generated DocumentNodes
     */
    protected readonly string $_cacheDir;

    /**
     * @param array $cacheOptions
     * @throws CacheException
     */
    public function __construct(array $cacheOptions = [])
    {
        $this->_cacheOptions = $cacheOptions;

        $cacheDir = realpath($this->_cacheOptions['directory'] ?? false);

        if (false === $cacheDir || !is_dir($cacheDir) || !is_writable($cacheDir)) {
            throw new Exceptions\CacheException('Cache directory invalid');
        }
        //if (!str_ends_with(!str_ends_with($cacheDir, '/'), '/') && !str_ends_with($cacheDir, '\\')) {
        //    $cacheDir .= DIRECTORY_SEPARATOR;
        //}
        $this->_cacheDir = $cacheDir . DIRECTORY_SEPARATOR;
    }

    protected function _uniqueKey(string $publicKey): string
    {
        return $this->_cacheDir . sha1($publicKey);
    }


    #endregion
    #region - CacheItemPoolInterface Stubs


    /**
     * @inheritDoc
     * @param string $key The relative path of the book as base64
     * @return CacheItemInterface
     */
    public function getItem(string $key): CacheItemInterface
    {
         return new CacheItem($this->_uniqueKey($key));
    }

    /**
     * @inheritDoc
     * @param array $keys
     * @return iterable
     * @throws InvalidArgumentException
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach($keys as $key) {
            $this->getItem($key);
        }
        return $items;
    }

    /**
     * @inheritDoc
     * @param string $key
     * @return bool
     */
    public function hasItem(string $key): bool
    {
        $item = new CacheItem($this->_uniqueKey($key));
        return $item->isHit();
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function clear(): bool
    {
        $deleted = true;
        $files = glob($this->_cacheDir.'*');
        foreach($files as $file) {
            if(is_file($file)) {
                $deleted = unlink($file);
                if (false === $deleted) break;
            }
        }
        return $deleted;
    }

    /**
     * @inheritDoc
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        if ($this->hasItem($key)) {
            return unlink($this->_uniqueKey($key));
        }
        return true;
    }

    /**
     * @inheritDoc
     * @param array $keys
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItems(array $keys): bool
    {
        $deleted = true;
        foreach($keys as $key) {
           $deleted = $this->deleteItem($key);
           if (false === $deleted) break;
        }
        return $deleted;
    }

    /**
     * @inheritDoc
     * @param CacheItemInterface $item
     * @return bool
     */
    public function save(CacheItemInterface $item): bool
    {
        // not implemented
        return $item->isHit();
    }

    /**
     * @inheritDoc
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        // not implemented
        return true;
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function commit(): bool
    {
        // not implemented
        return true;
    }

    #endregion


}