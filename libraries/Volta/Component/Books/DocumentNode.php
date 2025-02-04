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
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\ResourceNodeException;

/**
 * A DocumentNode represents a page in the book
 */
class DocumentNode extends Node
{

    /**
     * Returns the index in the BookNode list
     *
     * @return int
     * @throws Exception
     */
    public function getIndex(): int
    {
        foreach($this->getRoot()->getList() as $index => $node) {
            if ($node->getAbsolutePath() === $this->getAbsolutePath()) {
                return $index;
            }
        }
        return 0;
    }

    /**
     * @ignore (Do not show up in generated documenation)
     * @var Meta
     */
    protected Meta $_meta;

    /**
     * @return Meta The Meta information
     * @throws Exception When the meta file can not be read or a Json  parsing error has occurred
     */
    public function getMeta(): Meta
    {
        if (!isset($this->_meta)) {
            $this->_meta = new Meta($this->getAbsolutePath() . DIRECTORY_SEPARATOR . 'meta.json', $this);
        }
        return $this->_meta;
    }

    /**
     * @ignore (Do not show up in generated documenation)
     * @var array<string, NodeInterface>
     */
    protected array $_children;

    /**
     * Returns all children DocumentNodes
     *
     * @return array<string, DocumentNode>
     */
    public function getChildren(): array
    {
        if (!isset($this->_children)) {
            $this->_children = [];
            $dir  = new DirectoryIterator($this->getAbsolutePath());
            foreach($dir as $fileInfo) {
                if ($fileInfo->isDot()) continue;
                if (!$fileInfo->isDir()) continue;
                try {
                    $child = Node::factory($fileInfo->getPathname());
                    $this->_children[$child->getAbsolutePath()] = $child;
                } catch (Exception|DocumentNodeException|ResourceNodeException $e) {
                    continue;
                }
            }
            ksort($this->_children);
        }
        return $this->_children;
    }

    /**
     * @ignore (Do not show up in generated documenation)
     * @var array<string, ResourceNode>
     */
    private array $_resources;

    /**
     * Returns all resources for this DocumentNode
     *
     * @return array<string, ResourceNode>
     * @throws Exception
     */
    public function getResources(): array
    {
        if(!isset($this->_resources)) {
            $this->_resources = [];

            // loop through files in this directory and subdirectories which are not DocumentNodes
            $flags = FilesystemIterator::SKIP_DOTS;
            $dirIt = new DirectoryIterator($this->getAbsolutePath());
            foreach ($dirIt as $file) {
                try {
                    if ($file->isDot()) continue;
                    if ($file->isDir()) {
                        $docNode = Node::factory($file->getPathname());
                        if ($docNode->isDocument()) continue;

                        // a directory in the document node directory which is not a document qualifies as a resource directory
                        // therefor recursive iterate through all resources.
                        $recDirIt = new RecursiveDirectoryIterator($file->getPathname(), $flags);
                        $files = new RecursiveIteratorIterator($recDirIt);
                        foreach ($files as $resourceFile) {
                            $resourceNode = Node::factory($resourceFile->getPathname());
                            if ($resourceNode->isDocument()) continue;
                            $this->_resources[$resourceNode->getUri()] = $resourceNode;
                        }

                    } else {
                        $resource = Node::factory($file->getPathname());
                        $this->_resources[$resource->getUri()] = $resource;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        return $this->_resources;
    }

    /**
     * @ignore (Do not show up in generated documenation)
     * @var DocumentNode|null Lazy load memory cache
     */
    protected null|DocumentNode $_next;

    /**
     * Returns the next DocumentNode in the hierarchy. Null if there is none
     *
     * @return DocumentNode|null
     * @throws Exception
     */
    public function getNext(): null|DocumentNode
    {
        // lazy load, do cache the result
        if (!isset($this->_next)) {
            $this->_next = null;
            $next = false;
            foreach($this->getRoot()->getList() as $node) {
                if ($next) {
                    $this->_next = $node;
                    break;
                }
                $next = ($node->getAbsolutePath() === $this->getAbsolutePath());
            }
        }
        return $this->_next;
    }

    /**
     * @ignore (Do not show up in generated documenation)
     * @var DocumentNode|null
     */
    protected null|DocumentNode $_previous;

    /**
     * Returns the previous DocumentNode in the hierarchy. Null if there is none
     *
     * @return DocumentNode|null
     * @throws Exception
     */
    public function getPrevious(): null|DocumentNode
    {
        if (!isset($this->_previous)) {
            $this->_previous = null;
            foreach($this->getRoot()->getList() as $node) {
                if ($this->_previous === null && $this->getAbsolutePath() === $node->getAbsolutePath()) break;
                if ($this->getAbsolutePath() === $node->getAbsolutePath()) break;
                $this->_previous = $node;
            }
        }
        return $this->_previous;
    }


    public function getLevel():int
    {
        $level = 0;
        $parent = $this->getParent();
        while(null !== $parent) {
            $parent = $parent->getParent();
            $level++;
        }
        return $level;
    }


    /**
     * Returns the parsed content
     *
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        return $this->_getContentParser()->getContent($this->getContentFile());
    }

    /**
     * @ignore (Do not show up in generated documenation)
     * @var string $_contentFile Internal storage for the name of the content file
     */
    protected string $_contentFile;

    /**
     * Returns the name of the content file
     *
     * @return string The name of the Content File
     */
    public function getContentFile(): string
    {
        if(!isset($this->_contentFile)) {
            $result = glob( $this->getAbsolutePath() . DIRECTORY_SEPARATOR . 'content.*');
            return $this->_contentFile = $result[0];

            // NOTE:
            //     Document object cannot be instantiated without a content file present,
            //     so there's no need to test for its existence.
            //     @see Repository::getDocument()
        }
        return $this->_contentFile;
    }

    /**
     * Returns the content type in the HTTP header format
     *
     * @return string
     * @throws Exception
     */
    public function getContentType(): string
    {
        return $this->_getContentParser()->getContentType();
    }

    /**
     * @ignore (do not showup in gerated documentation)
     * @var ContentParserInterface
     */
    protected ContentParserInterface $_contentParser;

    /**
     * @return ContentParserInterface
     * @throws Exception
     */
    protected function _getContentParser(): ContentParserInterface
    {
        if (!isset($this->_contentParser)) {
            $contentFile = $this->getContentFile();
            $extension = pathinfo($contentFile, PATHINFO_EXTENSION);
            $contentParser = Settings::getContentParserFor($extension);
            if (false === $contentParser) {
                throw new Exception(sprintf('No Content Parser found for *.%s files', $extension));
            }
            $this->_contentParser = $contentParser;
            $this->_contentParser->setNode($this);
        }
        return $this->_contentParser;
    }

    /**
     * @return int|false Returns the modification time if available, false if not
     */
    public function getModificationTime(): int|false
    {
        return filemtime($this->getContentFile());
    }

    /**
     * @param string $absolutePath
     * @param bool $rebuild
     * @return DocumentNode
     * @throws Exception
     */
    public static function factory(string $absolutePath, bool $rebuild = false): DocumentNode
    {
        try {
            /** @var DocumentNode $node */
            $node = parent::factory($absolutePath, $rebuild);
            if (!$node->isBook()) {
                throw new Exception(__METHOD__ . ': Request can not be identified as a Document Node');
            }
            return $node;
        } catch(Exception $e ){
            throw new Exception(__METHOD__ . ': Request can not be identified as a Document Node');
        }


    }

}