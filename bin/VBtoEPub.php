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
use Volta\Component\Books\Logger;
use Volta\Component\Books\Node;
use Volta\Component\Books\Publisher;
use Volta\Component\Books\Publishers\Epub;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new  Logger();

try {

    $source = $argv[1] ?? false;
    $destination = $argv[2] ?? false;
    $exclude =array_map('trim', explode(',',  $argv[3] ?? ''));
    //print_r(array_map('trim',explode(',', $exclude)));

    $source = realpath($source) . DIRECTORY_SEPARATOR;
    $destination = realpath($destination) . DIRECTORY_SEPARATOR;

    $logger->log('From',  $source);
    $logger->log('To', $destination);

    $book = BookNode::factory($source);
    $publisher = Publisher::factory(Epub::class);
    $publisher->setLogger($logger);;

    $publisher->addBook($book);

    $logger->warning('All files in the destination folder will be deleted. This can not be undone! Proceed anyway? [n/Y]');
    $proceed = readline( );

    if (strtolower($proceed) !== 'y') {
        $logger->notice('process terminated by the user...');
        exit(0);
    }
    $publisher->exportBook(0, [
        'destination' => $destination,
        'exclude' => $exclude,
    ]);

    fwrite(STDOUT, "\n\n");

} catch(\Throwable $e) {
    $logger->error($e->getMessage());
    exit(1);
}



