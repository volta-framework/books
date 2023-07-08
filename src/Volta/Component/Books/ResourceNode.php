<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Volta\Component\Books;

use DirectoryIterator;
use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\ResourceNodeException;

/**
 * A ResourceNode is an end point for data to be used in a DocumentNode such as images, videos etc.
 */
class ResourceNode extends Node
{


    /**
     * @return int
     */
    public function getIndex(): int
    {
        foreach($this->getSiblings() as $index => $node) {
            if ($node->getAbsolutePath() === $this->getAbsolutePath()) {
                return $index;
            }
        }
        return 0;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        if (false === ($content = file_get_contents($this->getAbsolutePath()))) {
            throw new Exception('Gould not get the data as binary string');
        }
        return $content;
    }

    /**
     * A ResourceNode can not contain other nodes therefor it wil return an empty array
     *
     * @return array<mixed, mixed>
     */
    public function getChildren(): array
    {
        return [];
    }

    /**
     * @var array<string, NodeInterface>
     */
    protected array $_siblings;

    /**
     * @return NodeInterface[]
     */
    public function getSiblings(): array
    {
        if (!isset($this->_siblings)) {
            $dir  = new DirectoryIterator(dirname($this->getAbsolutePath()));
            foreach($dir as $fileInfo) {
                if ($fileInfo->isDot()) continue;
                if (!$fileInfo->isFile()) continue;
                try {
                    $sibling = Node::factory($fileInfo->getPathname());
                    if (is_a($sibling, static::class)) {
                        $this->_siblings[$sibling->getAbsolutePath()] = $sibling;
                    }
                } catch (Exception|DocumentNodeException|ResourceNodeException $e) {
                    continue;
                }
            }
            ksort($this->_siblings);
        }
        return $this->_siblings;
    }

    protected null|NodeInterface $_next;

    public function getNext(): null|NodeInterface
    {
        if (!isset($this->_next)) {
            $this->_next = null;
            $next = false;
            foreach ($this->getSiblings() as $absolutePath => $sibling) {
                if ($next) {
                    $this->_next = $sibling;
                    break;
                }
                $next = ($this->getAbsolutePath() === $absolutePath);
            }
        }
        return $this->_next;
    }


    protected null|NodeInterface $_previous;

    /**
     * @throws Exception
     */
    public function getPrevious(): null|NodeInterface
    {
        if (!isset($this->_previous)) {
            $this->_previous = null;
            foreach ($this->getSiblings() as $absolutePath => $sibling) {
                if ($this->_previous === null && $this->getAbsolutePath() === $absolutePath) break;
                if ($this->getUri() === $absolutePath) break;
                $this->_previous = $sibling;
            }
        }
        return $this->_previous;
    }

    const MEDIA_TYPE_NOT_SUPPORTED = 'Media-type not supported';

    public function getContentType(): string
    {
        $extension = pathinfo($this->getAbsolutePath(), PATHINFO_EXTENSION);
        return Settings::$supportedResources[$extension];
    }

    public function getMeta(): Meta
    {
       return new Meta();
    }

    public function getNode(string $relativePath): null|NodeInterface
    {
        return null;
    }
}