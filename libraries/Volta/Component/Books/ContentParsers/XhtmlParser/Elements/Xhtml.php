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

namespace Volta\Component\Books\ContentParsers\XhtmlParser\Elements;

use Volta\Component\Books\ContentParsers\XhtmlParser\Element as BaseElement;

/**
 * The element is used to define the namespaces for custom elements and also needed as a ROOT element in order to
 * make the content valid XHTML. If the element is not defined in the content the tag is automatically added. The result
 * is embedded inside an HTML page and there is no <xhtml> HTML element. Therefor we ignore the element in the
 * generated content by returning an empty string on onTranslateStart() and onTranslateEnd() parsing hooks
 */
class Xhtml  extends BaseElement
{
    /**
     * @inheritDoc
     */
    public function onTranslateStart(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function onTranslateEnd(): string
    {
        return '';
    }
}