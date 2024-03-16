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
 * Represents an Answer for a Question in a Quiz
 *
 * @container: Question
 * @attribuut: value
 */
class Answer extends BaseElement
{
    public static int $_counter = 0;

    public function onTranslateStart(): string
    {
        Answer::$_counter++;

        $_elementName =  'question-'. Quiz::$_counter. '-' . Question::$_counter;
        $_id = 'answer-'.Quiz::$_counter. '-' . Question::$_counter . '-' . Answer::$_counter ;
        $_value = (int) $this->getAttribute('value', '0');
        $_selected = '';
        $_status = 'unknown';
        if(isset($_GET[$_elementName])) {
            if ($_GET[$_elementName] == $_id) {
                $_selected = 'checked';
                if ($_value == 0) $_status = 'error';
                if ($_value == 1) $_status = 'correct';
            }
        }

        $html  = PHP_EOL.'  <div class="answer-container">';
        $html .= PHP_EOL.'    <input type="radio" '. $_selected .' name="'. $_elementName .'" class="answer" id="'. $_id .'" value="'. $_id .'"/>';
        $html .= PHP_EOL.'    <span class="answer-status  '.$_status.'">&nbsp;</span><label for="answer-'.Quiz::$_counter.'-'.Question::$_counter.'-'.Answer::$_counter.'" class="answer-data">';
        return $html;
    }

    public function onTranslateData(string $data): string
    {
        $data = $this->_deepTrim($data);
        if (empty($data)) return '';
        return  $data;
    }

    public function onTranslateEnd(): string
    {
        return  '</label>'.PHP_EOL.'  </div>';
    }


} 