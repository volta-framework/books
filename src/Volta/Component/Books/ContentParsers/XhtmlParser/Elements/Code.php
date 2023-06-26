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
 * Book XHTMl tag
 * 
 * The following XHTML structure:
 * 
 * &lt;code&GT;
 *    &lt;some-programming-language&GT;
 *       // code here
 *    &lt;/some-programming-language&GT;
 *    &lt;another-programming-language&GT;
 *       // code here
 *    &lt;/another-programming-language&GT;
 * &lt;/code&GT;
 * 
 * Will be translated into:
 * 
 * &lt;div class="tab-container" id="tab-container-id-%d"&GT;&lt;/div&GT;
 *    &lt;em&GT;some-programming-language&lt;/em&GT;
 *    &lt;pre&GT;&lt;code&GT;
 *       // code here
 *    &lt;/code&GT;&lt;/pre&GT;
 *    &lt;em&GT;another-programming-language&lt;/em&GT;
 *    &lt;pre&GT;&lt;code&GT;
 *       // code here
 *    &lt;/code&GT;&lt;/pre&GT;
 * &lt;/div&GT;
 * 
 * This class will translate the XHTML &lt;code&gt; element into
 * 
 * &lt;div class="tab-container" id="tab-container-id-%d"&GT;&lt;/div&GT;
 *
 * @package Volta\Component\Books\ContentParsers
 * @author Rob <rob@jaribio.nl> 
 */
class Code extends BaseElement
{
    public static int $counter = 0;
 
    public function onTranslateStart(): string
    {
        Code::$counter++;
        //return '<div class="tabs" id="tabs'. Code::$counter . '">';
        return '<div class="tab-container" id="tab-container-id-'. Code::$counter . '"></div>';
    }
 
    public function onTranslateEnd(): string
    {
        return '' ; //'</div>';
    }
}