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

use Volta\Component\Books\Node;
use Volta\Component\Books\Publisher;
use Volta\Component\Books\ResourceNode;
use Volta\Component\Books\Settings;

class Web extends Publisher
{

    /**
     * @param array $options
     */
    protected function __construct(array $options)
    {
        parent::__construct($options);
        Settings::setPublishingMode(Settings::PUBLISHING_WEB);

        if (isset($options['cache'])) {
            Settings::setCache($options['cache']);
        }

    }




    public function exportBook(string $bookIndex): bool
    {
        return false;
    }

    public function exportPage(string $bookIndex, string $path): bool
    {
        if (!isset($this->_bookCollection[$bookIndex])) {
            header('HTTP/1.0 404 Not found');
            echo "Book '$bookIndex' Not found";
            return false;
        }

        Node::$uriOffset = $bookIndex;
        $book = $this->_bookCollection[$bookIndex];
        $page =  str_replace(Node::$uriOffset, '', $path);
        $node = $book->getChild($page);

        //if the node is not found return a 404
        if (null === $node){
            header('HTTP/1.0 404 Not found');
            echo "Page '$bookIndex/$page' Not found";
            return false;
        }

        // if the requested node is a resource pass through
        if ($node->isResource()) {
            if ($node->getContentType() ===  ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
                header('HTTP/1.0 415 Media-type not supported');
                return false;
            }
            header('Content-Type: ' . $node->getContentType());
            header("Content-Length: " . filesize($node->getAbsolutePath()));
            readfile($node->getAbsolutePath());
            exit(0);
        }

        if (!str_ends_with($path, '/'))
        {
            //header("HTTP/1.1 301 Moved Permanently");
            //header('Location: '. $bookIndex . $page . '/');
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

                $uriOffset =
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
        return true;

    }
}