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

namespace Volta\Component\Books\Publishers;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Node;
use Volta\Component\Books\NodeInterface;
use Volta\Component\Books\Publisher;
use Volta\Component\Books\PublisherInterface;
use Volta\Component\Books\ResourceNode;
use Volta\Component\Books\Settings;

class Web extends Publisher
{


    #region - HTML Page Template file

    /** @var $_documentNodeTemplate string */
    private string $_documentNodeTemplate;

    /**
     * @param string $documentNodeTemplate
     * @return PublisherInterface
     * @throws Exception
     */
    private function _setDocumentNodeTemplate(string $documentNodeTemplate): PublisherInterface
    {
        $documentNodeTemplate = realpath($documentNodeTemplate);
        $pattern = "/^.*\.(php|phtml)$/i";
        if (false === $documentNodeTemplate || !is_file($documentNodeTemplate) || !preg_match($pattern, $documentNodeTemplate)) {
            throw new Exception('Invalid Volta Bookcase template! ' . __CLASS__. ' expects a valid file with extensions *.php|, *.phtml');
        }
        $this->_documentNodeTemplate = $documentNodeTemplate;
        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function _getDocumentNodeTemplate(): string
    {
        if (!isset($this->_documentNodeTemplate)) {
            $this->_setDocumentNodeTemplate(__DIR__ . '/../../../../templates/web-book.html.php');
        }
        return $this->_documentNodeTemplate;
    }

    #endregion --------------------------------------------------------------------------------------------------------
    #region - CSS Page style

    /** @var $_documentNodeStylesheet string */
    protected string $_documentNodeStylesheet;

    /**
     * @param string $documentNodeStylesheet
     * @return void
     * @throws Exception
     */
    private function _setDocumentNodeStylesheet(string $documentNodeStylesheet): void
    {
        $documentNodeStylesheet = realpath($documentNodeStylesheet);
        $pattern = "/^.*\.(css)$/i";
        if (false === $documentNodeStylesheet || !is_file($documentNodeStylesheet) || !preg_match($pattern, $documentNodeStylesheet)) {
            throw new Exception('Invalid Volta Bookcase css stylesheet! ' . __CLASS__. ' expects a valid file with extensions *.css');
        }
        $this->_documentNodeStylesheet = $documentNodeStylesheet;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getDocumentNodeStylesheet(): string
    {
        if (!isset($this->_documentNodeStylesheet)) {
            $this->_setDocumentNodeTemplate(__DIR__ . '/../../../../public/assets/css/web-book.css');
        }
        return $this->_documentNodeStylesheet;
    }

    #endregion --------------------------------------------------------------------------------------------------------
    #region - Caching settings

    /**
     * Cache object reference Set to private to enforce the use of the
     * getCache() and setCache() methods
     *
     * @ignore Do not show up in generated documentation
     * @var CacheItemPoolInterface|null
     */
    private null|CacheItemPoolInterface $_cachePool = null;

    /**
     * @return CacheItemPoolInterface|null
     */
    private function _getCache(): null|CacheItemPoolInterface
    {
        return $this->_cachePool;
    }

    /**
     * @param CacheItemPoolInterface $cachePool
     * @return void
     */
    private function _setCache(CacheItemPoolInterface $cachePool):void
    {
        $this->_cachePool = $cachePool;
    }

    #endregion --------------------------------------------------------------------------------------------------------
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
        parent::__construct($options);

        if (isset($options['documentNodeTemplate'])) $this->_setDocumentNodeTemplate($options['documentNodeTemplate']);
        if (isset($options['documentNodeStylesheet'])) $this->_setDocumentNodeStylesheet($options['documentNodeStylesheet']);
        if (isset($options['cache'])) $this->_setCache($options['cache']);
    }

    /**
     * @param string|int $bookIndex
     * @param array $option
     * @inheritdoc
     */
    public function exportBook(string|int $bookIndex = 0, array $options = []): bool
    {
        return false;
    }

    public function exportPage(string|int $bookIndex, string $path, array $options = []): bool|string
    {
        return false;
//        if (isset($options['documentNodeTemplate'])) $this->_setDocumentNodeTemplate($options['documentNodeTemplate']);
//        if (isset($options['documentNodeStylesheet'])) $this->_setDocumentNodeStylesheet($options['documentNodeStylesheet']);
//        if (isset($options['cache'])) $this->_setCache($options['cache']);
//
//        if (!isset($this->_bookCollection[$bookIndex])) {
//            header('HTTP/1.0 404 Not found');
//            echo "Book '$bookIndex' Not found";
//            return false;
//        }
//
//        Node::$uriOffset = $bookIndex;
//        $book = $this->_bookCollection[$bookIndex];
//        $page =  str_replace(Node::$uriOffset, '', $path);
//        $node = $book->getChild($page);
//
//        //if the node is not found, return a 404
//        if (null === $node){
//            header('HTTP/1.0 404 Not found');
//            echo "Page '$bookIndex/$page' Not found";
//            return false;
//        }
//
//        // if the requested node is a resource, pass through
//        if ($node->isResource()) {
//            if ($node->getContentType() ===  ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
//                header('HTTP/1.0 415 Media-type not supported');
//                return false;
//            }
//            header('Content-Type: ' . $node->getContentType());
//            header("Content-Length: " . filesize($node->getAbsolutePath()));
//            readfile($node->getAbsolutePath());
//            exit(0);
//        }
//
//        if (!str_ends_with($path, '/'))
//        {
//            //header("HTTP/1.1 301 Moved Permanently");
//            //header('Location: '. $bookIndex . $page . '/');
//        }
//        // cache pages for speed if the node can be cached
//        $start = microtime(true);
//
//        if ($node->getMeta()->get('isCacheable', true) && Settings::getCache() !== null) {
//            $cachedNode = Settings::getCache()->getItem($node->getRelativePath());
//
//            // check if we need to invalidate the cache
//            if ($cachedNode->isHit()) {
//                if ( $node->getModificationTime() > (int)@filemtime($cachedNode->getKey())) {
//                    echo "<pre>";
//                    echo "\n {$node->getAbsolutePath()} :" . $node->getModificationTime();
//                    echo "\n {$cachedNode->getKey()} :" . filemtime($cachedNode->getKey());
//                    echo "</pre>";
//                    Settings::getCache()->deleteItem($node->getRelativePath());
//                }
//            }
//
//            if ($cachedNode->isHit()) {
//                echo $cachedNode->get();
//                echo "\n<!-- Retrieved from cache in:  " . number_format(microtime(true) - $start, 10) . " seconds -->";
//            } else {
//
//                $uriOffset =
//                    ob_start();
//                include $this->getPageTemplate();
//                $cachedNode->set(ob_get_contents());
//                ob_end_flush();
//                echo "\n<!-- generated in:  " . number_format(microtime(true) - $start, 10) . " seconds -->";
//            }
//        } else {
//            include $this->getPageTemplate();
//            echo "\n<!-- generated in:  " . number_format(microtime(true) - $start, 10) . " seconds (page set not be cached)-->";
//        }
//        return true;

    }

    /**
     * @inheritDoc
     * @param NodeInterface $node
     * @return string
     */
    public function sanitizeUri(NodeInterface $node): string
    {
        // create the relative uri for this node thus including a leading SLUG_SEPARATOR
        $relativeUri = str_replace(DIRECTORY_SEPARATOR, Node::SLUG_SEPARATOR,  $node->getRelativePath());

        // if it is a DocumentNode, add the trailing slash to make all relative uris valid
        if($node->isDocument()) {
            $relativeUri .= "/";
        }

        return $this->getUriOffset() . $relativeUri;
    }
}