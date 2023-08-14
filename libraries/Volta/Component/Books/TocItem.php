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

/**
 * An Item in a Table Of Content collection
 */
class TocItem
{
    /**
     * @param string $caption
     * @param string $uri
     * @param TocItem[] $children
     */
    public function __construct(
        public readonly string $caption,
        public readonly string $uri,
        public readonly array $children
    ){}

}