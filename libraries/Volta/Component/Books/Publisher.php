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

    #endregion
    #-----------------------------------------------------------------------------------------------------------------
    #region - Book Collection

    /** @var $_bookCollection array<string, BookNode> */
    protected array $_bookCollection = [];

    /**
     * @inheritDoc
     */
    public function addBook(string $bookIndex, BookNode|string $book): BookNode
    {
        if(is_string($book)) {
            $node = Node::factory($book);
            if (!is_a($node, BookNode::class)) {
                throw new Exception('Path("' . $book . '") can not be identified as a BookNode:(Path does not point to a book)');
            }
            $this->_bookCollection[$bookIndex] = $node;
        } else {
            $this->_bookCollection[$bookIndex] = $book;
        }

        return $this->_bookCollection[$bookIndex];
    }

    /**
     * @inheritDoc
     */
    public function getBook(string $bookIndex): null|BookNode
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
    public function hasBook(string $bookIndex): bool
    {
        return isset($this->_bookCollection[$bookIndex]);
    }

    #endregion
    #-----------------------------------------------------------------------------------------------------------------
    #region - HTML Page Template file

    /** @var $_pageTemplate string */
    private string $_pageTemplate;

    /**
     * @inheritDoc
     */
    public function setPageTemplate(string $pageTemplate): PublisherInterface
    {
        $pageTemplate = realpath($pageTemplate);
        $pattern = "/^.*\.(php|phtml)$/i";
        if (false === $pageTemplate || !is_file($pageTemplate) || !preg_match($pattern, $pageTemplate)) {
            throw new Exception('Invalid Volta Bookcase template! Expects a valid file with extensions *.php|*phtml');
        }
        $this->_pageTemplate = $pageTemplate;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPageTemplate(): string
    {
        if (!isset($this->_pageTemplate)) {
            $this->setPageTemplate(__DIR__ . '/../../../../templates/web-book.phtml');
        }
        return $this->_pageTemplate;
    }

    #endregion
    #-----------------------------------------------------------------------------------------------------------------
    #region - CSS Page style

    /** @var $_pageStyle string */
    protected string $_pageStyle;

    /**
     * @inheritDoc
     */
    public function setPageStyle(string $pageStyle): PublisherInterface
    {
        $pageTemplate = realpath($pageStyle);
        $pattern = "/^.*\.(css)$/i";
        if (false === $pageStyle || !is_file($pageStyle) || !preg_match($pattern, $pageStyle)) {
            throw new Exception('Invalid Volta Bookcase css stylesheet! Expects a valid file with extensions *.css');
        }
        $this->_pageStyle = $pageStyle;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPageStyle(): string
    {
        if (!isset($this->_pageStyle)) {
            $this->setPageTemplate(__DIR__ . '/../../../../public/assets/css/web-book.css');
        }
        return $this->_pageStyle;
    }

    #endregion
    #-----------------------------------------------------------------------------------------------------------------
    #region - Construction

    /**
     * Expects Publishers specified options
     * The implementation should validate the options.
     *
     * @param array $options
     * @throws Exception
     */
    protected function __construct(array $options)
    {
        if (isset($options['pageTemplate'])) $this->setPageTemplate($options['pageTemplate']);
        if (isset($options['pageStyle'])) $this->setPageStyle($options['pageStyle']);
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
            throw new Exception(sprintf('Can not load Publisher. Either "%s" does not exists or does not implement "%s"', $class, PublisherInterface::class));
        }
        return new $class($options);
    }

    #endregion

}