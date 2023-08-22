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

use Volta\Component\Books\ContentParsers\XhtmlParser;
use Volta\Component\Books\ContentParsers\XhtmlParser\Element as BaseElement;

class Footnote  extends BaseElement
{

    protected static array $_footnotes = [];
    protected string $_ref = '';

    private int $_index = 0;
    public function onTranslateStart(): string
    {
        $href = $this->getAttribute('href', '') ;
        $ref = $this->getAttribute('ref', '') ;

        $this->_index = count(Footnote::$_footnotes);
        if ($this->_index === 0 ) {
            $this->getParser()->addListener(XhtmlParser::EVENT_ON_FINISH, [$this, 'addFootNotes']);
        }
        Footnote::$_footnotes[$this->_index] = [
            'href' => $href,
            'caption' => '',
        ];
        return '<em class="footnote">';
    }
    public function onTranslateData(string $data): string
    {
        Footnote::$_footnotes[$this->_index]['caption'] .= $data;
        return '';
    }

    /**
     * @return string
     */
    public function onTranslateEnd(): string
    {
        return sprintf( '<sup><a href="#footnote_%d">[%d]</a></sup></em>',   $this->_index+1, $this->_index+1);
    }


    public function addFootNotes(): string
    {
        if (0 === count(Footnote::$_footnotes)) return '';

        $html =  PHP_EOL . '<ol class="footnotes">'  . PHP_EOL;
        foreach(Footnote::$_footnotes as $index => $footnote) {
            if (!empty($footnote['href'])) {
                $html .= sprintf('<li><a id="footnote_%d" href="%s" target="_blank">%s</a></li>' . PHP_EOL,
                    $index + 1, $footnote['href'], $footnote['caption']);
            } else {
                $html .= sprintf('<li><a id="footnote_%d"></a><em>%s</em></li>' . PHP_EOL,
                    $index + 1, $footnote['caption']);
            }
        }

        Footnote::$_footnotes = [];

        $html .= '</ol>' . PHP_EOL;
        return $html;
    }


}