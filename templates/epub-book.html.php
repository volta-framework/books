<?php
declare(strict_types=1);
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

