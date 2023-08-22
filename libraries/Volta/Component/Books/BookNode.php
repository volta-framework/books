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
 * A DocumentNode with no parent(the root node) is considered a Book
 */
class BookNode extends DocumentNode
{

    /**
     * @var string
     */
    protected string $_uriOffset = '';

    /**
     * @return string
     */
    public function getUriOffset(): string
    {
        return $this->_uriOffset;
    }

    /**
     *
     * if we want the absolute uri we need to add the slash and the
     * uriOffset(stored in the Root BookNode::$uriOffset)
     *
     * NOTE:
     *     if the uriOffset is not in the correct format hence
     *     - ending with a SLUG_SEPARATOR or
     *     - not starting with a SLUG_SEPARATOR
     *     throw an Exception
     *
     * @param string $uriOffset
     * @return $this
     * @throws Exception
     */
    public function setUrlOffset(string $uriOffset): BookNode
    {
        if ($uriOffset!== '' && !str_starts_with($uriOffset, Node::SLUG_SEPARATOR))
            throw new Exception('BookNode::uriOffset; must start with a forward slash');
        if ($uriOffset!== '' &&  str_ends_with($uriOffset, Node::SLUG_SEPARATOR))
            throw new Exception('BookNode::$uriOffset; can not end with a forward slash');
        $this->_uriOffset = $uriOffset;
        return $this;
    }

    protected string $_uuid;

    /**
     * @return string
     * @throws Exception
     */
    public function getUuid(): string
    {
        if(!isset($this->_uuid)) {
            $this->_uuid = $this->getMeta()->get('uuid', sha1(uniqid('VOLTA', true)));
        }
        return $this->_uuid;
    }

    public function setUuid(string $uuid): BookNode
    {
        $this->_uuid = $uuid;
        return $this;
    }



}