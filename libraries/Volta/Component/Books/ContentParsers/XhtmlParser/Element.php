<?php
/*
 * This file is part of the Volta package.
 *
 * (c) Rob Demmenie <rob@volta-server-framework.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Volta\Component\Books\ContentParsers\XhtmlParser;

use Volta\Component\Books\ContentParsers\XhtmlParser;
use Volta\Component\Books\ContentParsers\XhtmlParser\Elements\Exception as Exception;
use Volta\Component\Books\NodeInterface;
use Volta\Component\Books\Settings;

/**
 * Class Element
 *
 * When a BookNode DocumentNode Node is written in xHTMl each xHTML element is translated through
 * a default element instance or one of its descendents. (Located in the Elements Folder)
 *
 * @package Volta\Component\Books\ContentParsers
 * @author Rob <rob@jaribio.nl> 
 */
class Element
{
    /**
     * Element constructor.
     *
     *  Is made protected to forcing to use the factory method.
     *
     * @ignore (do not show up in generated documentation)
     * @param string $name
     * @param array<string, string> $attributes
     * @param Element|false $parent
     */
    protected function __construct(string $name, array $attributes, Element|false $parent)
    {
        $namespaceSeparatorPos = strpos($name, ':');
        if (false !== $namespaceSeparatorPos) {
            $this->_nameSpace = substr($name, 0 , $namespaceSeparatorPos);
            $this->_name = substr($name, $namespaceSeparatorPos);
        } else {
            $this->_nameSpace = '';
            $this->_name = $name;
        }
        $this->_attributes = $attributes;
        $this->_parent = $parent;
    }

    protected NodeInterface $_node;
    protected function _setNode(NodeInterface $node): self
    {
        $this->_node = $node;
        return $this;
    }

    protected function _getNode(): NodeInterface
    {
        return $this->_node;
    }

    // ----------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var string
     */
    protected readonly string $_nameSpace;

    /**
     * @return string
     */
    public function getNameSpace():string
    {
        return $this->_nameSpace;
    }

    // ----------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var string
     */
    protected readonly string $_name;

    /**
     * @return string
     */
    public function getName():string
    {
        return $this->_name;
    }

    // ----------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var array<string, string>
     */
    protected readonly array $_attributes;

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->_attributes;
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getAttribute(string $name, string $default = ''): string
    {
        if ($this->hasAttribute($name)){
            return $this->_attributes[$name];
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->_attributes);
    }

    // ----------------------------------------------------------------------

    /**
     * @var Element|false
     */
    protected Element|false $_parent;

    /**
     * @return Element|false
     */
    public function getParent(): Element|false
    {
        return $this->_parent;
    }

    /**
     * @param Element|false $parent
     * @return static
     */
    public function setParent(Element|false $parent): static
    {
        $this->_parent = $parent;
        return $this;
    }

    protected XhtmlParser $_parser;

    public function setParser(XhtmlParser $parser): static
    {
        $this->_parser = $parser;
        return $this;
    }
    public function getParser(): XhtmlParser
    {
        return $this->_parser;
    }


    // ----------------------------------------------------------------------

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return (false === $this->_parent);
    }

    // ----------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @var Element[]
     */
    protected array $_children = [];

    /**
     * @return Element[]
     */
    public function getChildren(): array
    {
        return $this->_children;
    }

    /**
     * @param Element $child
     * @return static
     */
    public function addChild(Element $child): static
    {
        $this->_children[] = $child;
        return $this;
    }

    // ----------------------------------------------------------------------

    /**
     * Store the valid element classnames
     *
     * @var array
     */
    protected static array $_elementCache = [];

    /**
     * @param string $elementName
     * @param NodeInterface $node
     * @param array<string, string> $attributes
     * @param Element|false $parent
     * @return Element
     * @throws Exception
     */
    public static function factory(string $elementName, NodeInterface $node, array $attributes = [],   Element|bool $parent=false): Element
    {

        $element = null;

        if (isset(Element::$_elementCache[$elementName])) {
            $elementClass = Element::$_elementCache[$elementName];
            $element = new $elementClass($elementName, $attributes, $parent);

        } else {
            $namespaceSeparatorPos = strpos($elementName, ':');
            if (false !== $namespaceSeparatorPos) {
                $namespacePrefix = substr($elementName, 0, $namespaceSeparatorPos);
                $elementName = substr($elementName, $namespaceSeparatorPos + 1);
            } else {
                $namespacePrefix = '';
            }

            if (false !== $nameSpace = Settings::getXhtmlNamespace($namespacePrefix)) {

                $elementFile = $nameSpace[1] . ucfirst($elementName) . '.php';
                $elementClass = $nameSpace[2] . '\\' . ucfirst($elementName);

                if (is_file($elementFile)) {
                    $element = new $elementClass($elementName, $attributes, $parent);
                    if (!is_a($element, Element::class)) {
                        throw new Exception(sprintf('Element %s not found.', $elementName));
                    }
                    Element::$_elementCache[$elementName] = $elementClass;
                }

            }
        }

        if(null === $element) {
            $element = new Element($elementName, $attributes, $parent);
        }

        $element->_setNode($node);
        return $element;
    }

    // ----------------------------------------------------------------------

    /**
     * Empty elements, also called void elements, are elements without content. Like <br> or <hr>.
     * We check for these empty element in this Baseclass and return the empty XHTML compliant self-closing
     * tag (i.e. <br/>)
     *
     * @see https://developer.mozilla.org/en-US/docs/Glossary/Void_element
     * @var array|string[]
     */
    private array $_emptyElements = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'keygen', //(HTML 5.2 Draft removed)
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    /**
     * @return string
     */
    public function onTranslateStart(): string
    {
        if (in_array($this->getName(), $this->_emptyElements)) return '';
        return '<' . $this->getName() . $this->_attributesAsString() . '>';
    }

    /**
     * @param string $data
     * @return string
     */
    public function onTranslateData(string $data) : string
    {
        if (in_array($this->getName(), $this->_emptyElements)) return '';
        return $data;
    }

    /**
     * @return string
     */
    public function onTranslateEnd(): string
    {
        if (in_array($this->getName(), $this->_emptyElements)) {
            return '<' . $this->getName() . $this->_attributesAsString() . '/>';
        }
        return '</' . $this->getName() . '>';
    }

    // ----------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @return string
     */
    protected function _attributesAsString(...$exclude): string
    {
        $attribsAsString = '';
        foreach($this->getAttributes() as $attribName => $attribValue) {
            if (in_array($attribName, $exclude)) continue;
            $attribsAsString .= ' ' .$attribName . '="' . $attribValue . '"';
        }
        return $attribsAsString;
    }

    /**
     * @ignore (do not show up in generated documentation)
     * @param string $data
     * @return string
     */
    protected function _deepTrim(string $data) : string
    {
        return trim($this->_stripWhiteSpaces($data));
    }

    /**
     * @ignore (do not show up in generated documentation)
     * @param string $data
     * @return string
     */
    protected function _stripWhiteSpaces(string $data): string
    {
        $data = str_replace(["\n", "\t", "\r"], ' ', $data);
        while(str_contains($data, '  ')) {
            $data = str_replace('  ', ' ', $data);
        }
        return $data;
    }

    
}