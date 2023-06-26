<?php
/**
 * This file is part of the Quadro library which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
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

use DirectoryIterator;
use Parsedown;
use Psr\Http\Message\StreamInterface;
use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\ResourceNodeException;
use Slim\Psr7\Factory\StreamFactory;

/**
 *
 */
class DocumentNode extends Node
{

    /**
     * @return string
     * @throws Exception
     */
    public function getContent(): string
    {
        $contentFile = $this->getAbsolutePath() . DIRECTORY_SEPARATOR . $this->getContentFile();
        $extension = pathinfo($contentFile, PATHINFO_EXTENSION);
        $contentParser =  Settings::getContentParserFor($extension);
        if ( false === $contentParser) {
             throw new Exception(sprintf('No Content Parser found for *.%s files', $extension));
        }
        return $contentParser->getContent($contentFile, $this);
    }

    /**
     * @return StreamInterface
     * @throws Exception
     */
    public function getContentAsStream(): StreamInterface
    {
        $streamFactory = new StreamFactory();
        return $streamFactory->createStream($this->getContent());
    }

    /**
     * @var Meta
     */
    protected Meta $_meta;

    /**
     * @return Meta
     * @throws Exception
     */
    public function getMeta(): Meta
    {
        if (!isset($this->_meta)) {
            $this->_meta = new Meta($this->getAbsolutePath() . DIRECTORY_SEPARATOR . 'meta.json');
        }
        return $this->_meta;

    }

    /**
     * @var array<string, NodeInterface>
     */
    protected array $_children;

    /**
     * @return array<string, NodeInterface>
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
                    $this->_children[$child->getUri()] = $child;
                } catch (Exception|DocumentNodeException|ResourceNodeException $e) {
                    continue;
                }
            }
            ksort($this->_children);
        }
        return $this->_children;
    }

    /**
     * @var NodeInterface|null
     */
    protected null|NodeInterface $_next;

    /**
     * @return NodeInterface|null
     * @throws Exception
     */
    public function getNext(): null|NodeInterface
    {
        if (!isset($this->_next)) {
            $this->_next = null;
            if ($this->getParent() !== null) {
                $next = false;
                foreach ($this->getParent()->getChildren() as $uri => $child) {
                    if ($next) {
                        $this->_next = $child;
                        break;
                    }
                    $next = ($this->getUri() === $uri);
                }
            }

            // nothing found, get first child
            if($this->_next === null) {
                foreach ($this->getChildren() as $uri => $child) {
                    $this->_next = $child;
                    break;
                }
            }
        }
        return $this->_next;
    }

    /**
     * @var NodeInterface|null
     */
    protected null|NodeInterface $_previous;

    /**
     * @return NodeInterface|null
     * @throws Exception
     */
    public function getPrevious(): null|NodeInterface
    {
        if (!isset($this->_previous)) {
            $this->_previous = null;
            if ($this->getParent() !== null) {
                foreach ($this->getParent()->getChildren() as $uri => $child) {
                    if ($this->_previous === null && $this->getUri() === $uri) break;
                    if ($this->getUri() === $uri) break;
                    $this->_previous = $child;
                }

                // nothing found get parent if any
                if($this->_previous === null) {
                    if ($this->getParent() !== null) {
                        $this->_previous = $this->getParent();
                    }
                }

            }
        }
        return $this->_previous;
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
            $path = $this->getAbsolutePath() . DIRECTORY_SEPARATOR;
            if (is_file($path . 'content.xhtml'))
                $this->_contentFile =  'content.xhtml';
            if (is_file($path . 'content.html'))
                $this->_contentFile =  'content.html';
            if (is_file($path . 'content.php'))
                $this->_contentFile = 'content.php';
            if (is_file($path . 'content.phtml'))
                $this->_contentFile = 'content.phtml';
            if (is_file($path . 'content.txt'))
                $this->_contentFile = 'content.txt';
            if (is_file($path . 'content.md'))
                $this->_contentFile = 'content.md';

            // NOTE:
            //     Document object cannot be instantiated without a content file present,
            //     so there's no need to test for its existence.
            //     @see Repository::getDocument()
        }
        return $this->_contentFile;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        $extension = pathinfo($this->getAbsolutePath(), PATHINFO_EXTENSION);
        return match($extension) {
            'xhtml', 'html', 'php',  => 'text/html',
            'txt'  => 'text/plain',
            default => 'text/html'
        };
    }
}