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