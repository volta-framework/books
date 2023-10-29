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
 * A Volta XHTML element for inserting a link to a DocumentNode.
 */
class Node extends BaseElement
{
    /**
     * @ignore (do not show up in generated documentation)
     * @var string $_template The content with placeholders
     */
    private string $_template = '';

    /**
     * @inheritdoc
     */
    public function onTranslateStart(): string
    {
      return '';
    }

    /**
     * @inheritdoc
     */
    public function onTranslateData(string $data): string
    {
        $this->_template .= $data;
        return '';
    }

    /**
     * @inheritdoc
     */
    public function onTranslateEnd(): string
    {
        if ($this->hasAttribute('path')) {
            $current = false;
            $path = $this->getAttribute('path');
            $node = $this->_getNode()->getRoot()->getChild($path);
            if (null === $node) {
                return '<blockquote class="error">volta:node: Node "<strong>' . $path . '</strong>" not found!</blockquote>';
            }
        } else {
            $current = true;
            $node = $this->_getNode();
        }

        // now we have the node get the title and parse the template
        $title = $this->getAttribute('title', $node->getName());
        $text  = str_replace(
            ['{{NAME}}', '{{DISPLAY_NAME}}', '{{INDEX}}', '{{URI}}'],
            [$node->getName(), $node->getDisplayName(), $node->getIndex(), $node->getUri()],
            $this->_template
        );

        // do not create a link to itself...
        if ($current) return  sprintf('<span class="volta-node" title="%s">%s</span>', $title, $text);
        return  sprintf('<a class="volta-node" href="%s" title="%s">%s</a>', $node->getUri(), $title, $text);
    }
}