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

/**
 *
 */
class PhpParser implements ContentParserInterface
{
    use ContentParserTrait;

    /**
     * @inheritDoc
     */
    public function getContent(string $source, bool $verbose = false): string
    {
        /* NOTE: There is no need to test whether the $source is a valid file as the function
         *       should only be called from with a DocumentNode
         */
        $nodeContext = function (string $source) {include $source; return '';};
        $nodeContext = $nodeContext->bindTo($this->getNode());
        return $nodeContext($source);
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return 'text/html';
    }
}
