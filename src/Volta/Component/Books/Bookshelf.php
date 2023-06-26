<?php
/**
 * This file is part of the Quadro library which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * Therefor we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
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
     * @param string $absolutePath
     * @return NodeInterface
     * @throws Exception When there is no book found in the given path
     */
    public function addBook(string $absolutePath): NodeInterface
    {
        $node = Node::factory($absolutePath);
        if (!is_a($node, BookNode::class))
            throw new Exception('Cannot add to book _shelf(Path does not point to a book)');
        $this->_shelf[$node->getName()] = $node;
        return $node;
    }

    /**
     * Returns a book by its name or NULL when not exists
     *
     * @param string $name
     * @return NodeInterface|null
     */
    public function getBook(string $name): null|NodeInterface
    {
        if (!isset($this->_shelf[$name])) return null;
        return $this->_shelf[$name];
    }

    /**
     * Whether a book exists with the given name
     *
     * @param string $name
     * @return bool
     */
    public function hasBook(string $name): bool
    {
        return isset($this->_shelf[$name]);
    }



}