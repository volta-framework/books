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

/**
 * As this is an example we want to see all errors.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * By including the autoload we have all the classes at our fingertips
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Catch all errors as well
 */
try {

    /**
     * get the requested book and page by stripping the query string from the request Uri
     */
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (false !== ($pos = strpos($uri, '?'))) $uri = substr($uri, 0, $pos);

    /**
     * when using the cli-server serve static pages by returning false
     */
    if (is_file(__DIR__ . $uri) && php_sapi_name() === 'cli-server') return false;

    /**
     * initialize a cache pool. place comment markers before the line to disable the cache
     */
    //Settings::setCache(new Cache(realpath(__DIR__ . '/../__cache')));

    /**
     * configure our BookCase and add some books
     */
    $bs = new BookCase(__DIR__ . '/../templates/web-book.phtml');
    $bs->addBook('', '/home/rob/Development/PHP-REPOSITORIES/volta-framework/documentation/VoltaCookbook');
    //$bs->addBook('', 'C:\rob\DocumentenLokaal\volta-framework\documentation\VoltaCookbook');

    /**
     * Ask the bookCase to send the content of the requested page of the requested book
     */
    // TODO make this a PSR http compliant HTTP message
    $bs->sendContent('', $uri);

} catch(\Throwable $e) {

    /**
     * On error, we send an HTTP 500 status, empty the buffer if any
     * and print out the error
     */
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: text/plain');
    ob_end_clean();
    echo "ERROR \n";
    echo str_repeat('-', 120), " \n";
    echo "code   : {$e->getCode()} \n";
    echo "message: {$e->getMessage()} \n";
    echo "file   : {$e->getFile()} \n";
    echo "line   : {$e->getLine()} \n";
    echo "\n";
    if(count(debug_backtrace())) {
        echo "Backtrace(ignoring arguments and limits to 100): \n";
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100);
    }
    exit(1);
}