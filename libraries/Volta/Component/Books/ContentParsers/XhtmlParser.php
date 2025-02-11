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

    /**
     * When TRUE the PHP buffer is used
     *
     * @var bool $_useBuffer
     */
    private bool $_useBuffer = true;


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
            if (!is_string($callbackReturn)) {
                throw new Exception(__CLASS__ . '::' . __METHOD__ . '() - EventListener must return a string');
            }
            if ($this->_useBuffer) echo $callbackReturn;
            else  $this->_content .= $callbackReturn;

        }
    }

    // -----------------------------------------------------------------------------

    /**
     * Starts the parsing of the XML file set in the constructor
     *
     * @inheritDoc
     */
    public function getContent(string $source, bool $verbose = false): string
    {
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

//        xml_set_unparsed_entity_decl_handler($xmlParser, [$this, 'unparsedEntityDeclHandler']);
//        xml_set_external_entity_ref_handler($xmlParser, [$this, 'externalEntityRefHandler']);

        if ($this->_useBuffer)  ob_start();

        if (is_file($source)) {
            $this->_file = $source;
            $this->_getContentFromFile($xmlParser, $this->_file);
        } else {
            $this->_getContentFromString($xmlParser, $source);
        }

        $this->_notify(self::EVENT_ON_FINISH);
        xml_parser_free($xmlParser);

        if ($this->_useBuffer) {
            $this->_content = ob_get_contents();
            ob_end_clean();
        }


        // https://www.w3schools.com/charsets/ref_emoji_smileys.asp
        return str_replace(
            [':-)', '8-)', ';-)', ':-('],
            ['&#127773;', '&#128526;', '&#128521;', '&#128543;'],
            $this->_content
        );

    } // startParse(...)

    /**
     * @param XMLParser $xmlParser
     * @param string $source
     * @return void
     * @throws Exception
     */
    protected function _getContentFromFile(\XMLParser $xmlParser, string $source): void
    {
        $stream = fopen($source, 'r');
        if (false !== $stream) {

            $start = true;
            while (($data = fread($stream, 16384))) {

                // add root element(xhtml) to the data if not done
                if ($start ) {
                    $data = ltrim($data);
                    if (!str_starts_with(strtolower($data), '<volta:xhtml')) {
                        $data = '<volta:xhtml xmlns:volta="https://volta-framework.com/volta-component-books/xhtml">' . $data;
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


                $this->addDtd($data);

                if (!xml_parse($xmlParser, $data, $end)) {
                    $errorInfo = $this->_getErrorInfo($xmlParser);
                    $exceptionMessage = sprintf(
                        'XML error(%d) at line %d column %d: %s (%s)',
                        $errorInfo[0],
                        xml_get_current_line_number($xmlParser),
                        xml_get_current_column_number($xmlParser),
                        $errorInfo[1], $source
                    );
                    xml_parser_free($xmlParser);
                    fclose($stream);
                    throw new Exception($exceptionMessage, $errorInfo[0]);

                } // if ...
            } // while ...

            xml_parse($xmlParser, '', true);
            fclose($stream);
        }
    }


    protected function addDtd(string &$data): void
    {
        $dtd  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $dtd .= '<!DOCTYPE  xhtml [
         
           <!ENTITY atilde "atilde">
           <!ENTITY micro  "micro">
           <!ENTITY euro   "euro">
           <!ENTITY reg    "trademark">
           <!ENTITY trade    "trade">
           <!ENTITY copy   "copyright">
           <!ENTITY pi     "pi">       
           <!ENTITY beta     "beta">       
           <!ENTITY mu     "mu">       
           <!ENTITY hellip     "hellip">       
           <!ENTITY larr     "larr">    
           <!ENTITY uarr     "uarr">    
           <!ENTITY rarr     "rarr">    
           <!ENTITY darr     "darr">    
           <!ENTITY harr     "harr">    
           <!ENTITY crarr     "crarr">    
               
        ]>' . "\n";
        $data = $dtd . $data;
    }


    /**
     * @param XMLParser $xmlParser
     * @param string $source
     * @return void
     * @throws Exception
     */
    protected function _getContentFromString(\XMLParser $xmlParser, string $source): void
    {
        // add root element(xhtml) to the data if not done
        $data = trim($source);
        if (!str_starts_with(strtolower($data), '<volta:xhtml')) {
            $data = '<volta:xhtml xmlns:volta="https://volta-framework.com/volta-component-books/xhtml">' . $data;
        }
        if (!str_ends_with(strtolower($data), '</volta:xhtml>')) {
            $data .= '</volta:xhtml>' ;
        }

        $this->addDtd($data);

        if (!xml_parse($xmlParser, $data, true)) {
            $errorInfo = $this->_getErrorInfo($xmlParser);
            $exceptionMessage = sprintf(
                'XML error(%d) at line %d column %d: %s',
                $errorInfo[0],
                xml_get_current_line_number($xmlParser),
                xml_get_current_column_number($xmlParser),
                $errorInfo[1]
            );
            xml_parser_free($xmlParser);
            throw new Exception($exceptionMessage, $errorInfo[0]);
        }
    }

    /**
     * @param XMLParser $xmlParser
     * @return array
     */
    protected function _getErrorInfo(\XMLParser $xmlParser): array
    {
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

        return [$errorCode, $errorMessage];
    }


    // -----------------------------------------------------------------------------

//    protected function externalEntityRefHandler(\XmlParser $parser, string $entname,$base,$sysID,$pubID)
//    {
//        $this->_content .="externalEntityRefHandler $entname";
//        return false;
//    }
//    protected function unparsedEntityDeclHandler(\XmlParser $parser, string $entname,$base,$sysID,$pubID,$notname)
//    {
//        $this->_content .="unparsedEntityDeclHandler  $entname";
//        return false;
//    }

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
            if ($this->_useBuffer) {
                if ($this->_verbose) echo "\n<!--OnCharacterDataHandler: {$element->getName()}-->\n";
                echo $element->onTranslateData($data);
            } else {
                if ($this->_verbose) $this->_content .= "\n<!--OnCharacterDataHandler: {$element->getName()}-->\n";
                $this->_content .= $element->onTranslateData($data);
            }
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
            if ($this->_useBuffer) {
                if ($this->_verbose) echo "\nm<!--OnDefaultHandler: {$element->getName()}-->\n";
                echo $element->onTranslateData($data);
            } else {
                if ($this->_verbose) $this->_content .= "\nm<!--OnDefaultHandler: {$element->getName()}-->\n";
                $this->_content .= $element->onTranslateData($data);
            }
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

        if ($this->_useBuffer) {
            if($this->_verbose) echo "\n<!--OnElementStartHandler: {$element->getName()}-->\n";
            echo $element->onTranslateStart();
        } else {
            if($this->_verbose) $this->_content .= "\n<!--OnElementStartHandler: {$element->getName()}-->\n";
            $this->_content .= $element->onTranslateStart();
        }

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
            if ($this->_useBuffer) {
                if ($this->_verbose) echo "\n<!--OnElementEndHandler: {$element->getName()}-->\n";
                echo $element->onTranslateEnd();
            } else {
                if ($this->_verbose) $this->_content .= "\n<!--OnElementEndHandler: {$element->getName()}-->\n";
                $this->_content .= $element->onTranslateEnd();
            }
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
        if ($this->_useBuffer) {
            if ($this->_verbose) echo "\n<!--startNamespaceDeclHandler: {$prefix} : {$uri}-->\n";
        } else {
            if ($this->_verbose) $this->_content .= "\n<!--startNamespaceDeclHandler: {$prefix} : {$uri}-->\n";
        }

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