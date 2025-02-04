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

use FilesystemIterator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Volta\Component\Books\BookNode;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Node;
use Volta\Component\Books\NodeInterface;
use Volta\Component\Books\Publisher;
use Volta\Component\Books\ResourceNode;
use Volta\Component\Books\Settings;


/**
 * @see https://ebookflightdeck.com/
 * @see https://en.wikipedia.org/wiki/EPUB
 */
class Epub extends Publisher
{

    private null|BookNode $activeBook = null;
    private false|string $destination = false;
    private array $bookItems = [];
    private int $bookPages = 0;
    private int $bookResources = 0;

    private string $pageTemplate = '';
    private string $pageStyle = '';


    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    public function sanitizeUri(NodeInterface $node): string
    {
        // create the relative uri for this node thus including a leading SLUG_SEPARATOR
        $relativeUri = '.' . str_replace(DIRECTORY_SEPARATOR, Node::SLUG_SEPARATOR, $node->getRelativePath());

        if ($node->isDocument())  $relativeUri .= "/content.html";
        return $relativeUri;
    }

    /**
     * @param int|string $bookIndex
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public function exportBook(int|string $bookIndex = 0, array $options = []): bool
    {
        $this->setErrorHandler();
        $this->setExceptionHandler();
        $this->setActiveBook($bookIndex);
        $this->setDestination($options['destination'] ?? false);
        $this->createEpubFolderStructure();
        $this->createEpubContainerFile();
        $this->createMimetypeFile();
        $this->collectBookData((array) $options['exclude'] ?? []);
        $this->createPackage();
        $this->createEpubToc();
        $this->createContent();
        $this->createStyle();
        $this->zipIt();

       return true;
    }


    /**
     *  NOTE:
     *    On Windows I tried to do it with the 'tar' command, but it adds the absolute path in the epub and I can not
     *    find how to change them and make all paths relative. If done manually calibre still complains
     *    it is not in the right zip format. So on a Window's machine you should use something like 7-zip to compress
     *    the files and change the file extension to 'epub'
     */
    private function zipIt(): void
    {
        $epubFileName = $this->activeBook->getName()  . '.epub';
        if(is_file( $this->destination . $epubFileName)) {
            unlink(__DIR__ . DIRECTORY_SEPARATOR . $epubFileName);
        }
        //$this->getlogger()->debug("Try to Creat epub file " . $this->destination . $epubFileName . " from  " . $this->destination . 'src/');
        $cmd = "cd {$this->destination}src; pwd; zip -r ../{$epubFileName} .";
        echo shell_exec($cmd);
    }


    private function createStyle(): void
    {
        copy( __DIR__ .'/../../../../../public/assets/css/epub-book.css',
            $this->destination . 'src/OEBPS/css/epub-book.css'  );
        $this->getLogger()->log('Created',  './src/OEBPS/css/epub-book.css');
    }

    private function createContent(): void
    {
        foreach($this->bookItems as $bookItemId => $bookItem) {
            if ($bookItem['node']->isDocument()) {
                $node = $bookItem['node'];
                ob_start();
                include __DIR__ .'/../../../../../templates/epub-book.html.php';
                $content = ob_get_contents();
                ob_end_clean();
                $this->createFile('src/OEBPS/' . $bookItem['href'], $content);
            } else {
                copy($bookItem['node']->getAbsolutePath(), $this->destination . 'src/OEBPS/' .  $bookItem['href']);
                $this->getLogger()->log('Created',  './src/OEBPS/' .  $bookItem['href']);
            }
        }
    }

    private function createPackage(): void
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<package xmlns="http://www.idpf.org/2007/opf" version="2.0" unique-identifier="pub-id">';
        $xml[] = '  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">';
        $xml[] = '    <dc:identifier id="pub-id" opf:scheme="uuid">' . $this->activeBook->getGuid() . '</dc:identifier>';
//urn:uuid:
        $xml[] = '    <meta refines="#pub-id" property="identifier-type" scheme="xsd:string">uuid</meta>';
        $xml[] = '    <meta property="dcterms:modified">2020-01-01T01:01:01Z</meta>';

