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
class EpubOrg extends Publisher
{

    private string $_contentDir = 'OEBPS';
    private string $_metadataFileName = 'content.opf';
    private string $_tocFileName = 'toc.ncx';
    private string $_sourceDirName = 'src';




    /**
     * @throws Exception
     */
    public function  __construct(array $options)
    {
        parent::__construct($options);
        $this->_setDestination($options['destination'] ?? '');

    }

    private BookNode $_currentBook;

    /**
     * Exports a Volta Book to an epub file
     *
     * @param string|int $bookIndex
     * @param array $options = []
     * @return bool
     * @throws Exception When an invalid destination is given
     * @see https://en.wikipedia.org/wiki/EPUB
     */
    public function exportBook(string|int $bookIndex = 0, array $options = []): bool
    {

        $this->_currentBook = $this->getBook($bookIndex);
        $this->getLogger()->log('# SETUP', '');
        $this->_setup();


        //$this->getLogger()->log('#3 OPEN CONTAINER', '');
        $this->_createOpenContainer();

        $this->getLogger()->log( '# CONTENT', '');
        $this->_createEpubContent();

        $this->getLogger()->log( '# TOC', '');
        $this->_createEpubToc();

        $this->getLogger()->log('# RESOURCES', '');
        $this->_addResources();

        $this->getLogger()->log('# ZIP IT', '');
        $this->_zipIt();

        return true;
    }


    #region - #1 Setup and Teardown


    /**
     * Setup environment for creating the EPUB
     *
     * @return void
     */
    private function _setup():void
    {
        $this->getLogger()->debug('Set temporarily error handler', [__METHOD__]);
        $errorHandler = function(int $code, string $message, null|string $file = null, null|int $line = null, null|array $context = null ): bool {
            $this->getLogger()->error("$message in $file @ $line");
            $this->_teardown();
            exit(1);
        };
        $errorClosure = $errorHandler->bindTo($this);
        set_error_handler($errorClosure);

        $this->getLogger()->debug('Set temporarily exception handler', [__METHOD__]);
        $exceptionHandler = function(\Throwable $exception): void {
            $this->getLogger()->error(get_class($exception) . ' - ' . $exception->getMessage());
            $this->_teardown();
            exit();
        };
        $exceptionClosure = $exceptionHandler->bindTo($this);
        set_exception_handler($exceptionClosure);
    }

    /**
     * Restore environment
     *
     * @return void
     */
    private function _teardown():void
    {
        $this->getLogger()->info('Restore error and exception handler', [__METHOD__]);
        restore_error_handler();
        restore_exception_handler();
    }

    #endregion
    #region - #2 Validate and Sanitize  Destination

    private string $_destination;

    /**
     *
     * @return string
     */
    public function getSourceDir(): string
    {
        return $this->_destination . $this->_sourceDirName . DIRECTORY_SEPARATOR;
    }

    public function getDestination(): string
    {
        return $this->_destination;
    }

    /**
     * @param string $destination
     * @return void
     * @throws Exception
     */
    private function _setDestination(string $destination): void
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
        //$this->getLogger()->debug('Destination made empty');
        $this->_destination = $destination;

