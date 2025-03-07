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
readonly class TocItem
{
    /**
     * @param string $caption
     * @param string $uri
     * @param TocItem[] $children
     * @param int $page
     */
    public function __construct(
        public string $caption,
        public string $uri,
        public array  $children,
        public int    $page,
    ){}

}