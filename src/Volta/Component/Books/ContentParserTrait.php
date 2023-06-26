<?php

namespace Volta\Component\Books;

trait ContentParserTrait
{

    protected NodeInterface $_node;

    public function getNode():NodeInterface
    {
        return $this->_node;
    }
}