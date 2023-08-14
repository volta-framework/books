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

/**
 * Start the PHP webserver in this directory like:
 *
 *  php -S localhost:8080 index.php
 *
 * This way this index.php wil act as a front controller and will serve
 * all the static resources as well
 */

use Volta\Component\Books\BookCase;
use Volta\Component\Books\Cache;
use Volta\Component\Books\Settings;

// We want to see all errors hence it is an example
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load all classes
require_once __DIR__ . '/../vendor/autoload.php';

try {


    // get the requested book and page by stripping the query string from the request Uri
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (false !== ($pos = strpos($uri, '?'))) $uri = substr($uri, 0, $pos);

    //  when using the cli-server serve static pages by returning false
    if (is_file(__DIR__ . $uri) && php_sapi_name() === 'cli-server')
        return false;

    // initialize a cache pool
    //Settings::setCache(new Cache(realpath(__DIR__ . '/../__cache')));

    // configure our bookshelf
    $bs = new BookCase(__DIR__ . '/../templates/layout-single-book.phtml');

    // add books
    $bs->addBook('', '/home/rob/Development/PHP-REPOSITORIES/volta-framework/documentation/VoltaCookbook');
    //$bs->addBook('', 'C:\rob\DocumentenLokaal\volta-server-framework\documentation\VoltaCookbook');

    // Then sen d the requested content
    // TODO make this a PSR http compliant HTTP message
    $bs->sendContent('', $uri);

} catch(\Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    ob_end_flush();
    exit($e->getMessage());
}