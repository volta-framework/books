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
use Volta\Component\Books\TocItem;

class Toc extends BaseElement
{
    /**
     * @param TocItem[] $items
     * @return string
     */
    private function _printToc(array $items): string
    {
        $html = '';
        if(count($items)) $html .= "\n<ul>";
        foreach($items as $item) {
            $html .= "\n<li><a href=\"$item->uri\">{$item->caption}</a>";
            $html .= $this->_printToc($item->children);
            $html .= "\n</li>";
        }
        if(count($items)) $html .= "\n</ul>";
        return $html;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function onTranslateStart(): string
    {

        if ($this->getAttribute('target', 'self') == 'parent') {
            if (null === $this->_getNode()->getParent()) {
                throw new Exception('Target is set t o "parent" but the node does not have a parent.');
            }
            $toc = $this->_getNode()->getParent()->getToc();
        } else if ($this->getAttribute('target', 'self') == 'root') {
            $toc = $this->_getNode()->getRoot()->getToc();
        } else {
            $toc = $this->_getNode()->getToc();
        }


        $html = $this->_printToc($toc);
        return $html;
    }

    /**
     * @inheritDoc
     */
    public function onTranslateData(string $data) : string
    {
        return '';
    }

    /**
     * @see BaseElement->onTranslateEnd();
     */
    public function onTranslateEnd(): string
    {
        return '';
    }

}