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

/**
 * Start the PHP webserver in this directory like:
 *
 *  php -S localhost:8080 index.php
 *
 * This way the index.php wil act as a front controller and will serve
 * all the static resources as well
 */

use Volta\Component\Books\Cache;
use Volta\Component\Books\Node;
use Volta\Component\Books\ResourceNode;
use Volta\Component\Books\Settings;

// We want to see all errors hence it is an example
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load all classes
require_once __DIR__ . '/../vendor/autoload.php';

try {

    // serve static pages by returning false when using the cli-server
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (false !== ($pos = strpos($uri, '?'))) $uri = substr($uri, 0, $pos);
    if (is_file(__DIR__ . $uri) && php_sapi_name() === 'cli-server') return false;

    // in this example we want the book to be the website
    Node::$uriOffset = '';

    //$book = Node::factory(__dir__ . '/../Book');
    //$book = Node::factory('/home/rob/Development/PHP-REPOSITORIES/volta-framework/documentation/VoltaCookbook');
    $book = Node::factory('C:\rob\DocumentenLokaal\volta-framework\documentation\VoltaCookbook');
    $page =  str_replace(Node::$uriOffset, '',$uri );
    $node = $book->getChild($page);

    //if the node is not found return a 404
    if (null === $node){
        header('HTTP/1.0 404 Not found');
        exit(1);
    }

    // if the requested node is a resource pass through
    if (is_a($node,  ResourceNode::class)) {
        if ($node->getContentType() ===  ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
            header('HTTP/1.0 415 Media-type not supported');
            exit(1);
        }
        header('Content-Type: ' . $node->getContentType());
        header("Content-Length: " . filesize($node->getAbsolutePath()));
        readfile($node->getAbsolutePath());
        exit(0);
    }

    // cache pages for speed if the node can be cached
    $start = microtime(true);
    if ($node->getMeta()->get('isCacheable', true)) {

        Settings::setCache(new Cache(realpath(__DIR__ . '/../__cache')));
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
            echo "\n<!-- retrieved from cache in:  " . number_format(microtime(true) - $start, 10) . " seconds -->";
        } else {
            ob_start();
            include __DIR__ . '/../template.phtml';
            $cachedNode->set(ob_get_contents());
            ob_end_flush();
            echo "\n<!-- generated in:  " . number_format(microtime(true) - $start, 10) . " seconds -->";
        }
    } else {
        include __DIR__ . '/../template.phtml';
        echo "\n<!-- generated in:  " . number_format(microtime(true) - $start, 10) . " seconds (page set not be cached)-->";
    }

    exit(0);


} catch(\Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    ob_end_flush();
    exit($e->getMessage());
}