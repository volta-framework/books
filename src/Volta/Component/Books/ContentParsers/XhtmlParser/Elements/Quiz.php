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
        return PHP_EOL . '<form method="get"  class="quiz" id="quiz-'.Quiz::$_counter.'">'.PHP_EOL;
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