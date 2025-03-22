<?php

namespace Likewares\LaravelSearchString\AST;

use Illuminate\Support\Collection;
use Likewares\LaravelSearchString\Visitors\Visitor;

class AndSymbol extends Symbol
{
    /** @var Collection  */
    public $expressions;

    function __construct($expressions = [])
    {
        $this->expressions = Collection::wrap($expressions);
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitAnd($this);
    }
}
