<?php

namespace Volta\Component\Books;

use Volta\Component\Books\ContentParsers\HtmlParser;
use Volta\Component\Books\ContentParsers\MarkdownParser;
use Volta\Component\Books\ContentParsers\PhpParser;
use Volta\Component\Books\ContentParsers\XhtmlParser;

abstract class Settings
{


    /**
     * @var array<string, string> Defaults to build in parsers
     */
    protected static array $_contentParsers = [
        'md' => MarkdownParser::class,
        'php' => PhpParser::class,
        'phtml' => PhpParser::class,
        'xhtml' => XhtmlParser::class,
        'html' => HtmlParser::class,
        'htm' => HtmlParser::class,
    ];

    /**
     * @param string $extension
     * @return false|ContentParserInterface
     */
    public static function getContentParserFor(string $extension): false|ContentParserInterface
    {
        if (isset(static::$_contentParsers[$extension])) {
            $parser = static::$_contentParsers[$extension];
            $instance =  new $parser();
            if (is_a($instance, ContentParserInterface::class)) {
                return $instance;
            }
        }
        return false;
    }

    /**
     * @param string $extension
     * @param string $class
     * @return bool
     */
    public function registerContentParser(string $extension, string $class): bool
    {
        static::$_contentParsers[$extension] = $class;
        return true;
    }
}