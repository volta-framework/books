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
use Volta\Component\Books\Exceptions\Exception;
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
        if(count($items)) $html .= "\n<ul class=\"toc document-nodes\">";
        foreach($items as $item) {
            $html .= "\n<li class=\"toc document-node\"><a  class=\"toc link\" href=\"$item->uri\">{$item->caption}</a>";
            $html .= $this->_printToc($item->children);
            $html .= "\n</li>";
        }
        if(count($items)) $html .= "\n</ul>";
        return $html;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws Exception
     */
    public function onTranslateStart(): string
    {
        $target = $this->getAttribute('target', 'self') ;
        $html = "\n<!-- TOC $target -->\n";
        if ($target === 'parent') {
            if (null === $this->_getNode()->getParent()) {
                throw new Exception('TOC::target is set to "parent" but the node does not have a parent.');
            }
            $html .=  $this->_printToc($this->_getNode()->getParent()->getToc());

        } else if ($target == 'root') {
            $html .=  $this->_printToc($this->_getNode()->getRoot()->getToc());

        } else if ($target == 'self') {
            $html .= $this->_printToc($this->_getNode()->getRoot()->getToc());
        } else {
            $node = $this->_getNode()->getChild($target);
            if (null === $node) {
                $html = "\nTOC unknown target, target expected to be parent, root, self or a valid relative path to self\n";
            } else {
                $html .= $this->_printToc($node->getToc());
            }

        }

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