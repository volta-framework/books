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

use Psr\Http\Message\StreamInterface;
use Volta\Component\Books\Exceptions\Exception;

interface NodeInterface
{

    /**
     * @return TocItem[]
     */
    public function getToc(): array;

    /**
     * @return array
     */
    public function getList(): array;


    public function getIndex(): int;

    /**
     * The relative path as a valid URI
     * @param bool $absolute
     * @return string
     */
    public function getUri(bool $absolute = true): string;

    /**
     * Nodes directories basename made more readable friendly
     * @return string
     */
    public function getName(): string;

    /**
     * the name more human-readable
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Returns the node type
     * @return string
     */
    public function getType(): string;

    /**
     * Returns the node content type
     * @return string
     */
    public function getContentType(): string;

    /**
     * The node directory path relative to its root node
     * @return string
     */
    public function getRelativePath(): string;

    /**
     * The full path to the node directory
     * @return string
     */
    public function getAbsolutePath(): string;

    /**
     * @return NodeInterface|null The parent node
     */
    public function getParent(): null|NodeInterface;

    /**
     * When null is returned it is the root node
     * @return NodeInterface
     */
    public function getRoot(): NodeInterface;

    /**
     * Previous sibling, null when it is the first
     * @return NodeInterface|null
     */
    public function getPrevious(): null|NodeInterface;

    /**
     * The next sibling, null when it is the last
     * @return NodeInterface|null
     */
    public function getNext():null|NodeInterface;

    /**
     * Get a child node
     * @throws Exception
     */
    public function getChild(string $relativePath): null|NodeInterface;

    /**
     * List of child nodes.
     * @return array<mixed, mixed>
     */
    public function getChildren(): array;

    /**
     * Returns the content of the node as (binary) string
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Meta information object
     * @return Meta
     */
    public function getMeta(): Meta;


}