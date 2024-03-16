<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-server-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

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
     * Sets the (Document)Node the content belongs to
     *
     * @param NodeInterface $node
     * @return ContentParserInterface
     */
    public function setNode(NodeInterface $node): ContentParserInterface;

    /**
     * Returns the (parsed) content for the selected node
     *
     * @param string $source
     * @param bool $verbose
     * @return string
     */
    public function getContent(string $source, bool $verbose = false): string;

    /**
     * Returns the Mime type of the content parsed
     *
     * @return string
     */
    public function getContentType(): string;

}