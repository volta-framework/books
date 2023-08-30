# Volta\Component\Books - Templates

`web.book.php`
```php
<?php
declare(strict_types=1);
if (!isset($node))  return 'placeholder $node not set.';
?>
<!DOCTYPE html>
<html lang="<?=$node->getRoot()->getMeta()->get('language', 'en')?>">
<head>
    <meta charset="UTF-8" />
    <title><?= $node->getRoot()->getDisplayName() . ': ' . $node->getDisplayName();?></title>

    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" title="Default" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
    <!--    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/dark.min.css" >-->
    <link rel="stylesheet" href="/assets/css/web-book.css">

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
            <li><a href="<?= $node->getRoot()->getUri();?>index/">Index</a></li>
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

    <script type="module">
        import {addPageToc} from '/assets/js/book.mjs';
        addPageToc(1);
    </script>

</body>
</html>
```


`epub.book.php`
```php
if (!isset($node))  return 'placeholder $node not set.';
$level  = $level ?? 0;
?>
<!DOCTYPE html>
<html  xmlns="http://www.w3.org/1999/xhtml" lang="<?=$node->getRoot()->getMeta()->get('language', 'en')?>" xml:lang="<?=$node->getRoot()->getMeta()->get('language', 'en')?>">
<head>
    <meta charset="UTF-8" />
    <title><?= $node->getRoot()->getDisplayName() . ': ' . $node->getDisplayName();?></title>
    <link rel="stylesheet" href="<?= str_repeat('../', $level)?>epub-book.css"/>
</head>
<body>
    <main>
        <?php try { ?>
            <?= $node->getContent(); ?>
        <?php } catch(Throwable $e) { ?>
            <blockquote class="error"><?= $e->getMessage(); ?></blockquote>
        <?php }; ?>
    </main>
</body>
</html>
```