<?php

namespace Likewares\LaravelSearchString\AST;

use Likewares\LaravelSearchString\Visitors\Visitor;

class SoloSymbol extends Symbol
{
    use CanHaveRule;
    use CanBeNegated;

    /** @var string */
    public $content;

    function __construct(string $content)
    {
        $this->content = $content;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitSolo($this);
    }
}
