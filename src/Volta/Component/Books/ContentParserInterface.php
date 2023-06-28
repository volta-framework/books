<?php

namespace Volta\Component\Books;

interface ContentParserInterface
{
    /**
     * Returns the node the content belongs to.
     *
     * @return NodeInterface
     */
    public function getNode(): NodeInterface;

    /**
     * Returns the (parsed) content
     *
     * @param string $file
     * @param NodeInterface $node
     * @param bool $verbose
     * @return string
     */
    public function getContent(string $file, NodeInterface $node, bool $verbose = false): string;

    /**
     * Returns the Mime type of the content parsed
     *
     * @return string
     */
    public function getContentType(): string;

}