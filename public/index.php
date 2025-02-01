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

use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteInterface;
use Volta\Component\Books\Controllers\BooksController;

/*
 * By including the autoload, we have all the classes at our fingertips
 */
require_once __DIR__ . '/../vendor/autoload.php';

/*
 * Start the PHP webserver in this directory like:
 *
 *  php -S localhost:8080 index.php
 *
 * This way this index.php wil act as a front controller and will serve
 * all the static resources as well
 *
 * As this is an example(test) we make sure we see all errors.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
try {

    /* When using the build-in webserver return false to serve the static content */
    if (is_file(__DIR__. $_SERVER['REQUEST_URI']) &&
        php_sapi_name() === 'cli-server') {
        return false;
    }

   /*
    * Redirect to BooksController::getUriOffset() which is the start page of this component
    * NOTE: BooksController::getUriOffset() can not be set through the configuration as this is not loaded yet
    */
    if (!str_starts_with($_SERVER['REQUEST_URI'], BooksController::getUriOffset())) {
        header('location: ' . BooksController::getUriOffset());
        exit();
    }

    /*
     * Throughout the Volta packages we use the Slim implementation for routing
     * See https://www.php-fig.org/psr/psr-7/ for more information.
     */
    $app = AppFactory::create();
    $app->addRoutingMiddleware();
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);

    /**
     * Include the routes and run the application
     * @var RouteInterface $route
     */
    $routes = require_once __DIR__ . '/../config/routes.php';
    foreach($routes as $routeName => $routeInfo) {
        if (is_array($routeInfo['methods'])) {
            $route = $app->map($routeInfo['methods'], $routeInfo['path'], $routeInfo['handler']) ;
        } else if (is_string($routeInfo['methods'])) {
            $method = $routeInfo['methods'];
            $route = $app->$method($routeInfo['path'], $routeInfo['handler']) ;
        }
        if (is_string($routeName)) $route->setName($routeName);
    }
    $app->run();
    exit(0);

} catch(\Throwable $e) {

    /*
     * On error, we send an HTTP 500 status, empty the buffer if any
     * and print out the error
     */
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: text/plain');
    ob_end_clean();
    echo "VOLTA COMPONENT BOOKS ERROR \n";
    echo str_repeat('-', 120), " \n";
    echo "code   : {$e->getCode()} \n";
    echo "message: {$e->getMessage()} \n";
    echo "file   : {$e->getFile()} \n";
    echo "line   : {$e->getLine()} \n";
    echo str_repeat('-', 120), " \n";
    echo "Backtrace(ignoring arguments and limits to 100): \n";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100);
    exit(1);
}