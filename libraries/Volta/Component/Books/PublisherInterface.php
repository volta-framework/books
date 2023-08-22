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
     * Sets the file location of the general template to show the content of the books in the collection
     *
     * The template is expected to be a PHP/HTML file. An Exception is thrown if not pointing to
     * a (*.php|*.phtml) file.
     *
     * @param string $pageTemplate
     * @return PublisherInterface
     * @throws Exception
     */
    public function setPageTemplate(string $pageTemplate): PublisherInterface;

    /**
     * Returns the file location of the general template to show the content of the books in the collection
     * if not set defaults to ~/templates/web-book.phtml of this component.
     *
     * @return string
     * @throws Exception
     */
    public function getPageTemplate():string;

    /**
     * Sets the file location of the general css stylesheet to style the content of the books in the collection
     *
     * The file is expected to be a CSS file. An Exception is thrown if not pointing to
     * a (*.css) file.
     *
     * @param string $pageStyle
     * @return PublisherInterface
     * @throws Exception
     */
    public function setPageStyle(string $pageStyle): PublisherInterface;

    /**
     * Returns the file location of the general css stylesheet to style the content of the books in the collection
     * if not defaults to ~/public/assets/css/web-book.css of this component.
     *
     * @return string
     * @throws Exception
     */
    public function getPageStyle():string;

    /**
     * Adds a book to the collection by path and returns the BookNode.
     * Throws an Exception if the path does not point to a Volta BookNode
     *
     * @param string $bookIndex
     * @param BookNode|string $book
     * @return BookNode
     * @throws Exception When there is no book found in the given path
     */
    public function addBook(string $bookIndex, BookNode|string $book): BookNode;

    /**
     * Returns a book by its ID or NULL when not exists
     *
     * @param string $bookIndex
     * @return null|BookNode
     */
    public function getBook(string $bookIndex): null|BookNode;

    /**
     * Returns the full collection of books
     *
     * @return array<string, BookNode>
     */
    public function getBooks(): array;

    /**
     * Whether a book exists in the current collection with the given ID
     *
     * @param string $bookIndex
     * @return bool
     */
    public function hasBook(string $bookIndex): bool;

    /**
     * Exports the parsed contents of the entire book with the specified ID
     *
     * @param string $bookIndex
     * @return bool True on success, False otherwise
     */
    public function exportBook(string $bookIndex): bool;

    /**
     * Exports the parsed contents of the page referenced with the path of the book with the specified ID
     * @param string $bookIndex
     * @param string $path
     * @return bool
     */
    public function exportPage(string $bookIndex, string $path): bool;

}