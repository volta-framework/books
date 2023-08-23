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
use Volta\Component\Books\Settings;

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
        return PHP_EOL;
    }

    private string $_data = '';
    public function onTranslateData(string $data) : string
    {
        $this->_data .= $data;
        return '';
    }

    /**
     * @see BaseElement->onTranslateEnd();
     */
    public function onTranslateEnd(): string
    {
        // remove leading indentation of each line
//        $indents = (int) $this->getAttribute('trim', "1");
//        $lines = explode(PHP_EOL, $this->_data);
//        foreach($lines as $line){
//            $this->_data .= ""
//        }


        $this->_data = highlight_string("<?php\n" . trim($this->_data, "\n\r\0\x0B"), true);
        if (Settings::getPublishingMode() === Settings::PUBLISHING_EPUB) {
            $this->_data = str_replace(['&nbsp;'], [' '], $this->_data);
        }



        return trim($this->_data, "\n\r\0\x0B");
    }

} // class