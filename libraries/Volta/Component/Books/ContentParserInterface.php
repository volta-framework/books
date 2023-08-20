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
     * Returns the (parsed) content for the selected node
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