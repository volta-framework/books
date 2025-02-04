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

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Volta\Component\Books\Exceptions\Exception;

/**
 * Sends a page or a book in the requested format
 */
abstract class Publisher implements PublisherInterface
{

    #region - Logger

    use LoggerAwareTrait;

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if(!isset($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    #endregion --------------------------------------------------------------------------------------------------------

    protected string $_uriOffset = '';

    public function setUriOffset(string $uriOffset): PublisherInterface
    {
        $this->_uriOffset = $uriOffset;
        return $this;
    }
    public function getUriOffset(): string
    {
        return $this->_uriOffset;
    }

    #region - Book Collection

    /** @var $_bookCollection array<string, BookNode> */
    protected array $_bookCollection = [];

    /**
     * @inheritDoc
     */
    public function addBook(BookNode|string $book, string|int|null $bookIndex = null): BookNode
    {
        if (!isset($bookIndex)) $bookIndex = count($this->_bookCollection);
        if(is_string($book)) {
            $node = BookNode::factory($book);
            $node->setPublisher($this);
            $this->_bookCollection[$bookIndex] = $node;
        } else {
            $book->setPublisher($this);
            $this->_bookCollection[$bookIndex] = $book;
        }
        return $this->_bookCollection[$bookIndex];
    }

    /**
     * @inheritDoc
     */
    public function getBook(string|int $bookIndex): null|BookNode
    {
        if (!isset($this->_bookCollection[$bookIndex])) return null;
        return $this->_bookCollection[$bookIndex];
    }

    /**
     * @inheritDoc
     */
    public function getBooks(): array
    {
        return $this->_bookCollection;
    }

    /**
     * @inheritDoc
     */
    public function getFirst(): false|BookNode
    {
        return reset($this->_bookCollection);
    }
    public function getNext(): false|BookNode
    {
        return next($this->_bookCollection);
    }

    /**
     * @inheritDoc
     */
    public function getPrevious(): false|BookNode
    {
        return prev($this->_bookCollection);
    }

    public function getLast(): false|BookNode
    {
        return end($this->_bookCollection);
    }

    /**
     * @inheritDoc
     */
    public function hasBook(string|int $bookIndex): bool
    {
        return isset($this->_bookCollection[$bookIndex]);
    }

    #endregion --------------------------------------------------------------------------------------------------------
    #region - Construction


    protected array $options = [];

    /** Force the use of the factory method
     * @param array $options
     */
    protected function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param string $class
     * @param array $options
     * @return PublisherInterface|null
     * @throws Exception
     */
    public static function factory(string $class, array $options = []): null|PublisherInterface
    {
        $interfaces = class_implements($class);
        if (false === $interfaces || !isset($interfaces[PublisherInterface::class])) {
            throw new Exception(sprintf(__METHOD__ . ': Can not load Publisher. Either "%s" does not exists or does not implement "%s"', $class, PublisherInterface::class));
        }
        return new $class($options);
    }

    #endregion

}