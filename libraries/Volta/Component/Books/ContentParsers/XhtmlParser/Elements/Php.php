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
        $offset = (int) $this->getAttribute('offset', '0');
        $tab = (int) $this->getAttribute('tab', '4');

        // remove all leading and trailing spaces
        $data  = trim($this->_data, "\n\r\0\x0B");

        // create an array of all the lines
        $dataLines = explode("\n", $data);
        $dataLinesTrimmed = [];

        // remove all empty leading lines
        $empty = true;
        for($i = 0; $i < count($dataLines); $i++) {
            if ($empty) $empty = !preg_match('/\S+/', $dataLines[$i]);
            if (!$empty) $dataLinesTrimmed[] = $dataLines[$i];
        }

        // and empty trailing lines
        for($i = count($dataLinesTrimmed)-1; $i > 0; $i--) {
            if (preg_match('/\S+/', $dataLinesTrimmed[$i])) break;
            unset($dataLinesTrimmed[$i]);
        }

        // find the smallest indentation which serves as point zero
        $matches = [];
        $smallestIndentSize = null;
        for($i = 0; $i < count($dataLinesTrimmed); $i++) {
            if (preg_match('/^(\s+).+/', $dataLinesTrimmed[$i], $matches)){
                $indentSize = strlen($matches[1]);
                if ($smallestIndentSize === null || $indentSize < $smallestIndentSize) {
                    $smallestIndentSize = $indentSize;
                }
            }
        }

        // loop through the data and adjust the indentation offset;
        if( $smallestIndentSize) {
            $minIndentSize = ($tab * $offset);
            $requiredOffset = str_repeat(' ', $minIndentSize);
            for ($i = 0; $i < count($dataLinesTrimmed); $i++) {
                if (str_starts_with($dataLinesTrimmed[$i], str_repeat(' ', $smallestIndentSize))) {
                    $dataLinesTrimmed[$i] = substr($dataLinesTrimmed[$i], $smallestIndentSize);
                    $dataLinesTrimmed[$i] = $requiredOffset . $dataLinesTrimmed[$i];
                }
            }
        }

        // The highlight function only highlights after a '<?php' start processing instruction as PHP and HTML can
        // be mixed. We will however assume all PHP when no processing instruction is found and manual add this in
        // order to highlight the code. But if it was not there it probably was intentional to leave it out so,
        // we remove this afterward. But the highlighting code hase replaced the '<' with an HTML entity.
        // NOTE:
        //     To add a <?php processing instruction optionally mixed with HTML all data must be in enclosed
        //     in <![CDATA[ ]]> section otherwise the XHTML content will not be able to be parsed without an error.
        $data = trim( implode("\n", $dataLinesTrimmed), "\n\r\0\x0B");
        if (!str_starts_with($data, '<?php') && !str_starts_with($data, '<?=')) {
            $data = "<?php\n" . $data;
            $data = highlight_string($data, true);
            $data = str_replace('&lt;?php', '', $data);
        } else {
            $data = highlight_string($data, true);
        }
        return $data;
    }

} // class