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

use FilesystemIterator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Volta\Component\Books\Exceptions\Exception;

class Epub implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function  __construct()
    {

    }


    private function _setup():void
    {
        // create error and exception handlers
        $errorHandler = function(
            int $code, string $message,
            null|string $file = null,
            null|int $line = null,
            null|array $context = null
        ): bool {
            $this->getLogger()->error("$message in $file @ $line");
            $this->_teardown();
            exit(1);
        };
        $errorHandler->bindTo($this);
        set_error_handler($errorHandler);

        $exceptionHandler = function(\Throwable $exception
        ): void {
            $this->getLogger()->error(get_class($exception) . ' - ' . $exception->getMessage());
            $this->_teardown();
            exit();
        };
        $exceptionHandler->bindTo($this);
        set_exception_handler($exceptionHandler);
    }

    private function _teardown():void
    {
        restore_error_handler();
        restore_exception_handler();
    }


    private BookNode $_book;
    private string $_template;
    private string $_bookId;
    private string $_contentDir = 'OEBPS/';
    private string $_metadataFileName = 'metadata.opf';
    private string $_tocFileName = 'toc.ncx';


    /**
     * Exports a Volta Book to epub
     *
     * @see https://en.wikipedia.org/wiki/EPUB
     * @param BookNode $book
     * @param string $destination Existing empty writable directory
     * @param string $template
     * @param string|null $style
     * @return bool True on success, false otherwise
     * @throws Exception When an invalid destination is given
     */
    public function export(BookNode $book, string $destination, string $template, null|string $style=null): bool
    {
        $this->_book = $book;
        $this->_template = $template;
        $this->_bookId = sha1(uniqid('VOLTA', true));

        $this->_setup();
        $this->_setDestination($destination);
        $this->_createOpenContainer();
        $this->_createEpubContent();
        $this->_createEpubToc();
        $this->_addStyle();

        return true;
    }

    private function _getResourceId(NodeInterface $node): string
    {
        return 'V' .sha1($node->getUri());
    }

    private function _getFileName(NodeInterface $node):string
    {
        if (!is_dir($this->_getDestination() . $this->_contentDir . $node->getRelativePath())) {
            mkdir($this->_getDestination() . $this->_contentDir . $node->getRelativePath(), 0777, true);
            $this->getLogger()->debug('Created ' . $this->_contentDir . trim($node->getRelativePath(),  DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR );
        }
        $name = str_replace('//' , '/' , $this->_contentDir . trim($node->getRelativePath(),  DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'content');
        return  $name . '.xhtml';
    }
    private function _addStyle():void
    {}

    #region - Create EPUB TOC
    private function _createEpubToc(): void
    {
        $navMap = [];
        $depth = 0;
        $addToNavMap = function(NodeInterface $node, int $level) use(&$navMap, &$addToNavMap, &$depth) {
            $depth++;
            $file = $this->_getFileName($node);
            $offset = str_repeat('  ' , $level);
            $navMap[] = $offset . '    <navPoint id="'.$this->_getResourceId($node).'" playOrder="'.$node->getIndex().'">';
            $navMap[] = $offset . '      <navLabel>';
            $navMap[] = $offset . '        <text>'.$node->getMeta()->get('displayName',$node->getName()).'</text>';
            $navMap[] = $offset . '      </navLabel>';
            $navMap[] = $offset . '      <content src="'. $file.'"/>';
            foreach($node->getChildren() as $child) {
                $addToNavMap($child, $level+1);
            }
            $navMap[] = $offset . '    </navPoint>';
        };
        $addToNavMap->bindTo($this);

        foreach($this->_book->getChildren() as $child) {
            $addToNavMap($child, 0);
        }

        $head = [];
        $head[] = '    <meta name="dtb:uid" content="'.$this->_bookId.'"/>';
        $head[] = '    <meta name="dtb:depth" content="'.$depth.'"/>';
        $head[] = '    <meta name="dtb:generator" content="Volta Books"/>';
        $head[] = '    <meta name="dtb:totalPageCount" content="0"/>';
        $head[] = '    <meta name="dtb:maxPageNumber" content="0"/>';

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="utf-8"?>';
        $xml[] = '<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="enl">';
        $xml[] = '  <head>';
        $xml = array_merge($xml, $head);
        $xml[] = '  </head>';
        $xml[] = '  <docTitle>';
        $xml[] = '    <text>'.$this->_book->getMeta()->get('title', $this->_book->getName()).'</text>';
        $xml[] = '  </docTitle>';
        $xml[] = '  <navMap>';
        $xml = array_merge($xml, $navMap);
        $xml[] = '  </navMap>';
        $xml[] = '</ncx>';

        $fh = fopen(  $this->_destination . $this->_tocFileName, 'w');
        fwrite($fh, trim(implode(PHP_EOL, $xml)));
        fclose($fh);
        $this->getLogger()->info('Created '. $this->_tocFileName);

    }

    #endregion
    #region - Create EPUB Content

    private function _createEpubContent(): void
    {

        $metadata = [
            'title' => $this->_book->getName(),
            'language' => $this->_book->getMeta()->get('language', 'en'),
            'identifier' =>$this->_bookId,
            'creator' => $this->_book->getMeta()->get('author', 'anonymous')
        ];

        $manifest = [];
        $addToManifest = function(NodeInterface $node) use(&$manifest, &$addToManifest) {
            $file = $this->_getFileName($node);;
            $manifest[]  = [
                'id' => $this->_getResourceId($node),
                'href' => $file,
                'media-type' => 'application/xhtml+xml'
            ];
            $fh = fopen($this->_getDestination() . $file, 'w');

            ob_start();
            include $this->_template;
            $content = ob_get_contents();
            ob_end_clean();

            if(false !== fwrite($fh, $content)) {
                $this->getLogger()->info('Created ' . $file);
            }
            fclose($fh);

            foreach($node->getChildren() as $child) {
                $addToManifest($child);
            }
        };
        $addToManifest->bindTo($this);
        $addToManifest($this->_book);



        $xml = [];
        $xml[] = '<?xml version="1.0"?>';
        $xml[] = '<package version="2.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="' .$this->_bookId . '">';
        $xml[] = '  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">';
        $xml[] = '    <dc:title>' .$metadata['title']. '</dc:title>';
        $xml[] = '    <dc:language>e' .$metadata['language']. '</dc:language>';
        $xml[] = '    <dc:identifier id="'.$this->_bookId.'" opf:scheme="uuid">' .$this->_bookId . '</dc:identifier>';
        $xml[] = '    <dc:creator opf:file-as="' .$metadata['creator']. '" opf:role="aut">' .$metadata['creator']. '</dc:creator>';
        $xml[] = '  </metadata>';
        $xml[] = '  <manifest>';
        foreach($manifest as $item) {
            $xml[] = '    <item id="'.$item['id'].'" href="'.$item['href'].'" media-type="'.$item['media-type'].'"/>';
        }
        $xml[] = '    <item id="ncx" href="' . $this->_tocFileName . '" media-type="application/x-dtbncx+xml"/>';
        $xml[] = '  </manifest>';
        $xml[] = '  <spine toc="ncx">';
        foreach($manifest as $item) {
            $xml[] = '    <itemref idref="'.$item['id'].'"/>';
        }
        $xml[] = '  </spine>';
        $xml[] = '</package>';

        $fh = fopen( $this->_destination . $this->_metadataFileName, 'w');
        fwrite($fh, trim(implode(PHP_EOL, $xml)));
        fclose($fh);
        $this->getLogger()->info('Created ' . $this->_metadataFileName);

    }

    #endregion
    #region - Create Basic Open Container Organisation
    private function _createOpenContainer(): void
    {
        // creating the required mimetype file
        $fh = fopen($this->_getDestination(). 'mimetype', 'w');
        fwrite($fh, 'application/epub+zip'); // application/epub+zip
        fclose($fh);
        $this->getLogger()->info('Created ' . 'mimetype');

        // creating the required container file
        if (false === mkdir($this->_getDestination() . 'META-INF')) {
            throw new Exception('Failed to create required directory META-INF');
        }
        $fh = fopen($this->_getDestination(). 'META-INF'. DIRECTORY_SEPARATOR . 'container.xml', 'w');
        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL);
        fwrite($fh, '<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">' . PHP_EOL);
        fwrite($fh, '  <rootfiles>' . PHP_EOL);
        fwrite($fh, '    <rootfile full-path="' . $this->_metadataFileName. '" media-type="application/oebps-package+xml"/>' . PHP_EOL);
        fwrite($fh, '  </rootfiles>' . PHP_EOL);
        fwrite($fh, '</container>' . PHP_EOL);
        fclose($fh);
        $this->getLogger()->info('Created ' . 'META-INF'. DIRECTORY_SEPARATOR . 'container.xml');

        // creating the required root file(s)
        if (false === mkdir($this->_getDestination() . 'OEBPS')) {
            throw new Exception('Failed to create required directory OEBPS');
        }
    }
    #endregion
    #region - Validate and Sanitize  Destination

    private string $_destination;

    private function _getDestination(): string
    {
        return $this->_destination;
    }

    private function _setDestination(string $destination): self
    {
        // Validate the destination directory
        if (!is_dir($destination) || !is_writable($destination)) {
            throw new Exception('Destination not pointing to an existing writable directory ' . count(scandir($destination)) );
        }

        // Sanitize the destination directory
        $destination = realpath($destination) . DIRECTORY_SEPARATOR;
        $it = new RecursiveDirectoryIterator($destination, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        if (count(scandir($destination)) > 2) {
            throw new Exception('Destination not pointing to an empty directory');
        }
        $this->getLogger()->debug('Destination made empty');

        $this->_destination = $destination;
        $this->getLogger()->info('Destination set to ' . $this->_destination);

        return $this;
    }

    #endregion
    #region - LoggerAwareInterface  helpers


    public function getLogger(): LoggerInterface
    {
        if(!isset($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }



    #endregion
}