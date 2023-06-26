<?php
/**
 * -----------------------------------------------------------------------------
 *   This program is license under MIT License.
 *
 *   You should have received a copy of the MIT License with this program
 *   in the file LICENSE.txt and is available through the world-wide-web
 *   at http://license.digicademy.nl/mit-license.
 *
 *   If you did not receive a copy of the MIT License and are unable to obtain 
 *   it through the world-wide-web please send a note to
 *
 *      Rob <rob@jaribio.nl> 
 *
 *   so we can mail you a copy immediately.
 *
 *   @license ~/LICENSE.txt
 * -----------------------------------------------------------------------------
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
        return PHP_EOL.'  <div class="question-data">' . $data . '</div>';
    }

    /**
     * @return string
     */
    public function onTranslateEnd(): string
    {
        return PHP_EOL.'</div>'. PHP_EOL;
    }

} 