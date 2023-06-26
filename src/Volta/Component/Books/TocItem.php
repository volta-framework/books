<?php
/**
 * This file is part of the Quadro library which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
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