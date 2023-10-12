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


    #region - Publisher

    /**
     * @ignore(do not show up in the generated documentation)
     * @var PublisherInterface $_publisher Internal storage of the active publisher
     */
    protected PublisherInterface $_publisher;

    /**
     * If not manually set defaults to Publishers\Web
     *
     * @return PublisherInterface
     * @throws Exception
     */
    public function getPublisher(): PublisherInterface
    {
        if (!isset($this->_publisher)) {
            $this->_publisher = Publisher::factory(Publishers\Web::class, []);
        }
        return $this->_publisher;
    }

    /**
     * Sets the active publisher either by classname or through a reference to a Publishers object
     *
     * @param PublisherInterface|string $publisher
     * @param array $options
     * @return BookNode
     * @throws Exception
     */
    public function setPublisher(PublisherInterface|string $publisher, array $options = []): BookNode
    {
        if (is_string($publisher)) {
            $this->_publisher = Publisher::factory(Publishers\Web::class, $options);;
        } else {
            $this->_publisher = $publisher;
        }

        return $this;
    }


    #endregion -------------------------------------------------------------------------------------------------------
    #region - GUID

    /**
     * @ignore(do not show up in the generated documentation)
     */
    private const META_OPTION_GUID = 'GUID';

    /**
     * @ignore(do not show up in the generated documentation)
     * @var string $_guid Internal storage of the GUID
     */
    protected string $_guid;

    /**
     * If not set in the Metadata a GUID is generated
     *
     * @return string The GUID
     * @throws Exception
     */
    public function getGuid(): string
    {
        if (!$this->getMeta()->has(BookNode::META_OPTION_GUID)) {
            $this->getMeta()->set(BookNode::META_OPTION_GUID, $this->_createGUID());
        }
        return $this->getMeta()->get(BookNode::META_OPTION_GUID);
    }

    /**
     * Sets the guid for this book (will be saved in the metadata)
     *
     * @param string $guid
     * @return $this
     * @throws Exception
     */
    public function setGuid(string $guid): BookNode
    {
        $this->getMeta()->set(BookNode::META_OPTION_GUID, $guid);
        return $this;
    }

    /**
     * @return string
     */
    private function _createGUID(): string
    {
        if (function_exists('com_create_guid') === true){
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    #endregion -------------------------------------------------------------------------------------------------------

}