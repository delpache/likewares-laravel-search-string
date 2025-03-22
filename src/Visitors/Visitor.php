<?php

namespace Likewares\LaravelSearchString\Visitors;

use Likewares\LaravelSearchString\AST\AndSymbol;
use Likewares\LaravelSearchString\AST\ListSymbol;
use Likewares\LaravelSearchString\AST\NotSymbol;
use Likewares\LaravelSearchString\AST\EmptySymbol;
use Likewares\LaravelSearchString\AST\OrSymbol;
use Likewares\LaravelSearchString\AST\QuerySymbol;
use Likewares\LaravelSearchString\AST\RelationshipSymbol;
use Likewares\LaravelSearchString\AST\SearchSymbol;
use Likewares\LaravelSearchString\AST\SoloSymbol;

abstract class Visitor
{
    public function visitOr(OrSymbol $or)
    {
        return new OrSymbol($or->expressions->map->accept($this));
    }

    public function visitAnd(AndSymbol $and)
    {
        return new AndSymbol($and->expressions->map->accept($this));
    }

    public function visitNot(NotSymbol $not)
    {
        return new NotSymbol($not->expression->accept($this));
    }

    public function visitRelationship(RelationshipSymbol $relationship)
    {
        $relationship->expression = $relationship->expression->accept($this);

        return $relationship;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        return $solo;
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $query;
    }

    public function visitList(ListSymbol $list)
    {
        return $list;
    }

    public function visitEmpty(EmptySymbol $empty)
    {
        return $empty;
    }
}