        $xml[] = '    <dc:title id="title1">' . $this->activeBook->getName() . '</dc:title>';
        $xml[] = '    <meta refines="#title1" property="title-type">main</meta>';
        $xml[] = '    <meta refines="#title1" property="display-seq">1</meta>';
        $xml[] = '    <dc:language>e' . $this->activeBook->getMeta()->get('language', 'en-US'). '</dc:language>';
        $xml[] = '    <dc:creator opf:file-as="' .$this->activeBook->getMeta()->get('author', 'anonymous'). '" opf:role="aut">' .$this->activeBook->getMeta()->get('author', 'anonymous'). '</dc:creator>';
        $xml[] = '  </metadata>';
        $xml[] = '  <manifest>';
        foreach($this->bookItems as $bookItemId => $bookItem) {
            $xml[] = '    <item id="'.$bookItemId . '" href="'. $bookItem['href'] . '" media-type="'.$bookItem['media-type'].'"/>';
        }
        $xml[] = '    <item id="cover" properties="cover-image" href="cover.png" media-type="image/png" />';
        $xml[] = '    <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>';
        $xml[] = '    <item id="stylesheet" href="./css/epub-book.css" media-type="text/css"/>';
        $xml[] = '  </manifest>';
        $xml[] = '  <spine toc="ncx">';
        foreach($this->bookItems as $bookItemId => $bookItem) {
            if ($bookItem['node']->isDocument()) {
                $xml[] = '    <itemref idref="' . $bookItemId . '"/>';
            }
        }
        $xml[] = '  </spine>';
//        $xml[] = '<guide>';
//        $xml[] = '<reference href="00_Cover.xhtml" title="00_Cover" type="cover" />';
//        $xml[] = '</guide>';
        $xml[] = '</package>';