        // add the libraries dir
        mkdir($this->_destination . $this->_sourceDirName);
        $this->getLogger()->info('Destination set to ' . $this->_destination);

    }

    #endregion
    #region - #3 Create Basic Open Container Organisation

    /**
     * @return void
     * @throws Exception
     */
    private function _createOpenContainer(): void
    {
        // creating the required mimetype file
        $fh = fopen($this->getSourceDir(). 'mimetype', 'w');
        fwrite($fh, 'application/epub+zip'); // application/epub+zip
        fclose($fh);
        $this->getLogger()->log('Created' , './mimetype');

        // creating the required container file
        if (false === mkdir($this->getSourceDir() . 'META-INF')) {
            throw new Exception('Failed to create required directory META-INF');
        }
        $this->getLogger()->log('Created' , './META-INF/');
        $fh = fopen($this->getSourceDir(). 'META-INF'. DIRECTORY_SEPARATOR . 'container.xml', 'w');
        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . PHP_EOL);
        fwrite($fh, '<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">' . PHP_EOL);
        fwrite($fh, '  <rootfiles>' . PHP_EOL);
        fwrite($fh, '    <rootfile full-path="OEBPS/' . $this->_metadataFileName. '" media-type="application/oebps-package+xml"/>' . PHP_EOL);
        fwrite($fh, '  </rootfiles>' . PHP_EOL);
        fwrite($fh, '</container>' . PHP_EOL);
        fclose($fh);
        $this->getLogger()->log('Created', './META-INF'. DIRECTORY_SEPARATOR . 'container.xml');

        // creating apple information
        $fh = fopen($this->getSourceDir(). 'META-INF'. DIRECTORY_SEPARATOR . 'com.apple.ibooks.display-options.xml', 'w');
        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'. PHP_EOL);
        fwrite($fh, '<display_options>'. PHP_EOL);
        fwrite($fh, '<platform name="*">'. PHP_EOL);
        fwrite($fh, '<option name="specified-fonts">true</option>'. PHP_EOL);
        fwrite($fh, '</platform>'. PHP_EOL);
        fwrite($fh, '</display_options>'. PHP_EOL);
        fclose($fh);
        $this->getLogger()->log('Created', './META-INF'. DIRECTORY_SEPARATOR . 'com.apple.ibooks.display-options.xml');
        
        // creating the required root file(s)
        if (false === mkdir($this->getSourceDir() . $this->_contentDir)) {
            throw new Exception('Failed to create required directory ' . $this->_contentDir);
        }
        $this->getLogger()->log('Created' , './' . $this->_contentDir . DIRECTORY_SEPARATOR );
    }
    #endregion
    #region - #4 Create EPUB Content

    /**
     * @return void
     * @throws Exception
     */
    private function _createEpubContent(): void
    {
        // build the opf data
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="pub-id">';
        $xml[] = '  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">';
        $xml[] = '    <dc:identifier id="pub-id" opf:scheme="uuid">' .$this->_currentBook->getGuid() . '</dc:identifier>';
//urn:uuid:
        $xml[] = '    <meta refines="#pub-id" property="identifier-type" scheme="xsd:string">uuid</meta>';
        $xml[] = '    <meta property="dcterms:modified">2020-01-01T01:01:01Z</meta>';

        $xml[] = '    <dc:title id="title1">' . $this->_currentBook->getName() . '</dc:title>';
        $xml[] = '    <meta refines="#title1" property="title-type">main</meta>';
        $xml[] = '    <meta refines="#title1" property="display-seq">1</meta>';
        $xml[] = '    <dc:language>e' . $this->_currentBook->getMeta()->get('language', 'en-US'). '</dc:language>';
        $xml[] = '    <dc:creator opf:file-as="' .$this->_currentBook->getMeta()->get('author', 'anonymous'). '" opf:role="aut">' .$this->_currentBook->getMeta()->get('author', 'anonymous'). '</dc:creator>';
        $xml[] = '  </metadata>';
        $xml[] = '  <manifest>';

        // - build manifest
        $manifest = [];
        $this->_createManifest($this->_currentBook,  $manifest);
        foreach($manifest as $item) {
            $xml[] = '    <item id="'.$item['id'].'" href="'.str_replace('OEBPS/','',$item['href']).'" media-type="'.$item['media-type'].'"/>';
        }

        // add special entries
        $xml[] = '    <item id="cover" properties="cover-image" href="cover.png" media-type="image/png" />';
        $xml[] = '    <item id="ncx" href="' . $this->_tocFileName . '" media-type="application/x-dtbncx+xml"/>';
        $xml[] = '  </manifest>';
        $xml[] = '  <spine toc="ncx">';
        foreach($manifest as $item) {
            $xml[] = '    <itemref idref="'.$item['id'].'"/>';
        }
        $xml[] = '  </spine>';
        $xml[] = '</package>';


        // write the file
        $fh = fopen( $this->getSourceDir() . $this->_contentDir . DIRECTORY_SEPARATOR . $this->_metadataFileName, 'w');
        fwrite($fh, trim(implode(PHP_EOL, $xml)));
        fclose($fh);
        $this->getLogger()->log('Created', './' . $this->_contentDir . DIRECTORY_SEPARATOR . $this->_metadataFileName );



    }


    /**
     * Create all the content files and stores the in the manifest array
     * @param NodeInterface $node
     * @param array $manifest
     * @return void
     * @throws Exception
     */
    private function _createManifest(NodeInterface $node, array &$manifest): void
    {
        /**
         * @var Node $node
         */
        foreach($this->_currentBook->getList() as $node) {

            $file = $this->_getFileName($node);
            $manifest[$node->getUri()] = [
                'id' => $this->_getResourceId($node),
                'href' => $file,
                'media-type' => 'application/xhtml+xml'
            ];
            $fh = fopen($this->getSourceDir() . $file, 'w');
            $level = count(explode('/', $node->getUri())) -1;


            ob_start();
            include $this->options['pageTemplate']   ;
            $node->getContent();
            $content = ob_get_contents();
            ob_end_clean();

            if (false !== fwrite($fh, $content)) {
                $this->getLogger()->log('Created', './' . $file);
            } else {
                $this->getLogger()->error('Failed creating ' . $file);
            }
            fclose($fh);

            /**
             * @var ResourceNode $resource
             */
            foreach($node->getResources() as $resource) {
                $file = $this->_getFileName($resource);;
                $manifest[$resource->getUri()] = [
                    'id' => $this->_getResourceId($resource),
                    'href' =>  $file,
                    'media-type' => $resource->getContentType()
                ];
                copy($resource->getAbsolutePath(), $this->getSourceDir() .  $file);
                $this->getLogger()->log('Created',  './'  . $file);

            }
        }
    }

    #endregion
    #region - #5 Create EPUB TOC

    /**
     * @return void
     * @throws Exception
     */
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
            $navMap[] = $offset . '      <content libraries="'. $file.'"/>';
            foreach($node->getChildren() as $child) {
                $addToNavMap($child, $level+1);
            }
            $navMap[] = $offset . '    </navPoint>';
        };
        $addToNavMap = $addToNavMap->bindTo($this);


        foreach($this->_currentBook->getChildren() as $child) {
            $addToNavMap($child, 0);
        }

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="utf-8"?>';
        $xml[] = '<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="enl">';
        $xml[] = '  <head>';
        $xml[] = '    <meta name="dtb:uid" content="'.$this->_currentBook->getGuid().'"/>';
        $xml[] = '    <meta name="dtb:depth" content="'.$depth.'"/>';
        $xml[] = '    <meta name="dtb:generator" content="Volta Books"/>';
        $xml[] = '    <meta name="dtb:totalPageCount" content="0"/>';
        $xml[] = '    <meta name="dtb:maxPageNumber" content="0"/>';
        $xml[] = '  </head>';
        $xml[] = '  <docTitle>';
        $xml[] = '    <text>'.$this->_currentBook->getMeta()->get('title', $this->_currentBook->getName()).'</text>';
        $xml[] = '  </docTitle>';
        $xml[] = '  <navMap>';
        $xml = array_merge($xml, $navMap);
        $xml[] = '  </navMap>';
        $xml[] = '</ncx>';

        $fh = fopen(  $this->getSourceDir() . 'OEBPS/' . $this->_tocFileName, 'w');
        fwrite($fh, trim(implode(PHP_EOL, $xml)));
        fclose($fh);
        $this->getLogger()->log('Created ', 'OEBPS/'. $this->_tocFileName, [__METHOD__]);

    }
    #endregion
    #region - #6 Add resources to EPUB

    private function _addResources():void
    {
        // style sheet
        $cssContent = file_get_contents($this->options['pageStyle']);
        $fh = fopen($this->getSourceDir() . $this->_contentDir . DIRECTORY_SEPARATOR . 'epub-book.css', 'w');
        fwrite($fh, $cssContent);
        fclose($fh);
        $this->getLogger()->log("Created" ,  $this->_contentDir . DIRECTORY_SEPARATOR . 'epub-book.css');

        // cover file if anny otherwise generate default
        $coverFile = $this->_currentBook->getChild('/cover.png');
        if (NULL !== $coverFile) {
            if (copy($coverFile->getAbsolutePath(), $this->getSourceDir() . $this->_contentDir . DIRECTORY_SEPARATOR . 'cover.png')) {
                $this->getLogger()->log("Created", $this->_contentDir . DIRECTORY_SEPARATOR . 'cover.png');
            }
        }
    }

    #endregion
    #region - #7 Compress data and zip to epub

    /**
     *  NOTE:
     *    On Windows I tried to do it with the 'tar' command, but it adds the absolute path in the epub and I can not
     *    find how to change them and make all paths relative. If done manually calibre still complains
     *    it is not in the right zip format. So on a Window's machine you should use something like 7-zip to compress
     *    the files and change the file extension to 'epub'
     */
    private function _zipIt(): void
    {
        $epubFileName = $this->_currentBook->getName()  . '.epub';
        if(is_file( $this->getDestination() . $epubFileName)) unlink(__DIR__ . DIRECTORY_SEPARATOR . $epubFileName);
        $this->getlogger()->info("Try to Creat epub file " . $this->getDestination() . $epubFileName . " from  " . $this->getSourceDir());
        $cmd = "cd {$this->getSourceDir()}; pwd; zip -r ../{$epubFileName} .";
        $this->getlogger()->info($cmd);
        echo shell_exec($cmd);
    }

    #endregion
    #region - helpers


    private function _getResourceId(NodeInterface $node): string
    {
        return 'V' .sha1($node->getUri());
    }

    /**
     * @param NodeInterface $node
     * @return string
     */
    private function _getFileName(NodeInterface $node):string
    {
        if ($node->isDocument() ) {
            if (!is_dir($this->getSourceDir() . $this->_getContentDir() . $node->getRelativePath())) {
                mkdir($this->getSourceDir() . $this->_getContentDir() . $node->getRelativePath(), 0777, true);
                //$this->getLogger()->debug('Created ' . $this->_getContentDir() . $node->getRelativePath());
            }
            $name = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $this->_getContentDir() . trim($node->getRelativePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'content.html');

        } else {
            if (!is_dir($this->getSourceDir() . $this->_getContentDir() . dirname($node->getRelativePath()))) {
                mkdir($this->getSourceDir() . $this->_getContentDir() . dirname($node->getRelativePath()), 0777, true);
                //$this->getLogger()->debug('Created ' . $this->_getContentDir() . $node->getRelativePath());
            }
            $name = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $this->_getContentDir() . trim($node->getRelativePath(), DIRECTORY_SEPARATOR));
        }
        return $name ;
    }

    /**
     * Formats the name of the content directory
     *
     * @return string
     */
    private function _getContentDir(): string
    {
        return $this->_contentDir . DIRECTORY_SEPARATOR;
    }

    public function sanitizeUri(NodeInterface $node): string
    {
        // create the relative uri for this node thus including a leading SLUG_SEPARATOR
        $relativeUri = str_replace(DIRECTORY_SEPARATOR, Node::SLUG_SEPARATOR, $node->getRelativePath());

        // if it is a DocumentNode, add the trailing slash to make all relative uris valid
        if ($node->isDocument()) {
            $relativeUri .= "/content.html";
        }
        return $relativeUri;
    }
    #endregion
}