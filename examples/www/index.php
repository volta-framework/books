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
 * this way the index.php wil act as a front controller and will serve
 * all the resources as well
 */


/**
 * We want to see all errors hence it is an example
 */
use Volta\Component\Books\Node;
use Volta\Component\Books\ResourceNode;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';

try {

    // serve static pages by returning false when using the cli-server
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (false !== ($pos = strpos($uri, '?'))) $uri = substr($uri, 0, $pos);
    if (is_file(__DIR__ . $uri) && php_sapi_name() === 'cli-server') return false;

    // in this example we want the bool to be the website
    Node::$uriOffset = '';

    //$book = Node::factory(__dir__ . '/../Book');
    $book = Node::factory('/home/rob/Development/PHP-REPOSITORIES/volta-framework/documentation/VoltaCookbook');
    //$book = Node::factory('C:\rob\DocumentenLokaal\volta-framework\documentation\VoltaCookbook');

    $page =  str_replace(Node::$uriOffset, '', $_SERVER['REQUEST_URI']);
    $node = $book->getChild($page);

    if (null === $node){
        header('HTTP/1.0 404 Not found');
        exit(1);
    }
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
} catch(\Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= $node->getRoot()->getDisplayName() . ': ' . $node->getDisplayName();?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
    <link rel="stylesheet" href="/assets/css/book.css">
</head>
<body>
    <header><?= $node->getDisplayName();?></header>

    <nav>
        <?php if(null !== $node->getPrevious()): ?>
            <a href="<?= $node->getPrevious()->getUri();?>"><?= $node->getPrevious()->getDisplayName();?></a>
        <?php else: ?>
            <div><!-- placeholder --></div>
        <?php endif; ?>
        <?php if(null !== $node->getNext()): ?>
            <a href="<?= $node->getNext()->getUri();?>"><?= $node->getNext()->getDisplayName();?></a>
        <?php else: ?>
            <div><!-- placeholder --></div>
        <?php endif; ?>
    </nav>

    <main>

        <ul id="favorites">
            <li><a href="<?= $node->getRoot()->getUri();?>">Home</a></li>
            <li><a href="<?= $node->getRoot()->getUri();?>00-index">Index</a></li>
        </ul>

        <?php try { ?>
            <?= $node->getContent(); ?>
        <?php } catch(Throwable $e) { ?>
            <blockquote class="error"><?= $e->getMessage(); ?></blockquote>
        <?php }; ?>
    </main>

    <footer>- <?= $node->getIndex() ?>-<br>
        <?= $node->getRoot()->getMeta()->get('copyright', '')?>
    </footer>

    <!-- https://highlightjs.org/usage/ -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.4.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>

    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ startOnLoad: false });
        await mermaid.run({
            querySelector: '.language-mermaid',
        });
    </script>

</body>
</html>
