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

namespace Volta\Component\Books;

use Volta\Component\Books\Exceptions\Exception;

/**
 * Contains the Meta data for a Document Node
 */
class Meta
{

    /**
     * @ignore (do show up in generated documentaion)
     * @var string
     */
    protected readonly string $_file;

    /**
     * @ignore (do show up in generated documentaion)
     * @var string[]
     */
    protected array $_metadata = [];

    /**
     * @ignore (do show up in generated documentaion)
     * @var DocumentNode
     */
    protected readonly DocumentNode $_owner;

    /**
     * @param string $file
     * @param DocumentNode $owner
     * @throws Exception
     */
    public function __construct(string $file, DocumentNode $owner)
    {
        // get the content of the file
        if (!is_file($file) || !is_readable($file)) {
            throw new Exception(sprintf('DocumentNode Meta data file (%s) could not be openend', $file));
        }

        // NOTE:
        //   file_get_contents() returning false should not happen, see previous tests. And the case of the content being
        //   something else then plain text is most unlikely because the instantiation wil only be done when a valid
        //   json is found when creating the DocumentNode see Node::factory().
        //
        //   But PHP-stan knows file_get_contents() could return a boolean false in case of a failure and wants us
        //   to test against it. If not it will give you these annoying messages... As a solution we test
        //   against boolean false, or we cast the return value to a string to avoid getting these messages.
        //
        //   if (false === $json) {
        //        throw new Exception(sprintf('DocumentNode Meta data file (%s) could not be openend(#)', $file));
        //   }
        $json = (string) file_get_contents($file);

        // remove leading and trailing white space
        $json = trim($json);

        // get the json and cast to an array
        if (!empty($json)) {
            $this->_metadata = json_decode($json, true);
            if (json_last_error()) {
                throw new Exception(sprintf('DocumentNode Meta data (%s) json parse error: %s', $file, json_last_error_msg()));
            }
        }

        // set other properties
        $this->_file = $file;
        $this->_owner = $owner;
    }

    /**
     * Returns the value for the option __$option__
     * @param string $option
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public function get(string $option, mixed $default=null): mixed
    {
        $options = explode('.', $option);
        $current = &$this->_metadata;
        for($optionIndex = 0; $optionIndex < count($options); $optionIndex++){
            if (!is_array($current) || !isset($current[$options[$optionIndex]])) {
                if (null===$default) {
                    throw new Exception(sprintf('DocumentNode Meta(~/%s) option "%s" not found and no default value provided.', $this->getOwner()->getRelativePath(), $option));
                }
                return $default;
            }
            $current = &$current[$options[$optionIndex]];
        }
        return $current;
    }

    /**
     * Set the option in the metadata file.
     *
     * @param string $option
     * @param mixed $value
     * @param bool $overWrite
     * @return $this
     * @throws Exception
     */
    public function set(string $option, mixed $value, bool $overWrite=false ): self
    {
        if ($this->has($option) && !$overWrite) {
            throw new Exception(sprintf('DocumentNode Meta(~/%s) option "%s" already set', $this->getOwner()->getRelativePath(), $option));
        }
        if (!is_writable($this->_file)) {
        throw new Exception(sprintf('DocumentNode Meta(~/%s) is not writable', $this->getOwner()->getRelativePath()));
        }
        $options = explode('.', $option);
        $current = &$this->_metadata;
        for($optionIndex = 0; $optionIndex < count($options); $optionIndex++){
            if (!isset($current[$options[$optionIndex]])) {
                if (!is_array($current)) $current = [];
                $current[$options[$optionIndex]] = [];
            }
            $current = &$current[$options[$optionIndex]];
        }
        //$old = $current;
        $current = $value;

        // save metadata
        $fh = fopen($this->_file, 'w');
        fwrite( $fh, json_encode($this->_metadata, JSON_PRETTY_PRINT));
        fclose($fh);

        return $this;
    }

    /**
     * Checks if  the option __$option__ exists.
     * @param string $option
     * @return bool TRUE when the option exists, false otherwise
     */
    public function has(string $option): bool
    {
        $options = explode('.', $option);
        $current = &$this->_metadata;
        for($optionIndex = 0; $optionIndex < count($options); $optionIndex++){
            if (!is_array($current) || !isset($current[$options[$optionIndex]])) {
                return false;
            }
            $current = &$current[$options[$optionIndex]];
        }
        return true;
    }

    /**
     * The full path of the metadata file
     * @return string
     */
    public function getFile(): string
    {
        return $this->_file;
    }

    /**
     * Returns the DocumentNode the metadata belongs to
     * @return DocumentNode
     */
    public function getOwner(): DocumentNode
    {
        return $this->_owner;
    }

    /**
     * Returns the json as an array
     * @return string[]
     */
    public function getData():array
    {
        return $this->_metadata;
    }
}

