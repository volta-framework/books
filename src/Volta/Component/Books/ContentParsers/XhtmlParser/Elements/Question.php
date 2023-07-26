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

class Question extends BaseElement
{
    /**
     * @param string $message
     * @return bool
     */
    public function validate(string &$message=''): bool
    {
        if (false !== $this->getParent()) {
            if ($this->getParent()->getName() != 'quiz') {
                $message = 'Question must be inside a Quiz Element, currently in : ' . $this->getParent()->getName();
                return false;
            }
        }
        return true;
    }


    /**
     * @var int
     */
    public static int $_counter = 0;

    /**
     * @return string
     */
    public function onTranslateStart(): string
    {
        Question::$_counter++;
        return PHP_EOL.'<div class="question" id="question-'.Quiz::$_counter.'-' . Question::$_counter . '">';
    }

    /**
     * @param string $data
     * @return string
     */
    public function onTranslateData(string $data): string
    {
        $data = $this->_deepTrim($data);
        if (empty($data)) return '';
        return PHP_EOL.'  <span class="question-data">' . $data . '</span>';
    }

    /**
     * @return string
     */
    public function onTranslateEnd(): string
    {
        return PHP_EOL.'</div>'. PHP_EOL;
    }

} 