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

namespace Volta\Component\Books\ContentParsers;

use Closure;
use Volta\Component\Books\ContentParserInterface;
use Volta\Component\Books\ContentParsers\XhtmlParser\Element;
use Volta\Component\Books\ContentParsers\XhtmlParser\Exception;
use Volta\Component\Books\ContentParserTrait;
use Volta\Component\Books\NodeInterface;
use Volta\Component\Books\Settings;
use XMLParser;

/**
 * Class ContentParser
 * 
 * Parses DocumentNode XHTML content and generates html for it.
 * 
 * For each element found, the parser checks if a matching Element Class is defined.
 * If not a default Element object is used if found, a descendent of the default Element
 * class is used.
 * 
 * An Element translates the starting tag, all data found and the end tag to 
 * what ever HTML the element finds appropriate.
 *
 */
class XhtmlParser implements ContentParserInterface
{

    use ContentParserTrait;

    /**
     * When TRUE prints extra HTML comments 
     *
     * @var bool $_verbose
     */
    private bool $_verbose;


    // -----------------------------------------------------------------------------

    /**
     * Reference to the current XML file
     *
     * @var string $_file
     */
    private string $_file;

    /**
     * The location current opened XML file
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->_file;

    }

    // -----------------------------------------------------------------------------


    const EVENT_ON_FINISH = 'onFinish';

    /**
     * @var array<string, mixed>
     */
    protected array $_eventListeners = [];

    /**
     * @param string $event
     * @param mixed $callback
     * @return bool
     * @throws Exception
     */
    public function addListener(string $event, mixed $callback): bool
    {
        if (!is_callable($callback)) {
            throw new Exception(__CLASS__ . '::' . __METHOD__  .'() - Please provide a valid callback');
        }
        if (!isset($this->_eventListeners[$event])) {
            $this->_eventListeners[$event] = [];
        }
        $this->_eventListeners[$event][] = $callback;
        return true;
    }

    /**
     * @param string $event
     * @return void
     * @throws Exception
     */
    protected function _notify(string $event): void
    {
        if (!isset($this->_eventListeners[$event])) return;
        foreach($this->_eventListeners[$event] as $callback) {
            $callbackReturn = call_user_func($callback, $this);
            if ( !is_string($callbackReturn)) {
                throw new Exception(__CLASS__ . '::' . __METHOD__  .'() - EventListener must return a string');
            }
            $this->_content .= $callbackReturn;
        }
    }

    // -----------------------------------------------------------------------------

    /**
     * Starts the parsing of the XML file set in the constructor
     *
     * @param string $file
     * @param NodeInterface $node
     * @param bool $verbose
     * @return string  The parse data
     * @throws Exception On XML syntax errors
     */
    public function getContent(string $file, NodeInterface $node, bool $verbose = false): string
    {
        $this->_file = $file;
        $this->setNode($node);
        $this->_verbose = $verbose;

        $xmlParser = xml_parser_create();

        xml_set_object($xmlParser, $this);
        xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);


        xml_set_start_namespace_decl_handler($xmlParser, [$this, 'startNamespaceDeclHandler']);
        xml_set_end_namespace_decl_handler($xmlParser, [$this, 'endNamespaceDeclHandler']);


        xml_set_character_data_handler($xmlParser, [$this, 'characterDataHandler']);
        xml_set_default_handler($xmlParser, [$this, 'defaultHandler']);
        xml_set_element_handler($xmlParser, [$this,'elementStartHandler'], [$this,'elementEndHandler']);

        //xml_set_unparsed_entity_decl_handler($xmlParser, 'unparsedEntityDeclHandler');
        //xml_set_external_entity_ref_handler($xmlParser,'externalEntityRefHandler');

