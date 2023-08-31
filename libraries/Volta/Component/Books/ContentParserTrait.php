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

trait ContentParserTrait
{

    /**
     * @var NodeInterface $_node
     */
    protected NodeInterface $_node;

    /**
     * @return NodeInterface
     */
    public function getNode():NodeInterface
    {
        return $this->_node;
    }

    /**
     * @param NodeInterface $node
     * @return ContentParsers\HtmlParser|ContentParsers\PhpParser|ContentParsers\TxtParser|ContentParsers\XhtmlParser|ContentParserTrait
     */
    public function setNode(NodeInterface $node): self
    {
        $this->_node = $node;
        return $this;
    }
}