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

use Volta\Component\Books\Exceptions\Exception;

/**
 * Represents a collection of BookNodes(books).
 */
class Bookshelf
{

    /** @var $_shelf array<string, NodeInterface> */
    private array $_shelf = [];

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
        $node = Node::factory($absolutePath);
        if (!is_a($node, BookNode::class))
            throw new Exception('Cannot add the book (Path does not point to a book)');
        $this->_shelf[$bookIndex] = $node;
        return $node;
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
     * Whether a book exists with the given name
     *
     * @param string $bookIndex
     * @return bool
     */
    public function hasBook(string $bookIndex): bool
    {
        return isset($this->_shelf[$bookIndex]);
    }



}