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

use DirectoryIterator;
use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\MimeTypeNotSupportedException;
use Volta\Component\Books\Exceptions\ResourceNodeException;

/**
 * A ResourceNode is an end point for data to be used in a DocumentNode such as images, videos etc.
 */
class ResourceNode extends Node
{


    /**
     * Returns the index in the list
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
     * @return array<string, DocumentNode>
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
     * @return ResourceNode[]
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
                    if ($sibling->isResource()) {
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

    protected null|ResourceNode $_next;

    /**
     * @inheritDoc
     * @return ResourceNode|null
     */
    public function getNext(): null|ResourceNode
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


    protected null|ResourceNode $_previous;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getPrevious(): null|ResourceNode
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


    const string MEDIA_TYPE_NOT_SUPPORTED = 'Media-type not supported';

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        $extension = pathinfo($this->getAbsolutePath(), PATHINFO_EXTENSION);
        if (!Settings::isResourceSupported($extension)) {
            throw new MimeTypeNotSupportedException('Mime Type for "'.$extension.'" not supported');
        }
        return Settings::getResourceMimeType($extension);
    }

    /**
     * As a Resource node can not have a meta file attached to it this function will return the parents node Metadata
     * object.
     * @throws Exception
     */
    public function getMeta(): Meta
    {
       return $this->getParent()->getMeta();
    }

    /**
     * A Resource Node can not have children. This function will return NULL
     *
     * @param string $relativePath
     * @return NodeInterface|null
     */
    public function getNode(string $relativePath): null|NodeInterface
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getModificationTime(): int|false
    {
        return filemtime($this->getAbsolutePath());
    }
}