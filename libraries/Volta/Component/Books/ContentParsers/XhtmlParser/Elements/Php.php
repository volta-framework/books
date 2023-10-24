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
        // The highlight function only highlights after a '<?php' start processing instruction as PHP and HTML can
        // be mixed. We will however assume all PHP when no processing instruction is found and manual add this in
        // order to highlight the code. But if it was not there it probably was intentional to leave it out so,
        // we remove this afterward. But the highlighting code hase replaced the '<' with an HTML entity.
        // NOTE:
        //     To add a <?php processing instruction optionally mixed with HTML all data must be in enclosed
        //     in <![CDATA[ ]]> section otherwise the XHTML content will not be able to be parsed without an error.
        $data  = trim($this->_data, "\n\r\0\x0B");
        if (!str_contains($data, '<?php') && !str_contains($data, '<?=')) {
            $data = "<?php\n" . $data;
            $data = highlight_string($data, true);
            $data = str_replace('&lt;?php', '', $data);
        } else {
            $data = highlight_string($data, true);
        }
        return $data;
    }

} // class