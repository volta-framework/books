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

use Parsedown;

class MarkdownParser implements ContentParserInterface
{
    use ContentParserTrait;

    /**
     * @inheritDoc
     */
    public function getContent(string $source, bool $verbose = false): string
    {
        $parseDown = new Parsedown();
        return $parseDown->parse(file_get_contents($source));
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return 'text/html';
    }
}
