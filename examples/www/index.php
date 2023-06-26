<?php
/**
 * Start the PHP webserver in this directory with the like:
 *
 *  php -S localhost:8080 index.php
 *
 * this way the index.php wil act as a front controller and will serve
 * all the resources as well
 */
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $bookName = 'Book';
    $bookshelf = new Volta\Component\Books\Bookshelf();
    $bookshelf->addBook(__dir__ . '/../' . $bookName);
    $book = $bookshelf->getBook($bookName);
    $page = $_SERVER['REQUEST_URI'];
    $node = $book->getChild($page);
    if (is_a($node, Volta\Component\Books\ResourceNode::class)) {
        if ($node->getContentType() ===  Volta\Component\Books\ResourceNode::MEDIA_TYPE_NOT_SUPPORTED) {
            header('HTTP/1.0 415 Media-type not supported');
            exit();
        }
        header('Content-Type: ' . $node->getContentType());
        header("Content-Length: " . filesize($node->getAbsolutePath()));
        readfile($node->getAbsolutePath());
        exit();
    }

} catch(\Throwable $e) {
    exit($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Volta Component Books Example</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="" />
    <link rel="icon" href="favicon.png">
</head>
<body style="margin:auto; width:800px;padding:20px;">
    <nav style="display:flex; justify-content: space-between">
        <?php if(null !== $node->getPrevious()): ?>
            <?php if(null !== $node->getPrevious()->getParent()): ?>
                <a href="<?= $node->getPrevious()->getUri(false);?>"><?= $node->getPrevious()->getName();?></a>
            <?php else: ?>
                <a href="/"><?= $node->getPrevious()->getName();?></a>
            <?php endif; ?>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
        <?php if(null !== $node->getNext()): ?>
            <a href="<?= $node->getNext()->getUri(false);?>"><?= $node->getNext()->getName();?></a>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
    </nav>
    <?php echo $node->getContent(); ?>
</body>
</html>
