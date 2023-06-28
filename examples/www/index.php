<?php
/**
 * Start the PHP webserver in this directory like:
 *
 *  php -S localhost:8080 index.php
 *
 * this way the index.php wil act as a front controller and will serve
 * all the resources as well
 */
declare(strict_types=1);

/**
 * We want to see all errors hence it is an example
 */

use Volta\Component\Books\Node;
use Volta\Component\Books\Settings;
use Volta\Component\Books\ResourceNode;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    Node::$uriOffset  = '/book';

    $book = Node::factory(__dir__ . '/../Book');
    
    $page =  str_replace(Node::$uriOffset, '', $_SERVER['REQUEST_URI']);
    $node = $book->getChild($page);
    if (null === $node){
        header('HTTP/1.0 404 Not found');
        exit();
    }
    if (is_a($node,  ResourceNode::class)) {
        if ($node->getContentType() ===  ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
            header('HTTP/1.0 415 Media-type not supported');
            exit();
        }
        header('Content-Type: ' . $node->getContentType());
        header("Content-Length: " . filesize($node->getAbsolutePath()));
        readfile($node->getAbsolutePath());
        exit();
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
    <title>Volta Component Books Example</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        body {margin:auto; width:80vw; padding:20px; font-family: Verdana, serif; color:#252525; line-height: 1.8}
        header {text-align: center;  color:#cccccc;  padding:10px;}
        nav {padding:10px;}
        main {border:1px solid #eeeeee; min-height:80vh; padding:10px; border-radius: 10px;}
        footer {text-align: center; color:#cccccc;  padding:10px;}

        /**/
        img {width: 100%; height: auto;}
        h1, h2 { border-bottom: 1px solid #cccccc;}
        a:link, a:visited, a:active, a:hover { color:lightseagreen; text-decoration: none;}
        a:hover {text-decoration: underline;}
        p:first-letter{padding-left: 15px; font-weight: bold; color:darkseagreen;}

        /**/
        .error {color:darkred;}
    </style>
</head>
<body>
    <header><?= $node->getName();?></header>
    <nav style="display:flex; justify-content: space-between">
        <?php if(null !== $node->getPrevious()): ?>
            <?php if(null !== $node->getPrevious()->getParent()): ?>
                <a href="<?= $node->getPrevious()->getUri();?>"><?= $node->getPrevious()->getName();?></a>
            <?php else: ?>
                <a href="/"><?= $node->getPrevious()->getName();?></a>
            <?php endif; ?>
        <?php else: ?>
            <div><!-- placeholder --></div>
        <?php endif; ?>
        <?php if(null !== $node->getNext()): ?>
            <a href="<?= $node->getNext()->getUri();?>"><?= $node->getNext()->getName();?></a>
        <?php else: ?>
            <div><!-- placeholder --></div>
        <?php endif; ?>
    </nav>
    <main>
    <?php try { ?>
        <?= $node->getContent(); ?>
    <?php } catch(Throwable $e) { ?>
        <blockquote class="error"><?= $e->getMessage(); ?></blockquote>
    <?php }; ?>
    </main>
    <footer>- <?= $node->getIndex() ?>-</footer>
</body>
</html>
