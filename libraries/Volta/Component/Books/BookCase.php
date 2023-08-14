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

use Psr\Cache\InvalidArgumentException;
use Volta\Component\Books\Exceptions\Exception;

/**
 * Represents a collection of BookNodes(books).
 */
class BookCase
{

    /** @var $_shelf array<string, NodeInterface> */
    private array $_shelf = [];



    /**
     * @param string $pageTemplate
     * @throws Exception
     */
    public function __construct(string $pageTemplate)
    {

        $this->setPageTemplate($pageTemplate);
    }

    private string $_pageTemplate;

    public function setPageTemplate(string $pageTemplate): BookCase
    {
        if (!is_file($pageTemplate)) {
            throw new Exception('Invalid page template');
        }
        $this->_pageTemplate = $pageTemplate;
        return $this;
    }
    public function getPageTemplate(): string
    {
        return $this->_pageTemplate;
    }


    /**
     * Adds a book to the shelf and returns the BookNode.
     *
     * @param string $bookIndex
     * @param string $absolutePath
     * @return NodeInterface
     * @throws Exception When there is no book found in the given path
     */
    public function addBook(string $bookIndex, string $absolutePath): NodeInterface
    {
        $bookNode = Node::factory($absolutePath);

        if (!is_a($bookNode, BookNode::class))
            throw new Exception(sprintf('Cannot add the book "%s" (Path does not point to a book)', $bookIndex));

        $bookNode->setUrlOffset($bookIndex);
        $this->_shelf[$bookIndex] = $bookNode;
        return $bookNode;
    }

    /**
     * Returns a book by its name or NULL when not exists
     *
     * @param string $bookIndex
     * @return NodeInterface|null
     */
    public function getBook(string $bookIndex): null|NodeInterface
    {
        if (!isset($this->_shelf[$bookIndex])) return null;
        return $this->_shelf[$bookIndex];
    }

    /**
     * @return array<string, NodeInterface>
     */
    public function getBooks(): array
    {
        return $this->_shelf;
    }

    /**
     * Whether a book exists with the given name
     *
     * @param string $bookIndex
     * @return bool
     */
    public function hasBook(string $bookIndex): bool
    {
        return isset($this->_shelf[$bookIndex]);
    }

    /**
     * @param string $bookIndex
     * @param string $page
     * @return void
     * @throws Exception
     * @throws Exceptions\CacheException
     * @throws InvalidArgumentException
     */
    public function sendContent(string $bookIndex, string $page): void
    {
        if (!isset($this->_shelf[$bookIndex])) {
            header('HTTP/1.0 404 Not found');
            echo "Book '$bookIndex' Not found";
            return;
        }

        Node::$uriOffset = $bookIndex;
        $book = $this->_shelf[$bookIndex];
        $page =  str_replace(Node::$uriOffset, '', $page);
        $node = $book->getChild($page);

        //if the node is not found return a 404
        if (null === $node){
            header('HTTP/1.0 404 Not found');
            echo "Page '$bookIndex/$page' Not found";
            return;
        }

        // if the requested node is a resource pass through
        if (is_a($node,  ResourceNode::class)) {
            if ($node->getContentType() ===  ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
                header('HTTP/1.0 415 Media-type not supported');
                return;
            }
            header('Content-Type: ' . $node->getContentType());
            header("Content-Length: " . filesize($node->getAbsolutePath()));
            readfile($node->getAbsolutePath());
            exit(0);
        }

        // cache pages for speed if the node can be cached
        $start = microtime(true);

        if ($node->getMeta()->get('isCacheable', true) && Settings::getCache() !== null) {
            $cachedNode = Settings::getCache()->getItem($node->getRelativePath());

            // check if we need to invalidate the cache
            if ($cachedNode->isHit()) {
                if ( $node->getModificationTime() > (int)@filemtime($cachedNode->getKey())) {
                    echo "<pre>";
                    echo "\n {$node->getAbsolutePath()} :" . $node->getModificationTime();
                    echo "\n {$cachedNode->getKey()} :" . filemtime($cachedNode->getKey());
                    echo "</pre>";
                    Settings::getCache()->deleteItem($node->getRelativePath());
                }
            }

            if ($cachedNode->isHit()) {
                echo $cachedNode->get();
                echo "\n<!-- Retrieved from cache in:  " . number_format(microtime(true) - $start, 10) . " seconds -->";
            } else {
                ob_start();
                include $this->getPageTemplate();
                $cachedNode->set(ob_get_contents());
                ob_end_flush();
                echo "\n<!-- generated in:  " . number_format(microtime(true) - $start, 10) . " seconds -->";
            }
        } else {
            include $this->getPageTemplate();
            echo "\n<!-- generated in:  " . number_format(microtime(true) - $start, 10) . " seconds (page set not be cached)-->";
        }


    }


}