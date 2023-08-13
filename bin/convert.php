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

use Volta\Component\Books\BookNode;
use Volta\Component\Books\Epub;
use Volta\Component\Books\Node;

require_once __DIR__ . '/../vendor/autoload.php';

class tempLogger implements \Psr\Log\LoggerInterface
{
    use Psr\Log\LoggerTrait;

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        fwrite(STDOUT, sprintf("%-10s %s\n", $level, $message));
    }
}

$logger = new  tempLogger();

try {
    /**
     * expects the first argument to be the source and the second the destination
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

    $epub = new Epub();
    $epub->setLogger(new  tempLogger());;
    $epub->export($book, $destination, __DIR__ . '/../templates/epub.phtml');


    if(is_file(__DIR__ . '/converted.epub')) unlink(__DIR__ . '/converted.epub');
    shell_exec('zip converted.epub ' . $destination .'* ');

} catch(\Throwable $e) {
    $logger->error($e->getMessage());
    exit(1);
}





