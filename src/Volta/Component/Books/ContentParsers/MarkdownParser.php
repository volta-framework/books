<?php

namespace Volta\Component\Books\ContentParsers;

use Parsedown;
use Volta\Component\Books\ContentParserInterface;
use Volta\Component\Books\ContentParserTrait;
use Volta\Component\Books\NodeInterface;

class MarkdownParser implements ContentParserInterface
{
    use ContentParserTrait;

    public function getContent(string $file, NodeInterface $node, bool $verbose = false): string
    {
        $this->_node = $node;

        $ParseDown = new Parsedown();
        $ParseDown->setMarkupEscaped(true);
        return $ParseDown->text(file_get_contents($file));

    }
}