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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Volta\Component\Books\BookNode;
use Volta\Component\Books\Node;
use Volta\Component\Books\Publisher;
use Volta\Component\Books\Publishers\Epub;

require_once __DIR__ . '/../vendor/autoload.php';

class tempLogger implements LoggerInterface
{
    use Psr\Log\LoggerTrait;
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $w = 120;
        switch ($level) {
            case LogLevel::CRITICAL: // no break
            case LogLevel::ERROR   : fwrite(STDOUT, sprintf("\n\t\e[31m%s\e[0m\n", wordwrap($message, $w, "\n\t"))); break;
            case LogLevel::ALERT   : // no break;
            case LogLevel::WARNING : fwrite(STDERR, sprintf("\n\t\e[33m%s\e[0m\n", wordwrap($message, $w, "\n\t"))); break;
            case LogLevel::DEBUG   : fwrite(STDOUT, sprintf("\e[32m%-10s%s\e[0m\n", $level, wordwrap($message, $w, "\n\t"))); break;
            default:                 fwrite(STDOUT, sprintf("%-10s%s\n", $level, wordwrap($message, $w, "\n           ")));
        }
        if (!empty($context[0])) {
            fwrite(STDOUT, sprintf("         \e[3m\e[90m (%s) \e[0m\n", $context[0]));
        }
    }
}

$logger = new  tempLogger();

try {
    /**
     * expects the first argument to be the source and second the destination
     */
    $source = $argv[1] ?? false;
    $destination = $argv[2] ?? false;

    /**
     * Validate the directories
     */
    if ($source === false || $destination === false || !is_dir($source) || !is_dir($destination) || !is_readable($source) || !is_writable($destination)) {
        $logger->error("Expects source(first argument) and destination(second argument) to point to an existing and respectively readable and writable directory");
        exit(1);
    }

    /**
     * Sanitize the arguments, add a directory separator to the end
     */
    $source = realpath($source) . DIRECTORY_SEPARATOR;
    $destination = realpath($destination) . DIRECTORY_SEPARATOR;

    /**
     * TODO Check if we go from EPub -> Volta or from Volta -> Epub
     *      For now we assume the first is the Volta book
     */
    $book = Node::factory($source);
    if (!is_a($book, BookNode::class)) {
        $logger->error("Expects source(first argument) to be a Volta Book");
        exit(1);
    }

    /*
     * Add the book, template, style and the logger instance to the epub instance. Then export the generated epub to the
     * $destination location passed as an argument to this script. In this folder, there will be a sub folder called
     * "libraries" (retrieved through the function $epub->getSourceDir() )  which will contains the uncompressed epub files.
     */
    $publisher = Publisher::factory(Epub::class, [
        'destination' => $destination,
    ]);
    $publisher->addBook('', $book);
    $publisher->setPageTemplate(__DIR__ . '/../templates/epub-book.html.php');
    $publisher->setPageStyle(__DIR__ . '/../public/assets/css/epub-book.css');
    $publisher->setLogger($logger);;
    $publisher->exportBook('');

    fwrite(STDOUT, "\n\n");

} catch(\Throwable $e) {
    $logger->error($e->getMessage());
    exit(1);
}





