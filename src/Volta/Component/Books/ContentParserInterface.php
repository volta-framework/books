<?php

namespace Volta\Component\Books;

interface ContentParserInterface
{
    public function getNode(): NodeInterface;

    public function getContent(string $file, NodeInterface $node, bool $verbose = false): string;

}