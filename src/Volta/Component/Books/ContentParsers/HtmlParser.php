<?php

namespace Volta\Component\Books\ContentParsers;

use Volta\Component\Books\ContentParserInterface;
use Volta\Component\Books\ContentParserTrait;
use Volta\Component\Books\NodeInterface;

class HtmlParser implements ContentParserInterface
{
    use ContentParserTrait;

    public function getContent(string $file, NodeInterface $node, bool $verbose = false): string
    {
        $this->_node = $node;

        return file_get_contents($file);
    }

    public function getContentType(): string
    {
        return 'text/html';
    }
}