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

use Volta\Component\Books\Exceptions\Exception;

/**
 * Contains the Meta data for a Document Node
 */
class Meta
{
    /**
     * @var string[]
     */
    protected array $_metadata = [];

    /**
     * @param string|null $file
     * @throws Exception
     */
    public function __construct(string|null $file = null)
    {
        if($file !== null) {
            if (!is_file($file) || !is_readable($file)) {
                throw new Exception(sprintf('Could not open file "%s"', $file));
            }
            $json = file_get_contents($file);
            if (false === $json) throw new Exception('Could not open Meta.json');
            $this->_metadata = json_decode($json, true);;

            // todo check for JSON parse errors
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public function get(string $key, mixed $default=null): mixed
    {
        $keys = explode('.', $key);
        $current = &$this->_metadata;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                if (null===$default) {
                    throw new Exception(sprintf('Option "%s" not found in and no default value provided.', $key));
                }
                return $default;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return $current;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $overWrite
     * @return $this
     * @throws Exception
     */
    public function set(string $key, mixed $value, bool $overWrite=false ): Static
    {
        if ($this->has($key) && !$overWrite) {
            throw new Exception(sprintf('Key "%s" already set', $key));
        }
        $keys = explode('.', $key);
        $current = &$this->_metadata;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!isset($current[$keys[$keyIndex]])) {
                if (!is_array($current)) $current = [];
                $current[$keys[$keyIndex]] = [];
            }
            $current = &$current[$keys[$keyIndex]];
        }
        $old = $current;
        $current = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $current = &$this->_metadata;
        for($keyIndex = 0; $keyIndex < count($keys); $keyIndex++){
            if (!is_array($current) || !isset($current[$keys[$keyIndex]])) {
                return false;
            }
            $current = &$current[$keys[$keyIndex]];
        }
        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     * @throws Exception
     */
    public function equals(string $key, mixed $value): bool
    {
        return ($this->has($key) && $this->get($key) == $value);

    } // optionEquals(...)
}

