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
 * epub the link wil pointing to a content.xhtml file, If it is published on the web it wil point to
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
        $href = $this->getAttribute('path', '/');
        $title = $this->getAttribute('title', $this->_getNode()->getName());
        $supportedResources = trim(implode('|', array_keys(Settings::getSupportedResources())), '|');

        // if it is a resource do nothing...
        $matches = [];
        $pattern = "/^.*\.($supportedResources)$/i";
        if (!preg_match($pattern, $href, $matches)) {

            // if it is a document node we need to add a slash if not present
            // find a hashtag first and split the string
            $posHashtag = strpos($href, '#');
            if (false === $posHashtag) {
                $hashtag = '';
            } else {
                $hashtag = substr($href, $posHashtag);
                $href = substr($href, 0, $posHashtag);
            }
            if (!str_ends_with($href, '/')) {
                $href .= '/';
            }

            // if it is an epub add "content.xhtml" to it
            if (Settings::getPublishingMode() === Settings::PUBLISHING_EPUB) {
                $href .= 'content.xhtml';
            }

            // (re)build the hyperlink reference
            $href = $href . $hashtag;
        }
        return  sprintf('<a href="%s" title="%s">%s</a>', $href, $title, $this->_caption);
    }
}