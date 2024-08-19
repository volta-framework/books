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

namespace Volta\Component\Books\Controllers;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\StreamFactory;

use Volta\Component\Books\Cache;
use Volta\Component\Books\DocumentNode;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\MimeTypeNotSupportedException;
use Volta\Component\Books\Publisher;
use Volta\Component\Books\PublisherInterface;
use Volta\Component\Books\Publishers\Web;
use Volta\Component\Books\ResourceNode;
use Volta\Component\Books\Settings;
use Volta\Component\Configuration\Config;
use Volta\Component\Configuration\Key;
use Volta\Component\Configuration\Exception as ConfigException;
use Volta\Component\Logging\ConsoleLogger;

class BooksController
{

    #region - Construction and configuration


    private Config $_config;
    private LoggerInterface $_log;

    /**
     * When used with slim application a container instance is passed in the constructor.
     * From it, we check if there is a Volta configuration object or file is present to configure the controller.
     *
     * @param ContainerInterface|null $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws ConfigException
     */
    #[Key('volta.component.books.supportedResources', [], 'Limit or extend supported resources. Expected array in the format array<extension, mime-type>.See Settings::getSupportedResources() for more details')]
    #[Key('volta.component.books.contentParsers', [], 'Limit or extend supported resources. Expected array in the format array<extension, mime-type>. See Settings::registerContentParser() for more details')]
    #[Key('volta.component.books.library', ['een' => '~resources/ExampleBook', 'twee' => '~resources/ExampleBook'], '[REQUIRED] List of Volta Books. Expected to be in the format array<identifier, path>')]
    #[Key('volta.component.books.cache.class', '', 'Classname for a Cache:CacheItemPoolInterface object. Use the books internal class ('. Cache::class.') for file based cache.')]
    #[Key('volta.component.books.cache.options', [], 'Options for the cache instance. May be different based on the cache selected. When using the internal class ('. Cache::class.') a valid directory is expected, use ["directory"=> "???"] for the options')]
    #[Key('volta.component.books.book-template', '~templates/web-book.phtml', 'HTML template for displaying one book. Defaults to the Components "~templates/web-book.phtml" template.')]
    #[Key('volta.component.books.book-overview-template', '~templates/web-book-overview.phtml', 'HTML template for displaying all the books in the publishers library. Defaults to the Components "~templates/web-book-overview.phtml" template.')]
    public function __construct(ContainerInterface|null $container=null)
    {
        // create instance of the configuration object and set required and allowed options
        $this->_config = new Config();
        $this->_config->setRequiredOptions(['volta.component.books.library']);

        // check if we have a custom configuration passed through the slim app class
        // if not use our own configuration
        if (isset($container) && $container->has('conf')) {
            $this->_config->setOptions($container->get('conf'));
        } else {
            $this->_config->setOptions(__DIR__ . '/../config/config.php');
        }

        // set the log
        if (isset($container) && $container->has('log')) {
            $this->_log = $container->get('log');
        } else {
            $this->_log = new ConsoleLogger();
        }
        BooksController::getPublisher()->setLogger($this->_log);

        // only overwrite when present in the config-file
        if ($this->_config->hasOption('volta.component.books.supportedResources')) {
            Settings::setSupportedResources(
                (array)$this->_config->getOption('volta.component.books.supportedResources')
            );
        }

        // only overwrite per item when present in the config-file
        if ($this->_config->hasOption('volta.component.books.contentParsers')) {
            foreach ((array)$this->_config->getOption('volta.component.books.contentParsers') as $extension => $class) {
                Settings::registerContentParser($extension, $class);
            }
        }

        // this option is required, so we may assume it is there
        foreach ($this->_config->get('volta.component.books.library') as $bookIndex => $bookPath) {
            BooksController::getPublisher()->addBook(realpath($bookPath), $bookIndex);
        }

        // configure the controllers cache
        $cacheClass = $this->_config->get('volta.component.books.cache.class', '');
        $cacheOptions = $this->_config->get('volta.component.books.cache.options', ['directory' => realpath(__DIR__ . '/../__cache/')]);
        if (!empty($cacheClass)){
            $cache = new $cacheClass($cacheOptions);
            BooksController::setCache($cache);
        }

        // set template and style
        BooksController::setDocumentNodeTemplate($this->_config->get('volta.component.books.template' ,  __DIR__ . '/../templates/web-book.phtml'));

    }


