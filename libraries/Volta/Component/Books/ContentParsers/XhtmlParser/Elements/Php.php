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

/**
 * XHTML tag
 *
 * @package Volta\Component\Books\ContentParsers
 * @author Rob <rob@jaribio.nl>
 */
class Php extends BaseElement
{

    /**
     * @see BaseElement->onTranslateStart();
     */
    public function onTranslateStart(): string
    {
        return PHP_EOL .'<pre>';
    }
    public function onTranslateData(string $data) : string
    {
        return highlight_string($data, true);
    }

    /**
     * @see BaseElement->onTranslateEnd();
     */
    public function onTranslateEnd(): string
    {
        return '</pre>';
    }

} // class