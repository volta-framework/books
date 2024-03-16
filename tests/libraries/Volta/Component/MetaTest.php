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

use PHPUnit\Framework\TestCase;
use Volta\Component\Books\Meta;
use Volta\Component\Books\Node;

class MetaTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        // recreate test meta file
        unlink(TEST_BOOK . 'meta.json');
        touch(TEST_BOOK . 'meta.json');
    }

    public function test_Meta(): void
    {
        $book = Node::factory(TEST_BOOK, true);
        $this->assertTrue($book->isBook());
        $meta = $book->getMeta();
        $this->assertTrue(is_a($meta, Meta::class));
        $this->assertEquals( TEST_BOOK . 'meta.json',$meta->getFile());
    }

    public function test_MetaGetDefault(): void
    {
        $book = Node::factory(TEST_BOOK,true);
        $this->assertTrue($book->getMeta()->get('not_exists', true));
        $this->expectException(Volta\Component\Books\Exceptions\Exception::class);
        $this->assertTrue($book->getMeta()->get('not_exists'));
    }

    public function test_MetaSet(): void
    {
        $book = Node::factory(TEST_BOOK);
        $meta = $book->getMeta();
        $meta->set('option1', 'option1 value');
        $this->assertEquals('option1 value', $meta->get('option1'));
        $meta->set('option3.sub1', 'option1 value');
        $this->assertEquals('option1 value', $meta->get('option3.sub1'));
        unset($book, $meta);
        $book2 = Node::factory(TEST_BOOK, true);
        $meta2 = $book2->getMeta();
        $this->assertEquals('option1 value', $meta2->get('option1'));
        $this->assertEquals('option1 value', $meta2->get('option3.sub1'));
        unset($book2, $meta2);
    }

    public function test_MetaGuid(): void
    {
        $book = Node::factory(TEST_BOOK);
        $this->assertEquals($book->getRoot()->getAbsolutePath(), $book->getAbsolutePath());
        $guid = $book->getRoot()->getGuid();
        unset($book);
        $book = Node::factory(TEST_BOOK, true);
        $this->assertEquals($guid, $book->getMeta()->get('GUID'));
    }
}