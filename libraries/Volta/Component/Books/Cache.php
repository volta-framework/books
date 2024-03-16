<?php

namespace Volta\Component\Books;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Volta\Component\Books\Exceptions\CacheException;

class Cache implements CacheItemPoolInterface
{

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
    /**
     * @param string $key The relative path of the book as base64
     * @return CacheItemInterface
     */
    public function getItem(string $key): CacheItemInterface
    {
         return new CacheItem($this->_uniqueKey($key));
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach($keys as $key) {
            $this->getItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        $item = new CacheItem($this->_uniqueKey($key));
        return $item->isHit();
    }

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

    public function deleteItem(string $key): bool
    {
        if ($this->hasItem($key)) {
            return unlink($this->_uniqueKey($key));
        }
        return true;
    }

    public function deleteItems(array $keys): bool
    {
        $deleted = true;
        foreach($keys as $key) {
           $deleted = $this->deleteItem($key);
           if (false === $deleted) break;
        }
        return $deleted;
    }

    public function save(CacheItemInterface $item): bool
    {
        return $item->isHit();
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return true; // not implemented
    }

    public function commit(): bool
    {
        return true; // not implemented
    }
}