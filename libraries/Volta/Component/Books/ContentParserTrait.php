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

use Volta\Component\Books\ContentParsers\HtmlParser;
use Volta\Component\Books\ContentParsers\MarkdownParser;
use Volta\Component\Books\ContentParsers\PhpParser;
use Volta\Component\Books\ContentParsers\TxtParser;
use Volta\Component\Books\ContentParsers\XhtmlParser;

trait ContentParserTrait
{

    /**
     * @var NodeInterface $_node
     */
    protected NodeInterface $_node;

    /**
     * @inheritDoc
     */
    public function getNode():NodeInterface
    {
        return $this->_node;
    }

    /**
     * @inheritDoc
     */
    public function setNode(NodeInterface $node): self
    {
        $this->_node = $node;
        return $this;
    }
}