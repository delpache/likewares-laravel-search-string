<?php

namespace Likewares\LaravelSearchString\AST;

use Likewares\LaravelSearchString\Visitors\Visitor;

class EmptySymbol extends Symbol
{
    public function accept(Visitor $visitor)
    {
        return $visitor->visitEmpty($this);
    }
}
