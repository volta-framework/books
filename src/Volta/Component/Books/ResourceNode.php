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
use Psr\Http\Message\StreamInterface;
use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\ResourceNodeException;
use Slim\Psr7\Factory\StreamFactory;

/**
 * A ResourceNode is an end point for data to be used in a DocumentNode such as images, videos etc.
 */
class ResourceNode extends Node
{

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
     * @return StreamInterface
     */
    public function getContentAsStream(): StreamInterface
    {
        $streamFactory = new StreamFactory();
        return $streamFactory->createStreamFromFile($this->getAbsolutePath());
    }

    /**
     * A ResourceNode can not contain other nodes therefor it wil return an empty array
     *
     * @return array<mixed, mixed>
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
     * @return NodeInterface[]
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
                    if (is_a($sibling, static::class)) {
                        $this->_siblings[$sibling->getUri()] = $sibling;
                    }
                } catch (Exception|DocumentNodeException|ResourceNodeException $e) {
                    continue;
                }
            }
            ksort($this->_siblings);
        }
        return $this->_siblings;
    }

    protected null|NodeInterface $_next;

    public function getNext(): null|NodeInterface
    {
        if (!isset($this->_next)) {
            $this->_next = null;
            $next = false;
            foreach ($this->getSiblings() as $uri => $sibling) {
                if ($next) {
                    $this->_next = $sibling;
                    break;
                }
                $next = ($this->getUri() === $uri);
            }
        }
        return $this->_next;
    }


    protected null|NodeInterface $_previous;

    public function getPrevious(): null|NodeInterface
    {
        if (!isset($this->_previous)) {
            $this->_previous = null;
            foreach ($this->getSiblings() as $uri => $sibling) {
                if ($this->_previous === null && $this->getUri() === $uri) break;
                if ($this->getUri() === $uri) break;
                $this->_previous = $sibling;
            }
        }
        return $this->_previous;
    }

    const MEDIA_TYPE_NOT_SUPPORTED = 'Media-type not supported';

    public function getContentType(): string
    {

        $extension = pathinfo($this->getAbsolutePath(), PATHINFO_EXTENSION);

        return match($extension) {

            // textual files
            'html', 'htm'  => 'text/html',
            'txt'  => 'text/plain',
            'css'  => 'text/css',
            'js'  => 'text/javascript',

            // video's
            'avi'  => 'video/x-msvideo',
            'mpeg' => 'video/mpeg',
            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',

            // images
            'svg' => 'image/svg+xml',
            'bmp'  => 'image/bmp',
            'gif'  => 'image/gif',
            'ico'  => 'image/vnd.microsoft.icon',
            'jpeg','jpg'  => 'image/jpeg',
            'png'  => 'image/png',
            default => ResourceNode::MEDIA_TYPE_NOT_SUPPORTED

            // not supported extensions
            //default:
            //    header('HTTP/1.0 415 Media-type not supported');
            //    exit($extension . ' Media-type not supported');

        };
    }

    public function getMeta(): Meta
    {
       return new Meta();
    }

    public function getNode(string $relativePath): null|NodeInterface
    {
        return null;
    }
}