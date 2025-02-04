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

namespace Volta\Component\Books\Publishers;

use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\NodeInterface;
use Volta\Component\Books\Publisher;
use ZipArchive;

class VoltaBook extends Publisher
{

    public readonly string|bool $source;
    public readonly string|bool $destination;


    /**
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $source = $options['source'] ?? false;
        $destination = $options['destination'] ?? false;

        if ($source === false || !is_file($source) || !is_readable($source)) {
            throw new Exception('Expects source(first argument) to point to an existing and readable directory');
        }
        if ($destination === false || !is_dir($destination) || !is_writable($destination)) {
            throw new Exception('Expects destination(second argument) to point to an existing writable directory');
        }

        $this->source = realpath($source);
        $this->destination =  realpath($destination) . DIRECTORY_SEPARATOR;
    }


    public function sanitizeUri(NodeInterface $node): string
    {
        return  $node->getUri();
    }

    public function exportBook(int|string $bookIndex = 0, array $options = []): bool
    {

        $epub = new ZipArchive();
        $epub->open($this->source, ZipArchive::RDONLY);

        $this->getLogger()->info( 'numFiles   : ' . $epub->numFiles );
        $this->getLogger()->info( 'status     : ' . $epub->status);
        $this->getLogger()->info( 'statusSys  : ' . $epub->statusSys);
        $this->getLogger()->info( 'filename   : ' . $epub->filename);
        $this->getLogger()->info( 'comment    : ' . $epub->comment);
        $this->getLogger()->info( 'destination: ' . $this->destination);

        for ($i=0; $i<$epub->numFiles;$i++) {
            echo "index: $i\n";
            print_r($epub->statIndex($i));
        }
        return false;
    }
}