        $stream = fopen($this->getFile(), 'r');
        if (false !== $stream) {

            $start = true;
            while (($data = fread($stream, 16384))) {

                // add root element(xhtml) to the data if not done
                if ($start ) {
                    $data = ltrim($data);
                    if (!str_starts_with(strtolower($data), '<volta:xhtml')) {
                        $data = '<volta:xhtml xmlns:volta="https://volta-framework.com/component-books/xhtml">' . $data;
                    }
                    $start = false;
                }
                $end = feof($stream);
                if ($end) {
                    $data = rtrim($data);
                    if (!str_ends_with(strtolower($data), '</volta:xhtml>')) {
                        $data .= '</volta:xhtml>' ;
                    }
                }

                if (!xml_parse($xmlParser, $data, $end)) {
                    $errorCode = xml_get_error_code($xmlParser);
                    $errorMessage = match ($errorCode) {
                        XML_ERROR_NO_MEMORY => 'XML_ERROR_NO_MEMORY',
                        XML_ERROR_SYNTAX => 'XML_ERROR_SYNTAX',
                        XML_ERROR_NO_ELEMENTS => 'XML_ERROR_NO_ELEMENTS',
                        XML_ERROR_INVALID_TOKEN => 'XML_ERROR_INVALID_TOKEN',
                        XML_ERROR_UNCLOSED_TOKEN => 'XML_ERROR_UNCLOSED_TOKEN',
                        XML_ERROR_PARTIAL_CHAR => 'XML_ERROR_PARTIAL_CHAR',
                        XML_ERROR_TAG_MISMATCH => 'XML_ERROR_TAG_MISMATCH',
                        XML_ERROR_DUPLICATE_ATTRIBUTE => 'XML_ERROR_DUPLICATE_ATTRIBUTE',
                        XML_ERROR_JUNK_AFTER_DOC_ELEMENT => 'XML_ERROR_JUNK_AFTER_DOC_ELEMENT',
                        XML_ERROR_PARAM_ENTITY_REF => 'XML_ERROR_PARAM_ENTITY_REF',
                        XML_ERROR_UNDEFINED_ENTITY => 'XML_ERROR_UNDEFINED_ENTITY',
                        XML_ERROR_RECURSIVE_ENTITY_REF => 'XML_ERROR_RECURSIVE_ENTITY_REF',
                        XML_ERROR_ASYNC_ENTITY => 'XML_ERROR_ASYNC_ENTITY',
                        XML_ERROR_BAD_CHAR_REF => 'XML_ERROR_BAD_CHAR_REF',
                        XML_ERROR_BINARY_ENTITY_REF => 'XML_ERROR_BINARY_ENTITY_REF',
                        XML_ERROR_ATTRIBUTE_EXTERNAL_ENTITY_REF => 'XML_ERROR_ATTRIBUTE_EXTERNAL_ENTITY_REF',
                        XML_ERROR_MISPLACED_XML_PI => 'XML_ERROR_MISPLACED_XML_PI',
                        XML_ERROR_UNKNOWN_ENCODING => 'XML_ERROR_UNKNOWN_ENCODING',
                        XML_ERROR_INCORRECT_ENCODING => 'XML_ERROR_INCORRECT_ENCODING',
                        XML_ERROR_UNCLOSED_CDATA_SECTION => 'XML_ERROR_UNCLOSED_CDATA_SECTION',
                        XML_ERROR_EXTERNAL_ENTITY_HANDLING => 'XML_ERROR_EXTERNAL_ENTITY_HANDLING',

                        // see for  others:  https://gnome.pages.gitlab.gnome.org/libxml2/devhelp/libxml2-xmlerror.html

                        26 => 'XML_ERR_UNDECLARED_ENTITY',


                        default => "UNKNOWN ERROR"
                    };

                    $exceptionMessage = sprintf(
                        'XML error(%d) at line %d column %d: %s (%s)',
                        $errorCode,
                        xml_get_current_line_number($xmlParser),
                        xml_get_current_column_number($xmlParser),
                        $errorMessage,$file
                    );
                    xml_parser_free($xmlParser);
                    fclose($stream);
                    throw new Exception($exceptionMessage, $errorCode);
                } // if ...
            } // while ...

            $this->_notify(self::EVENT_ON_FINISH);

            xml_parse($xmlParser, '', true); // finalize parsing
            xml_parser_free($xmlParser);
            fclose($stream);
        }

