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
use Volta\Component\Books\ContentParsers\XhtmlParser\Exception as Exception;
/**
 * The Glossary element will organize all elements in one page alphabetical
 */
class Glossary  extends BaseElement
{

    /**
     * Static to store all glossaries on the page
     * @var array
     */
    private static array $_terms = [];

    private string $_term;

    /**
     * @inheritDoc
     * @throws XhtmlParser\Exception
     */
    public function onTranslateStart(): string
    {
        //  listen to the end of the page which will be the signal to print all terms
        if (count(Glossary::$_terms)  === 0 ) {
            $this->getParser()->addListener(XhtmlParser::EVENT_ON_FINISH, [$this, 'sortTerms']);
        }

        // get Glossary Item
        if ($this->getAttribute('term', '') === '') {
            throw new Exception('<volta:glossary> attribute "term" is required an can not be empty');
        }
        $this->_term = ucfirst($this->getAttribute('term'));
        Glossary::$_terms[$this->_term] = '';

        // return nothing
        return '';
    }


    private bool $_buffer = false;

    /**
     * @inheritDoc
     */
    public function onTranslateData(string $data): string
    {
        if (!$this->_buffer) {
            ob_start();
            $this->_buffer = true;
        }
        echo $data;
        return '';
    }

    /**
     * @inheritDoc
     */
    public function onTranslateEnd(): string
    {
        Glossary::$_terms[$this->_term] = ob_get_contents();
        ob_end_clean();
        return '';
    }


    public function sortTerms(): string
    {
        if (0 === count(Glossary::$_terms)) return '';
        ksort(Glossary::$_terms);

        $html = '';
        foreach(Glossary::$_terms as $item => $text) {
            $html .= '<div class="glossaryEntry" id="'. $item. '">';
            $html .= '<div class="glossaryTerm">'. $item. '</div>';
            $html .= '<div class="glossaryDescription">';
            $html .= $text;
            $html .= '</div></div>' . "\n";
        }
        return $html;
    }


}

