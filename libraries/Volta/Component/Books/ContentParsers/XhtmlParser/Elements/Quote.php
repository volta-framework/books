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

class Quote extends BaseElement
{

    /**
     * @var bool Whether the elements buffer is turned on.
     */
    private bool $_bufferIsOn = false;

    /**
     * @inheritDoc
     */
    public function onTranslateStart(): string
    {
        if ($this->getAttribute('inline', 'false') === 'false' ) {
            $html = '<blockquote>';
        } else {
            $html = '<q>';
        }
        return $html;
    }

    /**
     * @inheritDoc
     */
    public function onTranslateData(string $data) : string
    {
        if (!$this->_bufferIsOn) {
            $this->_bufferIsOn = ob_start();
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function onTranslateEnd(): string
    {
        $refHtml = '<em>unknown source</em>';
        if ($this->hasAttribute('href')) {
            $ref = $this->getAttribute('href');
            $refHtml = '<em><small><strong>Source @ </strong><a target="_blank" href="'.$ref.'">'.$ref.'</a></small></em>';
        }

        $html = trim(ob_get_contents());
        ob_end_clean();

        if ($this->getAttribute('inline', 'false') === 'false' ) {
            $html .=  '<br/>'.$refHtml.'</blockquote>';
        } else {
            $html .= '</q>(' . trim($refHtml) . ')';
        }

        return $html;
    }
}