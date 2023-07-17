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

namespace Volta\Component\Books;

use Volta\Component\Books\ContentParsers\HtmlParser;
use Volta\Component\Books\ContentParsers\PhpParser;
use Volta\Component\Books\ContentParsers\XhtmlParser;

abstract class Settings
{

    public static array $supportedResources = [
        // textual files
        'html', 'htm'  => 'text/html',
        'txt'  => 'text/plain',
        'css'  => 'text/css',
        'js'  => 'text/javascript',

        // video's
        'avi'  => 'video/x-msvideo',
        'mpeg' => 'video/mpeg',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',

        // images
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'ico'  => 'image/vnd.microsoft.icon',
        'jpeg','jpg'  => 'image/jpeg',
        'png'  => 'image/png',
    ];

    /**
     * @var array<string, string> Defaults to build in parsers
     */
    public static array $contentParsers = [
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
        if (isset(static::$contentParsers[$extension])) {
            $parser = static::$contentParsers[$extension];
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
    public static function registerContentParser(string $extension, string $class): bool
    {
        static::$contentParsers[$extension] = $class;
        return true;
    }
}
