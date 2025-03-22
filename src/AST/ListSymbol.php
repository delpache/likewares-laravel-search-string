<?php

namespace Likewares\LaravelSearchString\AST;

use Likewares\LaravelSearchString\Visitors\Visitor;

class ListSymbol extends Symbol
{
    use CanHaveRule;
    use CanBeNegated;

    /** @var string */
    public $key;

    /** @var array */
    public $values;

    public function __construct(string $key, array $values)
    {
        $this->key = $key;
        $this->values = $values;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitList($this);
    }
}
