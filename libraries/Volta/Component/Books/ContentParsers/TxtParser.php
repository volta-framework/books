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

namespace Volta\Component\Books\ContentParsers;

use Volta\Component\Books\ContentParserInterface;
use Volta\Component\Books\ContentParserTrait;
use Volta\Component\Books\NodeInterface;

class TxtParser implements ContentParserInterface
{
    use ContentParserTrait;

    public function getContent(string $source, NodeInterface $node, bool $verbose = false): string
    {
        $this->setNode($node);
        return '<div style="white-space: pre-wrap;">' . file_get_contents($source) . '</div>';
    }

    public function getContentType(): string
    {
        return 'text/plain';
    }
}