    #endregion --------------------------------------------------------------------------------------------------------
    #region - Publisher settings

    /**
     * @ignore(do not show up in the generated documentation)
     * @var PublisherInterface
     */
    protected static PublisherInterface $_publisher;

    /**
     * @param PublisherInterface $publisher
     * @return void
     */
    public function setPublisher(PublisherInterface $publisher): void
    {
        BooksController::$_publisher = $publisher;
    }

    /**
     * Returns the current publisher, defaults to components build in http(web) publisher
     *
     * @return PublisherInterface
     * @throws Exception
     */
    public static function getPublisher(): PublisherInterface
    {
        if(!isset(BooksController::$_publisher)) {
            BooksController::$_publisher = Publisher::factory(
                Web::class,
                [

                ]
            );
        }
        return BooksController::$_publisher;
    }

    #endregion --------------------------------------------------------------------------------------------------------
    #region - URI offset


    public static function getUriOffset(): string
    {
        return \Volta\Component\Books\Settings::getUriOffset();
    }
    public static function setUriOffset(string $uriOffset): void
    {
        \Volta\Component\Books\Settings::setUriOffset($uriOffset);
    }

    #endregion --------------------------------------------------------------------------------------------------------
    #region - DocumentNodeTemplate settings

    /**
     * @ignore(do not show up in the generated documentation)
     * @var string
     */
    protected static string $_documentNodeTemplate;

    /**
     * Sets the location for the PHP-HTML DocumentNode template.
     *
     * @param string $template The location for the DocumentNode template.
     * @return void
     * @throws Exception When $template is an invalid path
     */
    public static function setDocumentNodeTemplate(string $template): void
    {
        if (!is_file($template)) throw new Exception(__METHOD__ . ': invalid file.');
        BooksController::$_documentNodeTemplate = $template;
    }

    /**
     * Returns the location for the DocumentNode template. Defaults to PHP-HTML file "~/templates/web-book.phtml"
     * @return string
     */
    public static function getDocumentNodeTemplate(): string
    {
        if(!isset(BooksController::$_documentNodeTemplate)) {
            BooksController::$_documentNodeTemplate = __DIR__ . '/../templates/web-book.phtml';
        }
        return BooksController::$_documentNodeTemplate;
    }


    #endregion --------------------------------------------------------------------------------------------------------
    #region - Caching Settings


    protected static null|CacheItemPoolInterface $_cache=null;

    public static function setCache(CacheItemPoolInterface $cache ): void
    {
        BooksController::$_cache = $cache;
    }
    public static function getCache(): null|CacheItemPoolInterface
    {
        return BooksController::$_cache;
    }


    #endregion --------------------------------------------------------------------------------------------------------
    #region - Route action handlers



    /**
     * @throws MimeTypeNotSupportedException
     */
    private function _sendResourceNode(ResourceNode $node, ResponseInterface $response) : ResponseInterface
    {
        if ($node->getContentType() ===  ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
            $response = $response->withStatus(415, 'Media-type not supported');
        } else {
            $streamFactory = new StreamFactory();
            $stream = $streamFactory->createStreamFromFile($node->getAbsolutePath());
            $response = $response
                ->withBody($stream)
                ->withHeader('Content-type', $node->getContentType())
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public');
        }

        return $response;
    }

