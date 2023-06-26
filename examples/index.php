<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $bookName = 'Book';
    $bookshelf = new Volta\Component\Books\Bookshelf();
    $bookshelf->addBook(__dir__ . DIRECTORY_SEPARATOR . $bookName);
    $book = $bookshelf->getBook($bookName);
    $page = $_SERVER['REQUEST_URI'];
    $node = $book->getChild($page);
    if (is_a($node, Volta\Component\Books\ResourceNode::class)) {
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
<body style="margin:auto; width:800px;">
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
