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

use Psr\Log\LoggerAwareInterface;
use Volta\Component\Books\Exceptions\Exception;

interface PublisherInterface extends LoggerAwareInterface
{

    /**
     * Adds a book to the collection by path and returns the BookNode.
     * Throws an Exception if the path does not point to a Volta BookNode.
     * If __$bookIndex__ is missing a unique index should be created, preferable
     * the next numeral index.
     *
     * @param BookNode|string $book
     * @param string|int|null $bookIndex
     * @return BookNode
     * @throws Exception When there is no book found in the given path
     */
    public function addBook(BookNode|string $book, string|int|null $bookIndex = null): BookNode;

    /**
     * Returns a book by its __$bookIndex__ or NULL when not exists
     *
     * @param string|int $bookIndex
     * @return null|BookNode
     */
    public function getBook(string|int $bookIndex): null|BookNode;

    /**
     * Returns the first book
     *
     * @return bool|BookNode
     */
    public function getFirst(): bool|BookNode;

    /**
     * Returns the next book
     *
     * @return bool|BookNode
     */
    public function getNext(): bool|BookNode;

    /**
     * Returns the previous book
     *
     * @return bool|BookNode
     */
    public function getPrevious(): bool|BookNode;

    /**
     * Returns the last book
     *
     * @return bool|BookNode
     */
    public function getLast(): bool|BookNode;

    /**
     * Returns the full collection of books
     *
     * @return array<string, BookNode>
     */
    public function getBooks(): array;

    /**
     * Whether a book exists in the current collection with the given __$bookIndex__
     *
     * @param string|int $bookIndex
     * @return bool
     */
    public function hasBook(string|int $bookIndex): bool;

    /**
     * Alters the Uri for the current publishers platform
     *
     * @param NodeInterface $node
     * @return string
     */
    public function sanitizeUri(NodeInterface $node): string;


    public function setUriOffset(string $uriOffset): PublisherInterface;
    public function getUriOffset(): string;

    /**
     * Exports the parsed contents of the entire book with the specified __$bookIndex__
     * based on the options given
     *
     * @param string|int $bookIndex
     * @param array $options = []
     * @return bool True on success, False otherwise
     */
    public function exportBook(string|int $bookIndex, array $options = []): bool;

}