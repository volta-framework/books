<?php
declare(strict_types=1);

use Volta\Component\Books\Controllers\BooksController;
use Volta\Component\Books\NodeInterface;

/** @var NodeInterface $node  */
if (!isset($node))  return __FILE__ . '  placeholder $node not set.';
?>
<!DOCTYPE html>
<html lang="<?=$node->getRoot()->getMeta()->get('language', 'en')?>">
<head>
    <meta charset="UTF-8" />
    <title><?= $node->getRoot()->getDisplayName() . ': ' . $node->getDisplayName();?></title>

    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" title="Default" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/light.min.css" >
    <link rel="stylesheet" href="/assets/css/web-book.css">

</head>
<body>
    <header><?= $node->getDisplayName();?></header>

    <nav>
        <?php if(null !== $node->getPrevious()): ?>
            <a  class="previous" href="<?= $node->getPrevious()->getUri(true);?>"><?= $node->getPrevious()->getDisplayName();?></a>
        <?php else: ?>
            <div><!-- placeholder --></div>
        <?php endif; ?>
        <?php if(null !== $node->getNext()): ?>
            <a class="next" href="<?= $node->getNext()->getUri(true);?>"><?= $node->getNext()->getDisplayName();?></a>
        <?php else: ?>
            <div><!-- placeholder --></div>
        <?php endif; ?>
    </nav>

    <main>
        <ul id="favorites">
            <li><a href="<?= BooksController::getUriOffset();?>">Library</a></li>
            <li><a href="<?= $node->getRoot()->getUri();?>">Start</a></li>
            <?php if ($node->getRoot()->getMeta()->has('tocPage')) { ?>
                <li><a href="<?= $node->getRoot()->getPublisher()->getUriOffset() . $node->getRoot()->getMeta()->get('tocPage'); ?>">TOC</a></li>
            <?php }; ?>
            <?php foreach($node->getRoot()->getMeta()->get('favorites', []) as $favorite) { ?>
                <li><a href="<?= $favorite['link'] ?? '#'; ?>"><?= $favorite['caption'] ?? 'Unknown Root favorite'; ?></a></li>
            <?php }; ?>
            <?php if ( !$node->isBook()) { ?>
                <?php foreach($node->getMeta()->get('favorites', []) as $favorite) { ?>
                    <li><a href="<?= $favorite['link'] ?? '#'; ?>"><?= $favorite['caption'] ?? 'Unknown Node favorite'; ?></a></li>
                <?php }; ?>
            <?php }; ?>
        </ul>
        <div id="content">
            <?php if(null !== $node->getPrevious()): ?>
                <div id="pagePrevious" onclick="location.href='<?= $node->getPrevious()->getUri(true);?>'" title="&xlarr; <?= $node->getPrevious()->getDisplayName();?>"></div>
            <?php endif; ?>
            <?php if(null !== $node->getNext()): ?>
                <div id="pageNext" onclick="location.href='<?= $node->getNext()->getUri(true);?>'" title="<?= $node->getNext()->getDisplayName();?> &xrarr; "> </div>
            <?php endif; ?>
            <?php try { ?>
                <?= $node->getContent(); ?>
            <?php } catch(Throwable $e) { ?>
                <blockquote class="error"><?= $e->getMessage(); ?></blockquote>
            <?php }; ?>
        </div>
    </main>

    <footer>-&nbsp;<?= $node->getIndex() ?>&nbsp;-<br>
        <?= $node->getRoot()->getMeta()->get('copyright', '')?><br>
        last changed: <?= date('Y-m-d H:i:s', $node->getModificationTime())?>
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

    <script type="module">
        import {addPageToc} from '/assets/js/book.mjs';
        addPageToc(1);


    </script>


</body>
</html>