    /**
     * @param DocumentNode $node
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function _sendDocumentNode(DocumentNode $node, ResponseInterface $response): ResponseInterface
    {
        $start = microtime(true);
        if ($node->getMeta()->get('isCacheable', true)) {
            if (BooksController::getCache() !== null) {
                $cachedNode = BooksController::getCache()->getItem($node->getRelativePath());
                if ($cachedNode->isHit()) {
                    if ($node->getModificationTime() > (int)@filemtime($cachedNode->getKey())) {
                        echo "<pre>";
                        echo "\n {$node->getAbsolutePath()} :" . $node->getModificationTime();
                        echo "\n {$cachedNode->getKey()} :" . filemtime($cachedNode->getKey());
                        echo "</pre>";
                        BooksController::getCache()->deleteItem($node->getRelativePath());
                    }
                    echo $cachedNode->get();
                    $this->_log->info($node->getUri(). " retrieved from cache in: " . number_format(microtime(true) - $start, 10) . " seconds");

                } else {
                    $uriOffset = ob_start();
                    include BooksController::getDocumentNodeTemplate();
                    $cachedNode->set(ob_get_contents());
                    ob_end_flush();
                    $this->_log->info($node->getUri(). " generated in: " . number_format(microtime(true) - $start, 10) . " seconds");
                }
            } else {
                include (BooksController::getDocumentNodeTemplate());
                $this->_log->info($node->getUri(). " generated in: " . number_format(microtime(true) - $start, 10) . " seconds (cache not set)");
            }
        } else {
            include (BooksController::getDocumentNodeTemplate());
            $this->_log->info($node->getUri(). " generated in: " . number_format(microtime(true) - $start, 10) . " seconds (page set not be cached)");
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     * @throws Exception|InvalidArgumentException|ConfigException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        // no books return a 404
        if (count(BooksController::getPublisher()->getBooks()) === 0) {
            throw new HttpNotFoundException($request, 'Sorry, it seems we are out of books :-(');
        }

        // get information from the request
        $uriPath = $request->getUri()->getPath();
        $bookIndex = $args['bookIndex'] ?? false;
        $bookNode = $args['bookNode'] ?? '';

        // if there is no book information show the overview page
        if (!$bookIndex) {
            $uriOffset = $uriPath;
            $publisher = BooksController::getPublisher();
            include $this->_config->get('volta.component.books.book-overview-template', __DIR__ . '/../templates/web-book-overview.phtml');

            // otherwise show the requested node
        } else {

            $uriSeparator = '/';
            $nodePath = $uriSeparator . $bookNode;
            $startBookIndex = strpos($request->getUri()->getPath(), $uriSeparator . $bookIndex);
            $uriOffset = substr($uriPath, 0, $startBookIndex) . $uriSeparator . $bookIndex;
            BooksController::getPublisher()->setUriOffset($uriOffset);

            // get the book
            $book = BooksController::getPublisher()->getBook($bookIndex);
            if ($book === null) throw new HttpNotFoundException($request, 'Sorry, Could not find a book named  "' . $bookIndex . '" :-(');

            // get the child node
            $node = $book->getChild($nodePath);
            if ($node === null) {
                throw new HttpNotFoundException($request,
                    sprintf('Sorry, no such page(%s) in %s', $nodePath, $book->getDisplayName())
                );
            }

            // return the content, if it is a document node add a slash
            // in order to maintain the relative links in the pages
            if (is_a($node, ResourceNode::class)) {
                $response = $this->_sendResourceNode($node, $response);
            } else {
                if (is_a($node, DocumentNode::class)) {
                    if (!str_ends_with($request->getUri()->getPath(), $uriSeparator)) {
                        $response = $response->withStatus(302)->withHeader('Location', $request->getUri()->getPath() . '/');
                    } else {
                        $response = $this->_sendDocumentNode($node, $response);
                    }
                }
            }
        }

        // finally return response
        return $response;
    }

    #endregion --------------------------------------------------------------------------------------------------------

}