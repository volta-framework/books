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

use Volta\Component\Books\ContentParsers\XhtmlParser;
use Volta\Component\Books\ContentParsers\XhtmlParser\Element as BaseElement;

class Footnote  extends BaseElement
{

    protected static array $_footnotes = [];

    public function onTranslateStart(): string
    {
        return '<em class="footnote">';
    }

    /**
     * @return string
     * @throws Exception
     * @throws XhtmlParser\Exception
     */
    public function onTranslateEnd(): string
    {

        if(!$this->hasAttribute('href') && !$this->hasAttribute('caption')) {
            throw new Exception('Either the attribute "href" or "caption" must be present in a Footnote Element');
        }
        $href = $this->getAttribute('href', '') ;
        $index = count(Footnote::$_footnotes);

        if ( $index === 0 ) {
            $this->getParser()->addListener(XhtmlParser::EVENT_ON_FINISH, [$this, 'addFootNotes']);
        }
        Footnote::$_footnotes[] = [
            'caption' => $this->getAttribute('caption', $href),
            'href' => $href
        ];
        return sprintf( '<sup><a href="#footnote_%d">%d</a></sup></em>', $index+1, $index+1);
    }


    public function addFootNotes(): string
    {
        if (0 === count(Footnote::$_footnotes)) return '';

        $html =  PHP_EOL . '<ol class="footnotes">'  . PHP_EOL;
        foreach(Footnote::$_footnotes as $index => $footnote) {
            $html .= sprintf('<li><a id="footnote_%d" href="%s" target="_blank">%s</a></li>' . PHP_EOL,
                $index + 1, $footnote['href'], $footnote['caption']);
        }
        $html .= '</ol>' . PHP_EOL;
        return $html;
    }


}