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
 * An internal link. The link may vary based on the export format. In case of DocumentNode in an
 *  epub, the link will point to a content.xhtml file, If it is published on the web it will point to
 * the directory.
 */
class Node extends BaseElement
{

    private string $_caption = '';

    public function onTranslateStart(): string
    {
      return '';
    }

    public function onTranslateData(string $data): string
    {
        $this->_caption .= $data;
        return '';
    }

    public function onTranslateEnd(): string
    {
        $href = $this->getAttribute('path',  $this->_getNode()->getUri());
        $title = $this->getAttribute('title', $this->_getNode()->getName());
        $this->_caption = str_replace(
            [
                '{{NAME}}',
                '{{DISPLAY_NAME}}',
                '{{INDEX}}',
                '{{URI}}'
            ],
            [
                $this->_getNode()->getName(),
                $this->_getNode()->getDisplayName(),
                $this->_getNode()->getIndex(),
                $this->_getNode()->getUri()
            ],
            $this->_caption
        );
        return  sprintf('<a href="%s" title="%s">%s</a>', $this->_getNode()->getUri(), $title, $this->_caption);
    }
}