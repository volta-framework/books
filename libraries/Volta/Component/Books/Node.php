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

use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\MimeTypeNotSupportedException;
use Volta\Component\Books\Exceptions\ResourceNodeException;

abstract class Node implements NodeInterface
{


    /**
     * Global value for the UriOffset used when an absolute uri is requested
     * @see Node::getUri();
     * @var string $uriOffset
     */
    public static string $uriOffset = '';

    /**
     * @var string Internal cache for the absolute path
     */
    protected readonly string $_absolutePath;

    /**
     * Nodes can only be created in the Node::factory() method where all
     * checks are done and feedback is given on error.
     *
     * @param string $absolutePath
     */
    protected function __construct(string $absolutePath)
    {
        $this->_absolutePath = $absolutePath;
    }

    /**
     * Memory cache in case we search for the same node again
     * @var array<string, NodeInterface>
     */
    protected static array $_nodesCache = [];

    /**
     *
     * @param string $absolutePath
     * @return NodeInterface
     * @throws Exception
     */
    public static function factory(string $absolutePath): NodeInterface
    {
        if (isset(Node::$_nodesCache[$absolutePath])) return Node::$_nodesCache[$absolutePath];

        // must be a valid path, readable and slug-able
        $realPath = realpath($absolutePath);
        if (false === $realPath) throw new Exception('Path can not be identified as a node (Invalid path)');
        if (!is_readable($realPath)) throw new Exception('Path can not be identified as a node (Not readable)');
        if (false === preg_match('/[^a-zA-Z0-9_-]/', basename($realPath)))
            throw new Exception('Path can not be identified as a node (Name contains character other then a-z, A-Z, 0-9 hyphen or underscore)');

        // file(resource) or directory(DocumentNode)
        if (is_dir($realPath)) {
            $result = glob($absolutePath . DIRECTORY_SEPARATOR . 'content.*');
            if ($result === false || count($result) === 0)
                throw new DocumentNodeException('Path can not be identified as a node (Missing content.*)');

            if (!file_exists($realPath . DIRECTORY_SEPARATOR . 'meta.json'))
                throw new DocumentNodeException('Path can not be identified as a document node (Missing meta.json)');

            $node = new DocumentNode($realPath);
            if ($node->getParent() === null) {
                $node = new BookNode($realPath);
            }

            Node::$_nodesCache[$absolutePath] = $node;
        }

        // if not it is a file and must be a resource
        else {
            $extension = pathinfo($realPath, PATHINFO_EXTENSION);
            if (!array_key_exists($extension, Settings::$supportedResources)) {
                throw new MimeTypeNotSupportedException('Resources "*.'.$extension.'" not supported ');
            }
            Node::$_nodesCache[$absolutePath] = new ResourceNode($realPath);
        }

        return Node::$_nodesCache[$absolutePath] ;

    }

