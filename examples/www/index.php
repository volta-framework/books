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
    Node::$uriOffset = '';

    //$book = Node::factory(__dir__ . '/../Book');
    //$book = Node::factory('/home/rob/Development/PHP-REPOSITORIES/volta-framework/documentation/VoltaCookbook');
    $book = Node::factory('C:\rob\DocumentenLokaal\volta-framework\documentation\VoltaCookbook');



    
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
    <style>
        body {margin:auto; width:80vw; padding:20px; font-family: Verdana, serif; color:darkslategrey; line-height: 1.8}
        header {text-align: center;  color:lightgrey;  padding:10px;}
        nav {padding:10px;}
        main {border:1px solid lightgrey; min-height:80vh; padding:10px; border-radius: 10px;}
        footer {text-align: center; color:lightgrey;  padding:10px;}

        /**/
        main{counter-reset: h1}
        h1{counter-reset: h2;}

        h2{counter-reset: h3;} h2::before{ counter-increment: h2; content: counter(h2) ". "; }
        h3{counter-reset: h4;} h3::before{ counter-increment: h3; content: counter(h2) "." counter(h3) ". "}
        h4{counter-reset: h5;} h4::before{ counter-increment: h4; content: counter(h2) "." counter(h3) "." counter(h4) ". "}

        /**/
        img {width: 100%; height: auto; margin:auto 0 auto 0; display: block;}
        h1, h2 { border-bottom: 1px solid lightgrey; color:sienna}
        a:link, a:visited, a:active, a:hover { color:lightseagreen; text-decoration: none;}
        a:hover {text-decoration: underline;}
        p {text-align: justify; }
        p:first-letter{padding-left: 15px; font-weight: bold; color:darkseagreen;}
        blockquote{ border-left: 4px solid darkseagreen;
            border-top: 1px solid darkseagreen;
            border-right: 4px solid darkseagreen;
            border-bottom: 1px solid darkseagreen;
            border-radius: 4px; padding: 10px; }
        pre{ border: 1px solid lightgray; color #444; border-radius: 5px; padding: 5px; background-color: #f3f3f3}


        /**/
        blockquote.error{ border-left: 4px solid darkred;
            border-top: 1px solid darkred;
            border-right: 4px solid darkred;
            border-bottom: 1px solid darkred;
            border-radius: 4px; padding: 10px;
             color:darkred;
        }
        .footnotes { border-top: 1px solid lightgrey; padding: 20px; font-size: 8pt; margin: 40px 0 0 0 }
        .footnotes li { padding 5px 0 5px 0 }
        .footnote { border-bottom: 1px dotted lightseagreen}
        .footnote > sup {padding: 0 0 0 4px; font-size: 8pt;}

    </style>
</head>
<body>
    <header><?= $node->getDisplayName();?></header>
    <nav style="display:flex; justify-content: space-between">
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
