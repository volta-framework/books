<?php
/**
 * This file is part of the Quadro library which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
 */
declare(strict_types=1);

namespace Volta\Component\Books;

use Volta\Component\Books\Exceptions\DocumentNodeException;
use Volta\Component\Books\Exceptions\Exception;
use Volta\Component\Books\Exceptions\ResourceNodeException;

abstract class Node implements NodeInterface
{

    protected readonly string $_absolutePath;

    protected function __construct(string $absolutePath)
    {
        $this->_absolutePath = $absolutePath;
    }


    /**
     *  Memory cache in case we search for the same node again
     * @var array<string, NodeInterface>
     */
    protected static array $_nodesCache = [];

    /**
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
            if (!file_exists($realPath . DIRECTORY_SEPARATOR . 'content.txt') &&
                !file_exists($realPath . DIRECTORY_SEPARATOR . 'content.md') &&
                !file_exists($realPath . DIRECTORY_SEPARATOR . 'content.xhtml') &&
                !file_exists($realPath . DIRECTORY_SEPARATOR . 'content.html') &&
                !file_exists($realPath . DIRECTORY_SEPARATOR . 'content.phtml') &&
                !file_exists($realPath . DIRECTORY_SEPARATOR . 'content.php'))
                throw new DocumentNodeException('Path can not be identified as a node (Missing content.[html|xhtml|php|txt|md])');

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

            // TODO check file extensions
            Node::$_nodesCache[$absolutePath] = new \Volta\Component\Books\ResourceNode($realPath);
        }

        return Node::$_nodesCache[$absolutePath] ;

    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getUri(bool $absolute = true): string
    {
        if ($absolute) return '/' . $this->getRoot()->getName() . str_replace(DIRECTORY_SEPARATOR, '/' , $this->getRelativePath());
        return   trim(str_replace(DIRECTORY_SEPARATOR, '/' , $this->getRelativePath()), '/');
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
     */
    public function getDisplayName(): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $this->getName()));
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
     * @throws Exception
     */
    public function getChild(string $relativePath): null|NodeInterface
    {
        return Node::factory($this->getAbsolutePath() . $relativePath);
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

    public function findNode(string $uri): null|NodeInterface
    {
        return null;
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
                ucwords(str_replace(['_', '-'], ' ', $childNode->getName())),
                $childNode->getUri(false),
                $this->getTocFromNode($childNode)
            );
        }
        return $toc;
    }


}