    const SLUG_SEPARATOR = '/';

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getUri(bool $absolute = true): string
    {
        // create the relative uri for this node thus without a leading SLUG_SEPARATOR
        $relativeUri = trim(str_replace(DIRECTORY_SEPARATOR, Node::SLUG_SEPARATOR,  $this->getRelativePath()), Node::SLUG_SEPARATOR);

        // if we do not want the absolute uri return
        if (false === $absolute)  return $relativeUri;

        // if we want the absolute uri we need to add the slash and the uriOffset(stored in the global Node::$uriOffset)
        // NOTE:
        //    if the uriOffset is not in the correct format hence
        //    - ending with a SLUG_SEPARATOR or
        //    - not starting with a SLUG_SEPARATOR
        //    throw an Exception
        $uriOffset = Node::$uriOffset;
        if ($uriOffset!== '' && !str_starts_with($uriOffset, Node::SLUG_SEPARATOR))
            throw new Exception('Settings::$uriOffset; must start with a forward slash');
        if ($uriOffset!== '' &&  str_ends_with($uriOffset, Node::SLUG_SEPARATOR))
            throw new Exception('Settings::$uriOffset; can not end with a forward slash');

        return $uriOffset . Node::SLUG_SEPARATOR . $relativeUri;

    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return basename($this->getAbsolutePath());
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getDisplayName(): string
    {
        return $this->getMeta()->get('displayName', ucwords(str_replace(['_', '-'], ' ', $this->getName())));
    }


    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return static::class;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getRelativePath(): string
    {
        return str_replace($this->getRoot()->getAbsolutePath(), '', $this->getAbsolutePath());
    }

    /**
     * {@inheritdoc}
     */
    public function getAbsolutePath(): string
    {
        return $this->_absolutePath;
    }

    protected null|NodeInterface $_parent;

    public function getParent(): null|NodeInterface
    {
        // do this only ones in the live time of this Node
        if (!isset($this->_parent)) {

            // The root is the directory with no other DocumentNode directories above.
            // So we loop upwards to find this folder ...
            $directories = explode(DIRECTORY_SEPARATOR, rtrim($this->getAbsolutePath(), DIRECTORY_SEPARATOR));

            $currentNode = $this;
            $next = true;
            $loopCounter = count($directories);
            $this->_parent = null;
            while($next) {
                // start with removing the current  because we look for the parent
                array_pop($directories);
                $loopCounter--;
                if ($loopCounter == 0 ) break;
                $possibleParentPath = implode(DIRECTORY_SEPARATOR, $directories);
                try {
                    $parentNode = Node::factory($possibleParentPath);
                    if (is_a($parentNode, DocumentNode::class)) {
                        $this->_parent  = $parentNode;
                        $next = false;
                    }
                } catch (Exception|DocumentNodeException|ResourceNodeException $e) {
                    $next = true;
                }
            };
        }
        return $this->_parent;
    }

    /**
     */
    public function getChild(string $relativePath): null|NodeInterface
    {
        try {
            return Node::factory($this->getAbsolutePath() . $relativePath);
        } catch(Exception $e){
            return null;
        }
    }

    protected null|NodeInterface $_root;

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getRoot(): NodeInterface
    {
        // do this only ones in the live time of this Node
        if (!isset($this->_root)) {

            // The root is the directory with no other DocumentNode directories above.
            // So we loop upwards to find this folder ...
            $directories = explode(DIRECTORY_SEPARATOR, rtrim($this->getAbsolutePath(), DIRECTORY_SEPARATOR));
            $currentNode = $this;
            $next = true;
            $loopCounter = count($directories);
            while($next) {
                $loopCounter--;
                if ($loopCounter == 0 ) throw new Exception('Unexpected error searching for root folder');
                $possibleParentPath = implode(DIRECTORY_SEPARATOR, $directories);
                try {
                    $parentNode = Node::factory($possibleParentPath);
                    if (is_a($parentNode, DocumentNode::class)) $currentNode = $parentNode;
                } catch (Exception|DocumentNodeException|ResourceNodeException $e) {
                    $next = ($currentNode->getType() === \Volta\Component\Books\ResourceNode::class);
                }
                array_pop($directories);
            };
            $this->_root = $currentNode;
        }
        return $this->_root;
    }

    public function getList(): array
    {
        $list[] = $this;
        foreach($this->getChildren() as $child) {
            $list = array_merge($list, $child->getList());
        }
        return $list;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function __toString(): string
    {
        $str  = "Type {$this->getType()}";
        $str .= "\nAbsolute Path : " . $this->getAbsolutePath();
        $str .= "\nAbs. Root     : " . $this->getRoot()->getAbsolutePath();
        $str .= "\nAbs. Parent   : " . (($this->getParent()===null) ? 'null' : $this->getParent()->getAbsolutePath());
        $str .= "\nName          : " . $this->getName();
        $str .= "\nUri           : " . $this->getUri();
        $str .= "\nRelativePath  : " . $this->getRelativePath();
        $str .= "\nchildren      : ". print_r(array_keys($this->getChildren()), true);
        $str .= "next          : " . (($this->getNext()===null) ? 'null' : $this->getNext()->getAbsolutePath());
        $str .= "\nprevious      : " . (($this->getPrevious()===null) ? 'null' : $this->getPrevious()->getAbsolutePath());
        $str .= "\n";
        return $str;
    }

    /**
     * @var array|TocItem[]
     */
    protected array $_toc = [];

    /**
     * @return array|TocItem[]
     */
    public function getToc(): array
    {
        $this->_toc = $this->getTocFromNode($this);
        return $this->_toc;
    }

    /**
     * @param NodeInterface $node
     * @return array|TocItem[]
     */
    protected function getTocFromNode(NodeInterface $node): array
    {
        $toc = [];
        foreach($node->getChildren() as $childNode) {
            $toc[] = new TocItem(
                ucwords(str_replace(['_', '-'], ' ', $childNode->getDisplayName())),
                $childNode->getUri(),
                $this->getTocFromNode($childNode)
            );
        }
        return $toc;
    }


}