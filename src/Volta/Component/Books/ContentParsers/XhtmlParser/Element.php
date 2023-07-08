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

namespace Volta\Component\Books\ContentParsers\XhtmlParser;

use Volta\Component\Books\ContentParsers\XhtmlParser\Elements\Exception as Exception;
use Volta\Component\Books\NodeInterface;

/**
 * Class Element
 *
 * When a BookNode DocumentNode Node is written in xHTMl each xHTML element is translated through
 * a default element instance or one of its descendent. (Located in the Elements Folder)
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
        $this->_name = $name;
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
    protected string $_name;

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
    protected array $_attributes;

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
     * @param string $elementName
     * @param NodeInterface $node
     * @param array<string, string> $attributes
     * @param Element|false $parent
     * @return Element
     * @throws \Volta\Component\Books\ContentParsers\XhtmlParser\Elements\Exception
     */
    public static function factory(string $elementName, NodeInterface $node, array $attributes = [],   Element|bool $parent=false): Element
    {
        $elementFile = __DIR__ . DIRECTORY_SEPARATOR . 'Elements' . DIRECTORY_SEPARATOR . ucfirst($elementName) . '.php';
        $elementClass = Element::class . 's\\' . ucfirst($elementName);
        if (is_file($elementFile)){
            $element  = new $elementClass($elementName, $attributes, $parent);
            $element->_setNode($node);
            if (is_a($element, Element::class)) {
                return $element;
            } else {
                throw new Exception(sprintf('Element %s not found.', $elementName));
            }
        }
        $element =  new Element($elementName, $attributes, $parent);
        $element->_setNode($node);
        return $element;
    }

    // ----------------------------------------------------------------------

    /**
     * @return string
     */
    public function onTranslateStart(): string
    {
        return '<' . $this->getName() . $this->_attributesAsString() . '>';
    }

    /**
     * @param string $data
     * @return string
     */
    public function onTranslateData(string $data) : string
    {
        return $data;
    }

    /**
     * @return string
     */
    public function onTranslateEnd(): string
    {
        return '</' . $this->getName() . '>';
    }

    // ----------------------------------------------------------------------

    /**
     * @ignore (do not show up in generated documentation)
     * @return string
     */
    protected function _attributesAsString(): string
    {
        $attribsAsString = '';
        foreach($this->getAttributes() as $attribName => $attribValue) {
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