        $this->createFile('src/OEBPS/contents.opf', trim(implode(PHP_EOL, $xml)));
    }


    private function createEpubToc(): void
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="utf-8"?>';
        $xml[] = '<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="enl">';
        $xml[] = '  <head>';
        $xml[] = '    <meta name="dtb:uid" content="'.$this->activeBook->getGuid().'"/>';
        $xml[] = '    <meta name="dtb:depth" content="'.$this->bookPages.'"/>';
        $xml[] = '    <meta name="dtb:generator" content="Volta Books"/>';
        $xml[] = '    <meta name="dtb:totalPageCount" content="'.$this->bookPages.'"/>';
        $xml[] = '    <meta name="dtb:maxPageNumber" content="'.$this->bookPages.'"/>';
        $xml[] = '  </head>';
        $xml[] = '  <docTitle>';
        $xml[] = '    <text>'.$this->activeBook->getMeta()->get('title', $this->activeBook->getName()).'</text>';
        $xml[] = '  </docTitle>';
        $xml[] = '  <navMap>';
        foreach($this->bookItems as $bookItemId => $bookItem) {
            if ($bookItem['node']->isDocument()) {
                $xml[] = '    <navPoint id="' . $bookItemId . '" playOrder="' . $bookItem['node']->getIndex()+1 . '">';
                $xml[] = '        <navLabel>';
                $xml[] = '           <text>' . $bookItem['node']->getMeta()->get('displayName', $bookItem['node']->getName()) . '</text>';
                $xml[] = '         </navLabel>';
                $xml[] = '         <content src="' . $bookItem['href'] . '"/>';
                $xml[] = '    </navPoint>';
            }
        }
        $xml[] = '  </navMap>';
        $xml[] = '</ncx>';

        $this->createFile('src/OEBPS/toc.ncx', trim(implode(PHP_EOL, $xml)));

    }


    private function collectBookData(array $exclude): void
    {
        $this->bookPages = 0;
        $this->bookResources = 0;

        foreach($this->activeBook->getList() as $documentNode) {


            if (in_array($documentNode->getUri(), $exclude)) continue;
            $this->bookItems['VB' .sha1($documentNode->getUri())] = [
                'name' => $documentNode->getName(),
                'href' => $this->sanitizeUri($documentNode),
                'media-type' => 'application/xhtml+xml',
                'node' => $documentNode
            ];
            $this->bookPages++;

            foreach($documentNode->getResources() as $resourceNode) {
                $this->bookItems['VB' . sha1($resourceNode->getUri())] = [
                    'name' => $resourceNode->getName(),
                    'href' =>  $this->sanitizeUri($resourceNode),
                    'media-type' => $resourceNode->getContentType(),
                    'node' => $resourceNode
                ];
                $this->bookResources++;
            }
        }

        $this->getLogger()->debug(sprintf(
            'Found  %d(%d pages, %d resources) book items',
             count($this->bookItems),
             $this->bookPages,
             $this->bookResources
        ));

    }


    private function createMimetypeFile(): void
    {
        $this->createFile('src/mimetype', 'application/epub+zip');
    }


    private function createEpubContainerFile(): void
    {
        $this->createFile('src/META-INF/container.xml', <<< EOF
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">        
    <rootfiles>
        <rootfile full-path="OEBPS/contents.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>
EOF);
    }


    private function createFile(string $file, string $data): void
    {
        $subDir = dirname($file);
        if (!is_dir($this->destination . $subDir)) {
            if (false === mkdir($this->destination . $subDir) ) {
                throw new \RuntimeException('Unable to create subdirectory ' . $subDir);
            }
        }

        $fh = fopen($this->destination . $file, 'w');
        if (false === $fh)  throw new \RuntimeException('Unable to open the file ' . $file);
        if (false === fwrite($fh, $data)) throw new \RuntimeException('Unable to write to the file ' . $file);
        fclose($fh);

        $this->getLogger()->log('Created', './' . $file);
    }


    private function createEpubFolderStructure(): void
    {

        if(!mkdir($this->destination . 'src'))
            throw new Exception('Failed to create epub subdirectory ./src');
        $this->getLogger()->log('created', './src');

        if (!mkdir($this->destination . 'src/META-INF'))
            throw new Exception('Failed to create epub subdirectory ./src/META_INF');
        $this->getLogger()->log('created', './src/META_INF');

        if (!mkdir($this->destination . 'src/OEBPS'))
            throw new Exception('Failed to create epub subdirectory ./src/OEBPS');
        $this->getLogger()->log('created', './src/OEBPS');

        if (!mkdir($this->destination . 'src/OEBPS/css'))
            throw new Exception('Failed to create epub subdirectory ./src/OEBPS/css');
        $this->getLogger()->log('created', './src/OEBPS/css');
    }


    private function setErrorHandler(): void
    {
        $this->getLogger()->debug('Set temporarily error handler');
        $errorHandler = function (int $code, string $message, null|string $file = null, null|int $line = null, null|array $context = null): bool {
            $this->getLogger()->error("$message in $file @ $line");
            $this->teardown();
            exit(1);
        };
        $errorClosure = $errorHandler->bindTo($this);
        set_error_handler($errorClosure);
    }


    private function setExceptionHandler(): void
    {
        $this->getLogger()->debug('Set temporarily exception handler' );
        $exceptionHandler = function (\Throwable $exception): void {
            $this->getLogger()->error(get_class($exception) . ' - ' . $exception->getMessage());
            $this->teardown();
            exit(1);
        };
        $exceptionClosure = $exceptionHandler->bindTo($this);
        set_exception_handler($exceptionClosure);
    }


    private function teardown():void
    {
        restore_error_handler();
        restore_exception_handler();
        $this->getLogger()->debug('Restored error and exception handler');
    }


    private function setActiveBook(int|string $bookIndex = 0): void
    {
        $this->activeBook = $this->getBook($bookIndex);
        if (null === $this->activeBook) {
            throw new Exception(__METHOD__ . ': No book found at index ' . $bookIndex);
        }
    }


    private function setDestination(string $destination): void
    {
        if (!$this->isValidDestination($destination))
            throw new Exception('Destination not pointing to an existing writable directory ' . count(scandir($destination)) );

        if(!$this->sanitizeDestination($destination))
            throw new Exception('Destination not pointing to an empty directory');

        $this->destination = $destination;
        $this->getLogger()->debug('Destination set', [$this->destination]);
    }


    private function isValidDestination(string &$destination): bool
    {
        $destination = realpath($destination);
        return is_dir($destination) && is_writable($destination);
    }


    private function sanitizeDestination(string &$destination): bool
    {
        $destination = realpath($destination) . DIRECTORY_SEPARATOR;
        $this->getLogger()->debug('Sanitizing destination', [$destination]);

        $it = new RecursiveDirectoryIterator($destination, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()) rmdir($file->getRealPath());
            else unlink($file->getRealPath());
        }

        return (count(scandir($destination)) == 2);
    }


}