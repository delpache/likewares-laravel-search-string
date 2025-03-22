<?php

namespace Likewares\LaravelSearchString\AST;

use Likewares\LaravelSearchString\Visitors\Visitor;

abstract class Symbol
{
    abstract public function accept(Visitor $visitor);
}
