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
 * The following XHTML
 * ```xml
 *    <quiz>
 *       ...
 *    </quiz>
 * ```
 *
 * Will be translated to :
 *```html
 *    <form method="get" class="quiz" id="quiz-{%d}">
 *      <div class="quiz-data">
 *         ...
 *      </div>
 *      <div class="buttons"><button>Verstuur</button></div>
 *    </form>
 * ```
 */
class Quiz extends BaseElement
{

    /**
     * @var int $_counter Counts the number of Quiz tags in the XHTML
     */
    public static int $_counter = 0;

    public function onTranslateStart(): string
    {
        Quiz::$_counter++;
        return PHP_EOL . '<form action="#quiz-'.Quiz::$_counter.'" method="get"  class="quiz" id="quiz-'.Quiz::$_counter.'">'.PHP_EOL;
    }

    public function onTranslateData(string $data): string
    {
        $data = $this->_deepTrim($data);
        if (empty($data)) return '';
        return PHP_EOL. '<div class="quiz-data">' . $data  . '</div>' . PHP_EOL;
    }

    public function onTranslateEnd(): string
    {
        $html = '<div class="buttons"><button>Verstuur</button></div>';
        return $html . '</form>';
    }


}