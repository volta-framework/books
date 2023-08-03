<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Volta\Component\Books\ContentParsers\XhtmlParser\Elements;

use Volta\Component\Books\ContentParsers\XhtmlParser\Element as BaseElement;

class Xhtml extends BaseElement
{

    public function onTranslateStart(): string
    {
        return '';
    }


    /**
     * @return string
     */
    public function onTranslateEnd(): string
    {
        return '';
    }

}