        // https://www.w3schools.com/charsets/ref_emoji_smileys.asp
        return str_replace(
            [':-)', '8-)', ';-)', ':-('],
            ['&#127773;', '&#128526;', '&#128521;', '&#128543;'],
            $this->_content
        );

    } // startParse(...)




    // -----------------------------------------------------------------------------

    /**
     * @var array<Element> $_stack
     */
    private array $_stack = [];

    /**
     * @var string
     */
    private string $_content = '';

    /**
     * Receives the data and updates the result
     *
     * @see https://www.php.net/manual/en/function.xml-set-character-data-handler.php
     * @param XmlParser $xmlParser
     * @param string $data
     * @return bool
     */
    protected function characterDataHandler(\XmlParser $xmlParser, string $data): bool
    {
        $element = end($this->_stack);
        if ( false !== $element) {
            if ($this->_verbose) $this->_content .= "\n<!--OnCharacterDataHandler: {$element->getName()}-->\n";
            $this->_content .= $element->onTranslateData($data);
        }
        return true;
    }

    /**
     * Receives the data and updates the result
     *
     * @see https://www.php.net/manual/en/function.xml-set-default-handler.php
     * @param XMLParser $xmlParser
     * @param string $data
     * @return bool
     */
    protected function defaultHandler(XmlParser $xmlParser, string $data): bool
    {
        $element = end($this->_stack);
        if (false !== $element) {
            if ($this->_verbose) $this->_content .= "\nm<!--OnDefaultHandler: {$element->getName()}-->\n";
            $this->_content .= $element->onTranslateData($data);
        }
        return true;
    }

    /**
     * Receives the data and updates the result
     *
     * @see https://www.php.net/manual/en/function.xml-set-element-start-handler.php
     * @param XMLParser $xmlParser
     * @param string $name
     * @param array<string, string> $attribs
     * @return bool
     * @throws XhtmlParser\Elements\Exception
     */
    protected function elementStartHandler(XmlParser $xmlParser, string $name, array $attribs): bool
    {
        $parent =  end($this->_stack);
        $this->_stack[] = Element::factory($name, $this->_node, $attribs);
        $element = end($this->_stack);
        $element->setParent($parent);
        $element->setParser($this);
        if(false !== $parent) $parent->addChild($element);
        if($this->_verbose) $this->_content .= "\n<!--OnElementStartHandler: {$element->getName()}-->\n";
        $this->_content .= $element->onTranslateStart();
        return true;

    }

    /**
     * Receives the data and updates the result
     *
     * @see https://www.php.net/manual/en/function.xml-set-element-end-handler.php
     * @param XMLParser $xmlParser
     * @param string $name
     * @return bool
     */
    protected function elementEndHandler(XmlParser $xmlParser, string $name): bool
    {
        $element = array_pop($this->_stack);
        if (null !== $element) {
            if ($this->_verbose) $this->_content .= "\n<!--OnElementEndHandler: {$element->getName()}-->\n";
            $this->_content .= $element->onTranslateEnd();
        }
        return true;

    }


    /**
     * NOTE: this does not seem to work!!!
     *
     * @param XMLParser $xmlParser
     * @param string $prefix
     * @param string $uri
     * @return bool|int
     */
    public function startNamespaceDeclHandler(XmlParser $xmlParser, string $prefix, string $uri): bool|int
    {
        if ($this->_verbose) $this->_content .= "\n<!--startNamespaceDeclHandler: {$prefix} : {$uri}-->\n";
        $namespace = Settings::getXhtmlNamespace($prefix);
        if (false === $namespace) {
            //throw new Exception("$prefix is not recognized as a Volta Books Namespace");
            return false;
        }

        if ($namespace[1]  !== $uri) {
            //throw new Exception("$prefix is not recognized as a Volta Books Namespace");
            return false;
        }
        return 1;
    }


    public function endNamespaceDeclHandler(XmlParser $xmlParser, string $prefix): int
    {
        return 1;
    }

    public function getContentType(): string
    {
        return 'text/html';
    }

} // class